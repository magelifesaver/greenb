<?php

// File: classes/chat/admin/ajax/chatbot_ajax_handler.php
// Status: MODIFIED
// UPDATED FILE - Removed duplicate name check during rename.
// UPDATED FILE - If OpenAI Conversation State is enabled for an OpenAI bot, force global OpenAI 'Store Conversation' to true.

namespace WPAICG\Chat\Admin\Ajax;

use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Chat\Storage\DefaultBotSetup;
use WPAICG\Chat\Frontend\Shortcode; // Needed for get_chatbot_shortcode
use WPAICG\Chat\Admin\AdminSetup; // Needed for POST_TYPE constant
use WPAICG\AIPKit_Providers; // Added for updating global provider settings
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for Chatbot CRUD operations and settings.
 * Uses the BotStorage facade.
 */
class ChatbotAjaxHandler extends BaseAjaxHandler
{
    private $bot_storage;

    public function __construct()
    {
        // Ensure BotStorage exists and instantiate
        if (!class_exists(\WPAICG\Chat\Storage\BotStorage::class)) {
            return;
        }
        $this->bot_storage = new BotStorage();

        // Ensure AIPKit_Providers is available for updating global settings
        if (!class_exists(\WPAICG\AIPKit_Providers::class)) {
            $providers_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
            if (file_exists($providers_path)) {
                require_once $providers_path;
            }
        }
    }

    public function ajax_create_chatbot()
    {
        // REVISED: Use module access check
        $permission_check = $this->check_module_access_permissions('chatbot');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $botName = isset($_POST['bot_name']) ? sanitize_text_field(wp_unslash($_POST['bot_name'])) : '';
        if (empty($botName)) {
            wp_send_json_error(['message' => __('Chatbot name cannot be empty.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        // Uses facade method
        $result = $this->bot_storage->create_bot($botName);
        if (is_wp_error($result)) {
            $this->send_wp_error($result);
        } else {
            wp_send_json_success([
                'message' => __('Chatbot created successfully!', 'gpt3-ai-content-generator'),
                'bot_id' => $result['bot_id'],
                'bot_name' => $result['bot_name'],
                'bot_settings' => $result['bot_settings']
            ]);
        }
    }

    public function ajax_save_chatbot_settings()
    {
        // REVISED: Use module access check
        $permission_check = $this->check_module_access_permissions('chatbot');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $botId = isset($_POST['bot_id']) ? absint($_POST['bot_id']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $settings = isset($_POST) ? wp_unslash($_POST) : array(); // Use unslashed $_POST

        if (empty($botId)) {
            wp_send_json_error(['message' => __('Invalid Chatbot ID.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        // Uses facade method
        $result = $this->bot_storage->save_bot_settings($botId, $settings);

        if (is_wp_error($result)) {
            $this->send_wp_error($result);
        } else {
            // --- START: Check and update global OpenAI store_conversation setting ---
            if (isset($settings['provider']) && $settings['provider'] === 'OpenAI' &&
                isset($settings['openai_conversation_state_enabled']) && $settings['openai_conversation_state_enabled'] === '1') {
                if (class_exists(\WPAICG\AIPKit_Providers::class)) {
                    $openai_global_settings = AIPKit_Providers::get_provider_data('OpenAI');
                    if (($openai_global_settings['store_conversation'] ?? '0') !== '1') {
                        $openai_global_settings['store_conversation'] = '1';
                        AIPKit_Providers::save_provider_data('OpenAI', $openai_global_settings);
                    }
                }
            }
            // --- END: Check and update global OpenAI store_conversation setting ---

            wp_send_json_success([
                'message' => __('Chatbot settings saved successfully.', 'gpt3-ai-content-generator'),
            ]);
        }
    }

    public function ajax_delete_chatbot()
    {
        // REVISED: Use module access check
        $permission_check = $this->check_module_access_permissions('chatbot');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $botId = isset($_POST['bot_id']) ? absint($_POST['bot_id']) : 0;
        if (empty($botId)) {
            wp_send_json_error(['message' => __('Invalid Chatbot ID.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        // Check if it's the default bot (uses static method, no facade needed here)
        if (!class_exists(DefaultBotSetup::class)) {
            $this->send_wp_error(new WP_Error('dependency_missing', 'DefaultBotSetup class not found for deletion check.', ['status' => 500]));
            return;
        }
        $default_bot_id = DefaultBotSetup::get_default_bot_id();
        if ($botId === $default_bot_id) {
            $this->send_wp_error(new WP_Error('cannot_delete_default', __('The default chatbot cannot be deleted.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        // Uses facade method
        $result = $this->bot_storage->delete_bot($botId);
        if (is_wp_error($result)) {
            $this->send_wp_error($result);
        } else {
            wp_send_json_success(['message' => __('Chatbot deleted successfully.', 'gpt3-ai-content-generator'), 'bot_id' => $botId]);
        }
    }

    public function ajax_get_chatbot_shortcode()
    {
        // REVISED: Use module access check
        $permission_check = $this->check_module_access_permissions('chatbot');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $bot_id = isset($_REQUEST['bot_id']) ? absint($_REQUEST['bot_id']) : 0;

        // Ensure AdminSetup class is available
        if (!class_exists(AdminSetup::class)) {
            wp_send_json_error(['message' => __('Internal server error.', 'gpt3-ai-content-generator')], 500);
            return;
        }
        if (empty($bot_id) || get_post_type($bot_id) !== AdminSetup::POST_TYPE) {
            wp_send_json_error(['message' => __('Invalid Chatbot ID provided.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        try {
            // Ensure Shortcode class is available
            if (!class_exists(Shortcode::class)) {
                wp_send_json_error(['message' => __('Internal server error.', 'gpt3-ai-content-generator')], 500);
                return;
            }
            $shortcode_renderer = new Shortcode();
            $shortcode_html = $shortcode_renderer->render_chatbot_shortcode(['id' => $bot_id]);

            if (is_wp_error($shortcode_html)) {
                wp_send_json_error(['message' => $shortcode_html->get_error_message()], 500);
                return;
            }
            if (!is_string($shortcode_html)) {
                wp_send_json_error(['message' => __('Error generating shortcode HTML (non-string result).', 'gpt3-ai-content-generator')], 500);
                return;
            }
            // Basic UTF-8 check (optional but good practice)
            if (!mb_check_encoding($shortcode_html, 'UTF-8')) {
                $shortcode_html = mb_convert_encoding($shortcode_html, 'UTF-8', mb_detect_encoding($shortcode_html));
                if (!$shortcode_html) {
                    wp_send_json_error(['message' => __('Error generating shortcode HTML (encoding issue).', 'gpt3-ai-content-generator')], 500);
                    return;
                }
            }
            wp_send_json_success(['html' => $shortcode_html]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => __('Internal server error generating preview.', 'gpt3-ai-content-generator')], 500);
        }
    }

    public function ajax_reset_chatbot_settings()
    {
        // REVISED: Use module access check
        $permission_check = $this->check_module_access_permissions('chatbot');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $botId = isset($_POST['bot_id']) ? absint($_POST['bot_id']) : 0;
        if (empty($botId)) {
            wp_send_json_error(['message' => __('Invalid Chatbot ID.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        // Uses static method, no facade needed here
        if (!class_exists(DefaultBotSetup::class)) {
            $this->send_wp_error(new WP_Error('dependency_missing', 'DefaultBotSetup class not found for reset.', ['status' => 500]));
            return;
        }
        $result = DefaultBotSetup::reset_bot_settings($botId);
        if (is_wp_error($result)) {
            $this->send_wp_error($result);
        } else {
            wp_send_json_success(['message' => __('Chatbot settings reset to defaults.', 'gpt3-ai-content-generator')]);
        }
    }

    /**
     * AJAX: Renames a chatbot.
     * @since NEXT_VERSION
     */
    public function ajax_rename_chatbot()
    {
        // Permission Check: User needs access to the chatbot module
        $permission_check = $this->check_module_access_permissions('chatbot');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $bot_id = isset($_POST['bot_id']) ? absint($_POST['bot_id']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $new_name = isset($_POST['new_name']) ? sanitize_text_field(wp_unslash($_POST['new_name'])) : '';

        // Ensure AdminSetup class is available
        if (!class_exists(AdminSetup::class)) {
            wp_send_json_error(['message' => __('Internal server error.', 'gpt3-ai-content-generator')], 500);
            return;
        }

        // --- Validation ---
        // Check Bot ID
        if (empty($bot_id) || get_post_type($bot_id) !== AdminSetup::POST_TYPE || !in_array(get_post_status($bot_id), ['publish', 'draft'], true)) {
            wp_send_json_error(['message' => __('Invalid Chatbot ID.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        // Check if it's the default bot
        if (!class_exists(DefaultBotSetup::class)) {
            $this->send_wp_error(new WP_Error('dependency_missing', 'DefaultBotSetup class not found for rename check.', ['status' => 500]));
            return;
        }
        $default_bot_id = DefaultBotSetup::get_default_bot_id();
        if ($bot_id === $default_bot_id) {
            $this->send_wp_error(new WP_Error('cannot_rename_default', __('The default chatbot cannot be renamed.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        // Check empty name
        if (empty($new_name)) {
            wp_send_json_error(['message' => __('Chatbot name cannot be empty.', 'gpt3-ai-content-generator')], 400);
            return;
        }

        // --- End Validation ---

        // Update the post title
        $update_args = [
            'ID' => $bot_id,
            'post_title' => $new_name,
        ];
        $updated_post_id = wp_update_post($update_args, true); // Pass true to get WP_Error on failure

        if (is_wp_error($updated_post_id)) {
            wp_send_json_error(['message' => __('Failed to update chatbot name.', 'gpt3-ai-content-generator')], 500);
        } else {
            wp_send_json_success([
                'message' => __('Success!', 'gpt3-ai-content-generator'),
                'new_name' => $new_name
            ]);
        }
    }
}

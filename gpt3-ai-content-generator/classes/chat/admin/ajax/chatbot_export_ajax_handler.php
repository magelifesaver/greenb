<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/admin/ajax/chatbot_export_ajax_handler.php
// NEW FILE

namespace WPAICG\Chat\Admin\Ajax;

use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Chat\Storage\DefaultBotSetup;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for exporting Chatbot data.
 */
class ChatbotExportAjaxHandler extends BaseAjaxHandler {

    private $bot_storage;
    private $default_setup;

    public function __construct() {
        // Ensure BotStorage exists and instantiate
        if (!class_exists(\WPAICG\Chat\Storage\BotStorage::class)) {
            return;
        }
        $this->bot_storage = new BotStorage();

        // Ensure DefaultBotSetup exists and instantiate
        if (!class_exists(\WPAICG\Chat\Storage\DefaultBotSetup::class)) {
            return;
        }
        $this->default_setup = new DefaultBotSetup();
    }

    /**
     * AJAX: Exports all non-default chatbots and their settings as JSON.
     */
    public function ajax_export_all_chatbots() {
        $permission_check = $this->check_module_access_permissions('chatbot');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        if (!$this->bot_storage || !$this->default_setup) {
            $this->send_wp_error(new WP_Error('dependency_missing', __('Required components for export are missing.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        $all_bots = $this->bot_storage->get_chatbots();
        $default_bot_id = $this->default_setup->get_default_bot_id();
        $export_data = [];

        foreach ($all_bots as $bot_post) {
            // Skip the default bot
            if ($bot_post->ID === $default_bot_id) {
                continue;
            }

            $bot_id = $bot_post->ID;
            $bot_settings = $this->bot_storage->get_chatbot_settings($bot_id);

            // Remove sensitive or irrelevant data if needed before export
            // Example: unset($bot_settings['some_internal_key']);

            $export_data[] = [
                'title' => $bot_post->post_title, // Use the post title as the name
                'settings' => $bot_settings,
            ];
        }

        // Generate JSON
        $json_data = wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->send_wp_error(new WP_Error('json_encode_failed', __('Failed to generate export data.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        wp_send_json_success([
            /* translators: %d: The number of chatbots exported. */
            'message' => sprintf(__('%d chatbots exported successfully.', 'gpt3-ai-content-generator'), count($export_data)),
            'jsonData' => $json_data, // Send the JSON data back to JS
        ]);
    }
}
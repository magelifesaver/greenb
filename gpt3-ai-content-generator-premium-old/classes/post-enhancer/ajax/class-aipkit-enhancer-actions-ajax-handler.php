<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/post-enhancer/ajax/class-aipkit-enhancer-actions-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\PostEnhancer\Ajax;

use WPAICG\Dashboard\Ajax\BaseDashboardAjaxHandler;
use WPAICG\AIPKit_Role_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles AJAX requests for managing Post Enhancer custom actions.
 */
class AIPKit_Enhancer_Actions_Ajax_Handler extends BaseDashboardAjaxHandler
{
    private const OPTION_NAME = 'aipkit_enhancer_actions';
    public const MODULE_SLUG = 'ai_post_enhancer';
    public const MAX_ACTIONS = 20;

    /**
     * Get the default set of actions.
     * @return array
     */
    public function get_default_actions_public(): array
    {
        return [
            [
                'id' => 'rewrite-' . wp_generate_uuid4(),
                'label' => __('Rewrite', 'gpt3-ai-content-generator'),
                /* translators: %s: The text to be rewritten */
                'prompt' => __('Rewrite this to improve clarity and engagement: "%s"', 'gpt3-ai-content-generator'),
                'is_default' => true
            ],
            [
                'id' => 'expand-' . wp_generate_uuid4(),
                'label' => __('Expand', 'gpt3-ai-content-generator'),
                /* translators: %s: The text to be expanded */
                'prompt' => __('Expand on the following point: "%s"', 'gpt3-ai-content-generator'),
                'is_default' => true
            ],
            [
                'id' => 'fix_grammar-' . wp_generate_uuid4(),
                'label' => __('Fix Grammar & Spelling', 'gpt3-ai-content-generator'),
                /* translators: %s: The text to be corrected */
                'prompt' => __('Correct any spelling and grammar mistakes in the following text: "%s"', 'gpt3-ai-content-generator'),
                'is_default' => true
            ],
            [
                'id' => 'summarize-' . wp_generate_uuid4(),
                'label' => __('Summarize', 'gpt3-ai-content-generator'),
                /* translators: %s: The text to be summarized */
                'prompt' => __('Summarize the following text in 3–5 concise sentences while preserving key facts and tone: "%s"', 'gpt3-ai-content-generator'),
                'is_default' => true
            ],
            [
                'id' => 'outline-' . wp_generate_uuid4(),
                'label' => __('Create Outline (H2/H3)', 'gpt3-ai-content-generator'),
                /* translators: %s: The text to outline */
                'prompt' => __('Create a clear outline from the following text using headings (## for H2, ### for H3) and short bullets as needed: "%s"', 'gpt3-ai-content-generator'),
                'is_default' => true
            ],
            [
                'id' => 'faqs-' . wp_generate_uuid4(),
                'label' => __('Generate FAQs', 'gpt3-ai-content-generator'),
                /* translators: %s: The text to generate FAQs from */
                'prompt' => __('Generate 5–7 relevant FAQ questions and short answers based on this text. Use a simple Q/A format in Markdown. Text: "%s"', 'gpt3-ai-content-generator'),
                'is_default' => true
            ],
            [
                'id' => 'simplify-' . wp_generate_uuid4(),
                'label' => __('Simplify Tone', 'gpt3-ai-content-generator'),
                /* translators: %s: The text to be simplified */
                'prompt' => __('Rewrite the following in a friendly, simple tone (grade 7–8 readability) while preserving meaning and structure: "%s"', 'gpt3-ai-content-generator'),
                'is_default' => true
            ],
        ];
    }

    /**
     * AJAX: Reset actions to defaults.
     */
    public function ajax_reset_actions(): void
    {
        $permission_check = $this->check_module_access_permissions(self::MODULE_SLUG, 'aipkit_enhancer_actions_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        $defaults = $this->get_default_actions_public();
        update_option(self::OPTION_NAME, $defaults, 'no');
        wp_send_json_success([
            'message' => __('Actions reset to defaults.', 'gpt3-ai-content-generator'),
            'actions' => $defaults,
        ]);
    }

    /**
     * AJAX handler to get all custom and default actions.
     */
    public function ajax_get_actions(): void
    {
        $permission_check = $this->check_module_access_permissions(self::MODULE_SLUG, 'aipkit_enhancer_actions_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        $actions = get_option(self::OPTION_NAME);
        if ($actions === false || !is_array($actions)) {
            $actions = $this->get_default_actions_public();
        }

        wp_send_json_success(['actions' => $actions]);
    }

    /**
     * AJAX handler to save or update an action.
     */
    public function ajax_save_action(): void
    {
        $permission_check = $this->check_module_access_permissions(self::MODULE_SLUG, 'aipkit_enhancer_actions_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in the parent class.
        $action_id = isset($_POST['id']) && !empty($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : null;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in the parent class.
        $label = isset($_POST['label']) ? sanitize_text_field(wp_unslash($_POST['label'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in the parent class.
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field(wp_unslash($_POST['prompt'])) : '';
        $insert_position_raw = isset($_POST['insert_position']) ? sanitize_key(wp_unslash($_POST['insert_position'])) : null;
        $allowed_positions = ['replace','after','before'];

        if (empty($label) || empty($prompt)) {
            $this->send_wp_error(new WP_Error('missing_data', __('Label and prompt are required.', 'gpt3-ai-content-generator')));
            return;
        }

        $actions = get_option(self::OPTION_NAME, $this->get_default_actions_public());
        if (!is_array($actions)) {
            $actions = $this->get_default_actions_public(); // Fallback if option is corrupted
        }

        $found = false;
        if ($action_id && strpos($action_id, 'new-') !== 0) { // It's an existing action
            foreach ($actions as &$action) {
                if (isset($action['id']) && $action['id'] === $action_id) {
                    // A default action that is edited becomes a custom action.
                    if (isset($action['is_default'])) {
                        $action['is_default'] = false;
                    }
                    $action['label'] = $label;
                    $action['prompt'] = $prompt;
                    if ($insert_position_raw !== null) {
                        if ($insert_position_raw === '' || $insert_position_raw === 'default') {
                            unset($action['insert_position']);
                        } elseif (in_array($insert_position_raw, $allowed_positions, true)) {
                            $action['insert_position'] = $insert_position_raw;
                        }
                    }
                    $found = true;
                    break;
                }
            }
            unset($action);
        }

        $saved_action = null;
        if (!$found) {
            // It's a new action
            if (count($actions) >= self::MAX_ACTIONS) {
                $this->send_wp_error(new WP_Error('limit_reached', __('You have reached the maximum of 20 actions.', 'gpt3-ai-content-generator')));
                return;
            }
            $new_action = [
                'id' => 'custom-' . wp_generate_uuid4(),
                'label' => $label,
                'prompt' => $prompt,
                'is_default' => false
            ];
            if ($insert_position_raw && in_array($insert_position_raw, $allowed_positions, true)) {
                $new_action['insert_position'] = $insert_position_raw;
            }
            $actions[] = $new_action;
            $saved_action = $new_action;
        } else {
            $saved_action_array = array_filter($actions, function ($a) use ($action_id) {
                return isset($a['id']) && $a['id'] === $action_id;
            });
            $saved_action = reset($saved_action_array);
        }

        update_option(self::OPTION_NAME, $actions, 'no');
        wp_send_json_success([
            'message' => __('Action saved successfully.', 'gpt3-ai-content-generator'),
            'action' => $saved_action
        ]);
    }

    /**
     * AJAX handler to delete an action.
     */
    public function ajax_delete_action(): void
    {
        $permission_check = $this->check_module_access_permissions(self::MODULE_SLUG, 'aipkit_enhancer_actions_nonce');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in the parent class.
        $action_id_to_delete = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : null;

        if (empty($action_id_to_delete)) {
            $this->send_wp_error(new WP_Error('missing_id', __('Action ID is required.', 'gpt3-ai-content-generator')));
            return;
        }

        $actions = get_option(self::OPTION_NAME, []);
        if (!is_array($actions)) {
            wp_send_json_success(['message' => __('No actions to delete.', 'gpt3-ai-content-generator')]);
            return;
        }

        $updated_actions = array_filter($actions, function ($action) use ($action_id_to_delete) {
            return !isset($action['id']) || $action['id'] !== $action_id_to_delete;
        });

        update_option(self::OPTION_NAME, array_values($updated_actions), 'no');
        wp_send_json_success(['message' => __('Action deleted successfully.', 'gpt3-ai-content-generator')]);
    }
}

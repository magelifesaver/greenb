<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/ajax/class-chat-form-submission-ajax-handler.php
// Status: MODIFIED
// I have fixed the PHPCS warnings by properly unslashing and sanitizing all input from $_POST and $_SERVER.

namespace WPAICG\Chat\Frontend\Ajax;

use WPAICG\Chat\Admin\Ajax\Traits\Trait_CheckFrontendPermissions;
use WPAICG\Chat\Admin\Ajax\Traits\Trait_SendWPError;
use WPAICG\aipkit_dashboard;
use WPAICG\Chat\Storage\BotStorage;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for chatbot form submissions from the frontend.
 */
class ChatFormSubmissionAjaxHandler {

    use Trait_CheckFrontendPermissions;
    use Trait_SendWPError;

    private $bot_storage;

    public function __construct() {
        if (class_exists(\WPAICG\Chat\Storage\BotStorage::class)) {
            $this->bot_storage = new \WPAICG\Chat\Storage\BotStorage();
        } else {
            $this->bot_storage = null;
        }
    }

    /**
     * AJAX handler for 'aipkit_handle_form_submission'.
     */
    public function ajax_handle_form_submission(): void {

        $permission_check = $this->check_frontend_permissions();
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_frontend_permissions().
        $post_data = wp_unslash($_POST);
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_frontend_permissions().
        $bot_id = isset($post_data['bot_id']) ? absint($post_data['bot_id']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_frontend_permissions().
        $form_id = isset($post_data['form_id']) ? sanitize_text_field($post_data['form_id']) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_frontend_permissions().
        $submitted_data_json = isset($post_data['submitted_data']) ? wp_kses_post($post_data['submitted_data']) : '{}';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_frontend_permissions().
        $conversation_uuid = isset($post_data['conversation_uuid']) ? sanitize_key($post_data['conversation_uuid']) : '';
        
        $user_id = get_current_user_id();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_frontend_permissions().
        $session_id_from_post = isset($post_data['session_id']) ? sanitize_text_field($post_data['session_id']) : '';

        $final_session_id = ''; 
        if (!$user_id) { 
            if (!empty($session_id_from_post)) {
                $final_session_id = $session_id_from_post;
            }
        }
        
        $post_id_from_request = isset($post_data['post_id']) ? absint($post_data['post_id']) : 0;

        if (empty($bot_id) || empty($form_id) || empty($conversation_uuid)) {
            $this->send_wp_error(new WP_Error('missing_params', __('Missing required parameters (bot, form, or conversation ID).', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        $submitted_data = json_decode($submitted_data_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($submitted_data)) {
            $this->send_wp_error(new WP_Error('invalid_submitted_data', __('Invalid submitted form data.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        if (!$user_id && empty($final_session_id)) {
             $this->send_wp_error(new WP_Error('missing_identifier', __('User or Session ID is required for guests.', 'gpt3-ai-content-generator'), ['status' => 400]));
             return;
        }


        $triggers_addon_active = false;
        if (class_exists(\WPAICG\aipkit_dashboard::class)) {
            $triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
        }

        $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
        $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';
        
        $trigger_handler_function = '\WPAICG\Lib\Chat\Triggers\process_chat_triggers';

        if (!$triggers_addon_active || !class_exists($trigger_storage_class) || !class_exists($trigger_manager_class) || !function_exists($trigger_handler_function)) {
            wp_send_json_success(['message' => __('Form submitted.', 'gpt3-ai-content-generator') . ' (' . __('Triggers not active or fully available.', 'gpt3-ai-content-generator') . ')']);
            return;
        }

        if (!$this->bot_storage) {
             $this->send_wp_error(new WP_Error('internal_error', __('Chat system (storage) not ready.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }
        $bot_settings = $this->bot_storage->get_chatbot_settings($bot_id);
        if (empty($bot_settings)) {
            $this->send_wp_error(new WP_Error('bot_not_found', __('Chatbot configuration not found.', 'gpt3-ai-content-generator'), ['status' => 404]));
            return;
        }

        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null;
        $user_wp_roles = $user_id ? (array) wp_get_current_user()->roles : ['guest'];
        $log_storage_instance = null;
        if (class_exists('\WPAICG\Chat\Storage\LogStorage')) {
            $log_storage_instance = new \WPAICG\Chat\Storage\LogStorage();
        }

        // --- MODIFIED: Populate base_log_data correctly for trigger context ---
        $base_log_data_for_triggers = [
            'bot_id'            => $bot_id,
            'user_id'           => $user_id ?: null,
            'session_id'        => $final_session_id,
            'conversation_uuid' => $conversation_uuid,
            'module'            => 'chat', // This ensures trigger meta-logs go to the right conversation
            'is_guest'          => ($user_id === 0 || $user_id === null),
            'ip_address'        => $client_ip,
            'role'              => $user_wp_roles ? implode(', ', $user_wp_roles) : null,
        ];

        $trigger_context = [
            'event_type'            => 'form_submitted', // Added for clarity
            'bot_id'                => $bot_id,
            'form_id'               => $form_id,
            'submitted_data'        => $submitted_data,
            'submitted_data_json'   => $submitted_data_json,
            'user_id'               => $user_id ?: null,
            'session_id'            => $final_session_id,
            'conversation_uuid'     => $conversation_uuid,
            'client_ip'             => $client_ip,
            'post_id'               => $post_id_from_request,
            'bot_settings'          => $bot_settings,
            'user_roles'            => $user_wp_roles,
            'current_provider'      => $bot_settings['provider'] ?? null,
            'current_model_id'      => $bot_settings['model'] ?? null,
            'log_storage'           => $log_storage_instance,
            'base_log_data'         => $base_log_data_for_triggers, // Pass this populated array
            'module'                => 'chat' // Explicitly set top-level module as well
        ];
        // --- END MODIFICATION ---
        
        try {
            $trigger_storage_instance = new $trigger_storage_class();
            $trigger_manager_instance = new $trigger_manager_class($trigger_storage_instance, $log_storage_instance);
            $result = $trigger_manager_instance->process_event($bot_id, 'form_submitted', $trigger_context);


            $response_data = [
                'message' => $result['message_to_user'] ?? __('Form processed.', 'gpt3-ai-content-generator'),
                'actions_executed' => $result['actions_executed'] ?? [],
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['status'] ?? 'processed',
            ];

            if ($result['status'] === 'blocked') {
                wp_send_json_error($response_data, 400);
            } else {
                wp_send_json_success($response_data);
            }

        } catch (\Exception $e) {
            $this->send_wp_error(new WP_Error('trigger_processing_error', __('Error processing form submission triggers.', 'gpt3-ai-content-generator'), ['status' => 500]));
        }
    }
}
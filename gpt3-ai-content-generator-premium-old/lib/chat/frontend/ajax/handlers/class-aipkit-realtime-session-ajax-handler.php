<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/frontend/ajax/handlers/class-aipkit-realtime-session-ajax-handler.php

namespace WPAICG\Lib\Chat\Frontend\Ajax\Handlers;

use WPAICG\Chat\Admin\Ajax\BaseAjaxHandler;
use WPAICG\Chat\Storage\BotStorage;
use WPAICG\AIPKit_Providers;
use WP_Error;
use WPAICG\Chat\Storage\LogStorage;
use WPAICG\Core\TokenManager\AIPKit_Token_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AIPKit_Realtime_Session_Ajax_Handler extends BaseAjaxHandler
{
    private $bot_storage;
    private $log_storage;
    private $token_manager;

    public function __construct()
    {
        if (class_exists(BotStorage::class)) {
            $this->bot_storage = new BotStorage();
        }
        if (class_exists(LogStorage::class)) {
            $this->log_storage = new LogStorage();
        }
        if (class_exists(AIPKit_Token_Manager::class)) {
            $this->token_manager = new AIPKit_Token_Manager();
        }
    }

    /**
     * AJAX handler for creating an OpenAI Realtime API session.
     * This mints an ephemeral key for client-side use.
     */
    public function ajax_create_session(): void
    {
        // Security check
        $permission_check = $this->check_frontend_permissions();
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        if (!$this->bot_storage) {
            $this->send_wp_error(new WP_Error('dependency_missing', __('Bot storage component is not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        // Get bot_id from POST
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_frontend_permissions().
        $bot_id = isset($_POST['bot_id']) ? absint($_POST['bot_id']) : 0;
        if (empty($bot_id)) {
            $this->send_wp_error(new WP_Error('missing_bot_id', __('Bot ID is required to create a session.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        // Get bot settings
        $bot_settings = $this->bot_storage->get_chatbot_settings($bot_id);
        if (empty($bot_settings)) {
            $this->send_wp_error(new WP_Error('bot_not_found', __('Could not load chatbot configuration.', 'gpt3-ai-content-generator'), ['status' => 404]));
            return;
        }

        // Check if the feature is enabled for this bot
        if (!isset($bot_settings['enable_realtime_voice']) || $bot_settings['enable_realtime_voice'] !== '1') {
            $this->send_wp_error(new WP_Error('feature_disabled', __('Realtime voice is not enabled for this chatbot.', 'gpt3-ai-content-generator'), ['status' => 403]));
            return;
        }

        // Check token limits before creating session
        if (!$this->token_manager) {
            $this->send_wp_error(new WP_Error('dependency_missing_token_manager', __('Token management service is not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        $user_id = get_current_user_id();
        $is_guest = !$user_id;
        
        // Get session ID for guests
        $session_id = null;
        if ($is_guest) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_frontend_permissions().
            $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : null;
            if (empty($session_id) && class_exists('\WPAICG\Chat\Frontend\Shortcode\Configurator')) {
                $session_id = \WPAICG\Chat\Frontend\Shortcode\Configurator::get_guest_uuid();
            }
        }

        $token_check_result = $this->token_manager->check_and_reset_tokens($user_id ?: null, $session_id, $bot_id, 'chat');
        if (is_wp_error($token_check_result)) {
            $this->send_wp_error($token_check_result);
            return;
        }

        // Prepare request body for OpenAI
        $request_body = [];

        // These keys correspond to settings that will be created in Phase 2
        $request_body['model'] = $bot_settings['realtime_model'] ?? 'gpt-4o-realtime-preview';
        $request_body['voice'] = $bot_settings['realtime_voice'] ?? 'alloy';
        $request_body['instructions'] = $bot_settings['instructions'] ?? 'You are a helpful assistant.';
        $request_body['temperature'] = isset($bot_settings['temperature']) ? floatval($bot_settings['temperature']) : 0.8;
        $request_body['speed'] = isset($bot_settings['speed']) ? floatval($bot_settings['speed']) : 1.0;

        // Handle turn_detection mapping
        $turn_detection_setting = $bot_settings['turn_detection'] ?? 'none';
        if ($turn_detection_setting === 'server_vad') {
            $request_body['turn_detection'] = [
                'type' => 'server_vad',
                'threshold' => 0.5,
                'prefix_padding_ms' => 300,
                'silence_duration_ms' => 200
            ];
        } elseif ($turn_detection_setting === 'semantic_vad') {
            $request_body['turn_detection'] = ['type' => 'semantic_vad'];
        } else {
            $request_body['turn_detection'] = null;
        }

        // Get main OpenAI API key from settings
        $openai_data = AIPKit_Providers::get_provider_data('OpenAI');
        $main_api_key = $openai_data['api_key'] ?? null;
        if (empty($main_api_key)) {
            $this->send_wp_error(new WP_Error('missing_api_key', __('OpenAI API key is not configured in main settings.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        // Make API call to OpenAI
        $response = wp_remote_post('https://api.openai.com/v1/realtime/sessions', [
            'method'  => 'POST',
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $main_api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode(array_filter($request_body, fn ($value) => $value !== null)), // Remove null values
        ]);

        if (is_wp_error($response)) {
            $this->send_wp_error(new WP_Error('http_request_failed', $response->get_error_message(), ['status' => 500]));
            return;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($body, true);

        if ($status_code !== 200) {
            $error_message = $decoded_body['error']['message'] ?? __('Failed to create a session.', 'gpt3-ai-content-generator');
            $this->send_wp_error(new WP_Error('api_error', $error_message, ['status' => $status_code]));
            return;
        }

        if (!isset($decoded_body['client_secret'])) {
            $this->send_wp_error(new WP_Error('invalid_response', __('Invalid response from OpenAI: client_secret missing.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        // Success
        wp_send_json_success(['client_secret' => $decoded_body['client_secret']]);
    }

    /**
     * AJAX handler for logging a completed Realtime API session turn.
     * This receives data from the client after a 'response.done' event.
     */
    public function ajax_log_session_turn(): void
    {
        $permission_check = $this->check_frontend_permissions();
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        if (!$this->log_storage || !$this->token_manager) {
            $this->send_wp_error(new WP_Error('dependency_missing_log_turn', __('Logging service is not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        $post_data = wp_unslash($_POST);
        $bot_id = isset($post_data['bot_id']) ? absint($post_data['bot_id']) : 0;
        $session_id = isset($post_data['session_id']) ? sanitize_text_field($post_data['session_id']) : null;
        $conversation_uuid = isset($post_data['conversation_uuid']) ? sanitize_key($post_data['conversation_uuid']) : '';
        $user_transcript = isset($post_data['user_transcript']) ? sanitize_textarea_field($post_data['user_transcript']) : '';
        $bot_transcript = isset($post_data['bot_transcript']) ? sanitize_textarea_field($post_data['bot_transcript']) : '';
        $usage_data_json = isset($post_data['usage_data']) ? $post_data['usage_data'] : null;
        $usage_data = $usage_data_json ? json_decode($usage_data_json, true) : null;

        if (empty($bot_id) || empty($conversation_uuid) || (empty($user_transcript) && empty($bot_transcript))) {
            $this->send_wp_error(new WP_Error('missing_params_log_turn', __('Missing required parameters for logging.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        $user_id = get_current_user_id();
        $is_guest = !$user_id;

        $base_log_data = [
            'bot_id'            => $bot_id,
            'user_id'           => $user_id ?: null,
            'session_id'        => $is_guest ? $session_id : null,
            'conversation_uuid' => $conversation_uuid,
            'module'            => 'chat',
            'is_guest'          => $is_guest,
            'role'              => $is_guest ? null : implode(', ', wp_get_current_user()->roles),
            'ip_address'        => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null,
        ];
        
        $bot_settings = $this->bot_storage->get_chatbot_settings($bot_id);

        // Log user turn
        if (!empty(trim($user_transcript))) {
            $this->log_storage->log_message(array_merge($base_log_data, [
                'message_role'    => 'user',
                'message_content' => $user_transcript,
                'timestamp'       => time(),
                'ai_provider'     => 'OpenAIRealtime',
                'ai_model'        => $bot_settings['realtime_model'] ?? 'unknown',
            ]));
        }

        // Log bot turn
        if (!empty(trim($bot_transcript))) {
            $this->log_storage->log_message(array_merge($base_log_data, [
                'message_role'    => 'bot',
                'message_content' => $bot_transcript,
                'timestamp'       => time(),
                'ai_provider'     => 'OpenAIRealtime',
                'ai_model'        => $bot_settings['realtime_model'] ?? 'unknown',
                'usage'           => $usage_data,
            ]));
        }

        // Record token usage
        $tokens_consumed = $usage_data['total_tokens'] ?? 0;
        if ($tokens_consumed > 0) {
            $this->token_manager->record_token_usage($user_id ?: null, $session_id, $bot_id, $tokens_consumed, 'chat');
        }

        wp_send_json_success(['message' => 'Session turn logged successfully.']);
    }
}
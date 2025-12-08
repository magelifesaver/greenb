<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/core/ajax-processor/frontend-chat/class-chat-response-logger.php
// Status: MODIFIED

namespace WPAICG\Chat\Core\AjaxProcessor\FrontendChat;

use WPAICG\Chat\Storage\LogStorage;
use WPAICG\Core\TokenManager\AIPKit_Token_Manager;
use WPAICG\Core\TokenManager\Constants\GuestTableConstants; // Corrected use statement
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatResponseLogger
{
    private $log_storage;
    private $token_manager;

    public function __construct(LogStorage $log_storage, AIPKit_Token_Manager $token_manager)
    {
        $this->log_storage = $log_storage;
        $this->token_manager = $token_manager;
    }

    /**
     * Logs the initial user message.
     *
     * @param array $base_log_data
     * @param string $user_message_text
     * @param array|null $image_inputs_for_service
     * @return array|WP_Error Log result or WP_Error.
     */
    public function log_user_message_initial(array $base_log_data, string $user_message_text, ?array $image_inputs_for_service): array|\WP_Error
    {
        $log_user_data = array_merge($base_log_data, [
            'message_role'    => 'user',
            'message_content' => $user_message_text,
            'timestamp'       => time(),
        ]);
        if (!empty($image_inputs_for_service)) {
            $log_user_data['response_data'] = ['type' => 'user_image_upload', 'images' => $image_inputs_for_service];
        }
        $user_log_result = $this->log_storage->log_message($log_user_data);
        if ($user_log_result === false) {
            return new WP_Error('user_log_failed', __('Failed to log user message.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        return $user_log_result; // Contains ['log_id', 'message_id', 'is_new_session']
    }

    /**
     * Logs AI response (success or error) and sends JSON response.
     *
     * @param array|WP_Error $ai_result Result from ChatAIRequestRunner.
     * @param array $base_log_data
     * @param array $bot_settings
     * @param int|null $user_id
     * @param string|null $session_id
     * @return void
     */
    public function log_and_send_response(array|WP_Error $ai_result, array $base_log_data, array $bot_settings, ?int $user_id, ?string $session_id): void
    {
        $provider = $bot_settings['provider'] ?? \WPAICG\AIPKit_Providers::get_current_provider(); // For logging

        if (is_wp_error($ai_result)) {
            $error_data = $ai_result->get_error_data();
            $request_payload_on_error = is_array($error_data) ? ($error_data['request_payload_log'] ?? null) : null;
            $log_error_data = array_merge($base_log_data, [
                'message_role'    => 'bot',
                'message_content' => "Error: " . $ai_result->get_error_message(),
                'timestamp'       => time(),
                'ai_provider'     => $provider,
                'ai_model'        => $bot_settings['model'] ?? '',
                'usage'           => null,
                'request_payload' => $request_payload_on_error,
            ]);
            $this->log_storage->log_message($log_error_data);
            $status_code = is_array($error_data) && isset($error_data['status_code']) ? (int)$error_data['status_code'] : 400; // Use status_code
            wp_send_json_error(['message' => $ai_result->get_error_message()], $status_code);
        } else {
            $ai_response = $ai_result['content'] ?? '';
            $usage_data = $ai_result['usage'] ?? null;
            $request_payload_log = $ai_result['request_payload_log'] ?? null;
            $openai_response_id_for_log = $ai_result['openai_response_id'] ?? null;
            $used_previous_id_for_log = $ai_result['used_previous_response_id'] ?? false;
            $grounding_metadata_for_log = $ai_result['grounding_metadata'] ?? null;
            $vector_search_scores_for_log = $ai_result['vector_search_scores'] ?? null;

            $tokens_consumed = $usage_data['total_tokens'] ?? 0;
            if ($tokens_consumed > 0) {
                $this->token_manager->record_token_usage($user_id, $session_id, $bot_settings['bot_id'], $tokens_consumed);
            }

            $log_bot_data = array_merge($base_log_data, [
                'message_role'    => 'bot', 'message_content' => $ai_response, 'timestamp' => time(),
                'ai_provider'     => $provider, 'ai_model' => $bot_settings['model'] ?? '', 'usage' => $usage_data,
                'request_payload' => $request_payload_log, 'openai_response_id' => $openai_response_id_for_log,
                'used_previous_response_id' => $used_previous_id_for_log, 'grounding_metadata' => $grounding_metadata_for_log,
                'vector_search_scores' => $vector_search_scores_for_log,
            ]);
            $bot_log_result = $this->log_storage->log_message($log_bot_data);

            $response_payload = ['reply' => $ai_response, 'message_id' => ($bot_log_result !== false) ? $bot_log_result['message_id'] : null];
            if ($openai_response_id_for_log) {
                $response_payload['openai_response_id'] = $openai_response_id_for_log;
            }
            if ($grounding_metadata_for_log) {
                $response_payload['grounding_metadata'] = $grounding_metadata_for_log;
            }
            wp_send_json_success($response_payload);
        }
    }

    /**
     * Handles sending JSON for trigger-based direct replies or blocks.
     *
     * @param string $status 'blocked' or 'ai_stopped'.
     * @param string $message_to_user The message for the user.
     * @param string $message_id The ID of the trigger's message.
     * @return void
     */
    public function send_trigger_json_response(string $status, string $message_to_user, string $message_id): void
    {
        if ($status === 'blocked') {
            wp_send_json_error(['message' => $message_to_user, 'is_trigger_response' => true, 'message_id' => $message_id], 400);
        } elseif ($status === 'ai_stopped') {
            wp_send_json_success(['reply' => $message_to_user, 'is_trigger_response' => true, 'message_id' => $message_id]);
        }
    }
}

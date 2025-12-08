<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/chat/fn-process-chat.php
// Status: MODIFIED (Became Orchestrator)

namespace WPAICG\Core\Stream\Contexts\Chat;

use WP_Error;

// --- ADDED: Require all new process files ---
$process_path = __DIR__ . '/process/';
require_once $process_path . 'extract-request-params.php';
require_once $process_path . 'validate-stream-requirements.php';
require_once $process_path . 'run-token-check.php';
require_once $process_path . 'run-content-moderation.php';
require_once $process_path . 'log-user-message.php';
require_once $process_path . 'trigger-session-start.php';
require_once $process_path . 'trigger-user-message.php';
require_once $process_path . 'build-ai-request-data-for-stream.php';
require_once $process_path . 'construct-sse-processor-input.php';
// --- END ADDED ---


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* Main orchestrator function for processing a chat stream request.
*
* @param \WPAICG\Core\Stream\Contexts\Chat\SSEChatStreamContextHandler $handlerInstance The instance of the context handler.
* @param array $cached_data Contains 'user_message', 'image_inputs', and potentially 'client_user_message_id', 'active_openai_vs_id', 'active_pinecone_index_name', 'active_pinecone_namespace', 'active_qdrant_collection_name', 'active_qdrant_file_upload_context_id'.
* @param array $get_params Original $_GET parameters.
* @return array|WP_Error Prepared data for SSEStreamProcessor or WP_Error.
*/
function process_chat_logic(
    SSEChatStreamContextHandler $handlerInstance,
    array $cached_data,
    array $get_params
): array|WP_Error {
    // 1. Extract Parameters
    $params = Process\extract_request_params_logic($cached_data, $get_params);

    // 2. Validate Stream Requirements
    $validation_result = Process\validate_stream_requirements_logic(
        $params['bot_id'],
        $params['conversation_uuid'],
        $params['user_id'],
        $params['session_id'],
        $params['user_message_text'],
        $params['image_inputs']
    );
    if (is_wp_error($validation_result)) {
        return $validation_result;
    }

    // 3. Token Check
    $token_manager = $handlerInstance->get_token_manager();
    if (!$token_manager) {
        return new WP_Error('dependency_missing_token_manager', 'Token manager is unavailable.', ['status' => 500]);
    }
    $token_check_result = Process\run_token_check_logic($token_manager, $params['user_id'], $params['session_id'], $params['bot_id'], $handlerInstance->get_log_storage());
    if (is_wp_error($token_check_result)) {
        return $token_check_result;
    }

    // 4. Content Moderation
    $bot_storage = $handlerInstance->get_bot_storage();
    if (!$bot_storage) {
        return new WP_Error('dependency_missing_bot_storage_moderation', 'Bot storage is unavailable for moderation.', ['status' => 500]);
    }
    $bot_settings = $bot_storage->get_chatbot_settings($params['bot_id']);
    if (empty($bot_settings)) {
        return new WP_Error('settings_load_failure_moderation', __('Could not load chatbot configuration.', 'gpt3-ai-content-generator'), ['status' => 500]);
    }

    $moderation_result = Process\run_content_moderation_logic($params['user_message_text'], $params['client_ip'], $bot_settings, $handlerInstance->get_log_storage(), $params['bot_id'], $params['user_id'], $params['session_id']);
    if (is_wp_error($moderation_result)) {
        return $moderation_result;
    }

    // 5. Log User Message & Determine if New Session
    $base_log_data = [
        'bot_id' => $params['bot_id'], 'user_id' => $params['user_id'], 'session_id' => $params['session_id'],
        'conversation_uuid' => $params['conversation_uuid'], 'module' => 'chat', 'is_guest' => ($params['user_id'] === 0),
        'role' => ($params['user_id'] > 0 && class_exists('WP_User') && ($u = get_user_by('id', $params['user_id'])) && isset($u->roles) && is_array($u->roles)) ? implode(', ', $u->roles) : 'guest',
        'ip_address' => $params['client_ip'], 'form_id' => null,
        'user_message_id_from_client' => $params['client_user_message_id']
    ];
    $bot_message_id_for_stream = 'aipkit-msg-' . uniqid('', true);
    $base_log_data['bot_message_id'] = $bot_message_id_for_stream;

    $user_log_result = Process\log_user_message_logic($handlerInstance->get_log_storage(), $base_log_data, $params['user_message_text'], $params['image_inputs'], time());
    if (is_wp_error($user_log_result)) {
        return $user_log_result;
    }
    $is_new_session = $user_log_result['is_new_session'] ?? false;

    // 6. Trigger Processing
    $ai_service_for_triggers = $handlerInstance->get_ai_service_for_helper();
    if (!$ai_service_for_triggers) {
        return new WP_Error('dependency_missing_aiservice_triggers', 'AI Service component missing for triggers.', ['status' => 500]);
    }

    if (function_exists('\WPAICG\Chat\Core\AIService\determine_provider_model')) {
        $provider_model_info = \WPAICG\Chat\Core\AIService\determine_provider_model($ai_service_for_triggers, $bot_settings);
    } else {
        $provider_model_info = ['provider' => $bot_settings['provider'] ?? null, 'model' => $bot_settings['model'] ?? null];
    }

    $trigger_context = [
        'bot_id' => $params['bot_id'], 'bot_settings' => $bot_settings, 'user_id' => $params['user_id'], 'session_id' => $params['session_id'],
        'client_ip' => $params['client_ip'], 'post_id' => $params['post_id'],
        'user_message_text' => $params['user_message_text'],
        'system_instruction_for_ai' => $bot_settings['instructions'] ?? '',
        'user_wp_role' => $base_log_data['role'],
        'current_provider' => $provider_model_info['provider'], 'current_model_id' => $provider_model_info['model'],
        'base_log_data' => $base_log_data, 'log_storage' => $handlerInstance->get_log_storage()
    ];

    $initial_trigger_reply_data = null;

    if ($is_new_session) {
        $session_trigger_result = Process\process_session_start_trigger_logic($trigger_context);
        if (is_wp_error($session_trigger_result)) {
            return $session_trigger_result;
        }
        // Update context based on session trigger result for user_message triggers
        $trigger_context['system_instruction_for_ai'] = $session_trigger_result['modified_context_data']['system_instruction'] ?? $trigger_context['system_instruction_for_ai'];
        $trigger_context['final_user_message_for_ai'] = $session_trigger_result['modified_context_data']['user_message_text'] ?? $trigger_context['user_message_text'];
        $initial_trigger_reply_data = $session_trigger_result['message_to_user'] ? $session_trigger_result : null;
    } else {
        $trigger_context['final_user_message_for_ai'] = $trigger_context['user_message_text'];
        $trigger_context['final_system_instruction_for_ai'] = $trigger_context['system_instruction_for_ai'];
    }

    $history_for_triggers = $handlerInstance->get_log_storage()->get_conversation_thread_history($params['user_id'] ?: null, $params['session_id'], $params['bot_id'], $params['conversation_uuid']);
    if (count($history_for_triggers) > 0 && end($history_for_triggers)['role'] === 'user') {
        array_pop($history_for_triggers);
    }
    $max_msgs_for_history = isset($bot_settings['max_messages']) ? absint($bot_settings['max_messages']) : 15;
    if (count($history_for_triggers) > $max_msgs_for_history) {
        $history_for_triggers = array_slice($history_for_triggers, -$max_msgs_for_history);
    }
    $trigger_context['final_history_for_ai'] = $history_for_triggers;

    $user_message_trigger_result = Process\process_user_message_trigger_logic($trigger_context);
    if (is_wp_error($user_message_trigger_result)) {
        return $user_message_trigger_result;
    }
    if (!$initial_trigger_reply_data && $user_message_trigger_result['message_to_user']) { // Only overwrite if session didn't provide one
        $initial_trigger_reply_data = $user_message_trigger_result;
    }

    // 7. Build AI Request Data
    $request_data_for_ai = Process\build_ai_request_data_for_stream_logic(
        $ai_service_for_triggers->get_ai_caller(),
        $ai_service_for_triggers->get_vector_store_manager(),
        $user_message_trigger_result['modified_context_data']['user_message_text'],
        $bot_settings,
        $provider_model_info['provider'],
        $provider_model_info['model'],
        $user_message_trigger_result['modified_context_data']['current_history'],
        $user_message_trigger_result['modified_context_data']['system_instruction'],
        $params['post_id'],
        $params['image_inputs'],
        $params['frontend_previous_openai_response_id'],
        $params['frontend_openai_web_search_active'],
        $params['frontend_google_search_grounding_active'],
        $params['active_openai_vs_id'],
        $params['active_pinecone_index_name'],
        $params['active_pinecone_namespace'],
        $params['active_qdrant_collection_name'],
        $params['active_qdrant_file_upload_context_id']
    );
    if (is_wp_error($request_data_for_ai)) {
        return $request_data_for_ai;
    }

    // 8. Construct Final Input for SSEStreamProcessor
    return Process\construct_sse_processor_input_logic(
        $request_data_for_ai,
        $params['conversation_uuid'],
        $base_log_data,
        $bot_message_id_for_stream,
        $initial_trigger_reply_data
    );
}

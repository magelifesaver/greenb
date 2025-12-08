<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/processor/fn-log-bot-response.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Processor;

use WPAICG\Core\TokenManager\Constants\GuestTableConstants;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logs the final successful bot response.
 *
 * @param \WPAICG\Core\Stream\Processor\SSEStreamProcessor $processorInstance The instance of the processor class.
 * @return void
 */
function log_bot_response_logic(\WPAICG\Core\Stream\Processor\SSEStreamProcessor $processorInstance): void
{
    $full_bot_response = $processorInstance->get_full_bot_response();
    $log_base_data = $processorInstance->get_log_base_data();
    $error_occurred = $processorInstance->get_error_occurred_status();
    $current_bot_message_id = $processorInstance->get_current_bot_message_id();
    $log_storage = $processorInstance->get_log_storage();
    $current_provider = $processorInstance->get_current_provider();
    $current_model = $processorInstance->get_current_model();
    $final_usage_data = $processorInstance->get_final_usage_data();
    $request_payload_log = $processorInstance->get_request_payload_log();
    $current_stream_context = $processorInstance->get_current_stream_context();
    $current_conversation_uuid = $processorInstance->get_current_conversation_uuid();
    $current_openai_response_id = $processorInstance->get_current_openai_response_id();
    $used_previous_openai_response_id = $processorInstance->get_used_previous_openai_response_id_status();
    $grounding_metadata = $processorInstance->get_grounding_metadata();
    $token_manager = $processorInstance->get_token_manager();
    $vector_search_scores = $processorInstance->get_vector_search_scores();

    if (!$log_storage) {
        return;
    }


    if (!empty($full_bot_response) && !empty($log_base_data) && !$error_occurred && !empty($current_bot_message_id)) {
        $log_bot_data = array_merge($log_base_data, [
            'message_role'    => 'bot',
            'message_content' => $full_bot_response,
            'timestamp'       => time(),
            'ai_provider'     => $current_provider,
            'ai_model'        => $current_model,
            'usage'           => $final_usage_data,
            'message_id'      => $current_bot_message_id,
            'request_payload' => $request_payload_log,
        ]);

        if ($current_provider === 'OpenAI') {
            if ($current_openai_response_id) {
                $log_bot_data['openai_response_id'] = $current_openai_response_id;
            }
            if ($used_previous_openai_response_id) {
                $log_bot_data['used_previous_response_id'] = true;
            }
        }
        if ($current_provider === 'Google' && $grounding_metadata !== null) {
            $log_bot_data['grounding_metadata'] = $grounding_metadata;
        }
        if (!empty($vector_search_scores)) {
            $log_bot_data['vector_search_scores'] = $vector_search_scores;
        }
        $log_storage->log_message($log_bot_data);

        $tokens_consumed = $final_usage_data['total_tokens'] ?? 0;
        if ($token_manager && $tokens_consumed > 0) { // Check if token_manager is available
            $module_for_tokens = $current_stream_context;
            $context_id_for_tokens = null;

            if ($module_for_tokens === 'chat' && !empty($log_base_data['bot_id'])) {
                $context_id_for_tokens = $log_base_data['bot_id'];
            } elseif ($module_for_tokens === 'ai_forms') {
                if ($log_base_data['is_guest']) {
                    $context_id_for_tokens = GuestTableConstants::AI_FORMS_GUEST_CONTEXT_ID;
                } else {
                    $context_id_for_tokens = null; // Correct for logged-in users with a generic AI Forms limit
                }
            } elseif ($module_for_tokens === 'content_writer') {
                if ($log_base_data['is_guest']) {
                    $context_id_for_tokens = GuestTableConstants::CONTENT_WRITER_GUEST_CONTEXT_ID;
                } else {
                    $context_id_for_tokens = null;
                }
            }


            if ($context_id_for_tokens !== null || !$log_base_data['is_guest']) {
                $token_manager->record_token_usage(
                    $log_base_data['user_id'],
                    $log_base_data['session_id'],
                    $context_id_for_tokens,
                    $tokens_consumed,
                    $module_for_tokens
                );
            }
        }

    } elseif (empty($current_bot_message_id)) {
        // Cannot log bot response because current_bot_message_id is empty. This indicates an internal error state.
    } elseif ($error_occurred) {
        // Skipped logging a successful response because an error was flagged earlier in the process.
    } elseif (empty($full_bot_response)) {
        if (function_exists(__NAMESPACE__ . '\log_bot_error_logic')) {
            log_bot_error_logic($processorInstance, "(Empty Response)");
        }
    }
}

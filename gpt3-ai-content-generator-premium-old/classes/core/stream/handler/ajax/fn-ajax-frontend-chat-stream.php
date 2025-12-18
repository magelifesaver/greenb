<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/handler/ajax/fn-ajax-frontend-chat-stream.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Handler\Ajax;

use WPAICG\Core\Stream\Handler\SSEHandler; 
use WPAICG\Utils\AIPKit_CORS_Manager; // For CORS handling
use WP_Error; 


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AJAX handler for processing STREAMING messages using Server-Sent Events (SSE).
 *
 * @param \WPAICG\Core\Stream\Handler\SSEHandler $handlerInstance The instance of the SSEHandler class.
 * @return void
 */
function ajax_frontend_chat_stream_logic(SSEHandler $handlerInstance): void {
    $response_formatter = $handlerInstance->get_response_formatter();
    $request_handler    = $handlerInstance->get_request_handler();
    $stream_processor   = $handlerInstance->get_stream_processor();

    // --- Handle preflight OPTIONS request ---
    AIPKit_CORS_Manager::handle_preflight_request();

    // --- CORS Check ---
    $bot_id = 0;
    if (isset($_GET['bot_id'])) {
        $bot_id = absint(wp_unslash($_GET['bot_id']));
    }

    if ($bot_id > 0) {
        $origin_allowed = AIPKit_CORS_Manager::check_and_set_cors_headers($bot_id);
        if (!$origin_allowed) {
            $response_formatter->set_sse_headers();
            $response_formatter->send_sse_error(__('This domain is not permitted to access the chatbot.', 'gpt3-ai-content-generator'));
            $response_formatter->send_sse_done();
            exit;
        }
    }

    $response_formatter->set_sse_headers();

    // --- FIX for PHPCS NonceVerification ---
    // Perform nonce check at the top of the AJAX handler before using any user input.
    if (!check_ajax_referer('aipkit_frontend_chat_nonce', '_ajax_nonce', false)) {
        $response_formatter->send_sse_error(__('Security check failed. Please refresh the page and try again.', 'gpt3-ai-content-generator'));
        $response_formatter->send_sse_done();
        exit;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified above. All $_GET usage is now considered safe.
    $get_data = wp_unslash($_GET);
    // --- END FIX ---


    $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
    $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';
    $triggers_addon_active = false;
    if (class_exists('\WPAICG\aipkit_dashboard')) {
        $triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
    }

    if (!$request_handler || !$stream_processor) {
        $error_message = __('Server error: Stream processing components not ready.', 'gpt3-ai-content-generator');
        $response_formatter->send_sse_error($error_message);
        if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class)) {
             $bot_id = isset($get_data['bot_id']) ? absint($get_data['bot_id']) : null; 
             $log_storage_for_trigger_error = class_exists('\WPAICG\Chat\Storage\LogStorage') ? new \WPAICG\Chat\Storage\LogStorage() : null;
             if ($log_storage_for_trigger_error) {
                $error_event_context = [
                    'error_code'    => 'sse_component_not_ready', 'error_message' => $error_message,
                    'bot_id'        => $bot_id, 'user_id'       => get_current_user_id() ?: null, 
                    'session_id'    => isset($get_data['session_id']) ? sanitize_text_field($get_data['session_id']) : null,
                    'module'        => 'chat_stream_handler', 'operation'     => 'initialize_sse_components',
                ];
                $trigger_storage = new $trigger_storage_class();
                // --- MODIFIED: Pass LogStorage to TriggerManager ---
                $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_trigger_error);
                // --- END MODIFICATION ---
                $trigger_manager->process_event($bot_id ?? 0, 'system_error_occurred', $error_event_context);
             }
        }
        $response_formatter->send_sse_done(); 
        exit;
    }

    try {
        $processed_data = $request_handler->process_initial_request($get_data);

        if (is_wp_error($processed_data)) {
            $error_code = $processed_data->get_error_code();
            $error_data = $processed_data->get_error_data() ?: []; 
            $status_code = is_array($error_data) && isset($error_data['status']) && is_int($error_data['status']) ? $error_data['status'] : 500;
            $user_facing_message = $processed_data->get_error_message();

            if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class)) {
                $bot_id = isset($get_data['bot_id']) ? absint($get_data['bot_id']) : null; 
                $log_storage_for_trigger_error = class_exists('\WPAICG\Chat\Storage\LogStorage') ? new \WPAICG\Chat\Storage\LogStorage() : null;
                if ($log_storage_for_trigger_error) {
                    $error_event_context = [
                        'error_code'    => $error_code, 'error_message' => $user_facing_message,
                        'bot_id'        => $bot_id, 'user_id'       => get_current_user_id() ?: null,
                        'session_id'    => isset($get_data['session_id']) ? sanitize_text_field($get_data['session_id']) : null,
                        'module'        => $error_data['failed_module'] ?? 'chat_stream_handler', 
                        'operation'     => $error_data['failed_operation'] ?? 'process_initial_sse_request',
                        'failed_provider' => $error_data['failed_provider'] ?? null,
                        'failed_model'    => $error_data['failed_model'] ?? null,
                        'http_code'     => $status_code,
                    ];
                    // --- MODIFIED: Pass LogStorage to TriggerManager ---
                    $trigger_storage = new $trigger_storage_class();
                    $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_trigger_error);
                    // --- END MODIFICATION ---
                    $trigger_manager->process_event($bot_id ?? 0, 'system_error_occurred', $error_event_context);
                }
            }

            if ($error_code === 'trigger_blocked') {
                $response_formatter->send_sse_error($user_facing_message, false); 
                $response_formatter->send_sse_done();
                exit;
            } elseif ($error_code === 'trigger_direct_reply') {
                $reply_bot_message_id = is_array($error_data) && isset($error_data['message_id']) ? $error_data['message_id'] : ('trigger-reply-' . uniqid());
                $response_formatter->send_sse_event('message_start', ['message_id' => $reply_bot_message_id]);
                $response_formatter->send_sse_data(['delta' => $user_facing_message]); 
                $response_formatter->send_sse_done();
                exit;
            } elseif ($error_code === 'trigger_display_form') {
                $form_event_data = $error_data['display_form_event_data'] ?? null;
                if ($form_event_data) {
                    $response_formatter->send_sse_event('display_form_event', $form_event_data);
                } else {
                    $response_formatter->send_sse_error(__('Form display requested but data is missing.', 'gpt3-ai-content-generator'), false);
                }
                $response_formatter->send_sse_done(); 
                exit; 
            } else {
                throw new \Exception($user_facing_message, $status_code);
            }
        }
        
        if (isset($processed_data['initial_trigger_reply_data']) && is_array($processed_data['initial_trigger_reply_data'])) {
            $initial_reply = $processed_data['initial_trigger_reply_data'];
            if (!empty($initial_reply['message_id']) && !empty($initial_reply['message'])) {
                $response_formatter->send_sse_event('message_start', ['message_id' => $initial_reply['message_id']]);
                $response_formatter->send_sse_data(['delta' => $initial_reply['message']]);
            }
        }

        if (empty($processed_data['bot_message_id'])) {
            throw new \Exception('Internal error: Missing generated message ID for stream.', 500);
        }

        $base_log_data_with_msg_id = $processed_data['base_log_data'] ?? [];
        if (empty($base_log_data_with_msg_id['bot_message_id'])) {
            $base_log_data_with_msg_id['bot_message_id'] = $processed_data['bot_message_id'];
        }

        $response_formatter->send_sse_event('message_start', ['message_id' => $processed_data['bot_message_id']]);

        // Set vector search scores in the stream processor for logging
        if (isset($processed_data['vector_search_scores']) && is_array($processed_data['vector_search_scores'])) {
            $stream_processor->set_vector_search_scores($processed_data['vector_search_scores']);
        }

        $stream_processor->start_stream(
            $processed_data['provider'], $processed_data['model'],
            $processed_data['user_message'], $processed_data['history'],
            $processed_data['system_instruction_filtered'],
            $processed_data['api_params'], $processed_data['ai_params'],
            $processed_data['conversation_uuid'], $base_log_data_with_msg_id
        );

    } catch (\Exception $e) {
        $error_code_http = is_int($e->getCode()) && $e->getCode() >= 400 ? $e->getCode() : 500;
        $error_message_final = $e->getMessage();
        if (!$response_formatter->get_headers_sent_status()) $response_formatter->set_sse_headers();
        $response_formatter->send_sse_error($error_message_final);
        
        if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class)) {
             $bot_id = isset($get_data['bot_id']) ? absint($get_data['bot_id']) : null; 
             $log_storage_for_trigger_error = class_exists('\WPAICG\Chat\Storage\LogStorage') ? new \WPAICG\Chat\Storage\LogStorage() : null;
             if ($log_storage_for_trigger_error) {
                $error_event_context = [
                   'error_code'    => 'sse_handler_exception_' . $error_code_http, 'error_message' => $error_message_final,
                   'bot_id'        => $bot_id, 'user_id'       => get_current_user_id() ?: null, 
                   'session_id'    => isset($get_data['session_id']) ? sanitize_text_field($get_data['session_id']) : null,
                   'module'        => 'chat_stream_handler', 'operation'     => 'process_stream_request',
                   'http_code'     => $error_code_http,
                ];
                // --- MODIFIED: Pass LogStorage to TriggerManager ---
                $trigger_storage = new $trigger_storage_class();
                $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_trigger_error);
                // --- END MODIFICATION ---
                $trigger_manager->process_event($bot_id ?? 0, 'system_error_occurred', $error_event_context);
             }
        }
        
        $response_formatter->send_sse_done();
    } finally {
        if (function_exists('fastcgi_finish_request')) {
             fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
             litespeed_finish_request();
        }
        // Ensure script exits after sending all SSE data or handling errors
        exit;
    }
}
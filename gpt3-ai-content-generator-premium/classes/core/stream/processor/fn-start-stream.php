<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/processor/fn-start-stream.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Processor;

use WPAICG\Core\Providers\ProviderStrategyFactory;
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WPAICG\Core\AIPKit_Payload_Sanitizer;
use WPAICG\Chat\Storage\LogStorage; // Added for TriggerManager

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Starts the SSE stream processing.
 *
 * @param \WPAICG\Core\Stream\Processor\SSEStreamProcessor $processorInstance The instance of the processor class.
 * @param string $provider AI provider.
 * @param string $model AI model.
 * @param string $user_message User's message.
 * @param array  $history Conversation history.
 * @param string $system_instruction_filtered Processed system instruction.
 * @param array  $api_params API connection parameters.
 * @param array  $ai_params AI generation parameters.
 * @param string $conversation_uuid Conversation UUID.
 * @param array  $base_log_data Base data for logging.
 * @return void
 * @throws \Exception If strategy cannot be obtained or URL build fails.
 */
function start_stream_logic(
    \WPAICG\Core\Stream\Processor\SSEStreamProcessor $processorInstance,
    string $provider,
    string $model,
    string $user_message,
    array  $history,
    string $system_instruction_filtered,
    array  $api_params,
    array  $ai_params,
    string $conversation_uuid,
    array  $base_log_data
): void {
    $formatter = $processorInstance->get_formatter();
    $log_storage_for_triggers = $processorInstance->get_log_storage(); // Get LogStorage

    $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
    $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';
    $triggers_addon_active = false;
    if (class_exists('\WPAICG\aipkit_dashboard')) {
        $triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
    }

    try {
        $strategy = ProviderStrategyFactory::get_strategy($provider);
        if (is_wp_error($strategy)) {
            if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class) && $log_storage_for_triggers) {
                $error_event_context = [
                    'error_code'    => $strategy->get_error_code(),
                    'error_message' => $strategy->get_error_message(),
                    'bot_id'        => $base_log_data['bot_id'] ?? null,
                    'user_id'       => $base_log_data['user_id'] ?? null,
                    'session_id'    => $base_log_data['session_id'] ?? null,
                    'module'        => $base_log_data['module'] ?? 'unknown_stream',
                    'operation'     => 'get_strategy_for_stream',
                    'failed_provider' => $provider,
                    'failed_model'    => $model,
                ];
                $trigger_storage = new $trigger_storage_class();
                $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_triggers); // Pass LogStorage
                $trigger_manager->process_event($base_log_data['bot_id'] ?? 0, 'system_error_occurred', $error_event_context);
            }
            throw new \Exception($strategy->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $processorInstance->set_strategy($strategy);

        $processorInstance->initialize_stream_state(
            $provider,
            $model,
            $conversation_uuid,
            ($base_log_data['bot_message_id'] ?? null),
            $base_log_data,
            ($base_log_data['module'] ?? 'chat'),
            ($provider === 'OpenAI' && !empty($ai_params['previous_response_id']))
        );

        if (empty($processorInstance->get_current_bot_message_id())) {
            throw new \Exception('Internal error: Missing bot message ID for stream.');
        }

        $url_operation = 'stream';
        $url_params = array_merge($api_params, ['model' => $model, 'deployment' => $model]);
        $endpoint_url = $strategy->build_api_url($url_operation, $url_params);
        if (is_wp_error($endpoint_url)) {
            if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class) && $log_storage_for_triggers) {
                $error_event_context = [
                    'error_code'    => $endpoint_url->get_error_code(),
                    'error_message' => $endpoint_url->get_error_message(),
                    'bot_id'        => $base_log_data['bot_id'] ?? null,
                    'user_id'       => $base_log_data['user_id'] ?? null,
                    'session_id'    => $base_log_data['session_id'] ?? null,
                    'module'        => $base_log_data['module'] ?? 'unknown_stream',
                    'operation'     => 'build_stream_url',
                    'failed_provider' => $provider,
                    'failed_model'    => $model,
                ];
                $trigger_storage = new $trigger_storage_class();
                $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_triggers); // Pass LogStorage
                $trigger_manager->process_event($base_log_data['bot_id'] ?? 0, 'system_error_occurred', $error_event_context);
            }
            throw new \Exception($endpoint_url->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $headers = $strategy->get_api_headers($api_params['api_key'], 'stream');
        $curl_headers = $strategy->format_headers_for_curl($headers);

        $messages_for_strategy_payload = $history;
        if (($processorInstance->get_current_stream_context() === 'content_writer' || $processorInstance->get_current_stream_context() === 'ai_forms') && !empty($user_message)) {
            $messages_for_strategy_payload[] = ['role' => 'user', 'content' => $user_message];
        }

        $final_ai_params = array_merge($ai_params, $api_params);
        if ($provider === 'Google' && !isset($final_ai_params['safety_settings']) && class_exists(GoogleSettingsHandler::class)) {
            $final_ai_params['safety_settings'] = GoogleSettingsHandler::get_safety_settings();
        }

        $curl_post_data = $strategy->build_sse_payload(
            $messages_for_strategy_payload,
            $system_instruction_filtered,
            $final_ai_params,
            $model
        );

        $sanitized_curl_post_data_for_log = AIPKit_Payload_Sanitizer::sanitize_for_logging($curl_post_data);
        $processorInstance->set_request_payload_log([
            'provider' => $provider, 'model' => $model, 'payload_sent' => $sanitized_curl_post_data_for_log,
        ]);
        $curl_post_json_for_log = json_encode($sanitized_curl_post_data_for_log);

        $curl_post_data = apply_filters('aipkit_ai_query', $curl_post_data, $provider, $model, $history, $system_instruction_filtered, $api_params, $ai_params);
        $curl_post_json = json_encode($curl_post_data);
        // Post-encode sanitize: avoid environment-driven over-precision
        if (is_string($curl_post_json) && strpos($curl_post_json, 'score_threshold') !== false) {
            $curl_post_json = preg_replace_callback(
                '/("score_threshold"\s*:\s*)(-?\d+(?:\.\d+)?(?:[eE][+\-]?\d+)?)/',
                function ($m) {
                    $val = (float)$m[2];
                    if ($val <= 0) { $val = 0.0; }
                    elseif ($val >= 1) { $val = 1.0; }
                    else { $val = round($val, 6); }
                    $formatted = rtrim(rtrim(number_format($val, 6, '.', ''), '0'), '.');
                    if ($formatted === '' || $formatted === '-0') { $formatted = '0'; }
                    return $m[1] . $formatted;
                },
                $curl_post_json
            );
        }

        $curl_options_base = $strategy->get_request_options('stream');
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init -- Reason: Using cURL for streaming.
        $ch = curl_init();
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_URL, $endpoint_url);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_POST, true);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post_json);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, [$processorInstance, 'curl_stream_callback_public_wrapper']);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_TIMEOUT, $curl_options_base['timeout'] ?? 120);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $curl_options_base['sslverify'] ?? true);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($curl_options_base['sslverify'] ?? true) ? 2 : 0);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        if (!empty($curl_options_base['user-agent'])) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
            curl_setopt($ch, CURLOPT_USERAGENT, $curl_options_base['user-agent']);
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec -- Reason: Using cURL for streaming.
        curl_exec($ch);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo -- Reason: Using cURL for streaming.
        $final_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_errno -- Reason: Using cURL for streaming.
        $curl_error_num  = curl_errno($ch);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error -- Reason: Using cURL for streaming.
        $curl_error_msg  = curl_error($ch);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close -- Reason: Using cURL for streaming.
        curl_close($ch);

        if (!$processorInstance->get_error_occurred_status() && !empty($processorInstance->get_full_bot_response())) {
            log_bot_response_logic($processorInstance);
        }

        if ($curl_error_num) {
            $error_message = "Connection Error: {$curl_error_msg}";
            if (!$processorInstance->get_error_occurred_status()) {
                $formatter->send_sse_error($error_message, false);
                $processorInstance->set_error_occurred_status(true);
            }
            log_bot_error_logic($processorInstance, $error_message);
            if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class) && $log_storage_for_triggers) {
                $error_event_context = [
                   'error_code'    => 'curl_error_' . $curl_error_num, 'error_message' => $curl_error_msg,
                   'bot_id'        => $base_log_data['bot_id'] ?? null, 'user_id'       => $base_log_data['user_id'] ?? null,
                   'session_id'    => $base_log_data['session_id'] ?? null, 'module'        => $processorInstance->get_current_stream_context(),
                   'operation'     => 'stream_curl_execution', 'failed_provider' => $provider, 'failed_model'    => $model,
                   'http_code'     => $final_http_code,
                ];
                $trigger_storage = new $trigger_storage_class();
                $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_triggers); // Pass LogStorage
                $trigger_manager->process_event($base_log_data['bot_id'] ?? 0, 'system_error_occurred', $error_event_context);
            }
        } elseif ($final_http_code >= 400 && !$processorInstance->get_data_sent_to_frontend_status() && !$processorInstance->get_error_occurred_status()) {
            $api_error_message = $strategy->parse_error_response(trim($processorInstance->get_incomplete_sse_buffer()), $final_http_code);
            $formatter->send_sse_error("API Error: {$api_error_message}", false);
            $processorInstance->set_error_occurred_status(true);
            log_bot_error_logic($processorInstance, "API Error ({$final_http_code}): {$api_error_message}");
            if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class) && $log_storage_for_triggers) {
                $error_event_context = [
                   'error_code'    => 'api_error_http_' . $final_http_code, 'error_message' => $api_error_message,
                   'bot_id'        => $base_log_data['bot_id'] ?? null, 'user_id'       => $base_log_data['user_id'] ?? null,
                   'session_id'    => $base_log_data['session_id'] ?? null, 'module'        => $processorInstance->get_current_stream_context(),
                   'operation'     => 'stream_api_response', 'failed_provider' => $provider, 'failed_model'    => $model,
                   'http_code'     => $final_http_code,
                ];
                $trigger_storage = new $trigger_storage_class();
                $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_triggers); // Pass LogStorage
                $trigger_manager->process_event($base_log_data['bot_id'] ?? 0, 'system_error_occurred', $error_event_context);
            }
        } elseif ($final_http_code == 200 && !$processorInstance->get_data_sent_to_frontend_status() && empty($processorInstance->get_full_bot_response()) && !$processorInstance->get_error_occurred_status()) {
            $no_data_error_msg = "Connection error: no data received from AI.";
            $formatter->send_sse_error($no_data_error_msg, false);
            $processorInstance->set_error_occurred_status(true);
            log_bot_error_logic($processorInstance, $no_data_error_msg);
            if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class) && $log_storage_for_triggers) {
                $error_event_context = [
                   'error_code'    => 'no_data_received', 'error_message' => $no_data_error_msg,
                   'bot_id'        => $base_log_data['bot_id'] ?? null, 'user_id'       => $base_log_data['user_id'] ?? null,
                   'session_id'    => $base_log_data['session_id'] ?? null, 'module'        => $processorInstance->get_current_stream_context(),
                   'operation'     => 'stream_empty_response', 'failed_provider' => $provider, 'failed_model'    => $model,
                   'http_code'     => $final_http_code,
                ];
                $trigger_storage = new $trigger_storage_class();
                $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_triggers); // Pass LogStorage
                $trigger_manager->process_event($base_log_data['bot_id'] ?? 0, 'system_error_occurred', $error_event_context);
            }
        } elseif (!$processorInstance->get_error_occurred_status()) {
            $done_data = ['finished' => true];
            if ($processorInstance->get_grounding_metadata() !== null) {
                $done_data['grounding_metadata'] = $processorInstance->get_grounding_metadata();
            }
            $formatter->send_sse_event('done', $done_data);
        } else {
            $formatter->send_sse_done();
        }

    } catch (\Exception $e) {
        $error_message_final = $e->getMessage();
        $error_code_final = is_int($e->getCode()) && $e->getCode() !== 0 ? $e->getCode() : 500;
        $formatter->set_sse_headers();
        $formatter->send_sse_error($error_message_final);

        if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class) && $log_storage_for_triggers) {
            $error_event_context = [
               'error_code'    => 'stream_processor_exception', 'error_message' => $error_message_final,
               'bot_id'        => $base_log_data['bot_id'] ?? null, 'user_id'       => $base_log_data['user_id'] ?? null,
               'session_id'    => $base_log_data['session_id'] ?? null, 'module'        => $processorInstance->get_current_stream_context(),
               'operation'     => 'stream_setup_exception', 'failed_provider' => $provider ?? null, 'failed_model'    => $model ?? null,
               'http_code'     => $error_code_final,
            ];
            $trigger_storage = new $trigger_storage_class();
            $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_triggers); // Pass LogStorage
            $trigger_manager->process_event($base_log_data['bot_id'] ?? 0, 'system_error_occurred', $error_event_context);
        }

        $formatter->send_sse_done();
    } finally {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
        exit;
    }
}

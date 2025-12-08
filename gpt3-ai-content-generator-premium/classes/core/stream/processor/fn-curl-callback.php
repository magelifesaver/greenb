<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/processor/fn-curl-callback.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Processor;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * cURL callback function to process stream chunks.
 *
 * @param \WPAICG\Core\Stream\Processor\SSEStreamProcessor $processorInstance The instance of the processor class.
 * @param resource $ch cURL handle.
 * @param string $chunk The data chunk.
 * @return int Length of the processed chunk.
 */
function curl_stream_callback_logic(\WPAICG\Core\Stream\Processor\SSEStreamProcessor $processorInstance, $ch, string $chunk): int {
    $chunk_len = strlen($chunk);
    if ($chunk_len === 0 || !$processorInstance->get_strategy()) return 0;

    $processorInstance->set_curl_callback_invoked_status(true);
    $processorInstance->increment_curl_chunk_counter();
    // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo -- Reason: Using cURL for streaming.
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $data_sent_to_frontend = $processorInstance->get_data_sent_to_frontend_status();
    $incomplete_sse_buffer_ref = $processorInstance->get_incomplete_sse_buffer(); // Get initial value
    $formatter = $processorInstance->get_formatter();

    if (!$formatter) { 
        return -1; 
    }

    if ($http_code >= 400 && !$data_sent_to_frontend) {
        $processorInstance->append_to_incomplete_sse_buffer($chunk); 
        return $chunk_len;
    }
    if ($http_code >= 400 && $data_sent_to_frontend) {
         return $chunk_len;
    }

    $parsed = $processorInstance->get_strategy()->parse_sse_chunk($chunk, $incomplete_sse_buffer_ref); 
    $processorInstance->set_incomplete_sse_buffer($incomplete_sse_buffer_ref); // Update buffer in processor instance

    if ($parsed['usage'] !== null && is_array($parsed['usage'])) {
        $processorInstance->set_final_usage_data($parsed['usage']);
    }
    if (isset($parsed['openai_response_id']) && !empty($parsed['openai_response_id'])) {
        $processorInstance->set_current_openai_response_id($parsed['openai_response_id']);
        $formatter->send_sse_event('openai_response_id', ['id' => $parsed['openai_response_id']]);
    }
    if (isset($parsed['grounding_metadata']) && is_array($parsed['grounding_metadata'])) {
        $processorInstance->set_grounding_metadata($parsed['grounding_metadata']);
        $formatter->send_sse_event('grounding_metadata', $parsed['grounding_metadata']);
    }

    if ($parsed['is_error'] && $parsed['delta']) {
         if (!$processorInstance->get_error_occurred_status()) {
            $formatter->send_sse_error($parsed['delta'], false);
            $processorInstance->set_error_occurred_status(true);
            // Error handler will call log_bot_error_logic and dispatch trigger
         }
         return -1; // Signal error to cURL
    }
    if ($parsed['is_warning'] && $parsed['delta']) {
         $formatter->send_sse_error($parsed['delta'], true);
         $processorInstance->append_to_full_bot_response($parsed['delta']);
         $processorInstance->set_data_sent_to_frontend_status(true);
    }
    if ($parsed['delta'] !== null && !$parsed['is_error'] && !$parsed['is_warning']) {
        $processorInstance->append_to_full_bot_response($parsed['delta']);
        $formatter->send_sse_data(['delta' => $parsed['delta']]);
        $processorInstance->set_data_sent_to_frontend_status(true);
    }

    if (connection_aborted()) {
        $abort_message = "Connection aborted by client.";
        if (!$processorInstance->get_error_occurred_status()) { // Only set error if not already set
            $processorInstance->set_error_occurred_status(true); 
            // Note: log_bot_error_logic will be called by ConnectionValidator if this error is passed to it.
            // Or, if we want to log immediately here:
            // log_bot_error_logic($processorInstance, $abort_message);
        }
        return -1; 
    }

    return $chunk_len;
}
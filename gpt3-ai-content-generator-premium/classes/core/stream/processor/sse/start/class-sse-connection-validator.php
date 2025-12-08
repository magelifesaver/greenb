<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/processor/sse/start/class-sse-connection-validator.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Processor\SSE\Start;

use WPAICG\Core\Stream\Processor\SSEStreamProcessor;
use WPAICG\Core\Stream\Formatter\SSEResponseFormatter;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Validates the SSE connection after cURL execution and finalizes the stream.
 */
class SSEConnectionValidator {

    private $processorInstance;
    private $formatter;

    public function __construct(SSEStreamProcessor $processorInstance, SSEResponseFormatter $formatter) {
        $this->processorInstance = $processorInstance;
        $this->formatter = $formatter;
    }

    /**
     * Validates cURL execution results and finalizes the SSE stream.
     * Logs successful responses or returns WP_Error for the handler to manage.
     *
     * @param array $exec_result Result from SSERequestExecutor::execute()
     *                           ['final_http_code', 'curl_error_num', 'curl_error_msg']
     * @return true|WP_Error True if successful completion, WP_Error if an error occurred that needs handling by SSEErrorHandler.
     */
    public function validate_and_finalize(array $exec_result): bool|WP_Error {
        $final_http_code = $exec_result['final_http_code'];
        $curl_error_num  = $exec_result['curl_error_num'];
        $curl_error_msg  = $exec_result['curl_error_msg'];

        // 1. Log successful bot response if no errors occurred during stream and response exists
        if (!$this->processorInstance->get_error_occurred_status() && !empty($this->processorInstance->get_full_bot_response())) {
            if (function_exists('\WPAICG\Core\Stream\Processor\log_bot_response_logic')) {
                \WPAICG\Core\Stream\Processor\log_bot_response_logic($this->processorInstance);
            }
        }

        // 2. Check for cURL-level errors
        if ($curl_error_num) {
            $error_message = "Connection Error: {$curl_error_msg}";
            if (!$this->processorInstance->get_error_occurred_status()) {
                $this->formatter->send_sse_error($error_message, false); // false for fatal
                $this->processorInstance->set_error_occurred_status(true);
                 if (function_exists('\WPAICG\Core\Stream\Processor\log_bot_error_logic')) {
                    \WPAICG\Core\Stream\Processor\log_bot_error_logic($this->processorInstance, $error_message);
                }
            }
            $this->formatter->send_sse_done();
            return new WP_Error('curl_error_' . $curl_error_num, $error_message, ['operation' => 'stream_curl_execution', 'http_code' => $final_http_code]);
        }

        // 3. Check for HTTP errors if no data was sent and no prior error
        if ($final_http_code >= 400 && !$this->processorInstance->get_data_sent_to_frontend_status() && !$this->processorInstance->get_error_occurred_status()) {
            $strategy = $this->processorInstance->get_strategy();
            $api_error_message = $strategy ? $strategy->parse_error_response(trim($this->processorInstance->get_incomplete_sse_buffer()), $final_http_code) : "Unknown API error (strategy missing).";

            $this->formatter->send_sse_error("API Error: {$api_error_message}", false);
            $this->processorInstance->set_error_occurred_status(true);
            if (function_exists('\WPAICG\Core\Stream\Processor\log_bot_error_logic')) {
                \WPAICG\Core\Stream\Processor\log_bot_error_logic($this->processorInstance, "API Error ({$final_http_code}): {$api_error_message}");
            }
            $this->formatter->send_sse_done();
            return new WP_Error('api_error_http_' . $final_http_code, $api_error_message, ['operation' => 'stream_api_response', 'http_code' => $final_http_code]);
        }

        // 4. Check for HTTP 200 but no data and no prior error
        if ($final_http_code == 200 && !$this->processorInstance->get_data_sent_to_frontend_status() && empty($this->processorInstance->get_full_bot_response()) && !$this->processorInstance->get_error_occurred_status()) {
            $no_data_error_msg = "Connection error: no data received from AI.";
            $this->formatter->send_sse_error($no_data_error_msg, false);
            $this->processorInstance->set_error_occurred_status(true);
            if (function_exists('\WPAICG\Core\Stream\Processor\log_bot_error_logic')) {
                \WPAICG\Core\Stream\Processor\log_bot_error_logic($this->processorInstance, $no_data_error_msg);
            }
            $this->formatter->send_sse_done();
            return new WP_Error('no_data_received_validator', $no_data_error_msg, ['operation' => 'stream_empty_response', 'http_code' => $final_http_code]);
        }

        // 5. If no errors occurred *during* the stream processing by the callback, send 'done'
        if (!$this->processorInstance->get_error_occurred_status()) {
            $done_data = ['finished' => true];
            if ($this->processorInstance->get_grounding_metadata() !== null) {
                $done_data['grounding_metadata'] = $this->processorInstance->get_grounding_metadata();
            }
            $this->formatter->send_sse_event('done', $done_data);
        } else {
            // If an error occurred and was sent by the callback/validator, still send a final 'done'
            $this->formatter->send_sse_done();
        }
        return true;
    }
}
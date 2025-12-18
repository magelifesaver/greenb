<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/processor/sse/start/class-sse-request-executor.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Processor\SSE\Start;

use WPAICG\Core\Stream\Processor\SSEStreamProcessor;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Executes the cURL request for SSE streaming.
 */
class SSERequestExecutor {

    private $processorInstance;

    public function __construct(SSEStreamProcessor $processorInstance) {
        $this->processorInstance = $processorInstance;
    }

    /**
     * Executes the cURL request and handles the streaming callback.
     *
     * @param string $endpoint_url The API endpoint URL.
     * @param array $curl_headers HTTP headers for cURL.
     * @param string $curl_post_json JSON encoded POST data.
     * @return array ['final_http_code' => int, 'curl_error_num' => int, 'curl_error_msg' => string]
     */
    public function execute(string $endpoint_url, array $curl_headers, string $curl_post_json): array {
        $strategy = $this->processorInstance->get_strategy();
        if (!$strategy) {
            return ['final_http_code' => 0, 'curl_error_num' => -1, 'curl_error_msg' => 'Strategy not set for executor.'];
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
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, [$this->processorInstance, 'curl_stream_callback_public_wrapper']);
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
        $final_http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_errno -- Reason: Using cURL for streaming.
        $curl_error_num  = curl_errno($ch);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error -- Reason: Using cURL for streaming.
        $curl_error_msg  = curl_error($ch);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close -- Reason: Using cURL for streaming.
        curl_close($ch);
        return [
            'final_http_code' => $final_http_code,
            'curl_error_num'  => $curl_error_num,
            'curl_error_msg'  => $curl_error_msg,
        ];
    }
}
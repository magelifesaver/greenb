<?php

namespace WPAICG\Lib\Chat\Triggers\Actions; // UPDATED Namespace

use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor; // Ensure this is the correct new namespace if Placeholder_Processor is also in Lib.
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Action_Call_Webhook
 *
 * Handles the 'call_webhook' trigger action.
 */
class AIPKit_Action_Call_Webhook {

    /**
     * Executes the 'call_webhook' action.
     *
     * @param array $payload Payload for the webhook.
     * @param array $context_data Contextual data for placeholders.
     * @param int   $bot_id Bot ID.
     * @return array|WP_Error Action result or WP_Error.
     */
    public function execute(array $payload, array $context_data, int $bot_id): array|WP_Error {
        $endpoint_url_template = $payload['endpoint_url'] ?? null;
        $http_method = strtoupper($payload['http_method'] ?? 'POST');
        $headers_template = $payload['headers'] ?? [];
        $body_template = $payload['body_template'] ?? null; 
        $timeout_seconds = isset($payload['timeout_seconds']) ? absint($payload['timeout_seconds']) : 10;
        $timeout_seconds = max(1, min($timeout_seconds, 30));

        if (empty($endpoint_url_template)) {
            return new WP_Error('missing_webhook_endpoint', __('Webhook endpoint URL is required.', 'gpt3-ai-content-generator'));
        }
        if (!in_array($http_method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
            return new WP_Error('invalid_webhook_method', __('Invalid HTTP method for webhook.', 'gpt3-ai-content-generator'));
        }
        
        // Ensure PlaceholderProcessor class exists
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor::class)) {
             // Decide how to handle - perhaps return an error or process without placeholders.
             // For now, let it proceed and placeholders won't be replaced.
             $process_placeholders = function ($str, $ctx) { return $str; }; // No-op
        } else {
            $process_placeholders = [\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor::class, 'process'];
        }


        $endpoint_url = esc_url_raw(trim(call_user_func($process_placeholders, $endpoint_url_template, $context_data)));
        if (empty($endpoint_url) || !wp_http_validate_url($endpoint_url)) {
             return new WP_Error('invalid_webhook_url', __('Invalid or unsafe webhook URL after processing placeholders.', 'gpt3-ai-content-generator'));
        }

        $processed_headers = [];
        if (is_array($headers_template)) {
            foreach ($headers_template as $key => $value_template) {
                $processed_headers[sanitize_key($key)] = call_user_func($process_placeholders, (string)$value_template, $context_data);
            }
        }
        if (!isset($processed_headers['Content-Type']) && in_array($http_method, ['POST', 'PUT'])) {
             $processed_headers['Content-Type'] = 'application/json; charset=utf-8';
        }

        $request_body = null;
        if (in_array($http_method, ['POST', 'PUT']) && !empty($body_template)) {
            $request_body_string = call_user_func($process_placeholders, $body_template, $context_data);
            if (isset($processed_headers['Content-Type']) && stripos($processed_headers['Content-Type'], 'application/json') !== false) {
                json_decode($request_body_string); 
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return new WP_Error('invalid_webhook_body_json', __('Webhook body template is not valid JSON after processing placeholders.', 'gpt3-ai-content-generator'));
                }
            }
            $request_body = $request_body_string;
        }

        $args = [
            'method'    => $http_method,
            'headers'   => $processed_headers,
            'timeout'   => $timeout_seconds,
            'sslverify' => apply_filters('https_local_ssl_verify', true),
        ];
        if ($request_body !== null) {
            $args['body'] = $request_body;
        }

        $response = wp_remote_request($endpoint_url, $args);

        if (is_wp_error($response)) {
            return new WP_Error('webhook_request_failed', __('Webhook request failed: ', 'gpt3-ai-content-generator') . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body_content = wp_remote_retrieve_body($response);

        if ($response_code >= 400) {
            return [
                'type' => 'webhook_sent',
                'status' => 'failed',
                'http_code' => $response_code,
                /* translators: %d is the HTTP status code */
                'message' => sprintf(__('Webhook call failed with HTTP status %d.', 'gpt3-ai-content-generator'), $response_code),
                'response_body_snippet' => substr($response_body_content, 0, 200)
            ];
        }

        return [
            'type' => 'webhook_sent',
            'status' => 'success',
            'http_code' => $response_code,
            'response_body_snippet' => substr($response_body_content, 0, 200)
        ];
    }
}
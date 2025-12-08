<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/_request.php
// Status: MODIFIED

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the _request method of AIPKit_Vector_OpenAI_Strategy.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $method HTTP method (GET, POST, DELETE).
 * @param string $url Full request URL.
 * @param array $body Request body for POST requests or multipart data for file uploads.
 * @param bool $is_file_upload True if this is a multipart/form-data file upload.
 * @return array|WP_Error Decoded JSON response or WP_Error.
 */
function _request_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $method, string $url, array $body = [], bool $is_file_upload = false): array|WP_Error
{
    $api_key = $strategyInstance->get_api_key();
    if (empty($api_key)) {
        return new WP_Error('openai_vector_missing_key', __('OpenAI API Key is not configured.', 'gpt3-ai-content-generator'));
    }

    $headers = [
        'Authorization' => 'Bearer ' . $api_key,
        'OpenAI-Beta'   => 'assistants=v2',
    ];
    if (!$is_file_upload) {
        $headers['Content-Type'] = 'application/json';
    }

    $request_args = [
        'method'  => strtoupper($method),
        'headers' => $headers,
        'timeout' => 120,
    ];

    if (!$is_file_upload && !empty($body) && ($method === 'POST' || $method === 'PUT' || $method === 'PATCH')) {
        $request_args['body'] = wp_json_encode($body);
    } elseif ($is_file_upload && !empty($body)) {
        $request_args['body'] = $body;
    }

    $response_body = null;
    $status_code = null;

    if ($is_file_upload) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init -- Reason: Using cURL for streaming.
        $ch = curl_init();
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_URL, $url);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $curl_headers = [];
        foreach ($headers as $key => $value) {
            $curl_headers[] = "{$key}: {$value}";
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Reason: Using cURL for streaming.
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec -- Reason: Using cURL for streaming.
        $response_body = curl_exec($ch);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo -- Reason: Using cURL for streaming.
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error -- Reason: Using cURL for streaming.
        $curl_error = curl_error($ch);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close -- Reason: Using cURL for streaming.
        curl_close($ch);
        if ($curl_error) {
            return new WP_Error('openai_vector_curl_error', 'cURL error during request: ' . $curl_error);
        }
    } else {
        $response = wp_remote_request($url, $request_args);
        if (is_wp_error($response)) {
            return $response;
        }
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
    }

    // Call public methods on the strategy instance
    $decoded_response = $strategyInstance->decode_json($response_body, 'OpenAI Vector Store');


    if ($status_code >= 400) {
        $error_msg = $strategyInstance->parse_error_response($decoded_response ?: $response_body, $status_code, 'OpenAI Vector Store');
        /* translators: %1$d: HTTP status code, %2$s: API error message. */
        return new WP_Error('openai_vector_api_error', sprintf(__('OpenAI Vector API Error (%1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, esc_html($error_msg)));
    }

    if (is_wp_error($decoded_response)) {
        return $decoded_response;
    }
    return $decoded_response;
}

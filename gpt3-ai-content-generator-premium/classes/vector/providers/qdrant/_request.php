<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/_request.php

namespace WPAICG\Vector\Providers\Qdrant\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Qdrant_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the _request method of AIPKit_Vector_Qdrant_Strategy.
 *
 * @param AIPKit_Vector_Qdrant_Strategy $strategyInstance The instance of the strategy class.
 * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH).
 * @param string $path API path (e.g., '/collections').
 * @param array $body Request body for POST/PUT/PATCH requests.
 * @param array $query_params Query parameters for the request.
 * @return array|WP_Error Decoded JSON response or WP_Error.
 */
function _request_logic(AIPKit_Vector_Qdrant_Strategy $strategyInstance, string $method, string $path, array $body = [], array $query_params = []): array|WP_Error {
    if (!$strategyInstance->get_is_connected_status() && $path !== '/collections') {
        return new WP_Error('not_connected_qdrant', __('Not connected to Qdrant. Call connect() first.', 'gpt3-ai-content-generator'));
    }

    $qdrant_url = $strategyInstance->get_qdrant_url();
    $api_key = $strategyInstance->get_api_key();
    if (empty($qdrant_url)) { // Qdrant URL is essential
        return new WP_Error('qdrant_url_not_set', __('Qdrant URL not configured in strategy.', 'gpt3-ai-content-generator'));
    }

    $url = $qdrant_url . $path;
    if (!empty($query_params)) {
        $url = add_query_arg($query_params, $url);
    }

    $headers = ['Content-Type' => 'application/json'];
    if (!empty($api_key)) {
        $headers['api-key'] = $api_key;
    }

    $request_args = [
        'method'  => strtoupper($method),
        'headers' => $headers,
        'timeout' => 60,
    ];

    if (!empty($body) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
        $request_args['body'] = wp_json_encode($body);
    }

    $response = wp_remote_request($url, $request_args);

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $response_body_raw = wp_remote_retrieve_body($response);

    $decoded_response = $strategyInstance->decode_json_public_wrapper($response_body_raw, 'Qdrant Vector Store');

    if ($status_code >= 400) {
        $error_msg = $strategyInstance->parse_error_response_public_wrapper($decoded_response ?: $response_body_raw, $status_code, 'Qdrant Vector Store');
        /* translators: %1$d: HTTP status code, %2$s: Error message from the API. */
        return new WP_Error('qdrant_api_error', sprintf(__('Qdrant API Error (%1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, $error_msg), ['status' => $status_code]);
    }

    if (is_wp_error($decoded_response)) {
        return $decoded_response;
    }

    if (isset($decoded_response['result'])) {
        return is_array($decoded_response['result']) ? $decoded_response['result'] : ['result_data' => $decoded_response['result'], 'status' => $decoded_response['status'] ?? 'ok'];
    } elseif (is_array($decoded_response) && !empty($decoded_response)) {
        return $decoded_response;
    }
    return ['status' => 'ok', 'message' => 'Operation successful with no specific content returned.'];
}
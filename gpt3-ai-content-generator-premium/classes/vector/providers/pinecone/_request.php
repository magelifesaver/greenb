<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/_request.php
// Status: MODIFIED

namespace WPAICG\Vector\Providers\Pinecone\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Pinecone_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the _request method of AIPKit_Vector_Pinecone_Strategy.
 *
 * @param AIPKit_Vector_Pinecone_Strategy $strategyInstance The instance of the strategy class.
 * @param string $method HTTP method (GET, POST, DELETE, PATCH).
 * @param string $path API path (e.g., '/indexes').
 * @param array $body Request body for POST/PATCH requests.
 * @param string|null $index_host_url Optional. If provided, this URL is used as the base instead of controller API.
 * @return array|WP_Error Decoded JSON response or WP_Error.
 */
function _request_logic(AIPKit_Vector_Pinecone_Strategy $strategyInstance, string $method, string $path, array $body = [], string $index_host_url = null): array|WP_Error {
    if (!$strategyInstance->get_is_connected_status() && empty($index_host_url) && $path !== '/indexes?limit=1' /* Allow initial connection test */) {
        return new WP_Error('not_connected_pinecone', __('Not connected to Pinecone. Call connect() first or provide index host URL.', 'gpt3-ai-content-generator'));
    }

    $url = ($index_host_url ? rtrim($index_host_url, '/') : $strategyInstance->get_base_api_url()) . $path;
    $api_key = $strategyInstance->get_api_key();

    $headers = [
        'Api-Key'       => $api_key,
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
    ];

    $request_args = [
        'method'  => strtoupper($method),
        'headers' => $headers,
        'timeout' => 60, // Standard timeout
    ];

    if (!empty($body) && in_array(strtoupper($method), ['POST', 'PATCH'], true)) {
        $request_args['body'] = wp_json_encode($body);
    }

    $response = wp_remote_request($url, $request_args);

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    // For DELETE, Pinecone returns 202 (Accepted) or 204 (No Content) on success.
    if (strtoupper($method) === 'DELETE' && in_array($status_code, [200, 202, 204], true)) {
        return ['deleted' => true];
    }
    if (strtoupper($method) === 'PATCH' && $status_code === 202) { 
        return ['status' => 'accepted']; 
    }

    $decoded_response = $strategyInstance->decode_json($response_body, 'Pinecone Vector Store'); // MODIFIED: Call public method

    if ($status_code >= 400) {
        $error_msg = $strategyInstance->parse_error_response($decoded_response ?: $response_body, $status_code, 'Pinecone Vector Store'); // MODIFIED: Call public method
        /* translators: %1$d: HTTP status code, %2$s: Error message from the API. */
        return new WP_Error('pinecone_api_error', sprintf(__('Pinecone API Error (%1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, $error_msg));
    }

    if (is_wp_error($decoded_response)) {
        return $decoded_response;
    }
    return $decoded_response;
}
<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/describe-index.php

namespace WPAICG\Vector\Providers\Qdrant\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Qdrant_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the describe_index method of AIPKit_Vector_Qdrant_Strategy.
 * Describes a Qdrant collection.
 *
 * @param AIPKit_Vector_Qdrant_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index (collection).
 * @return array|WP_Error Collection details or WP_Error.
 */
function describe_index_logic(AIPKit_Vector_Qdrant_Strategy $strategyInstance, string $index_name): array|WP_Error {
    // First, get the main collection info like config
    $path = '/collections/' . urlencode($index_name);
    $description_response = _request_logic($strategyInstance, 'GET', $path);

    if (is_wp_error($description_response)) {
        return $description_response;
    }

    $result = $description_response['result'] ?? $description_response;
    
    if (!is_array($result)) {
        return new WP_Error('qdrant_describe_malformed', __('Malformed response when describing Qdrant collection.', 'gpt3-ai-content-generator'));
    }

    // Now, get the *exact* vector count using the dedicated count endpoint
    $count_path = '/collections/' . urlencode($index_name) . '/points/count';
    // Using an empty body is fine for a basic exact count
    $count_body = ['exact' => true];
    $count_response = _request_logic($strategyInstance, 'POST', $count_path, $count_body);

    if (!is_wp_error($count_response) && isset($count_response['count'])) {
        // The count response is like: {"result": {"count": 123}, "status": "ok"}
        // _request_logic returns the "result" part.
        $result['vectors_count'] = $count_response['count'];
    } else {
        // If count fails, we can either return the possibly stale count from the describe call, or mark it as an error.
        // Let's keep the possibly stale count but log the error if WP_DEBUG is on.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $error_message = is_wp_error($count_response) ? $count_response->get_error_message() : 'Count response malformed.';
        }
    }

    // Add name and ID for consistency with other providers and registry format
    $result['name'] = $index_name;
    $result['id'] = $index_name;

    return $result;
}
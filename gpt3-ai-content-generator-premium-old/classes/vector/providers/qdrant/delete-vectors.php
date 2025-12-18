<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/delete-vectors.php

namespace WPAICG\Vector\Providers\Qdrant\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Qdrant_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the delete_vectors method of AIPKit_Vector_Qdrant_Strategy.
 *
 * @param AIPKit_Vector_Qdrant_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index (collection).
 * @param array $vector_ids_or_filter Array of vector IDs or a filter object.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function delete_vectors_logic(AIPKit_Vector_Qdrant_Strategy $strategyInstance, string $index_name, array $vector_ids_or_filter): bool|WP_Error {
    $path = '/collections/' . urlencode($index_name) . '/points/delete';
    $body = [];

    if (isset($vector_ids_or_filter['points']) && is_array($vector_ids_or_filter['points'])) {
        $body['points'] = $vector_ids_or_filter['points'];
    } elseif (isset($vector_ids_or_filter['filter']) && is_array($vector_ids_or_filter['filter'])) {
        $body['filter'] = $vector_ids_or_filter['filter'];
    } else {
        $body['points'] = $vector_ids_or_filter;
    }

    $response = _request_logic($strategyInstance, 'POST', $path, $body);
    if (is_wp_error($response)) return $response;
    return isset($response['status']) && ($response['status'] === 'acknowledged' || $response['status'] === 'completed');
}
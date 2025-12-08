<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/query-vectors.php

namespace WPAICG\Vector\Providers\Qdrant\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Qdrant_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the query_vectors method of AIPKit_Vector_Qdrant_Strategy.
 *
 * @param AIPKit_Vector_Qdrant_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index (collection).
 * @param array $query_vector_param The query vector parameters.
 * @param int $top_k Number of nearest neighbors to return.
 * @param array $filter Optional metadata filter.
 * @return array|WP_Error Array of matching vectors or WP_Error.
 */
function query_vectors_logic(AIPKit_Vector_Qdrant_Strategy $strategyInstance, string $index_name, array $query_vector_param, int $top_k, array $filter = []): array|WP_Error {
    $path = '/collections/' . urlencode($index_name) . '/points/search';
    $vector_values = $query_vector_param['vector'] ?? ($query_vector_param['query'] ?? ($query_vector_param ?: null));

    if (!is_array($vector_values) || empty($vector_values)) {
        return new WP_Error('invalid_qdrant_query_vector', __('Invalid query vector provided for Qdrant search.', 'gpt3-ai-content-generator'));
    }

    $body = [
        'vector' => $vector_values,
        'limit' => $top_k,
        'with_payload' => true,
        'with_vector' => $query_vector_param['with_vector'] ?? false,
    ];
    if (isset($query_vector_param['using']) && is_string($query_vector_param['using'])) {
        $body['using'] = $query_vector_param['using'];
    }
    if (!empty($filter)) $body['filter'] = $filter;
    if (isset($query_vector_param['score_threshold'])) $body['score_threshold'] = floatval($query_vector_param['score_threshold']);
    if (isset($query_vector_param['offset'])) $body['offset'] = absint($query_vector_param['offset']);
    if (isset($query_vector_param['prefetch']) && is_array($query_vector_param['prefetch'])) {
        $body['prefetch'] = $query_vector_param['prefetch'];
    }

    $response = _request_logic($strategyInstance, 'POST', $path, $body);
    if (is_wp_error($response)) return $response;

    $points = (is_array($response) && isset($response['points']) && is_array($response['points'])) ? $response['points'] : ((is_array($response) && !isset($response['status'])) ? $response : []);
    $results = [];
    if (is_array($points)) {
        foreach($points as $point) {
            $results[] = [
                'id' => $point['id'] ?? null,
                'score' => $point['score'] ?? null,
                'metadata' => $point['payload'] ?? [],
                'vector' => $point['vector'] ?? null,
                // Annotate collection for downstream aggregation/UX
                'collection' => $index_name,
            ];
        }
    }
    return $results;
}

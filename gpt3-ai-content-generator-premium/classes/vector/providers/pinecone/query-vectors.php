<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/query-vectors.php
// Status: NEW

namespace WPAICG\Vector\Providers\Pinecone\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Pinecone_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the query_vectors method of AIPKit_Vector_Pinecone_Strategy.
 *
 * @param AIPKit_Vector_Pinecone_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index.
 * @param array $query_vector_param The query vector data.
 * @param int $top_k Number of nearest neighbors to return.
 * @param array $filter Optional metadata filter.
 * @return array|WP_Error Array of matching vectors or WP_Error.
 */
function query_vectors_logic(AIPKit_Vector_Pinecone_Strategy $strategyInstance, string $index_name, array $query_vector_param, int $top_k, array $filter = []): array|WP_Error {
    $index_description = describe_index_logic($strategyInstance, $index_name); // Use externalized describe_index_logic
    if (is_wp_error($index_description)) return $index_description;
    $host = $index_description['host'] ?? null;
    if (empty($host)) return new WP_Error('missing_host_pinecone_query', __('Index host not found for query operation.', 'gpt3-ai-content-generator'));

    if (!isset($query_vector_param['vector']) || !is_array($query_vector_param['vector'])) {
        return new WP_Error('invalid_query_vector_structure', __('Query vector must contain a "vector" key with an array of embeddings.', 'gpt3-ai-content-generator'));
    }

    $body = [
        'vector' => $query_vector_param['vector'],
        'topK' => $top_k,
        'includeMetadata' => true,
        'includeValues' => false
    ];
    if (!empty($filter)) {
        $body['filter'] = $filter;
    }
    if (isset($query_vector_param['namespace']) && !empty($query_vector_param['namespace'])) {
        $body['namespace'] = $query_vector_param['namespace'];
    }

    $response = _request_logic($strategyInstance, 'POST', '/query', $body, 'https://' . $host);
    if (is_wp_error($response)) return $response;

    $matches = $response['matches'] ?? [];
    $results = [];
    foreach($matches as $match) {
        $results[] = [
            'id' => $match['id'] ?? null,
            'score' => $match['score'] ?? null,
            'metadata' => $match['metadata'] ?? [],
        ];
    }
    return $results;
}
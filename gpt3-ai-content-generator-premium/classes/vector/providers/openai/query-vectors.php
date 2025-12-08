<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/query-vectors.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the query_vectors method of AIPKit_Vector_OpenAI_Strategy.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The ID of the Vector Store.
 * @param array $query_vector Array containing 'query_text'.
 * @param int $top_k Number of results.
 * @param array $filter Optional filters.
 * @return array|WP_Error Search results or WP_Error.
 */
function query_vectors_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $index_name, array $query_vector, int $top_k, array $filter = []): array|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }

    $vector_store_id = $index_name;
    if (!isset($query_vector['query_text']) || !is_string($query_vector['query_text'])) {
        return new WP_Error('invalid_query_type', __('For OpenAI vector store search, query_vector must be an array containing a "query_text" string.', 'gpt3-ai-content-generator'));
    }

    $url_params = [
        'base_url' => $strategyInstance->get_base_url(),
        'api_version' => $strategyInstance->get_api_version(),
        'vector_store_id' => $vector_store_id
    ];
    $url = OpenAIUrlBuilder::build('vector_stores_id_search', $url_params);
    if (is_wp_error($url)) return $url;

    $body = [
        'query' => $query_vector['query_text'],
        'max_num_results' => max(1, min($top_k, 50)), // OpenAI API limit for max_num_results
    ];
    if (!empty($filter)) {
        $body['filters'] = $filter;
    }
    if (isset($query_vector['ranking_options'])) {
        $body['ranking_options'] = $query_vector['ranking_options'];
    }

    $response = _request_logic($strategyInstance, 'POST', $url, $body);
    if (is_wp_error($response)) return $response;

    $results = [];
    if (isset($response['data']) && is_array($response['data'])) {
        foreach ($response['data'] as $item) {
            $results[] = [
                'id' => $item['file_id'] ?? null,
                'score' => $item['score'] ?? null,
                'metadata' => $item['attributes'] ?? [],
                'content' => $item['content'] ?? null,
                'raw_item' => $item // Include raw for potential future use
            ];
        }
    }
    return $results;
}
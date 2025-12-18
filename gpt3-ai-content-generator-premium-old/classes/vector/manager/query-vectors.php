<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/manager/query-vectors.php
// Status: NEW FILE

namespace WPAICG\Vector\ManagerMethods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for querying vectors from the specified index.
 * This was previously a method in AIPKit_Vector_Store_Manager.
 *
 * @param string $provider The vector store provider.
 * @param string $index_name The name of the index.
 * @param array $query_vector The vector to query against.
 * @param int $top_k The number of nearest neighbors to return.
 * @param array $filter Optional metadata filter.
 * @param array $provider_config Provider-specific connection/API configuration.
 * @return array|WP_Error Array of matching vectors or WP_Error.
 */
function query_vectors_logic(string $provider, string $index_name, array $query_vector, int $top_k, array $filter = [], array $provider_config = []): array|WP_Error {
    $strategy = get_connected_strategy_logic($provider, $provider_config);
    if (is_wp_error($strategy)) {
        return $strategy;
    }
    return $strategy->query_vectors($index_name, $query_vector, $top_k, $filter);
}
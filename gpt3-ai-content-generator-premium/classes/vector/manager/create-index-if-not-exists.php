<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/manager/create-index-if-not-exists.php
// Status: NEW FILE

namespace WPAICG\Vector\ManagerMethods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for creating an index in the specified vector store if it doesn't already exist.
 * This was previously a method in AIPKit_Vector_Store_Manager.
 *
 * @param string $provider The vector store provider (e.g., 'Pinecone', 'Qdrant', 'OpenAI').
 * @param string $index_name The name of the index to create.
 * @param array $index_config Provider-specific configuration for the index.
 * @param array $provider_config Provider-specific connection/API configuration.
 * @return array|WP_Error The store object (array) on success, WP_Error on failure.
 */
function create_index_if_not_exists_logic(string $provider, string $index_name, array $index_config, array $provider_config): array|WP_Error {
    $strategy = get_connected_strategy_logic($provider, $provider_config);
    if (is_wp_error($strategy)) {
        return $strategy;
    }
    return $strategy->create_index_if_not_exists($index_name, $index_config);
}
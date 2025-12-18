<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/manager/list-files-in-store.php
// Status: NEW FILE

namespace WPAICG\Vector\ManagerMethods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for listing files in a specific vector store (primarily for OpenAI).
 * This was previously a method in AIPKit_Vector_Store_Manager.
 *
 * @param string $provider The vector store provider (should be 'OpenAI').
 * @param string $vector_store_id The ID of the vector store.
 * @param array $provider_config Provider-specific connection/API configuration.
 * @param array $query_params Optional query parameters for listing.
 * @return array|WP_Error List of file objects or WP_Error.
 */
function list_files_in_store_logic(string $provider, string $vector_store_id, array $provider_config, array $query_params = []): array|WP_Error {
    $strategy = get_connected_strategy_logic($provider, $provider_config);
    if (is_wp_error($strategy)) {
        return $strategy;
    }
    if (method_exists($strategy, 'list_vector_store_files')) {
        return $strategy->list_vector_store_files($vector_store_id, $query_params);
    }
    return new WP_Error('method_not_supported', __('Listing files is not supported by this provider strategy.', 'gpt3-ai-content-generator'));
}
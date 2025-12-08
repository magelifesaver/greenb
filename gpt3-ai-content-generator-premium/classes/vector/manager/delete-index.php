<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/manager/delete-index.php
// Status: NEW FILE

namespace WPAICG\Vector\ManagerMethods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for deleting an entire index.
 * This was previously a method in AIPKit_Vector_Store_Manager.
 *
 * @param string $provider The vector store provider.
 * @param string $index_name The name of the index to delete.
 * @param array $provider_config Provider-specific connection/API configuration.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function delete_index_logic(string $provider, string $index_name, array $provider_config): bool|WP_Error {
    $strategy = get_connected_strategy_logic($provider, $provider_config);
    if (is_wp_error($strategy)) {
        return $strategy;
    }
    return $strategy->delete_index($index_name);
}
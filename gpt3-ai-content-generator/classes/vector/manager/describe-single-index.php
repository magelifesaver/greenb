<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/manager/describe-single-index.php
// Status: NEW FILE

namespace WPAICG\Vector\ManagerMethods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for describing an index (or collection), returning its configuration and status.
 * This was previously a method in AIPKit_Vector_Store_Manager.
 *
 * @param string $provider The vector store provider.
 * @param string $index_name The name of the index/collection.
 * @param array $provider_config Provider-specific connection/API configuration.
 * @return array|WP_Error An array containing index details, or WP_Error if not found or on failure.
 */
function describe_single_index_logic(string $provider, string $index_name, array $provider_config): array|WP_Error {
    $strategy = get_connected_strategy_logic($provider, $provider_config);
    if (is_wp_error($strategy)) {
        return $strategy;
    }
    return $strategy->describe_index($index_name);
}
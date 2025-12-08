<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/manager/list-all-indexes.php
// Status: NEW FILE

namespace WPAICG\Vector\ManagerMethods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for listing available indexes (or collections) for a given provider.
 * This was previously a method in AIPKit_Vector_Store_Manager.
 *
 * @param string $provider The vector store provider.
 * @param array $provider_config Provider-specific connection/API configuration.
 * @param int|null $limit The maximum number of items to return.
 * @param string|null $order The order of items ('asc' or 'desc').
 * @param string|null $after A cursor for use in pagination.
 * @param string|null $before A cursor for use in pagination.
 * @return array|WP_Error An array of index names or index detail objects, or WP_Error on failure.
 */
function list_all_indexes_logic(string $provider, array $provider_config, ?int $limit = 20, ?string $order = 'desc', ?string $after = null, ?string $before = null): array|WP_Error {
    $strategy = get_connected_strategy_logic($provider, $provider_config);
    if (is_wp_error($strategy)) {
        return $strategy;
    }
    return $strategy->list_indexes($limit, $order, $after, $before);
}
<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/manager/upsert-vectors.php
// Status: NEW FILE

namespace WPAICG\Vector\ManagerMethods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for adding or updating vectors in the specified index.
 * This was previously a method in AIPKit_Vector_Store_Manager.
 *
 * @param string $provider The vector store provider.
 * @param string $index_name The name of the index.
 * @param array $vectors An array of vector objects/data to upsert.
 * @param array $provider_config Provider-specific connection/API configuration.
 * @return array|WP_Error Result of the upsert operation or WP_Error.
 */
function upsert_vectors_logic(string $provider, string $index_name, array $vectors, array $provider_config): array|WP_Error {
    $strategy = get_connected_strategy_logic($provider, $provider_config);
    if (is_wp_error($strategy)) {
        return $strategy;
    }
    return $strategy->upsert_vectors($index_name, $vectors);
}
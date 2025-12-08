<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/list-indexes.php

namespace WPAICG\Vector\Providers\Qdrant\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Qdrant_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the list_indexes method of AIPKit_Vector_Qdrant_Strategy.
 * Lists Qdrant collections.
 *
 * @param AIPKit_Vector_Qdrant_Strategy $strategyInstance The instance of the strategy class.
 * @param int|null $limit Max items (unused by Qdrant list collections).
 * @param string|null $order Sort order (unused by Qdrant list collections).
 * @param string|null $after Cursor for next page (unused).
 * @param string|null $before Cursor for previous page (unused).
 * @return array|WP_Error List of collections or WP_Error.
 */
function list_indexes_logic(AIPKit_Vector_Qdrant_Strategy $strategyInstance, ?int $limit = null, ?string $order = null, ?string $after = null, ?string $before = null): array|WP_Error {
    $response = _request_logic($strategyInstance, 'GET', '/collections');
    if (is_wp_error($response)) return $response;

    $collections_data = $response['collections'] ?? [];
    $formatted_collections = [];
    if (is_array($collections_data)) {
        foreach ($collections_data as $collection) {
            if (isset($collection['name'])) {
                $formatted_collections[] = [
                    'id' => $collection['name'],
                    'name' => $collection['name'],
                ];
            }
        }
    }
    return $formatted_collections;
}
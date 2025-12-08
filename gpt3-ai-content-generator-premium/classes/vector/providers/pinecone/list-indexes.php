<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/list-indexes.php
// Status: NEW

namespace WPAICG\Vector\Providers\Pinecone\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Pinecone_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the list_indexes method of AIPKit_Vector_Pinecone_Strategy.
 * REVISED: This now only fetches the list of index names for performance.
 * Detailed stats are fetched on-demand when a user selects an index in the UI.
 *
 * @param AIPKit_Vector_Pinecone_Strategy $strategyInstance The instance of the strategy class.
 * @param int|null $limit Max items.
 * @param string|null $order Sort order.
 * @param string|null $after Cursor for next page.
 * @param string|null $before Cursor for previous page.
 * @return array|WP_Error List of indexes or WP_Error.
 */
function list_indexes_logic(AIPKit_Vector_Pinecone_Strategy $strategyInstance, ?int $limit = null, ?string $order = null, ?string $after = null, ?string $before = null): array|WP_Error {
    $path = '/indexes';
    $list_response = _request_logic($strategyInstance, 'GET', $path);

    if (is_wp_error($list_response)) {
        return $list_response;
    }

    $indexes_data = $list_response['indexes'] ?? [];
    if (empty($indexes_data) && is_array($list_response)) { // Fallback for older API format
        if (isset($list_response['collections'])) {
            $indexes_data = $list_response['collections'];
        } else {
            $indexes_data = $list_response;
        }
    }

    $formatted_indexes = [];
    if (is_array($indexes_data)) {
        foreach ($indexes_data as $index_obj_from_list) {
            $index_name = is_string($index_obj_from_list) ? $index_obj_from_list : ($index_obj_from_list['name'] ?? null);
            if (!$index_name) {
                continue;
            }

            // Only return basic info available from the list call.
            // Details like stats will be fetched on demand.
            $formatted_indexes[] = [
                'id'   => $index_name,
                'name' => $index_name,
                'dimension' => $index_obj_from_list['dimension'] ?? null,
                'metric'    => $index_obj_from_list['metric'] ?? null,
                'host'      => $index_obj_from_list['host'] ?? null,
                'status'    => $index_obj_from_list['status'] ?? null,
                'spec'      => $index_obj_from_list['spec'] ?? null,
            ];
        }
    }
    return $formatted_indexes;
}
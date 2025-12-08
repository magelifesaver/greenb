<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/qdrant/delete-index.php

namespace WPAICG\Vector\Providers\Qdrant\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Qdrant_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the delete_index method of AIPKit_Vector_Qdrant_Strategy.
 *
 * @param AIPKit_Vector_Qdrant_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index (collection) to delete.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function delete_index_logic(AIPKit_Vector_Qdrant_Strategy $strategyInstance, string $index_name): bool|WP_Error {
    $path = '/collections/' . urlencode($index_name);
    $response = _request_logic($strategyInstance, 'DELETE', $path);

    if (is_wp_error($response)) {
        return $response;
    }
    if (isset($response['status']) && $response['status'] === 'ok') {
        return true;
    }
    if (isset($response['result_data']) && $response['result_data'] === true) {
        return true;
    }
    if (isset($response['deleted']) && $response['deleted'] === true) { // Compatibility with Pinecone response if _request logic normalizes
        return true;
    }
    return false;
}
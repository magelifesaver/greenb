<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/delete-index.php
// Status: NEW

namespace WPAICG\Vector\Providers\Pinecone\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Pinecone_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the delete_index method of AIPKit_Vector_Pinecone_Strategy.
 *
 * @param AIPKit_Vector_Pinecone_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index to delete.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function delete_index_logic(AIPKit_Vector_Pinecone_Strategy $strategyInstance, string $index_name): bool|WP_Error {
    $path = '/indexes/' . urlencode($index_name);
    $response = _request_logic($strategyInstance, 'DELETE', $path);

    if (is_wp_error($response)) {
        return $response;
    }
    return isset($response['deleted']) && $response['deleted'] === true;
}
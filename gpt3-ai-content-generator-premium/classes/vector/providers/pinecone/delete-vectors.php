<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/pinecone/delete-vectors.php
// Status: NEW

namespace WPAICG\Vector\Providers\Pinecone\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_Pinecone_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the delete_vectors method of AIPKit_Vector_Pinecone_Strategy.
 *
 * @param AIPKit_Vector_Pinecone_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the index.
 * @param array $vector_ids An array of vector IDs to delete.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function delete_vectors_logic(AIPKit_Vector_Pinecone_Strategy $strategyInstance, string $index_name, array $vector_ids): bool|WP_Error {
    $index_description = describe_index_logic($strategyInstance, $index_name); // Use externalized describe_index_logic
    if (is_wp_error($index_description)) return $index_description;
    $host = $index_description['host'] ?? null;
    if (empty($host)) return new WP_Error('missing_host_pinecone_delete_vectors', __('Index host not found for delete vectors.', 'gpt3-ai-content-generator'));
    $path = '/vectors/delete';
    $body = ['ids' => $vector_ids];

    $response = _request_logic($strategyInstance, 'POST', $path, $body, 'https://' . $host);
    if (is_wp_error($response)) return $response;
    return empty($response); // Successful delete returns empty JSON object {}
}
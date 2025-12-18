<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/delete-index.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the delete_index method of AIPKit_Vector_OpenAI_Strategy.
 * Deletes an entire OpenAI Vector Store.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The ID of the Vector Store to delete.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function delete_index_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $index_name): bool|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }
    $vector_store_id = $index_name;

    $url_params = [
        'base_url' => $strategyInstance->get_base_url(),
        'api_version' => $strategyInstance->get_api_version(),
        'vector_store_id' => $vector_store_id
    ];
    $url = OpenAIUrlBuilder::build('vector_stores_id', $url_params);
    if (is_wp_error($url)) return $url;

    $response = _request_logic($strategyInstance, 'DELETE', $url);
    if (is_wp_error($response)) return $response;

    // OpenAI delete vector store returns {"id": "vs_...", "object": "vector_store.deleted", "deleted": true}
    return isset($response['deleted']) && $response['deleted'] === true;
}
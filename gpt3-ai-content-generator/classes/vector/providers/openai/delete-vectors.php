<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/delete-vectors.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the delete_vectors method of AIPKit_Vector_OpenAI_Strategy.
 * For OpenAI, this means detaching a file from a vector store.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The ID of the Vector Store.
 * @param array $vector_ids Array of file_ids to detach.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function delete_vectors_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $index_name, array $vector_ids): bool|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }
    if (empty($vector_ids)) return true; // No IDs to delete

    $vector_store_id = $index_name;
    $all_successful = true;

    foreach ($vector_ids as $file_id) {
        $url_params = [
            'base_url' => $strategyInstance->get_base_url(),
            'api_version' => $strategyInstance->get_api_version(),
            'vector_store_id' => $vector_store_id,
            'file_id' => $file_id
        ];
        $url = OpenAIUrlBuilder::build('vector_stores_id_files_id', $url_params);
        if (is_wp_error($url)) {
            $all_successful = false;
            continue;
        }

        $response = _request_logic($strategyInstance, 'DELETE', $url);
        if (is_wp_error($response)) {
            $all_successful = false;
        } elseif (!isset($response['deleted']) || $response['deleted'] !== true) {
            $all_successful = false;
        }
    }
    return $all_successful ? true : new WP_Error('partial_delete_failure', __('Some files could not be deleted from the vector store.', 'gpt3-ai-content-generator'));
}
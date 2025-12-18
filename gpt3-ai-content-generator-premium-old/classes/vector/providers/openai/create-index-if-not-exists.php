<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/create-index-if-not-exists.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the create_index_if_not_exists method of AIPKit_Vector_OpenAI_Strategy.
 * For OpenAI, this means creating a "Vector Store".
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The name of the Vector Store to create.
 * @param array  $index_config Configuration for the Vector Store.
 * @return array|WP_Error The store object (array) on success, WP_Error on failure.
 */
function create_index_if_not_exists_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $index_name, array $index_config): array|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }

    // Check if it exists first
    $list_response = list_indexes_logic($strategyInstance, 100); // List up to 100
    if (!is_wp_error($list_response) && isset($list_response['data'])) {
        foreach ($list_response['data'] as $store) {
            if (isset($store['name']) && $store['name'] === $index_name) {
                // If found by name, return the existing store object
                return $store;
            }
        }
    }

    $url_params = [
        'base_url' => $strategyInstance->get_base_url(),
        'api_version' => $strategyInstance->get_api_version()
    ];
    $url = OpenAIUrlBuilder::build('vector_stores', $url_params);
    if (is_wp_error($url)) return $url;

    $body = ['name' => $index_name];
    if (isset($index_config['file_ids'])) $body['file_ids'] = $index_config['file_ids'];
    if (isset($index_config['metadata'])) $body['metadata'] = $index_config['metadata'];
    if (isset($index_config['expires_after']) && is_array($index_config['expires_after'])) {
        $body['expires_after'] = $index_config['expires_after'];
    }
    if (isset($index_config['chunking_strategy'])) {
        $body['chunking_strategy'] = $index_config['chunking_strategy'];
    }


    $response = _request_logic($strategyInstance, 'POST', $url, $body);
    if (is_wp_error($response)) return $response;

    return isset($response['id']) ? $response : new WP_Error('store_creation_malformed_response', __('Malformed response after store creation.', 'gpt3-ai-content-generator'));
}
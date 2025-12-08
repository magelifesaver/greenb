<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/list-vector-store-files.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the list_vector_store_files method of AIPKit_Vector_OpenAI_Strategy.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $vector_store_id The ID of the Vector Store.
 * @param array $query_params Optional query parameters.
 * @return array|WP_Error List of file objects or WP_Error.
 */
function list_vector_store_files_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $vector_store_id, array $query_params = []): array|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }
    $url_params = [
        'base_url' => $strategyInstance->get_base_url(),
        'api_version' => $strategyInstance->get_api_version(),
        'vector_store_id' => $vector_store_id
    ];
    $url = OpenAIUrlBuilder::build('vector_stores_id_files', $url_params);
    if (is_wp_error($url)) return $url;

    if (!empty($query_params)) {
        $url = add_query_arg(array_map('sanitize_text_field', $query_params), $url);
    }

    $response = _request_logic($strategyInstance, 'GET', $url);
    if (is_wp_error($response)) return $response;

    return $response['data'] ?? [];
}
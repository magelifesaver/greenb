<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/describe-index.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the describe_index method of AIPKit_Vector_OpenAI_Strategy.
 * Describes an OpenAI Vector Store.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The ID of the Vector Store.
 * @return array|WP_Error Store details or WP_Error.
 */
function describe_index_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $index_name): array|WP_Error {
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

    return _request_logic($strategyInstance, 'GET', $url);
}
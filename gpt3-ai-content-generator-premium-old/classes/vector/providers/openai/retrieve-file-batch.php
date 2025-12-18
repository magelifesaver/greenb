<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/retrieve-file-batch.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the retrieve_file_batch method of AIPKit_Vector_OpenAI_Strategy.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $vector_store_id The ID of the Vector Store.
 * @param string $batch_id The ID of the file batch.
 * @return array|WP_Error Batch details or WP_Error.
 */
function retrieve_file_batch_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $vector_store_id, string $batch_id): array|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }
    $url_params = [
        'base_url' => $strategyInstance->get_base_url(),
        'api_version' => $strategyInstance->get_api_version(),
        'vector_store_id' => $vector_store_id,
        'batch_id' => $batch_id
    ];
    $url = OpenAIUrlBuilder::build('vector_stores_id_file_batches_id', $url_params);
    if (is_wp_error($url)) return $url;

    return _request_logic($strategyInstance, 'GET', $url);
}
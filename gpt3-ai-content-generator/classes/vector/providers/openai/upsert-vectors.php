<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/upsert-vectors.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the upsert_vectors method of AIPKit_Vector_OpenAI_Strategy.
 * For OpenAI, this means adding files to a vector store batch.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $index_name The ID of the Vector Store.
 * @param array $vectors Data containing 'file_ids' and optional 'chunking_strategy'.
 * @return array|WP_Error The batch object or WP_Error on failure.
 */
function upsert_vectors_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $index_name, array $vectors): array|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }

    $vector_store_id = $index_name;
    $file_ids = $vectors['file_ids'] ?? null;

    if (empty($file_ids) || !is_array($file_ids)) {
        return new WP_Error('missing_file_ids', __('File IDs are required for upserting to OpenAI Vector Store.', 'gpt3-ai-content-generator'));
    }

    $url_params = [
        'base_url' => $strategyInstance->get_base_url(),
        'api_version' => $strategyInstance->get_api_version(),
        'vector_store_id' => $vector_store_id
    ];
    $url = OpenAIUrlBuilder::build('vector_stores_id_file_batches', $url_params);
    if (is_wp_error($url)) return $url;

    $body = ['file_ids' => $file_ids];
    if (isset($vectors['chunking_strategy'])) {
        $body['chunking_strategy'] = $vectors['chunking_strategy'];
    }

    return _request_logic($strategyInstance, 'POST', $url, $body);
}
<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/build.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder; // For constants
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build static method of OpenAIUrlBuilder.
 */
function build_logic_for_url_builder(string $operation, array $params): string|WP_Error {
    $base_url = !empty($params['base_url']) ? rtrim($params['base_url'], '/') : 'https://api.openai.com';
    $api_version = !empty($params['api_version']) ? $params['api_version'] : 'v1';

    if (empty($base_url)) return new WP_Error("missing_base_url_OpenAI_logic", __('OpenAI Base URL is required.', 'gpt3-ai-content-generator'));
    if (empty($api_version)) return new WP_Error("missing_api_version_OpenAI_logic", __('OpenAI API Version is required.', 'gpt3-ai-content-generator'));

    $paths = [
        'responses'           => '/responses',
        'models'              => '/models',
        'moderation'          => OpenAIUrlBuilder::MODERATION_ENDPOINT,
        'audio/speech'        => OpenAIUrlBuilder::SPEECH_ENDPOINT,
        'audio/transcriptions'=> OpenAIUrlBuilder::TRANSCRIPTION_ENDPOINT,
        'images/generations'  => OpenAIUrlBuilder::IMAGES_ENDPOINT,
        'files'               => OpenAIUrlBuilder::FILES_ENDPOINT,
        'files_id'            => OpenAIUrlBuilder::FILES_ENDPOINT . '/{file_id}', // Added for specific file deletion
        'embeddings'          => OpenAIUrlBuilder::EMBEDDINGS_ENDPOINT,
        'vector_stores'       => OpenAIUrlBuilder::VECTOR_STORES_ENDPOINT,
        'vector_stores_id'    => OpenAIUrlBuilder::VECTOR_STORES_ENDPOINT . '/{vector_store_id}',
        'vector_stores_id_search' => OpenAIUrlBuilder::VECTOR_STORES_ENDPOINT . '/{vector_store_id}/search',
        'vector_stores_id_file_batches' => OpenAIUrlBuilder::VECTOR_STORES_ENDPOINT . '/{vector_store_id}/file_batches',
        'vector_stores_id_file_batches_id' => OpenAIUrlBuilder::VECTOR_STORES_ENDPOINT . '/{vector_store_id}/file_batches/{batch_id}',
        'vector_stores_id_files_id' => OpenAIUrlBuilder::VECTOR_STORES_ENDPOINT . '/{vector_store_id}/files/{file_id}',
        'vector_stores_id_files'    => OpenAIUrlBuilder::VECTOR_STORES_ENDPOINT . '/{vector_store_id}/files',
    ];

    $path_template = $paths[$operation] ?? null;

    if ($path_template === null) {
        // translators: %s is the operation name (e.g. 'models', 'files')
        return new WP_Error('unsupported_operation_OpenAI_logic', sprintf(__('Operation "%s" not supported for OpenAI URL Builder.', 'gpt3-ai-content-generator'), esc_html($operation)));
    }

    $path = $path_template;
    if (strpos($path, '{vector_store_id}') !== false) {
        if (empty($params['vector_store_id'])) return new WP_Error('missing_vector_store_id_logic', __('Vector Store ID is required for this operation.', 'gpt3-ai-content-generator'));
        $path = str_replace('{vector_store_id}', urlencode($params['vector_store_id']), $path);
    }
    if (strpos($path, '{batch_id}') !== false) {
        if (empty($params['batch_id'])) return new WP_Error('missing_batch_id_logic', __('Batch ID is required for this operation.', 'gpt3-ai-content-generator'));
        $path = str_replace('{batch_id}', urlencode($params['batch_id']), $path);
    }
    if (strpos($path, '{file_id}') !== false) {
         if (empty($params['file_id'])) return new WP_Error('missing_file_id_logic', __('File ID is required for this operation.', 'gpt3-ai-content-generator'));
         $path = str_replace('{file_id}', urlencode($params['file_id']), $path);
    }

    $version_segment = '/' . trim($api_version, '/');
    $url = '';
    if (strpos($base_url, $version_segment) !== false) {
        $url = $base_url . $path;
    } else {
        $url = $base_url . $version_segment . $path;
    }

    $query_args = [];
    if ($operation === 'vector_stores' || $operation === 'vector_stores_id_files') { // vector_stores_id_files also supports pagination
        if (isset($params['limit']) && is_numeric($params['limit'])) $query_args['limit'] = intval($params['limit']);
        if (isset($params['order']) && in_array($params['order'], ['asc', 'desc'])) $query_args['order'] = $params['order'];
        if (isset($params['after']) && !empty($params['after'])) $query_args['after'] = sanitize_text_field($params['after']);
        if (isset($params['before']) && !empty($params['before'])) $query_args['before'] = sanitize_text_field($params['before']);
    }
    if ($operation === 'vector_stores_id_files' && isset($params['filter']) && in_array($params['filter'], ['in_progress', 'completed', 'failed', 'cancelled'])) {
        $query_args['filter'] = sanitize_key($params['filter']);
    }


    if (!empty($query_args)) {
        $url = add_query_arg($query_args, $url);
    }
    return $url;
}
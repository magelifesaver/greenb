<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/upload-file.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;
use CURLFile;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the upload_file_for_vector_store method of AIPKit_Vector_OpenAI_Strategy.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $file_path Absolute path to the file on the server.
 * @param string $original_filename The original filename.
 * @param string $purpose Purpose of the file.
 * @return array|WP_Error OpenAI file object or WP_Error.
 */
function upload_file_for_vector_store_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $file_path, string $original_filename, string $purpose = 'assistants_file'): array|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }
    if (!file_exists($file_path) || !is_readable($file_path)) {
        return new WP_Error('file_not_readable', __('File not found or not readable at path: ', 'gpt3-ai-content-generator') . $file_path);
    }
    if (!class_exists('CURLFile')) {
        return new WP_Error('curlfile_missing', __('Server configuration error (CURLFile missing for file upload).', 'gpt3-ai-content-generator'), ['status' => 500]);
    }

    $url_builder_params = [
        'base_url' => $strategyInstance->get_base_url(),
        'api_version' => $strategyInstance->get_api_version()
    ];
    $url = OpenAIUrlBuilder::build('files', $url_builder_params);
    if (is_wp_error($url)) return $url;

    // Access the protected method get_mime_type_from_filename via a public wrapper if needed,
    // or make it public/protected in the strategy class itself.
    // For this modularization, if get_mime_type_from_filename is also externalized, call that.
    // Let's assume it's correctly externalized and can be called.
    $mime_type = get_mime_type_from_filename_logic($strategyInstance, $original_filename);
    if (is_wp_error($mime_type)) {
        $mime_type = 'application/octet-stream';
    }

    $cfile = new CURLFile($file_path, $mime_type, $original_filename);
    $data = ['purpose' => $purpose, 'file' => $cfile];

    return _request_logic($strategyInstance, 'POST', $url, $data, true);
}
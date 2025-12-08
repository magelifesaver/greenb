<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/delete-openai-file.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the delete_openai_file_object method of AIPKit_Vector_OpenAI_Strategy.
 * Deletes a file object from OpenAI account.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $file_id The ID of the file to delete.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function delete_openai_file_object_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $file_id): bool|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }
    if (empty($file_id)) {
        return new WP_Error('missing_file_id', __('File ID is required to delete the file object.', 'gpt3-ai-content-generator'));
    }

    $url_params = [
        'base_url' => $strategyInstance->get_base_url(),
        'api_version' => $strategyInstance->get_api_version(),
        'file_id' => $file_id
    ];
    // Assuming 'files_id' is a valid operation key for deleting a specific file.
    // If not, OpenAIUrlBuilder needs to be updated or a direct URL constructed.
    $url = OpenAIUrlBuilder::build('files_id', $url_params);
    if (is_wp_error($url)) {
        // Fallback if 'files_id' operation is not in builder for DELETE.
        // OpenAI DELETE file endpoint is /v1/files/{file_id}
        $version_segment = '/' . trim($strategyInstance->get_api_version(), '/');
        $url = $strategyInstance->get_base_url() . $version_segment . '/files/' . urlencode($file_id);
    }

    $response = _request_logic($strategyInstance, 'DELETE', $url);
    if (is_wp_error($response)) return $response;

    if (isset($response['deleted']) && $response['deleted'] === true && isset($response['id']) && $response['id'] === $file_id) {
        return true;
    } else {
        return new WP_Error('file_object_delete_failed', __('OpenAI API did not confirm file object deletion.', 'gpt3-ai-content-generator'));
    }
}
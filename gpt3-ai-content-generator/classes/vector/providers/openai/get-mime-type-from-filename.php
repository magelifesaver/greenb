<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/providers/openai/get-mime-type-from-filename.php
// Status: NEW FILE

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_mime_type_from_filename method of AIPKit_Vector_OpenAI_Strategy.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param string $filename The filename.
 * @return string|WP_Error The MIME type string or WP_Error.
 */
function get_mime_type_from_filename_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, string $filename): string|WP_Error {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (empty($extension)) {
        return new WP_Error('missing_extension', __('Filename has no extension, cannot determine MIME type.', 'gpt3-ai-content-generator'));
    }

    $mime_map = $strategyInstance::get_static_mime_type_map(); // Access static property via class

    if (isset($mime_map[$extension])) {
        return $mime_map[$extension];
    }

    /* translators: %s: File extension */
    return new WP_Error('unsupported_extension_for_mime', sprintf(__('File extension ".%s" is not explicitly mapped to a supported MIME type for OpenAI.', 'gpt3-ai-content-generator'), $extension));
}
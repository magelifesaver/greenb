<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/frontend/ajax/utils/class-chat-upload-validator.php
// Status: NEW FILE

namespace WPAICG\Lib\Chat\Frontend\Ajax\Utils;

use WPAICG\Includes\AIPKit_Upload_Utils;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ChatUploadValidator
 *
 * Wraps file validation logic for chat uploads.
 */
class ChatUploadValidator
{
    public function __construct()
    {
        // Ensure AIPKit_Upload_Utils is loaded (should be by main plugin loader)
        if (!class_exists(AIPKit_Upload_Utils::class)) {
            $path = WPAICG_PLUGIN_DIR . 'includes/class-aipkit-upload-utils.php';
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Validates an uploaded file against allowed MIME types and max size for chat uploads.
     *
     * @param array $file_data $_FILES entry for the uploaded file.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    public function validate_file(array $file_data): bool|WP_Error
    {
        if (!class_exists(AIPKit_Upload_Utils::class)) {
            return new WP_Error('upload_utils_missing_validator', __('File validation component is missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        // Define allowed MIME types for chat file uploads
        $allowed_mime_types = apply_filters('aipkit_chat_upload_allowed_mime_types', [
            'text/plain',
            'application/pdf',
            // Potentially add more types specifically for chat in the future
        ]);
        return AIPKit_Upload_Utils::validate_upload_file($file_data, $allowed_mime_types);
    }
}

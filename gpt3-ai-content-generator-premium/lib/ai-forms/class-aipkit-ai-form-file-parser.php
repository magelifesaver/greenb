<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/ai-forms/class-aipkit-ai-form-file-parser.php

namespace WPAICG\Lib\AIForms;

use WPAICG\Lib\Utils\AIPKit_Pdf_Parser;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_AI_Form_File_Parser
 *
 * Pro-only service to parse uploaded files from AI Forms and extract text content.
 */
class AIPKit_AI_Form_File_Parser
{
    /**
     * Parses the content of an uploaded file.
     *
     * @param array $file_data An item from the $_FILES superglobal array.
     * @return string|WP_Error The extracted text content or a WP_Error on failure.
     */
    public static function parse_file(array $file_data): string|WP_Error
    {
        if (empty($file_data['tmp_name']) || !is_readable($file_data['tmp_name'])) {
            return new WP_Error('file_unreadable', __('Uploaded file is not readable or missing.', 'gpt3-ai-content-generator'));
        }

        // Determine MIME type reliably if possible
        $file_mime_type = '';
        if (function_exists('mime_content_type') && is_readable($file_data['tmp_name'])) {
            $file_mime_type = mime_content_type($file_data['tmp_name']);
        } elseif (isset($file_data['type'])) {
            $file_mime_type = $file_data['type']; // Fallback to browser-sent type
        }

        if (empty($file_mime_type)) {
            return new WP_Error('unknown_mime_type', __('Could not determine the file type.', 'gpt3-ai-content-generator'));
        }

        switch ($file_mime_type) {
            case 'text/plain':
                $content = file_get_contents($file_data['tmp_name']);
                if ($content === false) {
                    return new WP_Error('read_error_txt', __('Failed to read the plain text file.', 'gpt3-ai-content-generator'));
                }
                return $content;

            case 'application/pdf':
                if (!class_exists(AIPKit_Pdf_Parser::class)) {
                    $pdf_parser_path = WPAICG_LIB_DIR . 'utils/class-aipkit-pdf-parser.php';
                    if (file_exists($pdf_parser_path)) {
                        require_once $pdf_parser_path;
                    } else {
                        return new WP_Error('pdf_parser_missing', __('PDF parsing library is unavailable.', 'gpt3-ai-content-generator'));
                    }
                }
                $pdf_parser = new AIPKit_Pdf_Parser();
                $extracted_text_or_error = $pdf_parser->extract_text($file_data['tmp_name']);
                if (is_wp_error($extracted_text_or_error)) {
                    return $extracted_text_or_error;
                }
                return $extracted_text_or_error;

            // Future support for other document types can be added here
            // case 'application/msword':
            // case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            //     return new WP_Error('unsupported_word', __('Microsoft Word documents are not yet supported for file uploads.', 'gpt3-ai-content-generator'));

            default:
                /* translators: %s is the MIME type */
                return new WP_Error('unsupported_file_type', sprintf(__('The file type "%s" is not supported for content extraction.', 'gpt3-ai-content-generator'), esc_html($file_mime_type)));
        }
    }
}
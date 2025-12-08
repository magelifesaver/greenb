<?php
// File: lib/utils/class-aipkit-file-text-extractor.php

namespace WPAICG\Lib\Utils;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AIPKit_File_Text_Extractor
 * Pro-only utility to extract and normalize text from uploaded files for vector indexing.
 * Supports: text/plain, application/pdf, text/html, docx.
 */
class AIPKit_File_Text_Extractor
{
    /**
     * Extracts text from the given uploaded file (from $_FILES array entry).
     * Returns cleaned UTF-8 text or WP_Error on failure.
     *
     * @param array $file_data
     * @return string|WP_Error
     */
    public function extract(array $file_data): string|WP_Error
    {
        $tmp = $file_data['tmp_name'] ?? '';
        if (!$tmp || !is_readable($tmp)) {
            return new WP_Error('file_unreadable', __('Uploaded file is not readable.', 'gpt3-ai-content-generator'));
        }

        $mime = $this->detect_mime($file_data);
        $ext  = strtolower(pathinfo($file_data['name'] ?? '', PATHINFO_EXTENSION));

        switch ($mime) {
            case 'text/plain':
                $raw = @file_get_contents($tmp);
                if ($raw === false) {
                    return new WP_Error('file_read_error', __('Could not read file content.', 'gpt3-ai-content-generator'));
                }
                $len = function_exists('mb_strlen') ? mb_strlen($raw) : strlen($raw);
                $clean = $this->clean_text($raw);
                $clean_len = function_exists('mb_strlen') ? mb_strlen($clean) : strlen($clean);
                return $clean;

            case 'application/pdf':
            case 'application/x-pdf':
                if (!class_exists(AIPKit_Pdf_Parser::class)) {
                    return new WP_Error('pdf_parser_missing', __('PDF parser not available.', 'gpt3-ai-content-generator'));
                }
                $parser = new AIPKit_Pdf_Parser();
                $out = $parser->extract_text($tmp);
                if (is_wp_error($out)) {
                    return $out;
                }
                $clean = $this->clean_text($out);
                return $clean;

            case 'text/html':
            case 'application/xhtml+xml':
                $raw = @file_get_contents($tmp);
                if ($raw === false) {
                    return new WP_Error('file_read_error_html', __('Could not read HTML file.', 'gpt3-ai-content-generator'));
                }
                // Use WP helper to strip tags; decode entities first for better results
                $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text    = wp_strip_all_tags($decoded);
                $clean = $this->clean_text($text);
                return $clean;

            default:
                // Heuristic docx detection by extension when MIME is generic
                if ($ext === 'docx' || $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    $text = $this->extract_docx_text($tmp);
                    if (is_wp_error($text)) {
                        return $text;
                    }
                    $clean = $this->clean_text($text);
                    return $clean;
                }
                return new WP_Error('unsupported_file_type', sprintf(__('Unsupported file type: %s', 'gpt3-ai-content-generator'), esc_html($mime ?: $ext ?: 'unknown')));
        }
    }

    private function detect_mime(array $file_data): string
    {
        $tmp = $file_data['tmp_name'] ?? '';
        $mime = '';
        if ($tmp && function_exists('mime_content_type')) {
            $det = @mime_content_type($tmp);
            if (is_string($det) && $det !== '') $mime = $det;
        }
        if (!$mime && !empty($file_data['type'])) {
            $mime = (string)$file_data['type'];
        }
        return strtolower($mime);
    }

    /**
     * Minimal docx reader: open as zip and read word/document.xml, strip tags.
     */
    private function extract_docx_text(string $path): string|WP_Error
    {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('zip_missing', __('DOCX extraction requires ZipArchive extension.', 'gpt3-ai-content-generator'));
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return new WP_Error('docx_open_failed', __('Failed to open DOCX file.', 'gpt3-ai-content-generator'));
        }
        $index = $zip->locateName('word/document.xml');
        if ($index === false) {
            $zip->close();
            return new WP_Error('docx_xml_missing', __('DOCX main document.xml not found.', 'gpt3-ai-content-generator'));
        }
        $xml = $zip->getFromIndex($index);
        $zip->close();
        if ($xml === false) {
            return new WP_Error('docx_read_failed', __('Failed to read DOCX XML.', 'gpt3-ai-content-generator'));
        }
        // Insert paragraph breaks where appropriate (<w:p>) then strip tags
        $xml = preg_replace('#</w:p>#i', "\n\n", $xml);
        $text = wp_strip_all_tags($xml);
        return $text;
    }

    /**
     * Normalize text: ensure UTF-8, normalize newlines, collapse excessive whitespace while preserving paragraphs,
     * remove control chars, trim, and allow final customization via filter.
     */
    public function clean_text(string $text): string
    {
        $original = $text;
        $orig_len = function_exists('mb_strlen') ? mb_strlen($original) : strlen($original);

        // Ensure UTF-8 (robustly). Avoid 'auto' which can yield empty.
        $seems_utf8 = seems_utf8($text);
        if (!$seems_utf8) {
            $detected = function_exists('mb_detect_encoding') ? @mb_detect_encoding($text, 'UTF-8, ISO-8859-1, ISO-8859-15, Windows-1252, ASCII', true) : false;
            if ($detected && $detected !== 'UTF-8') {
                $converted = function_exists('iconv') ? @iconv($detected, 'UTF-8//IGNORE', $text) : @mb_convert_encoding($text, 'UTF-8', $detected);
                if (is_string($converted) && $converted !== '') {
                    $text = $converted;
                } else {
                    $text = function_exists('wp_check_invalid_utf8') ? wp_check_invalid_utf8($text, true) : $text;
                }
            } else {
                // Strip invalid UTF-8 sequences without heavy conversion
                $text = function_exists('wp_check_invalid_utf8') ? wp_check_invalid_utf8($text, true) : $text;
            }
        }
        // Remove BOM
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        // Normalize newlines to \n
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Remove zero-width and control chars except tab and newline
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{200B}-\x{200F}\x{FEFF}]/u', '', $text);
        // Collapse 3+ newlines to 2 (preserve paragraph breaks)
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        // Collapse spaces around newlines
        $text = preg_replace("/[ \t]*\n[ \t]*/", "\n", $text);
        // Trim lines
        $text = implode("\n", array_map('trim', explode("\n", $text)));
        // Final trim
        $text = trim($text);
        // If cleaning nuked everything but original was non-empty, apply safer fallback
        $final_len = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
        if ($final_len === 0 && $orig_len > 0) {
            // Replace NBSP with space, normalize newlines, keep characters otherwise
            $fallback = str_replace(["\xC2\xA0"], ' ', $original); // NBSP
            $fallback = str_replace(["\r\n", "\r"], "\n", $fallback);
            $fallback = preg_replace('/^\xEF\xBB\xBF/', '', $fallback);
            $fallback = trim($fallback);
            $text = $fallback;
        }
        // Allow customization
        return apply_filters('aipkit_vector_clean_text', $text);
    }
}

<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/utils/class-aipkit-pdf-parser.php
// Status: NEW FILE

namespace WPAICG\Lib\Utils; // Pro feature namespace

use Smalot\PdfParser\Parser;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AIPKit_Pdf_Parser {

    private $parser;

    public function __construct() {
        // Ensure Composer autoloader has run
        if (!class_exists('\Smalot\PdfParser\Parser')) {
            // This error suggests Composer autoloading isn't working correctly
            // or the package wasn't installed.
            // You might throw an exception here or handle it gracefully
            $this->parser = null;
            return;
        }
        $this->parser = new Parser();
    }

    /**
     * Extracts text content from a PDF file.
     *
     * @param string $pdf_file_path Absolute path to the PDF file.
     * @return string|WP_Error Extracted text content or WP_Error on failure.
     */
    public function extract_text(string $pdf_file_path): string|WP_Error {
        if (!$this->parser) {
            return new WP_Error('pdf_parser_not_initialized', __('PDF parsing library is not available.', 'gpt3-ai-content-generator'));
        }
        if (!file_exists($pdf_file_path) || !is_readable($pdf_file_path)) {
            return new WP_Error('pdf_file_unreadable', __('PDF file is not readable or does not exist.', 'gpt3-ai-content-generator'));
        }

        try {
            $pdf = $this->parser->parseFile($pdf_file_path);
            $text = $pdf->getText();
            // Basic cleaning: trim whitespace and consolidate multiple newlines/spaces
            $cleaned_text = trim(preg_replace('/\s+/', ' ', $text));
            return $cleaned_text;
        } catch (\Exception $e) {
            return new WP_Error('pdf_parsing_failed', __('Failed to parse PDF content.', 'gpt3-ai-content-generator'), ['original_error' => $e->getMessage()]);
        }
    }
}
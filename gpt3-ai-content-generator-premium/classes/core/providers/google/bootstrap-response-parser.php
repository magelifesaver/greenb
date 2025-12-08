<?php
// File: classes/core/providers/google/bootstrap-response-parser.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load method logic files
require_once __DIR__ . '/parse-chat.php';
require_once __DIR__ . '/parse-error.php';
require_once __DIR__ . '/parse-sse.php';
require_once __DIR__ . '/parse-embeddings.php';

/**
 * Handles parsing responses and SSE chunks from the Google Gemini API (Modularized).
 * Original logic for static methods is now in separate files within the 'Methods' namespace.
 */
class GoogleResponseParser {

    public static function parse_chat(array $decoded_response): array|WP_Error {
        return \WPAICG\Core\Providers\Google\Methods\parse_chat_logic_for_response_parser($decoded_response);
    }

    public static function parse_error($response_body, int $status_code): string {
        return \WPAICG\Core\Providers\Google\Methods\parse_error_logic_for_response_parser($response_body, $status_code);
    }

    public static function parse_sse_chunk(string $sse_chunk, string &$current_buffer): array {
        return \WPAICG\Core\Providers\Google\Methods\parse_sse_chunk_logic_for_response_parser($sse_chunk, $current_buffer);
    }

    public static function parse_embeddings(array $decoded_response): array|WP_Error {
        return \WPAICG\Core\Providers\Google\Methods\parse_embeddings_logic_for_response_parser($decoded_response);
    }
}
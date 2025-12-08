<?php
// File: classes/core/providers/openrouter/bootstrap-response-parser.php
// Status: MODIFIED
// Was: classes/core/providers/openrouter/OpenRouterResponseParser.php

namespace WPAICG\Core\Providers\OpenRouter;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load method logic files
require_once __DIR__ . '/parse-chat.php';
require_once __DIR__ . '/parse-error.php';
require_once __DIR__ . '/parse-sse.php';

/**
 * Handles parsing responses and SSE chunks from the OpenRouter API (Chat Completions format) (Modularized).
 * Original logic for static methods is now in separate files within the 'Methods' namespace.
 */
class OpenRouterResponseParser {

    /**
     * Parses a standard Chat Completions API response (used by OpenRouter).
     *
     * @param array $decoded_response The decoded JSON response.
     * @return array|WP_Error ['content' => string, 'usage' => array|null] or WP_Error.
     */
    public static function parse_chat(array $decoded_response): array|WP_Error {
        return \WPAICG\Core\Providers\OpenRouter\Methods\parse_chat_logic_for_response_parser($decoded_response);
    }

    /**
     * Parses error response from OpenRouter. Structure can vary.
     *
     * @param mixed $response_body The raw or decoded error response body.
     * @param int $status_code The HTTP status code.
     * @return string A user-friendly error message.
     */
    public static function parse_error($response_body, int $status_code): string {
        return \WPAICG\Core\Providers\OpenRouter\Methods\parse_error_logic_for_response_parser($response_body, $status_code);
    }


    /**
     * Parses an SSE chunk from an OpenRouter stream (Chat Completions format).
     *
     * @param string $sse_chunk       The raw chunk received.
     * @param string &$current_buffer Reference to the incomplete buffer.
     * @return array Result containing delta, usage, flags.
     */
    public static function parse_sse_chunk(string $sse_chunk, string &$current_buffer): array {
        return \WPAICG\Core\Providers\OpenRouter\Methods\parse_sse_chunk_logic_for_response_parser($sse_chunk, $current_buffer);
    }
}
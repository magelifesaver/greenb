<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/bootstrap-response-parser.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load individual method logic files
$methods_path = __DIR__ . '/';
require_once $methods_path . 'parse-chat.php';
require_once $methods_path . 'parse-error.php';
require_once $methods_path . 'parse-sse.php';
require_once $methods_path . 'parse-moderation.php';
require_once $methods_path . 'parse-embeddings.php';

/**
 * Handles parsing responses and SSE chunks from the OpenAI Responses API (v1/responses) and Embeddings API.
 * Original logic for methods is now in separate files within the 'Methods' namespace.
 */
class OpenAIResponseParser {

    public static function parse_chat(array $decoded_response): array|WP_Error {
        return \WPAICG\Core\Providers\OpenAI\Methods\parse_chat_logic_for_response_parser($decoded_response);
    }

    public static function parse_error($response_body, int $status_code): string {
        return \WPAICG\Core\Providers\OpenAI\Methods\parse_error_logic_for_response_parser($response_body, $status_code);
    }

    public static function parse_sse_chunk(string $sse_chunk, string &$current_buffer): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\parse_sse_chunk_logic_for_response_parser($sse_chunk, $current_buffer);
    }

    public static function parse_moderation(array $decoded_response): bool {
        return \WPAICG\Core\Providers\OpenAI\Methods\parse_moderation_logic_for_response_parser($decoded_response);
    }

    public static function parse_embeddings(array $decoded_response): array|WP_Error {
        return \WPAICG\Core\Providers\OpenAI\Methods\parse_embeddings_logic_for_response_parser($decoded_response);
    }
}
<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/parse-chat-response.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAIProviderStrategy;
use WPAICG\Core\Providers\OpenAI\OpenAIResponseParser; // Use the new Parser class
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_chat_response method of OpenAIProviderStrategy.
 *
 * @param OpenAIProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $decoded_response The decoded JSON response body.
 * @param array $request_data The original request data sent.
 * @return array|WP_Error ['content' => string, 'usage' => array|null] or WP_Error.
 */
function parse_chat_response_logic(
    OpenAIProviderStrategy $strategyInstance,
    array $decoded_response,
    array $request_data
): array|WP_Error {
    $parsed = OpenAIResponseParser::parse_chat($decoded_response);
    // Add OpenAI specific 'id' to the parsed response if available
    if (!is_wp_error($parsed) && isset($decoded_response['id'])) {
        $parsed['openai_response_id'] = $decoded_response['id'];
    }
    return $parsed;
}
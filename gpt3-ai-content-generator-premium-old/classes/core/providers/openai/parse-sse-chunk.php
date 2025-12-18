<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/parse-sse-chunk.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAIProviderStrategy;
use WPAICG\Core\Providers\OpenAI\OpenAIResponseParser; // Use the new Parser class

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_sse_chunk method of OpenAIProviderStrategy.
 *
 * @param OpenAIProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $sse_chunk The raw chunk received from the stream.
 * @param string &$current_buffer The reference to the incomplete buffer for this provider.
 * @return array Result containing delta, usage, flags, and potentially openai_response_id.
 */
function parse_sse_chunk_logic(
    OpenAIProviderStrategy $strategyInstance,
    string $sse_chunk,
    string &$current_buffer
): array {
    $parsed = OpenAIResponseParser::parse_sse_chunk($sse_chunk, $current_buffer);
    // Check if the parsed result contains the 'openai_response_id' (added by OpenAIResponseParser::parse_sse_chunk)
    // and propagate it if present. This key is specific to OpenAI's Responses API.
    // Note: $parsed['raw_completion_event_data'] is no longer directly exposed by this method.
    // OpenAIResponseParser::parse_sse_chunk directly adds 'openai_response_id' to $parsed if found.
    return $parsed;
}
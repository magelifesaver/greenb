<?php
// File: classes/core/providers/openrouter/parse-sse-chunk.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WPAICG\Core\Providers\OpenRouterProviderStrategy;
use WPAICG\Core\Providers\OpenRouter\OpenRouterResponseParser; // For direct call
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_sse_chunk method of OpenRouterProviderStrategy.
 *
 * @param OpenRouterProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $sse_chunk The raw chunk received from the stream.
 * @param string &$current_buffer The reference to the incomplete buffer for this provider.
 * @return array Result containing delta, usage, flags.
 */
function parse_sse_chunk_logic(
    OpenRouterProviderStrategy $strategyInstance,
    string $sse_chunk,
    string &$current_buffer
): array {
    // Ensure OpenRouterResponseParser is available
    if (!class_exists(\WPAICG\Core\Providers\OpenRouter\OpenRouterResponseParser::class)) {
        $parser_bootstrap = dirname(__FILE__) . '/bootstrap-response-parser.php';
        if (file_exists($parser_bootstrap)) {
            require_once $parser_bootstrap;
        } else {
            return ['delta' => null, 'usage' => null, 'is_error' => true, 'is_warning' => false, 'is_done' => true];
        }
    }
    return \WPAICG\Core\Providers\OpenRouter\OpenRouterResponseParser::parse_sse_chunk($sse_chunk, $current_buffer);
}
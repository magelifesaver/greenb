<?php
// File: classes/core/providers/openrouter/parse-error-response.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WPAICG\Core\Providers\OpenRouterProviderStrategy;
use WPAICG\Core\Providers\OpenRouter\OpenRouterResponseParser; // For direct call

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_error_response method of OpenRouterProviderStrategy.
 *
 * @param OpenRouterProviderStrategy $strategyInstance The instance of the strategy class.
 * @param mixed $response_body The raw or decoded error response body.
 * @param int $status_code The HTTP status code.
 * @return string A user-friendly error message.
 */
function parse_error_response_logic(
    OpenRouterProviderStrategy $strategyInstance,
    $response_body,
    int $status_code
): string {
    // Ensure OpenRouterResponseParser is available
    if (!class_exists(\WPAICG\Core\Providers\OpenRouter\OpenRouterResponseParser::class)) {
        $parser_bootstrap = dirname(__FILE__) . '/bootstrap-response-parser.php';
        if (file_exists($parser_bootstrap)) {
            require_once $parser_bootstrap;
        } else {
            return "OpenRouter response parser component is not available."; // Fallback error
        }
    }
    return \WPAICG\Core\Providers\OpenRouter\OpenRouterResponseParser::parse_error($response_body, $status_code);
}
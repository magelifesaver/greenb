<?php
// File: classes/core/providers/google/parse-error-response.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\Core\Providers\GoogleProviderStrategy; 
use WPAICG\Core\Providers\Google\GoogleResponseParser;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_error_response method of GoogleProviderStrategy.
 *
 * @param GoogleProviderStrategy $strategyInstance The instance of the strategy class.
 * @param mixed $response_body The raw or decoded error response body.
 * @param int $status_code The HTTP status code.
 * @return string A user-friendly error message.
 */
function parse_error_response_logic(
    GoogleProviderStrategy $strategyInstance,
    $response_body,
    int $status_code
): string {
    if (!class_exists(\WPAICG\Core\Providers\Google\GoogleResponseParser::class)) {
        $parser_bootstrap = dirname(__FILE__) . '/bootstrap-response-parser.php';
        if (file_exists($parser_bootstrap)) {
            require_once $parser_bootstrap;
        } else {
            return "Google response parser component is not available."; 
        }
    }
    return \WPAICG\Core\Providers\Google\GoogleResponseParser::parse_error($response_body, $status_code);
}
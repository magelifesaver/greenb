<?php
// File: classes/core/providers/google/parse-chat-response.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\Core\Providers\GoogleProviderStrategy; 
use WPAICG\Core\Providers\Google\GoogleResponseParser; 
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_chat_response method of GoogleProviderStrategy.
 *
 * @param GoogleProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $decoded_response The decoded JSON response body.
 * @param array $request_data The original request data sent (unused here).
 * @return array|WP_Error ['content' => string, 'usage' => array|null] or WP_Error.
 */
function parse_chat_response_logic(
    GoogleProviderStrategy $strategyInstance,
    array $decoded_response,
    array $request_data
): array|WP_Error {
    if (!class_exists(\WPAICG\Core\Providers\Google\GoogleResponseParser::class)) {
        $parser_bootstrap = dirname(__FILE__) . '/bootstrap-response-parser.php';
        if (file_exists($parser_bootstrap)) {
            require_once $parser_bootstrap;
        } else {
            return new WP_Error('google_response_parser_missing_logic', 'Google response parser component is not available.');
        }
    }
    return \WPAICG\Core\Providers\Google\GoogleResponseParser::parse_chat($decoded_response);
}
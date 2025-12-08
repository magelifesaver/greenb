<?php
// File: classes/core/providers/azure/parse-chat-response.php

namespace WPAICG\Core\Providers\Azure\Methods;

use WPAICG\Core\Providers\AzureProviderStrategy;
use WPAICG\Core\Providers\Azure\AzureResponseParser; // For direct call
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_chat_response method of AzureProviderStrategy.
 *
 * @param AzureProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $decoded_response The decoded JSON response body.
 * @param array $request_data The original request data sent (unused here).
 * @return array|WP_Error ['content' => string, 'usage' => array|null] or WP_Error.
 */
function parse_chat_response_logic(
    AzureProviderStrategy $strategyInstance,
    array $decoded_response,
    array $request_data
): array|WP_Error {
    // This method in AzureProviderStrategy directly calls AzureResponseParser::parse_chat.
    if (!class_exists(\WPAICG\Core\Providers\Azure\AzureResponseParser::class)) {
        $parser_bootstrap = __DIR__ . '/bootstrap-response-parser.php';
        if (file_exists($parser_bootstrap)) {
            require_once $parser_bootstrap;
        } else {
            return new WP_Error('azure_response_parser_missing', 'Azure response parser component is not available.');
        }
    }
    return \WPAICG\Core\Providers\Azure\AzureResponseParser::parse_chat($decoded_response);
}
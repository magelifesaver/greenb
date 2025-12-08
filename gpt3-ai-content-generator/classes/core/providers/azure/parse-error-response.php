<?php
// File: classes/core/providers/azure/parse-error-response.php

namespace WPAICG\Core\Providers\Azure\Methods;

use WPAICG\Core\Providers\AzureProviderStrategy;
use WPAICG\Core\Providers\Azure\AzureResponseParser; // For direct call

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_error_response method of AzureProviderStrategy.
 *
 * @param AzureProviderStrategy $strategyInstance The instance of the strategy class.
 * @param mixed $response_body The raw or decoded error response body.
 * @param int $status_code The HTTP status code.
 * @return string A user-friendly error message.
 */
function parse_error_response_logic(
    AzureProviderStrategy $strategyInstance,
    $response_body,
    int $status_code
): string {
    // This method in AzureProviderStrategy directly calls AzureResponseParser::parse_error.
    if (!class_exists(\WPAICG\Core\Providers\Azure\AzureResponseParser::class)) {
        $parser_bootstrap = __DIR__ . '/bootstrap-response-parser.php';
        if (file_exists($parser_bootstrap)) {
            require_once $parser_bootstrap;
        } else {
            return "Azure response parser component is not available."; // Fallback error
        }
    }
    return \WPAICG\Core\Providers\Azure\AzureResponseParser::parse_error($response_body, $status_code);
}
<?php
// File: classes/core/providers/google/get-api-headers.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\Core\Providers\GoogleProviderStrategy;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_api_headers method of GoogleProviderStrategy.
 *
 * @param GoogleProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $api_key The API key (not directly used by Google headers, but part of interface).
 * @param string $operation The specific operation being performed.
 * @return array Key-value array of headers.
 */
function get_api_headers_logic(GoogleProviderStrategy $strategyInstance, string $api_key, string $operation): array {
    $headers = ['Content-Type' => 'application/json'];
    if ($operation === 'stream') { 
        $headers['Accept'] = 'text/event-stream';
        $headers['Cache-Control'] = 'no-cache';
    }
    return $headers;
}
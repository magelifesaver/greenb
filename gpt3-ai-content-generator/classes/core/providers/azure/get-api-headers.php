<?php
// File: classes/core/providers/azure/get-api-headers.php

namespace WPAICG\Core\Providers\Azure\Methods;

use WPAICG\Core\Providers\AzureProviderStrategy;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_api_headers method of AzureProviderStrategy.
 *
 * @param AzureProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $api_key The API key for the provider.
 * @param string $operation The specific operation being performed.
 * @return array Key-value array of headers.
 */
function get_api_headers_logic(AzureProviderStrategy $strategyInstance, string $api_key, string $operation): array {
    $headers = [
        'Content-Type' => 'application/json',
        'api-key' => $api_key,
    ];
    if ($operation === 'stream') {
        $headers['Accept'] = 'text/event-stream';
        $headers['Cache-Control'] = 'no-cache';
    }
    return $headers;
}
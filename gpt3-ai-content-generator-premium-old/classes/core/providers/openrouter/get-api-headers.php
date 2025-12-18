<?php
// File: classes/core/providers/openrouter/get-api-headers.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WPAICG\Core\Providers\OpenRouterProviderStrategy;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_api_headers method of OpenRouterProviderStrategy.
 *
 * @param OpenRouterProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $api_key The API key for the provider.
 * @param string $operation The specific operation being performed.
 * @return array Key-value array of headers.
 */
function get_api_headers_logic(OpenRouterProviderStrategy $strategyInstance, string $api_key, string $operation): array {
    $headers = [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $api_key,
        'HTTP-Referer' => get_bloginfo('url'),
        'X-Title' => 'AIPKit',
    ];
    if ($operation === 'stream') {
        $headers['Accept'] = 'text/event-stream';
        $headers['Cache-Control'] = 'no-cache';
    }
    return $headers;
}
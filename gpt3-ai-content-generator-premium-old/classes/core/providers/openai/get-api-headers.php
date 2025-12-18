<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/get-api-headers.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAIProviderStrategy;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_api_headers method of OpenAIProviderStrategy.
 *
 * @param OpenAIProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $api_key The API key for the provider.
 * @param string $operation The specific operation being performed.
 * @return array Key-value array of headers.
 */
function get_api_headers_logic(OpenAIProviderStrategy $strategyInstance, string $api_key, string $operation): array {
    $headers = [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $api_key,
    ];
    if ($operation === 'stream') {
        $headers['Accept'] = 'text/event-stream';
        $headers['Cache-Control'] = 'no-cache';
    }
    // OpenAI-Beta header for specific features like vector stores, assistants
    if (strpos($operation, 'vector_stores') === 0 || strpos($operation, 'assistants') === 0 || $operation === 'files') {
        $headers['OpenAI-Beta'] = 'assistants=v2';
    }

    return $headers;
}
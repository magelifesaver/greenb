<?php
// File: classes/core/providers/openrouter/parse-error.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_error static method of OpenRouterResponseParser.
 *
 * @param mixed $response_body The raw or decoded error response body.
 * @param int $status_code The HTTP status code.
 * @return string A user-friendly error message.
 */
function parse_error_logic_for_response_parser($response_body, int $status_code): string {
    $message = __('An unknown API error occurred.', 'gpt3-ai-content-generator');
    $decoded = is_string($response_body) ? json_decode($response_body, true) : $response_body;

    if (is_array($decoded)) {
        // Check common error structures
        if (!empty($decoded['error']['message'])) {
            $message = $decoded['error']['message'];
            if (!empty($decoded['error']['code'])) { $message .= ' (Code: ' . $decoded['error']['code'] . ')'; }
        } elseif (!empty($decoded['detail'])) { // Sometimes uses 'detail'
            $message = is_string($decoded['detail']) ? $decoded['detail'] : json_encode($decoded['detail']);
        } elseif (!empty($decoded['message'])) { // Top-level message fallback
            $message = $decoded['message'];
        }
    } elseif (is_string($response_body)) {
         $message = substr($response_body, 0, 200); // Raw snippet
    }

    return trim($message);
}
<?php
// File: classes/core/providers/google/parse-error.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_error static method of GoogleResponseParser.
 *
 * @param mixed $response_body The raw or decoded error response body.
 * @param int $status_code The HTTP status code.
 * @return string A user-friendly error message.
 */
function parse_error_logic_for_response_parser($response_body, int $status_code): string {
    $message = __('An unknown API error occurred.', 'gpt3-ai-content-generator');
    $decoded = is_string($response_body) ? json_decode($response_body, true) : $response_body;

    if (is_array($decoded) && !empty($decoded['error']['message'])) {
        $message = $decoded['error']['message'];
        if (!empty($decoded['error']['details'][0]['message'])) {
            $message .= " (" . $decoded['error']['details'][0]['message'] . ")";
        }
    } elseif (is_string($response_body)) {
         $message = substr($response_body, 0, 200);
    }

    return trim($message);
}
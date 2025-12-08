<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/parse-error.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_error static method of OpenAIResponseParser.
 */
function parse_error_logic_for_response_parser($response_body, int $status_code): string {
    $message = __('An unknown API error occurred.', 'gpt3-ai-content-generator');
    if (is_string($response_body)) {
        $decoded = json_decode($response_body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['error']['message'])) {
            $message = $decoded['error']['message'];
            if (!empty($decoded['error']['code'])) { $message .= ' (Code: ' . $decoded['error']['code'] . ')';}
        } else {
             $message = substr($response_body, 0, 200);
        }
    } elseif (is_array($response_body) && isset($response_body['error']['message'])) {
         $message = $response_body['error']['message'];
         if (!empty($response_body['error']['code'])) { $message .= ' (Code: ' . $response_body['error']['code'] . ')';}
    }
    return trim($message);
}
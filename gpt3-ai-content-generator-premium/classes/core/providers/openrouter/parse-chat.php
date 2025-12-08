<?php
// File: classes/core/providers/openrouter/parse-chat.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_chat static method of OpenRouterResponseParser.
 *
 * @param array $decoded_response The decoded JSON response.
 * @return array|WP_Error ['content' => string, 'usage' => array|null] or WP_Error.
 */
function parse_chat_logic_for_response_parser(array $decoded_response): array|WP_Error {
    $content = null;

    // Standard Chat Completions structure
    if (!empty($decoded_response['choices'][0]['message']['content'])) {
        $content = trim($decoded_response['choices'][0]['message']['content']);
    } elseif (!empty($decoded_response['choices'][0]['delta']['content'])) { // Handle potential stream-like response structure
        $content = trim($decoded_response['choices'][0]['delta']['content']);
    } elseif (!empty($decoded_response['choices'][0]['text'])) { // Handle older text field if present
         $content = trim($decoded_response['choices'][0]['text']);
    }

    if ($content === null) {
        if (isset($decoded_response['choices'][0]['finish_reason']) && $decoded_response['choices'][0]['finish_reason'] === 'content_filter') {
            return new WP_Error('content_filter_logic', __('Response blocked due to content filtering.', 'gpt3-ai-content-generator'));
        }
        return new WP_Error('invalid_response_structure_chatcompletion_logic', __('Unexpected response structure from OpenRouter API.', 'gpt3-ai-content-generator'));
    }

    // Extract usage (standard Chat Completion format)
    $usage = null;
    if (isset($decoded_response['usage']) && is_array($decoded_response['usage'])) {
        $usage = [
            'input_tokens'  => $decoded_response['usage']['prompt_tokens'] ?? 0,
            'output_tokens' => $decoded_response['usage']['completion_tokens'] ?? 0,
            'total_tokens'  => $decoded_response['usage']['total_tokens'] ?? 0,
            'provider_raw' => $decoded_response['usage'],
        ];
    }

    return ['content' => $content, 'usage' => $usage];
}
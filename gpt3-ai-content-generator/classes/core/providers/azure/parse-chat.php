<?php
// File: classes/core/providers/azure/parse-chat.php

namespace WPAICG\Core\Providers\Azure\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_chat static method of AzureResponseParser.
 *
 * @param array $decoded_response The decoded JSON response.
 * @return array|WP_Error ['content' => string, 'usage' => array|null] or WP_Error.
 */
function parse_chat_logic_for_response_parser(array $decoded_response): array|WP_Error {
    $content = null;
    $usage = null;

    if (!empty($decoded_response['choices'][0]['message']['content'])) {
        $content = trim($decoded_response['choices'][0]['message']['content']);
    } elseif (!empty($decoded_response['choices'][0]['delta']['content'])) {
        $content = trim($decoded_response['choices'][0]['delta']['content']);
    } elseif (!empty($decoded_response['choices'][0]['text'])) {
        $content = trim($decoded_response['choices'][0]['text']);
    }

    if ($content === null) {
        if (isset($decoded_response['choices'][0]['finish_reason']) && $decoded_response['choices'][0]['finish_reason'] === 'content_filter') {
            return new WP_Error('content_filter_logic', __('Response blocked due to content filtering.', 'gpt3-ai-content-generator'));
        }
        return new WP_Error('invalid_response_structure_azure_logic', __('Unexpected response structure from Azure API.', 'gpt3-ai-content-generator'));
    }

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
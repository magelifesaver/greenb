<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/parse-chat.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_chat static method of OpenAIResponseParser.
 */
function parse_chat_logic_for_response_parser(array $decoded_response): array|WP_Error {
    $content = null;
    $usage = null;

    if (isset($decoded_response['output']) && is_array($decoded_response['output'])) {
        foreach ($decoded_response['output'] as $output_item) {
            if (isset($output_item['type']) && $output_item['type'] === 'message' &&
                !empty($output_item['content'][0]['type']) && $output_item['content'][0]['type'] === 'output_text' &&
                isset($output_item['content'][0]['text'])) {
                $content = trim($output_item['content'][0]['text']);
                break;
            }
        }
    }

    if (isset($decoded_response['status']) && $decoded_response['status'] === 'failed' && isset($decoded_response['error']['message'])) {
         return new WP_Error($decoded_response['error']['code'] ?? 'openai_failed_response_logic', $decoded_response['error']['message']);
    }
    if (isset($decoded_response['status']) && $decoded_response['status'] === 'incomplete' && isset($decoded_response['incomplete_details']['reason'])) {
         $reason = $decoded_response['incomplete_details']['reason'];
         if ($content !== null) {
             $content .= sprintf(' (%s: %s)', __('Incomplete', 'gpt3-ai-content-generator'), $reason);
         } else {
            /* translators: %s: The reason why the response was incomplete. */
            return new WP_Error('openai_incomplete_response_logic', sprintf(__('Response incomplete due to: %s', 'gpt3-ai-content-generator'), $reason));
         }
    }

    if (isset($decoded_response['usage']) && is_array($decoded_response['usage'])) {
        $usage = [
            'input_tokens'  => $decoded_response['usage']['input_tokens'] ?? 0,
            'output_tokens' => $decoded_response['usage']['output_tokens'] ?? 0,
            'total_tokens'  => $decoded_response['usage']['total_tokens'] ?? 0,
            'provider_raw' => $decoded_response['usage'],
        ];
    }

    if ($content === null) {
         return new WP_Error('invalid_response_structure_openai_logic', __('Unexpected response structure from OpenAI Responses API.', 'gpt3-ai-content-generator'));
    }

    // openai_response_id will be added by the caller (OpenAIProviderStrategy::parse_chat_response)
    return ['content' => $content, 'usage' => $usage];
}
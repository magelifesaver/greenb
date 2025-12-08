<?php

namespace WPAICG\Core\Providers\Traits; // *** Correct namespace ***

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Trait for parsing responses from OpenAI Chat Completions compatible APIs.
 * Used by OpenAI (v1/chat/completions), Azure, and OpenRouter strategies.
 * NOTE: The OpenAI strategy uses the Responses API (v1/responses) which has a different structure,
 * so it will override this trait's parse_chat_response method.
 */
trait ChatCompletionsResponseParserTrait {

    /**
     * Parses a standard Chat Completions API response.
     *
     * @param array $decoded_response The decoded JSON response.
     * @param array $request_data     The original request data (unused here but part of interface).
     * @return array|WP_Error ['content' => string, 'usage' => array|null] or WP_Error.
     */
    public function parse_chat_response(array $decoded_response, array $request_data): array|WP_Error {
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
             // Check for specific errors like content filters before declaring invalid structure
             if (isset($decoded_response['choices'][0]['finish_reason']) && $decoded_response['choices'][0]['finish_reason'] === 'content_filter') {
                 return new WP_Error('content_filter', __('Response blocked due to content filtering.', 'gpt3-ai-content-generator'));
             }
            return new WP_Error('invalid_response_structure_chatcompletion', __('Unexpected response structure from Chat Completions API.', 'gpt3-ai-content-generator'));
        }

        // Extract usage (standard Chat Completion format)
        $usage = null;
        if (isset($decoded_response['usage']) && is_array($decoded_response['usage'])) {
            $usage = [
                'input_tokens'  => $decoded_response['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $decoded_response['usage']['completion_tokens'] ?? 0,
                'total_tokens'  => $decoded_response['usage']['total_tokens'] ?? 0,
                'provider_raw' => $decoded_response['usage'], // Include raw provider usage
            ];
        }

        return ['content' => $content, 'usage' => $usage];
    }
}
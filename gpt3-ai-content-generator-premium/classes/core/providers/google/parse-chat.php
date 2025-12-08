<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/google/parse-chat.php
// Status: MODIFIED

namespace WPAICG\Core\Providers\Google\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the parse_chat static method of GoogleResponseParser.
 * UPDATED: Extracts groundingMetadata.
 *
 * @param array $decoded_response The decoded JSON response.
 * @return array|WP_Error ['content' => string, 'usage' => array|null, 'grounding_metadata' => array|null] or WP_Error.
 */
function parse_chat_logic_for_response_parser(array $decoded_response): array|WP_Error
{
    $content = null;
    $usage = null;
    $grounding_metadata = null;

    if (!empty($decoded_response['promptFeedback']['blockReason'])) {
        $block_reason = $decoded_response['promptFeedback']['blockReason'];
        $safety_ratings = $decoded_response['promptFeedback']['safetyRatings'] ?? [];
        $details = array_map(fn ($r) => ($r['category'] ?? 'Unknown') . ': ' . ($r['probability'] ?? 'N/A'), $safety_ratings);
        /* translators: %1$s: The reason the request was blocked (e.g., SAFETY). %2$s: A comma-separated list of details. */
        $error_message = sprintf(__('Request blocked by Google due to: %1$s. Details: %2$s', 'gpt3-ai-content-generator'), $block_reason, implode(', ', $details));
        return new WP_Error('google_content_blocked_logic', $error_message);
    }

    if (isset($decoded_response['candidates'][0]['finishReason']) && $decoded_response['candidates'][0]['finishReason'] === 'SAFETY') {
        $reason = $decoded_response['promptFeedback']['blockReason'] ?? $decoded_response['candidates'][0]['safetyRatings'][0]['category'] ?? 'safety settings';
        /* translators: %s: The reason the response was filtered (e.g., 'SAFETY'). */
        return new WP_Error('google_content_filtered_logic', sprintf(__('Response filtered by Google due to: %s.', 'gpt3-ai-content-generator'), $reason));
    }

    if (!empty($decoded_response['candidates'][0]['content']['parts'][0]['text'])) {
        $content = trim($decoded_response['candidates'][0]['content']['parts'][0]['text']);
    }

    if (isset($decoded_response['candidates'][0]['groundingMetadata'])) {
        $grounding_metadata = $decoded_response['candidates'][0]['groundingMetadata'];
    }

    if (isset($decoded_response['usageMetadata']) && is_array($decoded_response['usageMetadata'])) {
        $usage = [
            'input_tokens'  => $decoded_response['usageMetadata']['promptTokenCount'] ?? 0,
            'output_tokens' => $decoded_response['usageMetadata']['candidatesTokenCount'] ?? 0,
            'total_tokens'  => $decoded_response['usageMetadata']['totalTokenCount'] ?? 0,
            'provider_raw' => $decoded_response['usageMetadata'],
        ];
    }

    if ($content === null && isset($decoded_response['candidates'][0]['finishReason']) && $decoded_response['candidates'][0]['finishReason'] !== 'STOP') {
        /* translators: %s: The reason the AI stopped generating content (e.g., 'MAX_TOKENS'). */
        return new WP_Error('google_no_content_logic', sprintf(__('No content returned from Google. Finish reason: %s', 'gpt3-ai-content-generator'), $decoded_response['candidates'][0]['finishReason']));
    } elseif ($content === null) {
        return new WP_Error('invalid_response_structure_google_logic', __('Unexpected response structure from Google API.', 'gpt3-ai-content-generator'));
    }

    $return_data = ['content' => $content, 'usage' => $usage];
    if ($grounding_metadata !== null) {
        $return_data['grounding_metadata'] = $grounding_metadata;
    }
    return $return_data;
}

<?php

// File: classes/core/providers/openai/moderate-text.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAIProviderStrategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WPAICG\Core\Providers\OpenAI\OpenAIPayloadFormatter;
use WPAICG\Core\Providers\OpenAI\OpenAIResponseParser;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the moderate_text method of OpenAIProviderStrategy.
 *
 * @param OpenAIProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $text The input text to moderate.
 * @param array $api_params API connection parameters.
 * @return bool|WP_Error True if flagged, false if not, WP_Error on API error.
 */
function moderate_text_logic(OpenAIProviderStrategy $strategyInstance, string $text, array $api_params): bool|WP_Error
{
    // URL Builder requires base_url and api_version to be passed from $api_params
    $url_builder_params = [
        'base_url' => $api_params['base_url'] ?? 'https://api.openai.com',
        'api_version' => $api_params['api_version'] ?? 'v1',
    ];
    $url = OpenAIUrlBuilder::build('moderation', $url_builder_params);
    if (is_wp_error($url)) {
        return $url;
    }

    $headers = $strategyInstance->get_api_headers($api_params['api_key'], 'moderation');
    $options = $strategyInstance->get_request_options('moderation');
    $payload_data = OpenAIPayloadFormatter::format_moderation($text);
    $payload_json = wp_json_encode($payload_data);

    $response = wp_remote_post($url, array_merge($options, ['headers' => $headers, 'body' => $payload_json]));

    if (is_wp_error($response)) {
        return new WP_Error('moderation_http_request_failed_logic', __('Moderation check failed (HTTP).', 'gpt3-ai-content-generator'));
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($status_code >= 400) {
        $error_message = OpenAIResponseParser::parse_error($response_body, $status_code);
        /* translators: %1$d: HTTP status code, %2$s: Error message from the API. */
        return new WP_Error('moderation_api_error_logic', sprintf(__('Moderation check failed (API %1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, esc_html($error_message)));
    }

    $decoded = $strategyInstance->decode_json_public($response_body, 'OpenAI Moderation'); // Call public wrapper
    if (is_wp_error($decoded)) {
        return $decoded;
    }

    $is_flagged = OpenAIResponseParser::parse_moderation($decoded);

    if ($is_flagged) {
        $flagged_categories = [];
        if (isset($decoded['results'][0]['categories'])) {
            foreach ($decoded['results'][0]['categories'] as $category => $flagged_status) {
                if ($flagged_status === true) {
                    $flagged_categories[] = $category;
                }
            }
        }
    }
    return $is_flagged;
}

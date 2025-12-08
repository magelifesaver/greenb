<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/generate-embeddings.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAIProviderStrategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder; // Use the new UrlBuilder
use WPAICG\Core\Providers\OpenAI\OpenAIPayloadFormatter; // Use the new Formatter
use WPAICG\Core\Providers\OpenAI\OpenAIResponseParser; // Use the new Parser
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the generate_embeddings method of OpenAIProviderStrategy.
 *
 * @param OpenAIProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string|array $input The input text or array of texts.
 * @param array $api_params Provider-specific API connection parameters.
 * @param array $options Embedding options (model, dimensions, encoding_format, user).
 * @return array|WP_Error An array of embedding vectors or WP_Error on failure.
 */
function generate_embeddings_logic(
    OpenAIProviderStrategy $strategyInstance,
    $input,
    array $api_params,
    array $options = []
): array|WP_Error {
    // URL Builder requires base_url and api_version to be passed from $api_params
    $url_builder_params = [
        'base_url' => $api_params['base_url'] ?? 'https://api.openai.com',
        'api_version' => $api_params['api_version'] ?? 'v1',
    ];
    $url = OpenAIUrlBuilder::build('embeddings', $url_builder_params);
    if (is_wp_error($url)) {
        return $url;
    }

    $headers = $strategyInstance->get_api_headers($api_params['api_key'], 'embeddings');
    $request_options = $strategyInstance->get_request_options('embeddings');
    $payload = OpenAIPayloadFormatter::format_embeddings($input, $options);
    $request_body_json = wp_json_encode($payload);

    $response = wp_remote_post($url, array_merge($request_options, ['headers' => $headers, 'body' => $request_body_json]));

    if (is_wp_error($response)) {
        return new WP_Error('openai_embedding_http_error_logic', __('HTTP error during embedding generation.', 'gpt3-ai-content-generator'));
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $decoded_response = $strategyInstance->decode_json_public($body, 'OpenAI Embeddings'); // Call public wrapper

    if ($status_code !== 200 || is_wp_error($decoded_response)) {
        $error_msg = is_wp_error($decoded_response)
                    ? $decoded_response->get_error_message()
                    : OpenAIResponseParser::parse_error($body, $status_code);
        /* translators: %1$d: HTTP status code, %2$s: Error message from the API. */
        return new WP_Error('openai_embedding_api_error_logic', sprintf(__('OpenAI Embeddings API Error (%1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, esc_html($error_msg)));
    }
    return OpenAIResponseParser::parse_embeddings($decoded_response);
}
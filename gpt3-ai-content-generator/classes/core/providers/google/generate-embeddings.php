<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/google/generate-embeddings.php
// Status: MODIFIED

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\Core\Providers\GoogleProviderStrategy; 
use WPAICG\Core\Providers\Google\GoogleUrlBuilder;
use WPAICG\Core\Providers\Google\GooglePayloadFormatter;
use WPAICG\Core\Providers\Google\GoogleResponseParser;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the generate_embeddings method of GoogleProviderStrategy.
 *
 * @param GoogleProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string|array $input The input text or array of texts.
 * @param array $api_params Provider-specific API connection parameters.
 * @param array $options Embedding options (model, taskType, outputDimensionality).
 * @return array|WP_Error An array of embedding vectors or WP_Error on failure.
 */
function generate_embeddings_logic(
    GoogleProviderStrategy $strategyInstance,
    $input,
    array $api_params,
    array $options = []
): array|WP_Error {
    if (!class_exists(GoogleUrlBuilder::class) || !class_exists(GooglePayloadFormatter::class) || !class_exists(GoogleResponseParser::class)) {
        return new WP_Error('google_embedding_dependency_missing_logic', __('Google embedding components are missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
    }

    $model_id = $options['model'] ?? '';
    if (empty($model_id)) {
        return new WP_Error('missing_google_embedding_model_logic', __('Google embedding model ID is required.', 'gpt3-ai-content-generator'));
    }

    $url_params = array_merge($api_params, ['model' => $model_id]);
    $url = GoogleUrlBuilder::build('embedContent', $url_params);
    if (is_wp_error($url)) {
        return $url;
    }

    $headers = $strategyInstance->get_api_headers($api_params['api_key'], 'embedContent');
    $request_options = $strategyInstance->get_request_options('embedContent');
    $payload = GooglePayloadFormatter::format_embeddings($input, $options);
    $request_body_json = wp_json_encode($payload);

    $response = wp_remote_post($url, array_merge($request_options, ['headers' => $headers, 'body' => $request_body_json]));

    if (is_wp_error($response)) {
        return new WP_Error('google_embedding_http_error_logic', __('HTTP error during embedding generation.', 'gpt3-ai-content-generator'));
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $decoded_response = $strategyInstance->decode_json($body, 'Google Embeddings'); 

    if ($status_code !== 200 || is_wp_error($decoded_response)) {
        $error_msg = is_wp_error($decoded_response)
                    ? $decoded_response->get_error_message()
                    : GoogleResponseParser::parse_error($body, $status_code);
        /* translators: %1$d: HTTP status code, %2$s: API error message. */
        return new WP_Error('google_embedding_api_error_logic', sprintf(__('Google Embeddings API Error (%1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, esc_html($error_msg)));
    }
    return GoogleResponseParser::parse_embeddings($decoded_response);
}
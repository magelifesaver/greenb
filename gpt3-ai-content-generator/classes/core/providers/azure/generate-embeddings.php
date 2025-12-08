<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/azure/generate-embeddings.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Azure\Methods;

use WPAICG\Core\Providers\AzureProviderStrategy;
use WPAICG\Core\Providers\Azure\AzureUrlBuilder;
use WPAICG\Core\Providers\Azure\AzurePayloadFormatter;
use WPAICG\Core\Providers\Azure\AzureResponseParser;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the generate_embeddings method of AzureProviderStrategy.
 *
 * @param AzureProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string|array $input The input text or array of texts.
 * @param array $api_params Provider-specific API connection parameters.
 * @param array $options Embedding options (model - deployment ID, dimensions, user).
 * @return array|WP_Error An array of embedding vectors or WP_Error on failure.
 */
function generate_embeddings_logic(
    AzureProviderStrategy $strategyInstance,
    $input,
    array $api_params,
    array $options = []
): array|WP_Error {
    if (!class_exists(AzureUrlBuilder::class) || !class_exists(AzurePayloadFormatter::class) || !class_exists(AzureResponseParser::class)) {
        return new WP_Error('azure_embedding_dependency_missing_logic', __('Azure embedding components are missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
    }

    $deployment_id = $options['model'] ?? ''; // For Azure, 'model' in options is the deployment ID
    if (empty($deployment_id)) {
        return new WP_Error('missing_azure_embedding_deployment_logic', __('Azure embedding deployment ID (model) is required.', 'gpt3-ai-content-generator'));
    }

    // Add deployment_id to $api_params for the URL builder
    $url_params = array_merge($api_params, ['deployment' => $deployment_id]);
    $url = AzureUrlBuilder::build('embeddings', $url_params);
    if (is_wp_error($url)) {
        return $url;
    }

    $headers = $strategyInstance->get_api_headers($api_params['api_key'], 'embeddings');
    $request_options = $strategyInstance->get_request_options('embeddings');
    $payload = AzurePayloadFormatter::format_embeddings($input, $options);
    $request_body_json = wp_json_encode($payload);

    $response = wp_remote_post($url, array_merge($request_options, ['headers' => $headers, 'body' => $request_body_json]));

    if (is_wp_error($response)) {
        return new WP_Error('azure_embedding_http_error_logic', __('HTTP error during Azure embedding generation.', 'gpt3-ai-content-generator'));
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    $decoded_response = $strategyInstance->decode_json_public($body, 'Azure Embeddings'); // Call public wrapper

    if ($status_code !== 200 || is_wp_error($decoded_response)) {
        $error_msg = is_wp_error($decoded_response)
                    ? $decoded_response->get_error_message()
                    : AzureResponseParser::parse_error($body, $status_code); // Call static method
        /* translators: %1$d: HTTP status code, %2$s: API error message. */
        return new WP_Error('azure_embedding_api_error_logic', sprintf(__('Azure Embeddings API Error (%1$d): %2$s', 'gpt3-ai-content-generator'), $status_code, esc_html($error_msg)));
    }

    return AzureResponseParser::parse_embeddings($decoded_response); // Call static method
}
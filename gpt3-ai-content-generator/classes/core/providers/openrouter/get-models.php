<?php
// File: classes/core/providers/openrouter/get-models.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WPAICG\Core\Providers\OpenRouterProviderStrategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_models method of OpenRouterProviderStrategy.
 *
 * @param OpenRouterProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $api_params Connection parameters (api_key, base_url, etc.).
 * @return array|WP_Error Formatted list [['id' => ..., 'name' => ...]] or WP_Error.
 */
function get_models_logic(OpenRouterProviderStrategy $strategyInstance, array $api_params): array|WP_Error {
    $url = $strategyInstance->build_api_url('models', $api_params);
    if (is_wp_error($url)) {
        return $url;
    }

    $headers = $strategyInstance->get_api_headers($api_params['api_key'] ?? '', 'models');
    $options = $strategyInstance->get_request_options('models');
    $options['method'] = 'GET';

    $response = wp_remote_get($url, array_merge($options, ['headers' => $headers]));
    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($status_code !== 200) {
        $error_msg = $strategyInstance->parse_error_response($body, $status_code);
        return new WP_Error('api_error_openrouter_models_logic', sprintf('OpenRouter API Error (HTTP %d): %s', $status_code, esc_html($error_msg)));
    }

    // decode_json is public in BaseProviderStrategy
    $decoded = $strategyInstance->decode_json($body, 'OpenRouter Models');
    if (is_wp_error($decoded)) {
        return $decoded;
    }

    $raw_models = $decoded['data'] ?? [];
    // format_model_list is public in BaseProviderStrategy
    return $strategyInstance->format_model_list($raw_models, 'id', 'name');
}
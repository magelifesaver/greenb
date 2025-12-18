<?php
// File: classes/core/providers/google/get-models.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\Core\Providers\GoogleProviderStrategy; 
use WPAICG\Core\Providers\Google\GoogleUrlBuilder;
use WPAICG\Core\Providers\Google\GoogleResponseParser;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_models method of GoogleProviderStrategy.
 *
 * @param GoogleProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $api_params Connection parameters (api_key, base_url, etc.).
 * @return array|WP_Error Formatted list [['id' => ..., 'name' => ...]] or WP_Error.
 */
function get_models_logic(GoogleProviderStrategy $strategyInstance, array $api_params): array|WP_Error {
    if (!class_exists(GoogleUrlBuilder::class) || !class_exists(GoogleResponseParser::class)) {
        return new WP_Error('google_dependency_missing_models_logic', __('Google components for model listing are missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
    }

    $all_results = [];
    $next_page_token = null;
    $page_size = 100;

    do {
        $params_for_page = $api_params;
        $params_for_page['pageSize'] = $page_size;
        if ($next_page_token) $params_for_page['pageToken'] = $next_page_token;

        $url = GoogleUrlBuilder::build('models', $params_for_page);
        if (is_wp_error($url)) return $url;

        $headers = $strategyInstance->get_api_headers($api_params['api_key'], 'models');
        $options = $strategyInstance->get_request_options('models');
        $options['method'] = 'GET';

        $response = wp_remote_get($url, array_merge($options, ['headers' => $headers]));
        if (is_wp_error($response)) return $response;

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            $error_msg = GoogleResponseParser::parse_error($body, $status_code);
            return new WP_Error('api_error_google_models_logic', sprintf('Google API Error (HTTP %d): %s', $status_code, esc_html($error_msg)));
        }

        $decoded = $strategyInstance->decode_json($body, 'Google Models');
        if (is_wp_error($decoded)) return $decoded;

        $raw_models = $decoded['models'] ?? [];
        $formatted_page = format_google_model_list_logic($strategyInstance, $raw_models); // Call the namespaced function
        $all_results = array_merge($all_results, $formatted_page);

        $next_page_token = $decoded['nextPageToken'] ?? null;

    } while (!empty($next_page_token));

    usort($all_results, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
    return $all_results;
}
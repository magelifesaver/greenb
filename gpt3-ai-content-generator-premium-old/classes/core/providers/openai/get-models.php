<?php
// File: classes/core/providers/openai/get-models.php
// Status: MODIFIED

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAIProviderStrategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder;
use WPAICG\Core\AIPKit_Models_API; // Need this for grouping
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_models method of OpenAIProviderStrategy.
 *
 * @param OpenAIProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $api_params Connection parameters (api_key, base_url, etc.).
 * @return array|WP_Error Formatted list [['id' => ..., 'name' => ...]] or WP_Error.
 */
function get_models_logic(OpenAIProviderStrategy $strategyInstance, array $api_params): array|WP_Error {
    // URL Builder requires base_url and api_version to be passed from $api_params
    $url_builder_params = [
        'base_url' => $api_params['base_url'] ?? 'https://api.openai.com',
        'api_version' => $api_params['api_version'] ?? 'v1',
    ];
    $url = OpenAIUrlBuilder::build('models', $url_builder_params);
    if (is_wp_error($url)) {
        return $url;
    }

    $headers = $strategyInstance->get_api_headers($api_params['api_key'], 'models'); // Call instance method
    $options = $strategyInstance->get_request_options('models'); // Call instance method
    $options['method'] = 'GET'; // Override method for GET request

    $response = wp_remote_get($url, array_merge($options, ['headers' => $headers]));
    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($status_code !== 200) {
        $error_msg = $strategyInstance->parse_error_response($body, $status_code); // Call instance method
        return new WP_Error('api_error_openai_models_logic', sprintf('OpenAI API Error (HTTP %d): %s', $status_code, esc_html($error_msg)));
    }

    $decoded = $strategyInstance->decode_json_public($body, 'OpenAI Models'); // Call public wrapper on instance
    if (is_wp_error($decoded)) {
        return $decoded;
    }

    $raw_models = $decoded['data'] ?? [];

    // Ensure AIPKit_Models_API class is available (though not used for grouping here anymore, it's a good check)
    if (!class_exists(\WPAICG\Core\AIPKit_Models_API::class)) {
         $models_api_path = WPAICG_PLUGIN_DIR . 'classes/core/models_api.php';
         if(file_exists($models_api_path)) { require_once $models_api_path; }
         else {
             // Fallback to base formatter if AIPKit_Models_API is critical for some reason (it's not for this function's direct output)
             return $strategyInstance->format_model_list($raw_models);
         }
    }
    // Return the flat, formatted list. Grouping will be handled by the AJAX handler.
    return $strategyInstance->format_model_list($raw_models);
}
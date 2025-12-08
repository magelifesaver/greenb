<?php
// File: classes/core/providers/azure/get-models.php
// Status: MODIFIED

namespace WPAICG\Core\Providers\Azure\Methods;

use WPAICG\Core\Providers\AzureProviderStrategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_models method of AzureProviderStrategy.
 * Fetches Azure OpenAI deployments.
 *
 * @param AzureProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $api_params Connection parameters (api_key, azure_endpoint, azure_authoring_version).
 * @return array|WP_Error Formatted list [['id' => ..., 'name' => ...]] or WP_Error.
 */
function get_models_logic(AzureProviderStrategy $strategyInstance, array $api_params): array|WP_Error {
    $url = $strategyInstance->build_api_url('deployments', $api_params);
    if (is_wp_error($url)) {
        return $url;
    }

    $headers = $strategyInstance->get_api_headers($api_params['api_key'], 'deployments');
    $options = $strategyInstance->get_request_options('models'); // 'models' operation type for general request options
    $options['method'] = 'GET'; // Override method

    $response = wp_remote_get($url, array_merge($options, ['headers' => $headers]));
    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($status_code !== 200) {
        $error_msg = $strategyInstance->parse_error_response($body, $status_code);
        return new WP_Error('api_error_azure_deployments_logic', sprintf('Azure API Error (HTTP %d): %s', $status_code, esc_html($error_msg)));
    }

    $decoded = $strategyInstance->decode_json_public($body, 'Azure Deployments'); // Call public wrapper
    if (is_wp_error($decoded)) {
        return $decoded;
    }

    $raw_deployments = $decoded['data'] ?? [];
    $formatted = [];
    foreach ($raw_deployments as $dep) {
        $dep_id   = $dep['id'] ?? null; // Deployment name is the 'id' for Azure
        $model_name = $dep['model'] ?? null; // Underlying model name
        $status = $dep['status'] ?? '';
        if (!empty($dep_id) && $status === 'succeeded') { // Only include succeeded deployments
            $display_name = $dep_id;
            if ($model_name && $model_name !== $dep_id) {
                $display_name .= " ({$model_name})";
            }
            $formatted[] = [
                'id'      => $dep_id,
                'name'    => $display_name,
                'status'  => $status,
                'model'   => $model_name // Keep original model name for filtering
            ];
        }
    }
    usort($formatted, fn($a, $b) => strcmp($a['id'] ?? '', $b['id'] ?? ''));
    return $formatted;
}
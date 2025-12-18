<?php
// File: classes/core/providers/azure/build.php
// Status: MODIFIED

namespace WPAICG\Core\Providers\Azure\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build static method of AzureUrlBuilder.
 *
 * @param string $operation ('chat', 'stream', 'deployments', 'models', 'embeddings')
 * @param array  $params Required parameters (azure_endpoint, api_version_authoring, api_version_inference, deployment)
 * @return string|WP_Error The full URL or WP_Error.
 */
function build_logic_for_url_builder(string $operation, array $params): string|WP_Error {
    $azure_endpoint = !empty($params['azure_endpoint']) ? rtrim($params['azure_endpoint'], '/') : '';
    $deployment_name = !empty($params['deployment']) ? $params['deployment'] : '';

    if (empty($azure_endpoint)) return new WP_Error('missing_azure_endpoint_logic', __('Azure endpoint is required.', 'gpt3-ai-content-generator'));

    $api_version = '';
    if ($operation === 'deployments' || $operation === 'models') {
        $api_version = $params['azure_authoring_version'] ?? '2023-03-15-preview';
    } else {
        $api_version = $params['azure_inference_version'] ?? '2024-02-01'; // Default to inference for chat/stream/embeddings
    }

    if (empty($api_version)) return new WP_Error('missing_azure_api_version_logic', __('Azure API Version is required.', 'gpt3-ai-content-generator'));

    $paths = [
        'chat'        => '/chat/completions',
        'deployments' => '/openai/deployments',
        'models'      => '/openai/models',
        'embeddings'  => '/embeddings',
    ];
    $path_key = ($operation === 'stream') ? 'chat' : $operation;
    $path_segment = $paths[$path_key] ?? null;

    if ($path_segment === null) {
        /* translators: %s: The name of the API operation (e.g., 'chat', 'embeddings'). */
        return new WP_Error('unsupported_operation_Azure_logic', sprintf(__('Operation "%s" not supported for Azure.', 'gpt3-ai-content-generator'), $operation));
    }

    $query_param = '?api-version=' . urlencode($api_version);

    if ($operation === 'deployments' || $operation === 'models') {
        return $azure_endpoint . $path_segment . $query_param;
    } elseif ($operation === 'chat' || $operation === 'stream' || $operation === 'embeddings') {
        if (empty($deployment_name)) return new WP_Error('missing_azure_deployment_logic', __('Azure deployment name is required for this operation.', 'gpt3-ai-content-generator'));
        return $azure_endpoint . '/openai/deployments/' . urlencode($deployment_name) . $path_segment . $query_param;
    } else {
        /* translators: %s: The name of the API operation. */
        return new WP_Error('unhandled_azure_operation_logic', sprintf(__('Unhandled Azure operation path building for: %s', 'gpt3-ai-content-generator'), $operation));
    }
}
<?php
// File: classes/core/providers/azure/build-api-url.php

namespace WPAICG\Core\Providers\Azure\Methods;

use WPAICG\Core\Providers\AzureProviderStrategy;
use WPAICG\Core\Providers\Azure\AzureUrlBuilder; // For constants if any, or direct call
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build_api_url method of AzureProviderStrategy.
 *
 * @param AzureProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $operation ('chat', 'stream', 'deployments', 'models', 'embeddings')
 * @param array  $params Required parameters (azure_endpoint, api_version_authoring, api_version_inference, deployment)
 * @return string|WP_Error The full URL or WP_Error.
 */
function build_api_url_logic(AzureProviderStrategy $strategyInstance, string $operation, array $params): string|WP_Error {
    // This method in AzureProviderStrategy directly calls AzureUrlBuilder::build.
    // So, the logic here is to ensure AzureUrlBuilder is available and call its static method.
    if (!class_exists(\WPAICG\Core\Providers\Azure\AzureUrlBuilder::class)) {
        // Attempt to load it if not already - though ProviderDependenciesLoader should handle this.
        $url_builder_bootstrap = __DIR__ . '/bootstrap-url-builder.php';
        if (file_exists($url_builder_bootstrap)) {
            require_once $url_builder_bootstrap;
        } else {
            return new WP_Error('azure_url_builder_missing', 'Azure URL builder component is not available.');
        }
    }
    return \WPAICG\Core\Providers\Azure\AzureUrlBuilder::build($operation, $params);
}
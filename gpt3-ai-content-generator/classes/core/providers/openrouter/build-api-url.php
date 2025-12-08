<?php
// File: classes/core/providers/openrouter/build-api-url.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WPAICG\Core\Providers\OpenRouterProviderStrategy;
use WPAICG\Core\Providers\OpenRouter\OpenRouterUrlBuilder; // For direct call
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build_api_url method of OpenRouterProviderStrategy.
 *
 * @param OpenRouterProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $operation ('chat', 'models', 'stream')
 * @param array  $params Required parameters (base_url, api_version).
 * @return string|WP_Error The full URL or WP_Error.
 */
function build_api_url_logic(OpenRouterProviderStrategy $strategyInstance, string $operation, array $params): string|WP_Error {
    // Ensure OpenRouterUrlBuilder is available
    if (!class_exists(\WPAICG\Core\Providers\OpenRouter\OpenRouterUrlBuilder::class)) {
        $url_builder_bootstrap = dirname(__FILE__) . '/bootstrap-url-builder.php';
        if (file_exists($url_builder_bootstrap)) {
            require_once $url_builder_bootstrap;
        } else {
            return new WP_Error('openrouter_url_builder_missing_logic', 'OpenRouter URL builder component is not available.');
        }
    }
    return \WPAICG\Core\Providers\OpenRouter\OpenRouterUrlBuilder::build($operation, $params);
}
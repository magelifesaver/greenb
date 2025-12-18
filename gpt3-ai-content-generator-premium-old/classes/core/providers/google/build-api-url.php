<?php
// File: classes/core/providers/google/build-api-url.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\Core\Providers\GoogleProviderStrategy; 
use WPAICG\Core\Providers\Google\GoogleUrlBuilder; 
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build_api_url method of GoogleProviderStrategy.
 *
 * @param GoogleProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $operation ('chat', 'models', 'stream', 'embedContent')
 * @param array  $params Required parameters (api_key, base_url, api_version, model) and optional (pageSize, pageToken).
 * @return string|WP_Error The full URL or WP_Error.
 */
function build_api_url_logic(GoogleProviderStrategy $strategyInstance, string $operation, array $params): string|WP_Error {
    if (!class_exists(\WPAICG\Core\Providers\Google\GoogleUrlBuilder::class)) {
        $url_builder_bootstrap = dirname(__FILE__) . '/bootstrap-url-builder.php';
        if (file_exists($url_builder_bootstrap)) {
            require_once $url_builder_bootstrap;
        } else {
            return new WP_Error('google_url_builder_missing_logic', 'Google URL builder component is not available.');
        }
    }
    return \WPAICG\Core\Providers\Google\GoogleUrlBuilder::build($operation, $params);
}
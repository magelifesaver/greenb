<?php
// File: classes/core/providers/openrouter/bootstrap-url-builder.php
// Status: MODIFIED
// Was: classes/core/providers/openrouter/OpenRouterUrlBuilder.php

namespace WPAICG\Core\Providers\OpenRouter;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the method logic file
require_once __DIR__ . '/build.php';

/**
 * Handles building API URLs specific to the OpenRouter provider (Modularized).
 * Original logic for static methods is now in separate files within the 'Methods' namespace.
 */
class OpenRouterUrlBuilder {

    /**
     * Build the full API endpoint URL for a given OpenRouter operation.
     *
     * @param string $operation ('chat', 'models', 'stream')
     * @param array  $params Required parameters (base_url, api_version).
     * @return string|WP_Error The full URL or WP_Error.
     */
    public static function build(string $operation, array $params): string|WP_Error {
        // The namespaced function should be in WPAICG\Core\Providers\OpenRouter\Methods
        return \WPAICG\Core\Providers\OpenRouter\Methods\build_logic_for_url_builder($operation, $params);
    }
}
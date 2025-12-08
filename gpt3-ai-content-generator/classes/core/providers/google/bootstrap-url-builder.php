<?php
// File: classes/core/providers/google/bootstrap-url-builder.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the method logic file
require_once __DIR__ . '/build.php';

/**
 * Handles building API URLs specific to the Google Gemini provider (Modularized).
 * Original logic for static methods is now in separate files within the 'Methods' namespace.
 */
class GoogleUrlBuilder {

    public static function build(string $operation, array $params): string|WP_Error {
        return \WPAICG\Core\Providers\Google\Methods\build_logic_for_url_builder($operation, $params);
    }
}
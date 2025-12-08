<?php
// File: classes/core/providers/azure/bootstrap-url-builder.php

namespace WPAICG\Core\Providers\Azure;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the method logic file
require_once __DIR__ . '/build.php';

/**
 * Handles building API URLs specific to the Azure OpenAI provider.
 * Original logic for static methods is now in separate files within the 'Methods' namespace.
 */
class AzureUrlBuilder {

    public static function build(string $operation, array $params): string|WP_Error {
        return \WPAICG\Core\Providers\Azure\Methods\build_logic_for_url_builder($operation, $params);
    }
}
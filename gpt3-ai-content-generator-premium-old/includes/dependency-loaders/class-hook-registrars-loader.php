<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-hook-registrars-loader.php
// Status: NEW FILE

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Hook_Registrars_Loader
 * Handles loading all the hook registrar classes.
 */
class Hook_Registrars_Loader {

    public static function load() {
        $registrars_path = WPAICG_PLUGIN_DIR . 'includes/hook-registrars/';

        $registrar_files = [
            'class-core-hooks-registrar.php',
            'class-admin-asset-hooks-registrar.php',
            'class-ajax-hooks-registrar.php',
            'class-rest-api-hooks-registrar.php',
            'class-module-initializer-hooks-registrar.php',
            // Add any other registrar files here
        ];

        foreach ($registrar_files as $file) {
            $full_path = $registrars_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
    }
}
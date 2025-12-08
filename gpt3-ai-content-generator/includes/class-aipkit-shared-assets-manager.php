<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/class-aipkit-shared-assets-manager.php
// Status: MODIFIED

namespace WPAICG\Includes;

// Ensure this file is only loaded by WordPress
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Shared_Assets_Manager
 * Handles registering scripts shared across admin and public contexts.
 * REVISED: Only registers vendor scripts. Core utils are now part of main bundles.
 * MODIFIED: jsPDF registration moved to lib/wpaicg__premium_only.php.
 */
class AIPKit_Shared_Assets_Manager
{
    /**
     * Register scripts shared across admin and public contexts.
     *
     * @param string $plugin_version The current plugin version.
     */
    public static function register(string $plugin_version)
    {
        // Vendor JS files are copied to dist/vendor/js/ by esbuild
        $vendor_js_url = WPAICG_PLUGIN_URL . 'dist/vendor/js/';

        // Markdown-it (copied by esbuild)
        $markdownit_url = $vendor_js_url . 'markdown-it.min.js';
        if (!wp_script_is('aipkit_markdown-it', 'registered')) {
            wp_register_script('aipkit_markdown-it', $markdownit_url, [], '14.1.0', true); // Assuming version from previous config
        }

        // --- jsPDF registration has been moved to lib/wpaicg__premium_only.php ---

        // Note: Core utility scripts like btn-utils, html-escaper, date-utils
        // are now imported directly into admin-main.js or public-main.js and bundled.
        // They are no longer registered as separate handles here.
    }
}

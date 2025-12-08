<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/assets/class-assets-dependency-registrar.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\Assets;

// REMOVED Unused use statements for sub-registrars as they are no longer needed here.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the registration of all public chat JavaScript dependencies.
 */
class AssetsDependencyRegistrar
{
    public static function register(): void
    {
        $version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
        $plugin_base_url = defined('WPAICG_PLUGIN_URL') ? WPAICG_PLUGIN_URL : plugin_dir_url(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        $dist_js_url = $plugin_base_url . 'dist/js/';
        $markdownit_handle = 'aipkit_markdown-it';
        // $jspdf_handle = 'aipkit_jspdf'; // This handle is registered conditionally in lib/wpaicg__premium_only.php

        // Main public bundle (contains all chat frontend JS)
        // Dependencies like wp-i18n, markdown-it are for this main bundle.
        // aipkit_jspdf is now a *conditional* dependency added by lib/wpaicg__premium_only.php
        $public_main_js_handle = 'aipkit-public-main';
        if (!wp_script_is($public_main_js_handle, 'registered')) {
            wp_register_script(
                $public_main_js_handle,
                $dist_js_url . 'public-main.bundle.js',
                ['wp-i18n', $markdownit_handle], // REMOVED $jspdf_handle from here
                $version,
                true
            );
        }
    }
}

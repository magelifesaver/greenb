<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/admin/assets/class-aipkit-content-writer-pro-assets.php
// Status: NEW FILE

namespace WPAICG\Lib\Admin\Assets;

use WPAICG\Admin\Assets\DashboardAssets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing Pro assets for the Content Writer and AutoGPT (Content Writing) modules.
 */
class AIPKit_Content_Writer_Pro_Assets
{
    private $version;

    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
    }

    public function register_hooks(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets($hook_suffix): void
    {
        $screen = get_current_screen();
        $is_aipkit_page = $screen && (strpos($screen->id, 'page_wpaicg') !== false || strpos($screen->id, 'toplevel_page_wpaicg') !== false);
        if (!$is_aipkit_page) {
            return;
        }

        // lib-main.js contains pro features for both Content Writer and AutoGPT URL/GSheets modes.
        $admin_main_js_handle = 'aipkit-admin-main';
        if (!wp_script_is($admin_main_js_handle, 'enqueued')) {
            wp_enqueue_script($admin_main_js_handle);
        }

        $lib_main_js_handle = 'aipkit-lib-main';
        $dist_lib_js_url = WPAICG_PLUGIN_URL . 'dist/js/';

        if (!wp_script_is($lib_main_js_handle, 'registered')) {
            wp_register_script(
                $lib_main_js_handle,
                $dist_lib_js_url . 'lib-main.bundle.js',
                ['wp-i18n', $admin_main_js_handle, 'aipkit_markdown-it'],
                $this->version,
                true
            );
        }

        if (!wp_script_is($lib_main_js_handle, 'enqueued')) {
            wp_enqueue_script($lib_main_js_handle);
            wp_set_script_translations($lib_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
        }

        // Localize data for Pro Content Writer features
        static $localized = false;
        if (!$localized && wp_script_is($lib_main_js_handle, 'enqueued')) {
            wp_localize_script($lib_main_js_handle, 'aipkit_cw_pro_config', [
                'gsheets_text' => [
                    'verifying' => __('Verifying...', 'gpt3-ai-content-generator'),
                    'accessible' => __('Accessible', 'gpt3-ai-content-generator'),
                    'error_prefix' => __('Error:', 'gpt3-ai-content-generator'),
                    'invalid_creds' => __('Invalid credentials or sheet not shared.', 'gpt3-ai-content-generator')
                ]
            ]);
            $localized = true;
        }
    }
}

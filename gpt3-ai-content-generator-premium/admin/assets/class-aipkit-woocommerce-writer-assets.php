<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/assets/class-aipkit-woocommerce-writer-assets.php
// Status: MODIFIED

namespace WPAICG\Admin\Assets;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets for the AIPKit WooCommerce Product Writer,
 * likely used within the Content Writer module.
 * MODIFIED: Calls DashboardAssets::localize_core_data() to ensure global JS object is available.
 */
class AIPKit_Woocommerce_Writer_Assets
{
    private $version;
    private $is_woo_writer_css_enqueued = false;
    private $is_admin_main_js_enqueued = false;


    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_aipkit_page = $screen && strpos($screen->id, 'page_wpaicg') !== false;

        $load_assets = false;
        if ($is_aipkit_page && class_exists('\WPAICG\aipkit_dashboard')) {
            $modules = \WPAICG\aipkit_dashboard::get_module_settings();
            if (!empty($modules['content_writer']) && class_exists('WooCommerce')) {
                $load_assets = true;
            }
        }

        if (!$load_assets) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();

        // Ensure core dashboard data is localized if admin-main.js was enqueued
        if ($this->is_admin_main_js_enqueued && class_exists(DashboardAssets::class) && method_exists(DashboardAssets::class, 'localize_core_data')) {
            DashboardAssets::localize_core_data($this->version);
        }
    }

    private function enqueue_styles()
    {
        $dist_css_url = WPAICG_PLUGIN_URL . 'dist/css/';
        $woo_writer_css_handle = 'aipkit-admin-woocommerce-writer-css';
        $admin_main_css_handle = 'aipkit-admin-main-css';

        if (!wp_style_is($woo_writer_css_handle, 'registered')) {
            wp_register_style(
                $woo_writer_css_handle,
                $dist_css_url . 'admin-woocommerce-writer.bundle.css',
                [$admin_main_css_handle],
                $this->version
            );
        }
        if (!$this->is_woo_writer_css_enqueued && !wp_style_is($woo_writer_css_handle, 'enqueued')) {
            wp_enqueue_style($woo_writer_css_handle);
            $this->is_woo_writer_css_enqueued = true;
        }
    }

    private function enqueue_scripts()
    {
        $admin_main_js_handle = 'aipkit-admin-main';
        $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';

        if (!wp_script_is($admin_main_js_handle, 'registered')) {
            wp_register_script(
                $admin_main_js_handle,
                $dist_js_url . 'admin-main.bundle.js',
                ['wp-i18n', 'aipkit_markdown-it'],
                $this->version,
                true
            );
        }
        if (!$this->is_admin_main_js_enqueued && !wp_script_is($admin_main_js_handle, 'enqueued')) {
            wp_enqueue_script($admin_main_js_handle);
            wp_set_script_translations($admin_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            $this->is_admin_main_js_enqueued = true;
        }
    }
}

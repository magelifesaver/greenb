<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/assets/class-aipkit-content-writer-assets.php
// Status: MODIFIED

namespace WPAICG\Admin\Assets;

use WPAICG\ContentWriter\AIPKit_Content_Writer_Prompts;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets for the AIPKit Content Writer module.
 * REVISED: Enqueues admin-main.bundle.js and admin-content-writer.bundle.css.
 *          Markdown-it registration moved to SharedAssetsManager.
 * MODIFIED: Calls DashboardAssets::localize_core_data() to ensure global JS object is available.
 */
class AIPKit_Content_Writer_Assets
{
    private $version;
    private $is_admin_main_js_enqueued = false;
    private $is_admin_content_writer_css_enqueued = false;
    private $is_admin_woocommerce_writer_css_enqueued = false;

    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_content_writer_assets']);
    }

    public function enqueue_content_writer_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_aipkit_page = $screen && strpos($screen->id, 'page_wpaicg') !== false;

        if (!$is_aipkit_page) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();

        // Ensure core dashboard data is localized if admin-main.js was enqueued
        if ($this->is_admin_main_js_enqueued && class_exists(DashboardAssets::class) && method_exists(DashboardAssets::class, 'localize_core_data')) {
            DashboardAssets::localize_core_data($this->version);
        }

        $this->localize_data();
    }

    private function enqueue_styles()
    {
        $dist_css_url = WPAICG_PLUGIN_URL . 'dist/css/';
        $cw_css_handle = 'aipkit-admin-content-writer-css';
        $woo_writer_css_handle = 'aipkit-admin-woocommerce-writer-css';
        $admin_main_css_handle = 'aipkit-admin-main-css';


        if (!wp_style_is($cw_css_handle, 'registered')) {
            wp_register_style(
                $cw_css_handle,
                $dist_css_url . 'admin-content-writer.bundle.css',
                [$admin_main_css_handle],
                $this->version
            );
        }
        if (!$this->is_admin_content_writer_css_enqueued && !wp_style_is($cw_css_handle, 'enqueued')) {
            wp_enqueue_style($cw_css_handle);
            $this->is_admin_content_writer_css_enqueued = true;
        }

        if (class_exists('WooCommerce')) {
            if (!wp_style_is($woo_writer_css_handle, 'registered')) {
                wp_register_style(
                    $woo_writer_css_handle,
                    $dist_css_url . 'admin-woocommerce-writer.bundle.css',
                    [$admin_main_css_handle],
                    $this->version
                );
            }
            if (!$this->is_admin_woocommerce_writer_css_enqueued && !wp_style_is($woo_writer_css_handle, 'enqueued')) {
                wp_enqueue_style($woo_writer_css_handle);
                $this->is_admin_woocommerce_writer_css_enqueued = true;
            }
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

    private function localize_data()
    {
        $admin_main_js_handle = 'aipkit-admin-main';
        if (wp_script_is($admin_main_js_handle, 'enqueued')) {
            $script_data_check = wp_scripts()->get_data($admin_main_js_handle, 'data');
            if (is_string($script_data_check) && strpos($script_data_check, 'var aipkit_content_writer_config =') !== false) {
                return; // Already localized
            }
            wp_localize_script($admin_main_js_handle, 'aipkit_content_writer_config', [
                'default_prompts' => [
                    'title' => AIPKit_Content_Writer_Prompts::get_default_title_prompt(),
                    'content' => AIPKit_Content_Writer_Prompts::get_default_content_prompt(),
                    'meta' => AIPKit_Content_Writer_Prompts::get_default_meta_prompt(),
                    'keyword' => AIPKit_Content_Writer_Prompts::get_default_keyword_prompt(),
                    'image' => AIPKit_Content_Writer_Prompts::get_default_image_prompt(),
                    'featured_image' => AIPKit_Content_Writer_Prompts::get_default_featured_image_prompt(),
                ]
            ]);
        }
    }
}

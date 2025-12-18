<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/assets/class-aipkit-role-manager-assets.php
// Status: MODIFIED

namespace WPAICG\Admin\Assets;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets (CSS/JS) and localization for the AIPKit Role Manager page.
 */
class RoleManagerAssets
{
    private $version;
    private $is_admin_main_js_enqueued = false;
    private $is_admin_main_css_enqueued = false;

    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.9.15';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_role_manager_assets']);
    }

    public function enqueue_role_manager_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_role_manager = $screen && strpos($screen->id, 'page_aipkit-role-manager') !== false;

        if (!$is_role_manager) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();
    }

    private function enqueue_styles()
    {
        $admin_main_css_handle = 'aipkit-admin-main-css';
        $dist_css_url = WPAICG_PLUGIN_URL . 'dist/css/';

        // Main admin CSS bundle contains role-manager.css via dashboard/index.css
        if (!wp_style_is($admin_main_css_handle, 'registered')) {
            wp_register_style(
                $admin_main_css_handle,
                $dist_css_url . 'admin-main.bundle.css',
                ['dashicons'],
                $this->version
            );
        }
        if (!$this->is_admin_main_css_enqueued && !wp_style_is($admin_main_css_handle, 'enqueued')) {
            wp_enqueue_style($admin_main_css_handle);
            $this->is_admin_main_css_enqueued = true;
        }
    }

    private function enqueue_scripts()
    {
        $admin_main_js_handle = 'aipkit-admin-main';
        $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';

        // aipkit_role_manager.js is part of admin-main.bundle.js
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
            $this->is_admin_main_js_enqueued = true;
        }

        // Localize data for Role Manager (attached to the main admin bundle)
        static $role_manager_localized = false;
        if (!$role_manager_localized) {
            $aipkit_role_manager_nonce = wp_create_nonce('aipkit_role_manager_nonce');
            wp_localize_script($admin_main_js_handle, 'aipkit_role_manager_config', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => $aipkit_role_manager_nonce,
                'text' => [
                     'saving' => __('Saving...', 'gpt3-ai-content-generator'),
                     'saveButton' => __('Save Permissions', 'gpt3-ai-content-generator'),
                     'success' => __('Permissions saved!', 'gpt3-ai-content-generator'),
                     'fail' => __('Failed to save permissions.', 'gpt3-ai-content-generator'),
                ]
            ]);
            $role_manager_localized = true;
        }
    }
}

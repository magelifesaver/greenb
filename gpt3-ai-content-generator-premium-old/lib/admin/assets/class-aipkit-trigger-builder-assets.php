<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/admin/assets/class-aipkit-trigger-builder-assets.php
// Status: MODIFIED

namespace WPAICG\Lib\Admin\Assets;

use WPAICG\aipkit_dashboard;
use WPAICG\Admin\Assets\DashboardAssets; // Added for localize_core_data

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles enqueueing assets for the AIPKit Chatbot Trigger Builder UI (Pro).
 */
class AIPKit_Trigger_Builder_Assets
{
    private $version;
    private $is_lib_main_js_enqueued = false;
    private $is_lib_triggers_admin_css_enqueued = false;
    private $is_admin_main_js_enqueued_by_triggers = false; // Track if admin-main was enqueued by this class

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
        if (!$is_aipkit_page) {
            return;
        }

        // Ensure main admin CSS is registered as a dependency
        $admin_main_css_handle = 'aipkit-admin-main-css';
        if (!wp_style_is($admin_main_css_handle, 'registered')) {
            // Fallback registration if DashboardAssets didn't register it
            wp_register_style(
                $admin_main_css_handle,
                WPAICG_PLUGIN_URL . 'dist/css/admin-main.bundle.css',
                ['dashicons'],
                $this->version
            );
        }
        // Enqueue main admin CSS if not already enqueued
        if (!wp_style_is($admin_main_css_handle, 'enqueued')) {
            wp_enqueue_style($admin_main_css_handle);
        }


        $dist_lib_css_url = WPAICG_PLUGIN_URL . 'dist/css/';
        $trigger_builder_css_handle = 'aipkit-lib-triggers-admin-css';

        if (!wp_style_is($trigger_builder_css_handle, 'registered')) {
            wp_register_style(
                $trigger_builder_css_handle,
                $dist_lib_css_url . 'lib-triggers-admin.bundle.css',
                [$admin_main_css_handle],
                $this->version
            );
        }
        if (!$this->is_lib_triggers_admin_css_enqueued && !wp_style_is($trigger_builder_css_handle, 'enqueued')) {
            wp_enqueue_style($trigger_builder_css_handle);
            $this->is_lib_triggers_admin_css_enqueued = true;
        }

        // Ensure main admin JS ('aipkit-admin-main') is registered as a dependency for 'aipkit-lib-main'
        $admin_main_js_handle = 'aipkit-admin-main';
        if (!wp_script_is($admin_main_js_handle, 'registered')) {
            // Fallback registration for admin-main if DashboardAssets hasn't run or missed it.
            wp_register_script(
                $admin_main_js_handle,
                WPAICG_PLUGIN_URL . 'dist/js/admin-main.bundle.js',
                ['wp-i18n', 'aipkit_markdown-it'],
                $this->version,
                true
            );
        }
        // Enqueue admin-main.js if not already enqueued by DashboardAssets
        if (!wp_script_is($admin_main_js_handle, 'enqueued')) {
            wp_enqueue_script($admin_main_js_handle);
            // If this class enqueues admin-main, it should also ensure core data is localized
            if (class_exists(DashboardAssets::class) && method_exists(DashboardAssets::class, 'localize_core_data')) {
                DashboardAssets::localize_core_data($this->version);
            }
            $this->is_admin_main_js_enqueued_by_triggers = true; // Track that this class enqueued it
        } elseif (!$this->is_admin_main_js_enqueued_by_triggers) {
            // If admin-main.js was already enqueued by DashboardAssets, ensure localization is still attempted
            // in case this hook runs before DashboardAssets' localization on some admin pages.
            if (class_exists(DashboardAssets::class) && method_exists(DashboardAssets::class, 'localize_core_data')) {
                DashboardAssets::localize_core_data($this->version);
            }
        }


        $lib_main_js_handle = 'aipkit-lib-main';
        $dist_lib_js_url = WPAICG_PLUGIN_URL . 'dist/js/';

        if (!wp_script_is($lib_main_js_handle, 'registered')) {
            wp_register_script(
                $lib_main_js_handle,
                $dist_lib_js_url . 'lib-main.bundle.js',
                ['wp-i18n', $admin_main_js_handle, 'aipkit_markdown-it'], // Depends on admin-main
                $this->version,
                true
            );
        }
        if (!$this->is_lib_main_js_enqueued && !wp_script_is($lib_main_js_handle, 'enqueued')) {
            wp_enqueue_script($lib_main_js_handle);
            wp_set_script_translations($lib_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            $this->is_lib_main_js_enqueued = true;
        }

        static $trigger_builder_localized = false;
        if (!$trigger_builder_localized && wp_script_is($lib_main_js_handle, 'enqueued')) {
            wp_localize_script($lib_main_js_handle, 'aipkit_trigger_builder_config', [
                'nonce' => wp_create_nonce('aipkit_manage_triggers_nonce'),
                'text' => [
                    'confirmDeleteTrigger' => __('Are you sure you want to delete this trigger?', 'gpt3-ai-content-generator'),
                    'errorSaving' => __('Error saving triggers.', 'gpt3-ai-content-generator'),
                    'fieldIdHelp' => __('Must be unique, letters, numbers, underscores only.', 'gpt3-ai-content-generator'),
                    'valuePipeText' => __('Format: value|DisplayText. e.g., red_val|Red Color', 'gpt3-ai-content-generator'),
                ],
            ]);
            $trigger_builder_localized = true;
        }
    }
}

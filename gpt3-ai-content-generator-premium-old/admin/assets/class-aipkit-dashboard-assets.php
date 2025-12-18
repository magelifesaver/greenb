<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/assets/class-aipkit-dashboard-assets.php
// Status: MODIFIED
// I have updated this file to include the 'isAdmin' flag to the localized data, making the current user's role available to the frontend JavaScript.

namespace WPAICG\Admin\Assets;

use WPAICG\AIPKit_Providers;
use WPAICG\aipkit_dashboard;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing CORE assets for the main AIPKit Dashboard page.
 */
class DashboardAssets
{
    private $version;
    private $is_admin_main_js_enqueued = false;
    private $is_admin_main_css_enqueued = false;
    private static $is_core_data_localized = false; // Static flag to ensure localization happens only once

    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.9.15';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_core_dashboard_assets']);
    }

    private function register_core_admin_assets()
    {
        $dist_css_url = WPAICG_PLUGIN_URL . 'dist/css/';
        $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';

        // --- Register All Admin CSS Bundles ---
        $admin_css_bundles = [
            'aipkit-admin-main-css' => 'admin-main.bundle.css',
            'aipkit-admin-chat' => 'admin-chat.bundle.css',
            'aipkit-admin-ai-forms' => 'admin-ai-forms.bundle.css',
            'aipkit-admin-ai-training' => 'admin-ai-training.bundle.css',
            'aipkit-admin-autogpt' => 'admin-autogpt.bundle.css',
            'aipkit-admin-content-writer' => 'admin-content-writer.bundle.css',
            'aipkit-admin-image-generator' => 'admin-image-generator.bundle.css',
            'aipkit-admin-post-enhancer' => 'admin-post-enhancer.bundle.css',
            'aipkit-admin-vector-post-processor' => 'admin-vector-post-processor.bundle.css',
            'aipkit-admin-woocommerce-writer' => 'admin-woocommerce-writer.bundle.css',
            'aipkit-lib-triggers-admin' => 'lib-triggers-admin.bundle.css',
        ];

        foreach ($admin_css_bundles as $handle => $file) {
            if (!wp_style_is($handle, 'registered')) {
                wp_register_style(
                    $handle,
                    $dist_css_url . $file,
                    ['dashicons'], // Common dependency
                    $this->version
                );
            }
        }
        // --- End Register All Admin CSS Bundles ---

        $admin_main_js_handle = 'aipkit-admin-main';
        if (!wp_script_is($admin_main_js_handle, 'registered')) {
            wp_register_script(
                $admin_main_js_handle,
                $dist_js_url . 'admin-main.bundle.js',
                ['wp-i18n', 'aipkit_markdown-it'],
                $this->version,
                true
            );
        }
    }

    public function enqueue_core_dashboard_assets($hook_suffix)
    {
        $this->register_core_admin_assets();

        $screen = get_current_screen();
        $is_aipkit_page = $screen && (
            strpos($screen->id, 'page_wpaicg') !== false ||
            $screen->id === 'toplevel_page_wpaicg' ||
            strpos($screen->id, 'aipkit-role-manager') !== false ||
            strpos($screen->id, 'aipkit-migration-tool') !== false
        );


        if ($is_aipkit_page) {
            // --- Enqueue ALL Admin CSS Bundles ---
            $all_css_handles = [
                'aipkit-admin-main-css', 'aipkit-admin-chat', 'aipkit-admin-ai-forms',
                'aipkit-admin-ai-training', 'aipkit-admin-autogpt', 'aipkit-admin-content-writer',
                'aipkit-admin-image-generator', 'aipkit-admin-post-enhancer', 'aipkit-admin-vector-post-processor',
                'aipkit-admin-woocommerce-writer', 'aipkit-lib-triggers-admin',
            ];
            foreach ($all_css_handles as $handle) {
                if (wp_style_is($handle, 'registered') && !wp_style_is($handle, 'enqueued')) {
                    wp_enqueue_style($handle);
                }
            }
            $this->is_admin_main_css_enqueued = true; // Set general flag
            // --- End Enqueue All ---

            $admin_main_js_handle = 'aipkit-admin-main';
            if (!$this->is_admin_main_js_enqueued && !wp_script_is($admin_main_js_handle, 'enqueued')) {
                wp_enqueue_script($admin_main_js_handle);
                wp_set_script_translations($admin_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
                $this->is_admin_main_js_enqueued = true;
            }
            // Call static localization method
            self::localize_core_data($this->version);
        }
    }

    /**
     * Localizes core data for the aipkit-admin-main script handle.
     * Made public and static to be callable from other asset managers.
     *
     * @param string $plugin_version The current plugin version.
     */
    public static function localize_core_data(string $plugin_version) // Added version param for consistency
    {
        if (self::$is_core_data_localized) {
            return;
        }

        $admin_main_js_handle = 'aipkit-admin-main';

        if (!wp_script_is($admin_main_js_handle, 'registered')) {
            $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';
            wp_register_script(
                $admin_main_js_handle,
                $dist_js_url . 'admin-main.bundle.js',
                ['wp-i18n', 'aipkit_markdown-it'],
                $plugin_version,
                true
            );
        }

        $script_data_check = wp_scripts()->get_data($admin_main_js_handle, 'data');
        if (is_string($script_data_check) && strpos($script_data_check, 'var aipkit_dashboard =') !== false) {
            self::$is_core_data_localized = true;
            return;
        }


        $openai_models = [];
        $openrouter_models = [];
        $google_models = [];
        $azure_deployments = [];
        $deepseek_models = [];
        $ollama_models = [];
        $openai_embedding_models = [];
        $google_embedding_models = [];
        $azure_embedding_models = [];
        $google_image_models = [];
        if (class_exists('\\WPAICG\\AIPKit_Providers')) {
            $openai_models     = AIPKit_Providers::get_openai_models();
            $openrouter_models = AIPKit_Providers::get_openrouter_models();
            $google_models     = AIPKit_Providers::get_google_models();
            $azure_deployments = AIPKit_Providers::get_azure_deployments();
            $deepseek_models   = AIPKit_Providers::get_deepseek_models();
            $ollama_models     = AIPKit_Providers::get_ollama_models();
            $openai_embedding_models = AIPKit_Providers::get_openai_embedding_models();
            $google_embedding_models = AIPKit_Providers::get_google_embedding_models();
            $google_image_models = AIPKit_Providers::get_google_image_models();
            $azure_embedding_models = AIPKit_Providers::get_azure_embedding_models();
        }

        $aipkit_nonce = wp_create_nonce('aipkit_nonce');
        $localized_text_path = WPAICG_PLUGIN_DIR . 'admin/data/dashboard-localized-texts.php';
        $dashboard_texts = file_exists($localized_text_path) ? require $localized_text_path : [];

        $is_pro_plan = class_exists('\\WPAICG\\aipkit_dashboard') ? aipkit_dashboard::is_pro_plan() : false;
        $addon_status = class_exists('\\WPAICG\\aipkit_dashboard') ? aipkit_dashboard::get_addon_status() : [];

        wp_localize_script($admin_main_js_handle, 'aipkit_dashboard', [
            'ajaxurl'    => admin_url('admin-ajax.php'),
            'nonce'      => $aipkit_nonce,
            'isProPlan'  => $is_pro_plan,
            'isAdmin'    => current_user_can('manage_options'),
            'addons'     => $addon_status,
            'modulesUrl' => WPAICG_PLUGIN_URL . 'admin/views/modules/',
            'upgradeUrl' => admin_url('admin.php?page=wpaicg-pricing'),
            'adminUrl'   => admin_url(),
            'models' => [
                'openai'     => $openai_models, 'openrouter' => $openrouter_models,
                'google'     => $google_models, 'azure'      => $azure_deployments,
                'deepseek'   => $deepseek_models, 'ollama'     => $ollama_models,
            ],
            'embeddingModels' => [
                'openai' => $openai_embedding_models,
                'google' => $google_embedding_models,
                'azure' => $azure_embedding_models,
            ],
            'imageGeneratorModels' => [
                'openai' => [
                    ['id' => 'gpt-image-1', 'name' => 'GPT Image 1'],
                    ['id' => 'dall-e-3', 'name' => 'DALL-E 3'],
                    ['id' => 'dall-e-2', 'name' => 'DALL-E 2'],
                ],
                'google' => $google_image_models,
                'azure' => AIPKit_Providers::get_azure_image_models(),
                'replicate' => AIPKit_Providers::get_replicate_models(),
            ],
            'text' => $dashboard_texts,
            'currentUserId' => get_current_user_id()
        ]);
        self::$is_core_data_localized = true;
    }
}

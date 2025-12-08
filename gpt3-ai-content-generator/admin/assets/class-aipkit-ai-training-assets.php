<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/assets/class-aipkit-ai-training-assets.php

namespace WPAICG\Admin\Assets;

use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\AIPKit_Providers;
use WPAICG\aipkit_dashboard; // Added for is_pro_plan check

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets for the AIPKit AI Training module.
 * REVISED: Enqueues admin-main.bundle.js and admin-ai-training.bundle.css.
 *          Localizes data to admin-main.bundle.js.
 */
class AITrainingAssets
{
    private $version;
    private $is_admin_main_js_enqueued = false;
    private $is_admin_ai_training_css_enqueued = false;

    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_ai_training_assets']);
    }

    public function enqueue_ai_training_assets($hook_suffix)
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
        $ai_training_css_handle = 'aipkit-admin-ai-training-css';
        $admin_main_css_handle = 'aipkit-admin-main-css';

        if (!wp_style_is($ai_training_css_handle, 'registered')) {
            wp_register_style(
                $ai_training_css_handle,
                $dist_css_url . 'admin-ai-training.bundle.css',
                [$admin_main_css_handle],
                $this->version
            );
        }
        if (!$this->is_admin_ai_training_css_enqueued && !wp_style_is($ai_training_css_handle, 'enqueued')) {
            wp_enqueue_style($ai_training_css_handle);
            $this->is_admin_ai_training_css_enqueued = true;
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

        // Ensure Pro upload (multi-file + drag/drop) code is available on AI Training when eligible
        // These features live in lib-main.bundle.js and are required for the File Upload tab.
        $should_enqueue_pro_upload = false;
        if (class_exists('\\WPAICG\\aipkit_dashboard')) {
            $is_pro = \WPAICG\aipkit_dashboard::is_pro_plan();
            $has_file_upload_addon = \WPAICG\aipkit_dashboard::is_addon_active('file_upload');
            $should_enqueue_pro_upload = ($is_pro && $has_file_upload_addon);
        }
        if ($should_enqueue_pro_upload) {
            $lib_main_js_handle = 'aipkit-lib-main';
            if (!wp_script_is($lib_main_js_handle, 'registered')) {
                wp_register_script(
                    $lib_main_js_handle,
                    $dist_js_url . 'lib-main.bundle.js',
                    ['wp-i18n', $admin_main_js_handle, 'aipkit_markdown-it'],
                    $this->version,
                    true
                );
            }
            if (!wp_script_is($lib_main_js_handle, 'enqueued')) {
                wp_enqueue_script($lib_main_js_handle);
                wp_set_script_translations($lib_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            }
        }
    }

    private function localize_data()
    {
        $admin_main_js_handle = 'aipkit-admin-main';

        if (!wp_script_is($admin_main_js_handle, 'enqueued')) {
            if (!wp_script_is($admin_main_js_handle, 'registered')) { // Check if registered before trying to register again
                $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';
                wp_register_script($admin_main_js_handle, $dist_js_url . 'admin-main.bundle.js', ['wp-i18n', 'aipkit_markdown-it'], $this->version, true);
            }
        }

        $script_data_openai = wp_scripts()->get_data($admin_main_js_handle, 'data');
        $already_localized_openai = is_string($script_data_openai) && strpos($script_data_openai, 'var aipkit_openai_vs_config =') !== false;

        if (!$already_localized_openai) {
            $initial_openai_stores = [];
            if (class_exists(AIPKit_Vector_Store_Registry::class)) {
                $initial_openai_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
            }
            $openai_embedding_models = [];
            $google_embedding_models = [];
            $azure_embedding_models = [];
            if (class_exists(AIPKit_Providers::class)) {
                $openai_embedding_models = AIPKit_Providers::get_openai_embedding_models();
                $google_embedding_models = AIPKit_Providers::get_google_embedding_models();
                $azure_embedding_models = AIPKit_Providers::get_azure_embedding_models();
            }
            wp_localize_script($admin_main_js_handle, 'aipkit_openai_vs_config', [
                'initialStores' => $initial_openai_stores,
                'apiKeyIsSet' => !empty(AIPKit_Providers::get_provider_data('OpenAI')['api_key']),
                'openaiEmbeddingModels' => $openai_embedding_models,
                'googleEmbeddingModels' => $google_embedding_models,
                'azureEmbeddingModels' => $azure_embedding_models,
            ]);
        }

        $script_data_pinecone = wp_scripts()->get_data($admin_main_js_handle, 'data');
        $already_localized_pinecone = is_string($script_data_pinecone) && strpos($script_data_pinecone, 'var aipkit_pinecone_vs_config =') !== false;

        if (!$already_localized_pinecone) {
            $initial_pinecone_indexes = [];
            if (class_exists(AIPKit_Vector_Store_Registry::class)) {
                $initial_pinecone_indexes = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Pinecone');
            }
            wp_localize_script($admin_main_js_handle, 'aipkit_pinecone_vs_config', [
                'initialIndexes' => $initial_pinecone_indexes,
                'apiKeyIsSet' => !empty(AIPKit_Providers::get_provider_data('Pinecone')['api_key'])
            ]);
        }

        $script_data_qdrant = wp_scripts()->get_data($admin_main_js_handle, 'data');
        $already_localized_qdrant = is_string($script_data_qdrant) && strpos($script_data_qdrant, 'var aipkit_qdrant_vs_config =') !== false;

        if (!$already_localized_qdrant) {
            $initial_qdrant_collections = [];
            $qdrant_config_data = ['urlIsSet' => false, 'apiKeyIsSet' => false];
            if (class_exists(AIPKit_Vector_Store_Registry::class) && class_exists(AIPKit_Providers::class)) {
                $initial_qdrant_collections = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Qdrant');
                $qdrant_provider_settings   = AIPKit_Providers::get_provider_data('Qdrant');
                $qdrant_config_data = [
                    'urlIsSet' => !empty($qdrant_provider_settings['url']),
                    'apiKeyIsSet' => !empty($qdrant_provider_settings['api_key']),
                ];
            }
            wp_localize_script($admin_main_js_handle, 'aipkit_qdrant_vs_config', [
                'initialCollections' => $initial_qdrant_collections,
                'urlIsSet'           => $qdrant_config_data['urlIsSet'],
                'apiKeyIsSet'        => $qdrant_config_data['apiKeyIsSet'],
            ]);
        }
        
        // --- NEW: Dedicated localization for settings tab ---
        $script_data_settings = wp_scripts()->get_data($admin_main_js_handle, 'data');
        $already_localized_settings = is_string($script_data_settings) && strpos($script_data_settings, 'var aipkit_ai_training_settings_config =') !== false;
        if (!$already_localized_settings) {
            wp_localize_script($admin_main_js_handle, 'aipkit_ai_training_settings_config', [
                'settings_nonce' => wp_create_nonce('aipkit_ai_training_settings_nonce'),
                'isPro' => aipkit_dashboard::is_pro_plan(), // Pass pro status
                // --- ADDED: Pass addon status for file upload ---
                'isFileUploadAddonActive' => aipkit_dashboard::is_addon_active('file_upload'),
            ]);
        }
        // --- END NEW ---
    }
}

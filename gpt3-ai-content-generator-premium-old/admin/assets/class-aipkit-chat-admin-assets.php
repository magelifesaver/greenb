<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/assets/class-aipkit-chat-admin-assets.php
// Status: MODIFIED

namespace WPAICG\Admin\Assets;

use WPAICG\aipkit_dashboard;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\AIPKit_Providers;
use WPAICG\Chat\Frontend\Assets as ChatFrontendAssetsOrchestrator;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets (CSS/JS) and localization for the AIPKit Chat Admin module.
 */
class ChatAdminAssets
{
    private $version;
    private $base_dir;
    private $is_admin_main_js_enqueued = false;
    private $is_public_main_js_enqueued = false;
    private $is_admin_chat_css_enqueued = false;
    private $is_public_main_css_enqueued = false;


    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.9.15';
        $this->base_dir = defined('WPAICG_PLUGIN_DIR') ? WPAICG_PLUGIN_DIR : plugin_dir_path(__FILE__) . '../../';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_chat_admin_assets']);
    }

    public function enqueue_chat_admin_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_aipkit_page = $screen && strpos($screen->id, 'page_wpaicg') !== false;

        if (!$is_aipkit_page) {
            return;
        }

        if (class_exists(ChatFrontendAssetsOrchestrator::class) && method_exists(ChatFrontendAssetsOrchestrator::class, 'register_public_chat_dependencies')) {
            ChatFrontendAssetsOrchestrator::register_public_chat_dependencies();
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();

        // Ensure core dashboard data is localized if admin-main.js was enqueued
        if ($this->is_admin_main_js_enqueued && class_exists(DashboardAssets::class) && method_exists(DashboardAssets::class, 'localize_core_data')) {
            DashboardAssets::localize_core_data($this->version);
        }

        $this->localize_chat_data();
    }

    private function enqueue_styles()
    {
        $dist_css_url = WPAICG_PLUGIN_URL . 'dist/css/';
        $admin_main_css_handle = 'aipkit-admin-main-css';
        $ver_public_main_css = $this->asset_ver('dist/css/public-main.bundle.css');
        $ver_admin_chat_css  = $this->asset_ver('dist/css/admin-chat.bundle.css');

        $public_main_css_handle = 'aipkit-public-main-css';
        if (!wp_style_is($public_main_css_handle, 'registered')) {
            wp_register_style(
                $public_main_css_handle,
                $dist_css_url . 'public-main.bundle.css',
                ['dashicons'],
                $ver_public_main_css
            );
        }
        if (!$this->is_public_main_css_enqueued && !wp_style_is($public_main_css_handle, 'enqueued')) {
            wp_enqueue_style($public_main_css_handle);
            $this->is_public_main_css_enqueued = true;
        }

        $admin_chat_css_handle = 'aipkit-admin-chat-css';
        if (!wp_style_is($admin_chat_css_handle, 'registered')) {
            wp_register_style(
                $admin_chat_css_handle,
                $dist_css_url . 'admin-chat.bundle.css',
                [$admin_main_css_handle, $public_main_css_handle],
                $ver_admin_chat_css
            );
        }
        if (!$this->is_admin_chat_css_enqueued && !wp_style_is($admin_chat_css_handle, 'enqueued')) {
            wp_enqueue_style($admin_chat_css_handle);
            $this->is_admin_chat_css_enqueued = true;
        }
    }

    private function enqueue_scripts()
    {
        $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';
        $admin_main_js_handle = 'aipkit-admin-main';
        $public_main_js_handle = 'aipkit-public-main';
        $jspdf_handle = 'aipkit_jspdf';
        $ver_admin_main_js = $this->asset_ver('dist/js/admin-main.bundle.js');
        $ver_public_main_js = $this->asset_ver('dist/js/public-main.bundle.js');

        if (!wp_script_is($admin_main_js_handle, 'registered')) {
            wp_register_script(
                $admin_main_js_handle,
                $dist_js_url . 'admin-main.bundle.js',
                ['wp-i18n', 'aipkit_markdown-it'],
                $ver_admin_main_js,
                true
            );
        }
        if (!$this->is_admin_main_js_enqueued && !wp_script_is($admin_main_js_handle, 'enqueued')) {
            wp_enqueue_script($admin_main_js_handle);
            wp_set_script_translations($admin_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            $this->is_admin_main_js_enqueued = true;
        }

        // Register public-main.bundle.js without aipkit_jspdf initially
        // It will be added conditionally by lib/wpaicg__premium_only.php
        if (!wp_script_is($public_main_js_handle, 'registered')) {
            wp_register_script(
                $public_main_js_handle,
                $dist_js_url . 'public-main.bundle.js',
                ['wp-i18n', 'aipkit_markdown-it'], // REMOVED 'aipkit_jspdf' from here
                $ver_public_main_js,
                true
            );
        }
        // Enqueue if not already
        if (!$this->is_public_main_js_enqueued && !wp_script_is($public_main_js_handle, 'enqueued')) {
            wp_enqueue_script($public_main_js_handle);
            wp_set_script_translations($public_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            $this->is_public_main_js_enqueued = true;
        }

        // --- MODIFICATION START: Conditionally enqueue jsPDF for admin chat preview ---
        // The chat preview uses public-main.bundle.js. If PDF download is a feature
        // shown in the preview (and Pro plan is active, PDF addon active), we need jsPDF.
        if (class_exists('\WPAICG\aipkit_dashboard') &&
            \WPAICG\aipkit_dashboard::is_pro_plan() &&
            \WPAICG\aipkit_dashboard::is_addon_active('pdf_download') &&
            wp_script_is($jspdf_handle, 'registered') &&
            !wp_script_is($jspdf_handle, 'enqueued')) {
            wp_enqueue_script($jspdf_handle);
        }
        // --- MODIFICATION END ---
    }

    private function localize_chat_data()
    {
        $public_main_js_handle = 'aipkit-public-main';

        // Ensure script is registered (might have been by ChatAssetsOrchestrator if this runs first)
        if (!wp_script_is($public_main_js_handle, 'registered')) {
            $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';
            wp_register_script($public_main_js_handle, $dist_js_url . 'public-main.bundle.js', ['wp-i18n','aipkit_markdown-it'], $ver_public_main_js, true); // No jspdf here
        }
        // Ensure script is enqueued if not already (Chat Admin Assets always needs public-main for preview)
        if (!wp_script_is($public_main_js_handle, 'enqueued')) {
            wp_enqueue_script($public_main_js_handle); // Dependencies will be handled by WP, including conditional jspdf
            wp_set_script_translations($public_main_js_handle, 'gpt3-ai-content-generator', WPAICG_PLUGIN_DIR . 'languages');
            $this->is_public_main_js_enqueued = true; // Track local enqueue status
        }


        $script_data = wp_scripts()->get_data($public_main_js_handle, 'data');
        if (is_string($script_data) && strpos($script_data, 'var aipkit_chat_config =') !== false) {
            return; // Already localized
        }

        $aipkit_frontend_nonce = wp_create_nonce('aipkit_frontend_chat_nonce');
        $dashboard_texts_data = wp_scripts()->get_data('aipkit-admin-main', 'data');
        $dashboard_texts_localized_obj = $dashboard_texts_data ? json_decode(str_replace('var aipkit_dashboard = ', '', rtrim($dashboard_texts_data, ';')), true) : null;
        $dashboard_texts = ($dashboard_texts_localized_obj && isset($dashboard_texts_localized_obj['text'])) ? $dashboard_texts_localized_obj['text'] : [];

        $openai_vector_stores = [];
        $pinecone_indexes = [];
        $qdrant_collections = [];
        $openai_embedding_models = [];
        $google_embedding_models = [];
        $azure_embedding_models = [];
        $is_pro_plan = false;
        $is_triggers_addon_active = false;

        if (class_exists('\\WPAICG\\Vector\\AIPKit_Vector_Store_Registry')) {
            $openai_vector_stores = \WPAICG\Vector\AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
        }
        if (class_exists('\\WPAICG\\AIPKit_Providers')) {
            $pinecone_indexes = \WPAICG\AIPKit_Providers::get_pinecone_indexes();
            $qdrant_collections = \WPAICG\AIPKit_Providers::get_qdrant_collections();
            $openai_embedding_models = \WPAICG\AIPKit_Providers::get_openai_embedding_models();
            $google_embedding_models = \WPAICG\AIPKit_Providers::get_google_embedding_models();
            $azure_embedding_models = \WPAICG\AIPKit_Providers::get_azure_embedding_models();
        }
        if (class_exists('\\WPAICG\\aipkit_dashboard')) {
            $is_pro_plan = \WPAICG\aipkit_dashboard::is_pro_plan();
            $is_triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
        }

        // --- FIX: Correctly sanitize the IP address ---
        // This addresses both the MissingUnslash and InputNotSanitized warnings.
        $user_ip_sanitized = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null;
        // --- END FIX ---
        
        // --- ADDED: Get banned IPs for the new feature ---
        $security_options = AIPKIT_AI_Settings::get_security_settings();
        $banned_ips_settings = $security_options['bannedips'] ?? ['ips' => ''];
        // --- END ADDED ---

        $preview_config_for_js = [
            'nonce' => $aipkit_frontend_nonce, 'ajaxUrl' => admin_url('admin-ajax.php'),
            'userIp' => $user_ip_sanitized, 'requireConsentCompliance' => false,
            'openaiVectorStores' => $openai_vector_stores, 'pineconeIndexes' => $pinecone_indexes,
            'qdrantCollections' => $qdrant_collections, 'openaiEmbeddingModels' => $openai_embedding_models,
            'googleEmbeddingModels' => $google_embedding_models, 'azureEmbeddingModels' => $azure_embedding_models, 'isProPlan' => $is_pro_plan,
            'isTriggersAddonActive' => $is_triggers_addon_active,
            'banned_ips' => $banned_ips_settings['ips'], // ADDED
            'nonce_toggle_ip_block' => wp_create_nonce('aipkit_toggle_ip_block_nonce'), // ADDED
            'text' => array_merge($dashboard_texts, [
                'fullscreenError' => $dashboard_texts['fullscreenError'] ?? __('Error: Fullscreen functionality is unavailable.', 'gpt3-ai-content-generator'),
                'copySuccess'     => $dashboard_texts['copySuccess'] ?? __('Copied!', 'gpt3-ai-content-generator'),
                'copyFail'        => $dashboard_texts['copyFail'] ?? __('Failed to copy', 'gpt3-ai-content-generator'),
                'selectVectorStoreOpenAI' => __('Select OpenAI Store(s)', 'gpt3-ai-content-generator'),
                'selectVectorStorePinecone' => __('Select Pinecone Index', 'gpt3-ai-content-generator'),
                'selectVectorStoreQdrant' => __('Select Qdrant Collection', 'gpt3-ai-content-generator'),
                'selectEmbeddingProvider' => __('Select Embedding Provider', 'gpt3-ai-content-generator'),
                'selectEmbeddingModel' => __('Select Embedding Model', 'gpt3-ai-content-generator'),
                'noStoresFoundOpenAI' => __('No OpenAI Stores Found (Sync in AI Training)', 'gpt3-ai-content-generator'),
                'noIndexesFoundPinecone' => __('No Pinecone Indexes Found (Sync in AI Settings)', 'gpt3-ai-content-generator'),
                'noCollectionsFoundQdrant' => __('No Qdrant Collections Found (Sync in AI Settings)', 'gpt3-ai-content-generator'),
                'noEmbeddingModelsFound' => __('No Models (Select Provider or Sync)', 'gpt3-ai-content-generator'),
            ])
        ];
        wp_localize_script($public_main_js_handle, 'aipkit_chat_config', $preview_config_for_js);

        // --- NEW: Add global nonce for Index Content functionality ---
        if (wp_script_is('aipkit-admin-main', 'enqueued')) {
            $index_content_nonce = wp_create_nonce('aipkit_chatbot_index_content_nonce');
            wp_add_inline_script('aipkit-admin-main', 'window.aipkit_index_content_nonce = "' . esc_js($index_content_nonce) . '";', 'before');
        }
        // --- END NEW ---
    }

    /**
     * Returns a cache-busting version based on file mtime; falls back to plugin version.
     */
    private function asset_ver(string $relative_path): string
    {
        $abs = rtrim($this->base_dir, '/\\') . '/' . ltrim($relative_path, '/\\');
        $ts = @filemtime($abs);
        if ($ts) {
            return (string) $ts;
        }
        return $this->version;
    }
}

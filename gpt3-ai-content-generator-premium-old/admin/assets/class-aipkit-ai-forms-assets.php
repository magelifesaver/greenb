<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/assets/class-aipkit-ai-forms-assets.php
// Status: MODIFIED

namespace WPAICG\Admin\Assets;

// --- ADDED: Use statements for vector store/provider data ---
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\AIPKit_Providers;

// --- END ADDED ---

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets (CSS/JS) and localization for the AIPKit AI Forms module.
 */
class AIPKit_AI_Forms_Assets
{
    private $version;
    private $is_admin_main_js_enqueued = false;
    private $is_admin_ai_forms_css_enqueued = false;


    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_ai_forms_assets']);
    }

    public function enqueue_ai_forms_assets($hook_suffix)
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
        $ai_forms_css_handle = 'aipkit-admin-ai-forms-css';
        $admin_main_css_handle = 'aipkit-admin-main-css';

        if (!wp_style_is($ai_forms_css_handle, 'registered')) {
            wp_register_style(
                $ai_forms_css_handle,
                $dist_css_url . 'admin-ai-forms.bundle.css',
                [$admin_main_css_handle],
                $this->version
            );
        }
        if (!$this->is_admin_ai_forms_css_enqueued && !wp_style_is($ai_forms_css_handle, 'enqueued')) {
            wp_enqueue_style($ai_forms_css_handle);
            $this->is_admin_ai_forms_css_enqueued = true;
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
        // Script should be registered by enqueue_scripts if needed. If not enqueued yet, localization will wait.
        if (!wp_script_is($admin_main_js_handle, 'enqueued')) {
            // It's possible that admin-main.bundle.js was registered but not enqueued by DashboardAssets
            // if the current page isn't the main dashboard page but is an AIPKit page.
            // enqueue_scripts() above should handle this.
            // However, if for some reason it's not enqueued, localizing might fail.
            // For safety, ensure it is registered before attempting to localize.
            if (!wp_script_is($admin_main_js_handle, 'registered')) {
                $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';
                wp_register_script($admin_main_js_handle, $dist_js_url . 'admin-main.bundle.js', ['wp-i18n', 'aipkit_markdown-it'], $this->version, true);
            }
        }

        // Check if this specific localization has already been done for this handle
        $script_data_check = wp_scripts()->get_data($admin_main_js_handle, 'data');
        if (is_string($script_data_check) && strpos($script_data_check, 'var aipkit_ai_forms_config =') !== false) {
            return; // Already localized for AI Forms
        }

        // --- NEW: Add vector store and embedding model data to localization ---
        $openai_vector_stores = [];
        if (class_exists(AIPKit_Vector_Store_Registry::class)) {
            $openai_vector_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
        }
        $pinecone_indexes = [];
        if (class_exists(AIPKit_Providers::class)) {
            $pinecone_indexes = AIPKit_Providers::get_pinecone_indexes();
        }
        $qdrant_collections = [];
        if (class_exists(AIPKit_Providers::class)) {
            $qdrant_collections = AIPKit_Providers::get_qdrant_collections();
        }
        $openai_embedding_models = [];
        $google_embedding_models = [];
        $azure_embedding_models = [];
        if (class_exists(AIPKit_Providers::class)) {
            $openai_embedding_models = AIPKit_Providers::get_openai_embedding_models();
            $google_embedding_models = AIPKit_Providers::get_google_embedding_models();
            $azure_embedding_models = AIPKit_Providers::get_azure_embedding_models();
        }
        // --- END NEW ---

        wp_localize_script($admin_main_js_handle, 'aipkit_ai_forms_config', [
            'nonce_manage_forms' => wp_create_nonce('aipkit_manage_ai_forms_nonce'),
            'nonce_settings' => wp_create_nonce('aipkit_ai_forms_settings_nonce'),
            // --- NEW: Add vector data ---
            'vectorStores' => [
                'openai' => $openai_vector_stores,
                'pinecone' => $pinecone_indexes,
                'qdrant' => $qdrant_collections
            ],
            'embeddingModels' => [
                'openai' => $openai_embedding_models,
                'google' => $google_embedding_models,
                'azure' => $azure_embedding_models,
            ],
            // --- END NEW ---
            'text' => [
                'savingForm'      => __('Saving form...', 'gpt3-ai-content-generator'),
                'formSaved'       => __('Form saved successfully!', 'gpt3-ai-content-generator'),
                'errorSavingForm' => __('Error saving form.', 'gpt3-ai-content-generator'),
                'loadingForms'    => __('Loading forms...', 'gpt3-ai-content-generator'),
                'deletingForm'    => __('Deleting form...', 'gpt3-ai-content-generator'),
                'deletingAllForms' => __('Deleting all forms...', 'gpt3-ai-content-generator'),
                'duplicatingForm' => __('Duplicating...', 'gpt3-ai-content-generator'),
                'formDeleted'     => __('Form deleted.', 'gpt3-ai-content-generator'),
                'allFormsDeleted'     => __('All forms deleted.', 'gpt3-ai-content-generator'),
                'errorDeletingForm' => __('Error deleting form.', 'gpt3-ai-content-generator'),
                'errorDeletingAllForms' => __('Error deleting all forms.', 'gpt3-ai-content-generator'),
                'errorDuplicatingForm' => __('Error duplicating form.', 'gpt3-ai-content-generator'),
                'confirmDeleteForm' => __('Are you sure you want to delete this form? This action cannot be undone.', 'gpt3-ai-content-generator'),
                'confirmDeleteAllForms' => __('Are you sure you want to delete ALL forms? This action cannot be undone.', 'gpt3-ai-content-generator'),
                'formTitleRequired' => __('Form title is required.', 'gpt3-ai-content-generator'),
                'promptTemplateRequired' => __('Prompt template is required.', 'gpt3-ai-content-generator'),
                'editFormTitle' => __('Edit AI Form', 'gpt3-ai-content-generator'),
                'createNewFormTitle' => __('Create New AI Form', 'gpt3-ai-content-generator'),
                'confirmDeleteElement' => __('Are you sure you want to delete this form element?', 'gpt3-ai-content-generator'),
                'noOptionsConfigured' => __('No options configured', 'gpt3-ai-content-generator'),
                'settingsLabel' => __('Label Text', 'gpt3-ai-content-generator'),
                'settingsFieldId' => __('Field Variable Name (for prompt)', 'gpt3-ai-content-generator'),
                'settingsFieldIdHelp' => __('Use as {your_variable_name} in the Prompt Template. Must be unique and contain only letters, numbers, underscores.', 'gpt3-ai-content-generator'),
                'settingsPlaceholder' => __('Placeholder Text', 'gpt3-ai-content-generator'),
                'settingsRequired' => __('Required Field', 'gpt3-ai-content-generator'),
                'settingsSelectOptions' => __('Options (Value|Text)', 'gpt3-ai-content-generator'),
                'settingsSelectOptionValue' => __('Value', 'gpt3-ai-content-generator'),
                'settingsSelectOptionText' => __('Display Text', 'gpt3-ai-content-generator'),
                'settingsAddOption' => __('Add Option', 'gpt3-ai-content-generator'),
                'settingsRemoveOption' => __('Remove Option', 'gpt3-ai-content-generator'),
                'settingsDoneButton' => __('Done', 'gpt3-ai-content-generator'),
                'errorUniqueFieldId' => __('Field Variable Name must be unique and valid (letters, numbers, underscores).', 'gpt3-ai-content-generator'),
            ]
        ]);
    }
}
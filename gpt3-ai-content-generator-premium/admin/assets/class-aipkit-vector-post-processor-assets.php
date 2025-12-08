<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/vector/post-processor/class-aipkit-vector-post-processor-assets.php
// Status: MODIFIED

namespace WPAICG\Admin\Assets;

use WPAICG\AIPKit_Role_Manager;
use WPAICG\Utils\AIPKit_Admin_Header_Action_Buttons;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\AIPKit_Providers;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets for the "Add to Vector Store" feature.
 */
class AIPKit_Vector_Post_Processor_Assets
{
    private $version;
    public const MODULE_SLUG = 'vector_content_indexer';
    private $is_admin_main_js_enqueued = false;
    private $is_admin_vpp_css_enqueued = false;


    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        // Register the Index button only if module access AND user setting enabled
        add_action('admin_init', function(){
            if (!AIPKit_Role_Manager::user_can_access_module(self::MODULE_SLUG)) {
                return;
            }
            $general = get_option('aipkit_training_general_settings', []);
            $show = $general['show_index_button'] ?? true; // default enabled
            if ($show) {
                AIPKit_Admin_Header_Action_Buttons::register_button(
                    'aipkit_add_to_vector_store_btn',
                    __('Index', 'gpt3-ai-content-generator'),
                    [ 'capability' => 'edit_posts' ]
                );
            }
        });
    }

    /**
     * NEW: Adds the "Index" button to the post list screens via a PHP action.
     */

    public function enqueue_assets($hook_suffix)
    {
        $screen = get_current_screen();
        $is_post_list_screen = $screen && $screen->base === 'edit';

    if ($is_post_list_screen && AIPKit_Role_Manager::user_can_access_module(self::MODULE_SLUG)) {
            $this->enqueue_styles();
            $this->enqueue_scripts($screen->post_type); // Pass post_type to enqueue_scripts

            // Ensure core dashboard data is localized if admin-main.js was enqueued
            if ($this->is_admin_main_js_enqueued && class_exists(DashboardAssets::class) && method_exists(DashboardAssets::class, 'localize_core_data')) {
                DashboardAssets::localize_core_data($this->version);
            }
            $this->localize_vpp_data($screen->post_type); // Localize VPP specific data
        }
    }

    // (REMOVED) Inline index button injector replaced by shared utility

    private function enqueue_styles()
    {
        $dist_css_url = WPAICG_PLUGIN_URL . 'dist/css/';
        $vpp_css_handle = 'aipkit-admin-vector-post-processor-css';
        $admin_main_css_handle = 'aipkit-admin-main-css';

        if (!wp_style_is($admin_main_css_handle, 'registered')) {
            wp_register_style(
                $admin_main_css_handle,
                $dist_css_url . 'admin-main.bundle.css',
                ['dashicons'],
                $this->version
            );
        }

        if (!wp_style_is($vpp_css_handle, 'registered')) {
            wp_register_style(
                $vpp_css_handle,
                $dist_css_url . 'admin-vector-post-processor.bundle.css',
                [$admin_main_css_handle],
                $this->version
            );
        }
        if (!$this->is_admin_vpp_css_enqueued && !wp_style_is($vpp_css_handle, 'enqueued')) {
            wp_enqueue_style($vpp_css_handle);
            $this->is_admin_vpp_css_enqueued = true;
        }
    }

    private function enqueue_scripts(string $post_type)
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

    private function localize_vpp_data(string $post_type)
    {
        $admin_main_js_handle = 'aipkit-admin-main';
        static $vpp_localized = false;
        if (!$vpp_localized && wp_script_is($admin_main_js_handle, 'enqueued')) { // Ensure script is enqueued
            $openai_vector_stores = [];
            $pinecone_indexes = [];
            $qdrant_collections = [];
            $openai_embedding_models = [];
            $google_embedding_models = [];
            $azure_embedding_models = [];

            if (class_exists(AIPKit_Vector_Store_Registry::class)) {
                $openai_vector_stores = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI');
                $pinecone_indexes = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Pinecone');
                $qdrant_collections = AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('Qdrant');
            }
            if (class_exists(AIPKit_Providers::class)) {
                $openai_embedding_models = AIPKit_Providers::get_openai_embedding_models();
                $google_embedding_models = AIPKit_Providers::get_google_embedding_models();
                $azure_embedding_models = AIPKit_Providers::get_azure_embedding_models();
            }

            wp_localize_script($admin_main_js_handle, 'aipkit_vpp_config', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce_index_posts' => wp_create_nonce('aipkit_index_posts_to_vector_store_nonce'),
                'post_type' => $post_type,
                'openai_vector_stores' => $openai_vector_stores,
                'pinecone_indexes' => $pinecone_indexes,
                'qdrant_collections' => $qdrant_collections,
                'openaiEmbeddingModels' => $openai_embedding_models,
                'googleEmbeddingModels' => $google_embedding_models,
                'azureEmbeddingModels' => $azure_embedding_models,
                'text' => [
                    'modal_title' => __('Add Content to Vector Store', 'gpt3-ai-content-generator'),
                    'provider_label' => __('Provider', 'gpt3-ai-content-generator'),
                    'select_store' => __('Select OpenAI Store', 'gpt3-ai-content-generator'),
                    'no_stores_found' => __('No OpenAI stores found. Create one in AI Training > Knowledge Base.', 'gpt3-ai-content-generator'),
                    'loading_stores' => __('Loading stores...', 'gpt3-ai-content-generator'),
                    'start_indexing' => __('Start Indexing', 'gpt3-ai-content-generator'),
                    'processingButton' => __('Processing...', 'gpt3-ai-content-generator'),
                    'close' => __('Close', 'gpt3-ai-content-generator'),
                    'stop' => __('Stop', 'gpt3-ai-content-generator'),
                    'stopping' => __('Stopping...', 'gpt3-ai-content-generator'),
                    'indexing_progress' => __('Processing: %1$d/%2$d', 'gpt3-ai-content-generator'),
                    'indexing_complete' => __('Indexing complete!', 'gpt3-ai-content-generator'),
                    'error_fetching_stores' => __('Error fetching vector stores.', 'gpt3-ai-content-generator'),
                    'error_no_store_selected_vpp' => __('Please select an existing OpenAI store.', 'gpt3-ai-content-generator'),
                    'error_no_posts_selected' => __('Please select at least one post to index.', 'gpt3-ai-content-generator'),
                    'confirm_start_indexing' => __('Are you sure you want to index the selected content?', 'gpt3-ai-content-generator'),
                    'status_preparing' => __('Preparing content...', 'gpt3-ai-content-generator'),
                    /* translators: %1$s is the file name, %2$s is the total number of files being indexed */
                    'status_uploading' => __('Uploading file %1$s of %2$s...', 'gpt3-ai-content-generator'),
                    'status_adding_files' => __('Adding files to vector store...', 'gpt3-ai-content-generator'),
                    'status_error' => __('An error occurred.', 'gpt3-ai-content-generator'),
                    /* translators: %d is the number of items selected */
                    'items_selected_singular' => __('You have selected %d item to index.', 'gpt3-ai-content-generator'),
                    /* translators: %d is the number of items selected */
                    'items_selected_plural' => __('You have selected %d items to index.', 'gpt3-ai-content-generator'),
                    'select_pinecone_index' => __('Select Pinecone Index', 'gpt3-ai-content-generator'),
                    'loading_indexes' => __('Loading indexes...', 'gpt3-ai-content-generator'),
                    'error_fetching_indexes' => __('Error fetching indexes.', 'gpt3-ai-content-generator'),
                    'no_pinecone_indexes_found' => __('No Pinecone indexes found. Create one in AI Training or via Pinecone console.', 'gpt3-ai-content-generator'),
                    'error_no_pinecone_index_selected' => __('Please select a Pinecone index.', 'gpt3-ai-content-generator'),
                    'select_qdrant_collection' => __('Select Qdrant Collection', 'gpt3-ai-content-generator'),
                    'no_qdrant_collections_found' => __('No Qdrant collections found. Create one in AI Training.', 'gpt3-ai-content-generator'),
                    'error_no_qdrant_collection_selected' => __('Please select a Qdrant collection.', 'gpt3-ai-content-generator'),
                    'embedding_provider_label' => __('Embedding Provider', 'gpt3-ai-content-generator'),
                    'embedding_model_label' => __('Embedding Model', 'gpt3-ai-content-generator'),
                    'select_model' => __('Select Model', 'gpt3-ai-content-generator'),
                    'error_no_embedding_config' => __('Embedding provider and model are required.', 'gpt3-ai-content-generator'),
                    'ensure_api_key_for_embedding' => __('Ensure API key is set for the selected embedding provider in AI Settings.', 'gpt3-ai-content-generator'),
                ]
            ]);
            $vpp_localized = true;
        }
    }
}
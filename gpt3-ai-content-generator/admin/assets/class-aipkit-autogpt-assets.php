<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/assets/class-aipkit-autogpt-assets.php
// Status: MODIFIED
// I have updated this file to include `azure_embedding_models` in the localized data for the frontend scripts.

namespace WPAICG\Admin\Assets;

use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\AIPKit_Providers;
use WPAICG\ContentWriter\AIPKit_Content_Writer_Prompts;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets for the AIPKit AutoGPT module.
 */
class AIPKit_Autogpt_Assets
{
    private $version;
    private $is_admin_main_js_enqueued = false;
    private $is_admin_autogpt_css_enqueued = false;

    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.0.0';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_autogpt_assets']);
    }

    public function enqueue_autogpt_assets($hook_suffix)
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
        $autogpt_css_handle = 'aipkit-admin-autogpt-css';
        $admin_main_css_handle = 'aipkit-admin-main-css';

        if (!wp_style_is($autogpt_css_handle, 'registered')) {
            wp_register_style(
                $autogpt_css_handle,
                $dist_css_url . 'admin-autogpt.bundle.css',
                [$admin_main_css_handle],
                $this->version
            );
        }
        if (!$this->is_admin_autogpt_css_enqueued && !wp_style_is($autogpt_css_handle, 'enqueued')) {
            wp_enqueue_style($autogpt_css_handle);
            $this->is_admin_autogpt_css_enqueued = true;
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
        if (!wp_script_is($admin_main_js_handle, 'enqueued')) {
            if (!wp_script_is($admin_main_js_handle, 'registered')) {
                $dist_js_url = WPAICG_PLUGIN_URL . 'dist/js/';
                wp_register_script($admin_main_js_handle, $dist_js_url . 'admin-main.bundle.js', ['wp-i18n', 'aipkit_markdown-it'], $this->version, true);
            }
        }

        $script_data = wp_scripts()->get_data($admin_main_js_handle, 'data');
        if (is_string($script_data) && strpos($script_data, 'var aipkit_automated_tasks_config =') !== false) {
            return;
        }

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
        $task_types_for_js = [
            // --- NEW: Category: Knowledge Base ---
            'content_indexing' => [
                'label' => __('Index WordPress Content', 'gpt3-ai-content-generator'),
                'category' => 'knowledge_base',
                'description' => __('Index WordPress posts, pages, or products into a vector store for RAG.', 'gpt3-ai-content-generator'),
            ],
            // --- NEW: Category: Content Creation ---
            'content_writing_bulk' => [
                'label' => __('List', 'gpt3-ai-content-generator'),
                'category' => 'content_creation',
                'description' => __('Generate full articles from a list of titles and optional keywords.', 'gpt3-ai-content-generator'),
            ],
            'content_writing_csv' => [
                'label' => __('CSV', 'gpt3-ai-content-generator'),
                'category' => 'content_creation',
                'description' => __('Generate articles by importing topics and metadata from a CSV file.', 'gpt3-ai-content-generator'),
            ],
            'content_writing_rss' => [
                'label' => __('RSS', 'gpt3-ai-content-generator'),
                'category' => 'content_creation',
                'description' => __('Automatically generate articles from new items in one or more RSS feeds.', 'gpt3-ai-content-generator'),
                'pro' => true,
            ],
            'content_writing_url' => [
                'label' => __('URL', 'gpt3-ai-content-generator'),
                'category' => 'content_creation',
                'description' => __('Generate articles by scraping content from a list of URLs to use as context.', 'gpt3-ai-content-generator'),
                'pro' => true,
            ],
            'content_writing_gsheets' => [
                'label' => __('Google Sheet', 'gpt3-ai-content-generator'),
                'category' => 'content_creation',
                'description' => __('Generate articles from a list of topics in a Google Sheets spreadsheet.', 'gpt3-ai-content-generator'),
                'pro' => true,
            ],
            // --- NEW: Category: Content Enhancement ---
            'enhance_existing_content' => [
                'label' => __('Update Existing Content', 'gpt3-ai-content-generator'),
                'category' => 'content_enhancement',
                'description' => __('Automatically update titles, excerpts, or meta descriptions for existing posts based on your custom prompts.', 'gpt3-ai-content-generator'),
                'pro' => true, // This will be used by the frontend to show "Pro" tag
                'disabled' => false, // This task is now enabled
            ],
            // --- NEW: Category: Engagement ---
            'community_reply_comments' => [
                'label' => __('Auto-Reply to Comments', 'gpt3-ai-content-generator'),
                'category' => 'community_engagement',
                'description' => __('Automatically generate and post replies to new comments.', 'gpt3-ai-content-generator'),
                'disabled' => false,
            ],
        ];

        $default_cw_prompts = [];
        if (class_exists(AIPKit_Content_Writer_Prompts::class)) {
            $default_cw_prompts = [
                'title'          => AIPKit_Content_Writer_Prompts::get_default_title_prompt(),
                'content'        => AIPKit_Content_Writer_Prompts::get_default_content_prompt(),
                'meta'           => AIPKit_Content_Writer_Prompts::get_default_meta_prompt(),
                'keyword'        => AIPKit_Content_Writer_Prompts::get_default_keyword_prompt(),
                'image'          => AIPKit_Content_Writer_Prompts::get_default_image_prompt(),
                'featured_image' => AIPKit_Content_Writer_Prompts::get_default_featured_image_prompt(),
            ];
        }
        $frequencies = [];
        $wp_schedules = wp_get_schedules();
        foreach ($wp_schedules as $slug => $details) {
            $frequencies[$slug] = $details['display'];
        }
        wp_localize_script($admin_main_js_handle, 'aipkit_automated_tasks_config', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce_manage_tasks' => wp_create_nonce('aipkit_automated_tasks_manage_nonce'),
            'openai_vector_stores' => $openai_vector_stores,
            'pinecone_indexes' => $pinecone_indexes,
            'qdrant_collections' => $qdrant_collections,
            'openai_embedding_models' => $openai_embedding_models,
            'google_embedding_models' => $google_embedding_models,
            'azure_embedding_models' => $azure_embedding_models,
            'task_types' => $task_types_for_js,
            'default_cw_prompts' => $default_cw_prompts,
            'frequencies' => $frequencies,
            'text' => [
                'confirm_delete_task' => __('Are you sure you want to delete this automated task? This action cannot be undone.', 'gpt3-ai-content-generator'),
                'task_name_required' => __('Task name is required.', 'gpt3-ai-content-generator'),
                'task_type_required' => __('Task type is required.', 'gpt3-ai-content-generator'),
                'target_store_required' => __('Please select a target vector store/index.', 'gpt3-ai-content-generator'),
                'content_type_required' => __('Please select at least one content type.', 'gpt3-ai-content-generator'),
                'embedding_provider_required' => __('Embedding provider is required for this vector database.', 'gpt3-ai-content-generator'),
                'embedding_model_required' => __('Embedding model is required for this vector database.', 'gpt3-ai-content-generator'),
                'content_title_required_cw_task' => __('Content Title/Topic is required for this Content Writing task mode.', 'gpt3-ai-content-generator'),
                'ai_config_required_cw_task' => __('AI Provider and Model are required for Content Writing task.', 'gpt3-ai-content-generator'),
                'saving_task' => __('Saving Task...', 'gpt3-ai-content-generator'),
                'deleting_task' => __('Deleting Task...', 'gpt3-ai-content-generator'),
                'running_task' => __('Initiating Run...', 'gpt3-ai-content-generator'),
                'pausing_task' => __('Pausing Task...', 'gpt3-ai-content-generator'),
                'resuming_task' => __('Resuming Task...', 'gpt3-ai-content-generator'),
                'save_task_button' => __('Save Task', 'gpt3-ai-content-generator'),
                'create_task_button' => __('Create Task', 'gpt3-ai-content-generator'),
                'edit_task_title' => __('Edit Automated Task', 'gpt3-ai-content-generator'),
                'create_task_title' => __('Create New Automated Task', 'gpt3-ai-content-generator'),
                'loading_stores' => __('Loading stores...', 'gpt3-ai-content-generator'),
                'loading_indexes' => __('Loading indexes...', 'gpt3-ai-content-generator'),
                'select_target_store' => __('-- Select Target Store --', 'gpt3-ai-content-generator'),
                'select_target_index' => __('-- Select Target Index --', 'gpt3-ai-content-generator'),
                'no_targets_found_configure' => __('No targets found. Configure in AI Training.', 'gpt3-ai-content-generator'),
                'loading_models' => __('Loading models...', 'gpt3-ai-content-generator'),
                'select_embedding_model' => __('-- Select Model --', 'gpt3-ai-content-generator'),
                'no_embedding_models_sync' => __('No models - Sync in AI Settings.', 'gpt3-ai-content-generator'),
                'loading_tasks' => __('Loading tasks...', 'gpt3-ai-content-generator'),
                'error_loading_tasks' => __('Error loading tasks:', 'gpt3-ai-content-generator'),
                'no_tasks_configured' => __('No automated tasks configured yet.', 'gpt3-ai-content-generator'),
                'edit_button' => __('Edit', 'gpt3-ai-content-generator'),
                'pause_button' => __('Pause', 'gpt3-ai-content-generator'),
                'resume_button' => __('Resume', 'gpt3-ai-content-generator'),
                'run_now_button' => __('Run Now', 'gpt3-ai-content-generator'),
                'task_not_active_run_title' => __('Task must be active to run', 'gpt3-ai-content-generator'),
                'delete_button' => __('Delete', 'gpt3-ai-content-generator'),
                'never_run' => __('Never', 'gpt3-ai-content-generator'),
                'not_scheduled' => __('Not Scheduled', 'gpt3-ai-content-generator'),
                'task_deleted_success' => __('Task deleted successfully.', 'gpt3-ai-content-generator'),
                'error_deleting_task' => __('Error deleting task:', 'gpt3-ai-content-generator'),
                'task_status_updated' => __('Task status updated to', 'gpt3-ai-content-generator'),
                'error_updating_status' => __('Error updating task status:', 'gpt3-ai-content-generator'),
                'task_run_initiated' => __('Task run initiated. Check queue below for progress.', 'gpt3-ai-content-generator'),
                'error_initiating_run' => __('Error initiating task run:', 'gpt3-ai-content-generator'),
                'loading_queue' => __('Loading queue items...', 'gpt3-ai-content-generator'),
                'error_loading_queue' => __('Error loading queue:', 'gpt3-ai-content-generator'),
                'queue_empty' => __('Task queue is currently empty.', 'gpt3-ai-content-generator'),
                'target_id_prefix' => __('Target ID:', 'gpt3-ai-content-generator'),
                'task_id_prefix' => __('Task ID:', 'gpt3-ai-content-generator'),
                'not_applicable' => __('N/A', 'gpt3-ai-content-generator'),
                // Labels for Added/Scheduled display in queue table
                'added_at_label' => __('Added', 'gpt3-ai-content-generator'),
                'scheduled_for_label' => __('Scheduled', 'gpt3-ai-content-generator'),
                'item_singular' => __('item', 'gpt3-ai-content-generator'),
                'item_plural' => __('items', 'gpt3-ai-content-generator'),
                'page_label' => __('Page', 'gpt3-ai-content-generator'),
                'of_label' => __('of', 'gpt3-ai-content-generator'),
                'previous_button' => __('Previous', 'gpt3-ai-content-generator'),
                'next_button' => __('Next', 'gpt3-ai-content-generator'),
                'confirm_delete_queue_item' => __('Are you sure you want to remove this item from the queue?', 'gpt3-ai-content-generator'),
                /* translators: %s is the status of the queue items, e.g. "Failed" or "Pending" */
                'confirmDeleteQueueByStatus' => __('Are you sure you want to delete all %s items from the queue? This cannot be undone.', 'gpt3-ai-content-generator'),
                'confirmDeleteQueueAll' => __('Are you sure you want to delete ALL items from the queue? This cannot be undone.', 'gpt3-ai-content-generator'),
                'queue_item_deleted' => __('Queue item deleted.', 'gpt3-ai-content-generator'),
                'error_deleting_queue_item' => __('Error deleting item:', 'gpt3-ai-content-generator'),
                'errorDeletingAllItems' => __('Error deleting items:', 'gpt3-ai-content-generator'),
                'retry_button' => __('Retry', 'gpt3-ai-content-generator'),
                'item_marked_retry' => __('Item marked for retry. Queue processing will pick it up.', 'gpt3-ai-content-generator'),
                'error_retrying_item' => __('Error retrying item:', 'gpt3-ai-content-generator'),
                'task_singular' => __('task', 'gpt3-ai-content-generator'),
                'task_plural' => __('tasks', 'gpt3-ai-content-generator'),
            ]
        ]);
    }
}
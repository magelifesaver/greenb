<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/post-enhancer/class-aipkit-post-enhancer-assets.php
// Status: MODIFIED
// I have updated this file to add nonces and localized text for the new "Generate Tags" feature.

namespace WPAICG\Admin\Assets;

use WPAICG\AIPKit_Role_Manager;
use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\Vector\AIPKit_Vector_Store_Registry;
use WPAICG\PostEnhancer\Ajax\AIPKit_Enhancer_Actions_Ajax_Handler; // Added for default actions

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles enqueueing assets for the Content Enhancer feature.
 */
class PostEnhancerAssets
{
    public const MODULE_SLUG = 'ai_post_enhancer';

    private $version;
    private $is_admin_main_js_enqueued = false;
    private $is_admin_post_enhancer_css_enqueued = false;


    public function __construct()
    {
        $this->version = defined('WPAICG_VERSION') ? WPAICG_VERSION : '1.9.15';
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_post_enhancer_assets']);
    }

    public function enqueue_post_enhancer_assets($hook_suffix)
    {
        if (!AIPKit_Role_Manager::user_can_access_module(self::MODULE_SLUG)) {
            return;
        }

        $screen = get_current_screen();
        $is_aipkit_page = $screen && strpos($screen->id, 'page_wpaicg') !== false;
        $is_post_edit_screen = in_array($hook_suffix, ['post.php', 'post-new.php']);

        // --- MODIFICATION: Dynamically support all post types with an admin UI ---
        $ui_post_types = get_post_types(['show_ui' => true]);
        unset($ui_post_types['attachment']); // Exclude attachments from enhancement
        $supported_post_types = apply_filters('aipkit_post_enhancer_post_types', array_keys($ui_post_types));
        $is_post_list_screen = $screen && $screen->base === 'edit' && in_array($screen->post_type, $supported_post_types, true);
        // --- END MODIFICATION ---

        // Scripts (and their localized data) are needed on list, edit, and the main dashboard (for settings tab).
        if ($is_post_list_screen || $is_post_edit_screen || $is_aipkit_page) {
            $this->enqueue_scripts($screen->post_type);
        }

        // Styles are ONLY needed on the post list screen for row actions and the bulk enhance modal.
        if ($is_post_list_screen) {
            $this->enqueue_styles();
        }
    }

    private function enqueue_styles()
    {
        $dist_css_url = WPAICG_PLUGIN_URL . 'dist/css/';
        $post_enhancer_css_handle = 'aipkit-admin-post-enhancer-css';
        $admin_main_css_handle = 'aipkit-admin-main-css';

        if (!wp_style_is($admin_main_css_handle, 'registered')) {
            // Fallback registration in case DashboardAssets didn't register it (e.g. load order)
            wp_register_style(
                $admin_main_css_handle,
                $dist_css_url . 'admin-main.bundle.css',
                ['dashicons'],
                $this->version
            );
        }


        if (!wp_style_is($post_enhancer_css_handle, 'registered')) {
            wp_register_style(
                $post_enhancer_css_handle,
                $dist_css_url . 'admin-post-enhancer.bundle.css',
                [$admin_main_css_handle],
                $this->version
            );
        }
        if (!$this->is_admin_post_enhancer_css_enqueued && !wp_style_is($post_enhancer_css_handle, 'enqueued')) {
            wp_enqueue_style($post_enhancer_css_handle);
            $this->is_admin_post_enhancer_css_enqueued = true;
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

        // --- MODIFIED: Call static localization from DashboardAssets ---
        if (class_exists(DashboardAssets::class) && method_exists(DashboardAssets::class, 'localize_core_data')) {
            DashboardAssets::localize_core_data($this->version);
        }
        // --- END MODIFICATION ---


        static $post_enhancer_localized = false;
        if (!$post_enhancer_localized) {
            $opts = get_option('aipkit_options', []);
            $default_insert_position = isset($opts['enhancer_settings']['default_insert_position']) ? sanitize_key($opts['enhancer_settings']['default_insert_position']) : 'replace';
            if (!in_array($default_insert_position, ['replace','after','before'], true)) {
                $default_insert_position = 'replace';
            }
            $default_ai_config = AIPKit_Providers::get_default_provider_config();
            $default_ai_params = AIPKIT_AI_Settings::get_ai_parameters();
            $openai_vector_stores = class_exists(AIPKit_Vector_Store_Registry::class) ? AIPKit_Vector_Store_Registry::get_registered_stores_by_provider('OpenAI') : [];
            $pinecone_indexes = class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_pinecone_indexes() : [];
            $qdrant_collections = class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_qdrant_collections() : [];
            $openai_embedding_models = class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_openai_embedding_models() : [];
            $google_embedding_models = class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_google_embedding_models() : [];
            $azure_embedding_models = class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_azure_embedding_models() : [];

            // Define default prompts for the bulk enhancer modal
            $default_prompts = [
                'title'   => "You are an expert SEO copywriter. Generate the single best and most compelling SEO title based on the provided information. The title must:\n- Be under 60 characters\n- Start with the main focus keyword\n- Include at least one power word (e.g., Stunning, Must-Have, Exclusive)\n- Include a positive or negative sentiment word (e.g., Best, Effortless, Affordable)\n\nReturn ONLY the new title text. Do not include any introduction, explanation, or quotation marks.\n\nOriginal title: \"{original_title}\"\nPost content snippet: \"{original_content}\"\nFocus keyword: \"{original_focus_keyword}\"",

                'excerpt' => "Rewrite the post excerpt to be more compelling and engaging based on the information provided. Use a friendly tone and aim for 1–2 concise sentences. Return ONLY the new excerpt without any explanation or formatting.\n\nPost title: \"{original_title}\"\nPost content snippet: \"{original_content}\"",

                'content' => "You are an expert editor. Rewrite and improve the following article to make it more engaging, clear, and informative. Maintain the original tone and intent, but enhance the writing quality. Ensure the following:\n- The revised content is at least 600 words long\n- The focus keyword appears in one or more subheadings (H2 or H3)\n- The focus keyword is used naturally throughout the article, especially in the introduction and conclusion\n\nThe article title is: {original_title}\nFocus keyword: {original_focus_keyword}\n\nOriginal Content:\n{original_content}",

                'meta'    => "Generate a single, concise, and SEO-friendly meta description (under 155 characters) for a web page based on the provided information. The description must:\n- Begin with or include the focus keyword near the start\n- Use an active voice\n- Include a clear call-to-action\n\nReturn ONLY the new meta description without any introduction or formatting.\n\nPage title: \"{original_title}\"\nPage content snippet: \"{original_content}\"\nFocus keyword: \"{original_focus_keyword}\"",

                'keyword' => "You are an SEO expert. Your task is to identify the single most important and relevant focus keyphrase for the following article. The keyphrase should be concise (ideally 2–4 words) and must be present within the provided content.\n\nReturn ONLY the keyphrase. Do not add any explanation, labels, or quotation marks.\n\nArticle Title: \"{original_title}\"\nArticle Content:\n{original_content}",

                'tags'    => "You are an SEO expert. Generate a list of 5–10 relevant tags for a blog post titled \"{original_title}\". Return ONLY a comma-separated list of the tags. Do not include any introduction, explanation, or numbering.\n\nArticle Content Snippet:\n{original_content}"
            ];
            
            // --- ADDED: Fetch custom and default actions ---
            $enhancer_actions = get_option('aipkit_enhancer_actions', []);
            if (empty($enhancer_actions) && class_exists(AIPKit_Enhancer_Actions_Ajax_Handler::class)) {
                // Initialize with defaults if option is empty
                $enhancer_actions = (new AIPKit_Enhancer_Actions_Ajax_Handler())->get_default_actions_public(); // Assuming a new public method
            }
            // --- END ADDED ---

            // Developer filter for enabling inline formatting parsing (always true by default)
            $parse_formats_enabled = apply_filters('aipkit_enhancer_enable_formatting', true);

            wp_localize_script($admin_main_js_handle, 'aipkit_post_enhancer', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce_generate_title' => wp_create_nonce('aipkit_generate_title_nonce'),
                'nonce_update_title' => wp_create_nonce('aipkit_update_title_nonce'),
                'nonce_generate_excerpt' => wp_create_nonce('aipkit_generate_excerpt_nonce'),
                'nonce_update_excerpt' => wp_create_nonce('aipkit_update_excerpt_nonce'),
                'nonce_generate_meta' => wp_create_nonce('aipkit_generate_meta_nonce'),
                'nonce_update_meta' => wp_create_nonce('aipkit_update_meta_nonce'),
                'nonce_generate_tags' => wp_create_nonce('aipkit_generate_tags_nonce'),
                'nonce_update_tags' => wp_create_nonce('aipkit_update_tags_nonce'),
                'nonce_process_text' => wp_create_nonce('aipkit_process_enhancer_text_nonce'),
                'nonce_manage_templates' => wp_create_nonce('aipkit_content_writer_template_nonce'),
                'nonce_manage_actions' => wp_create_nonce('aipkit_enhancer_actions_nonce'),
                'default_ai_provider' => $default_ai_config['provider'] ?? 'N/A',
                'default_ai_model' => $default_ai_config['model'] ?? 'N/A',
                'default_ai_params' => $default_ai_params,
                'default_prompts' => $default_prompts,
                'openai_vector_stores' => $openai_vector_stores,
                'pinecone_indexes' => $pinecone_indexes,
                'qdrant_collections' => $qdrant_collections,
                'openaiEmbeddingModels' => $openai_embedding_models,
                'googleEmbeddingModels' => $google_embedding_models,
                'azureEmbeddingModels' => $azure_embedding_models,
                'actions' => $enhancer_actions, // ADDED
                'parse_html_formats' => (bool) $parse_formats_enabled,
                'default_insert_position' => $default_insert_position,
                'text' => [
                    'modal_title_title' => __('Title Suggestions', 'gpt3-ai-content-generator'),
                    'loading_title' => __('Generating Title Suggestions...', 'gpt3-ai-content-generator'),
                    'updating_title' => __('Updating Title...', 'gpt3-ai-content-generator'),
                    'error_loading_title' => __('Error loading title suggestions.', 'gpt3-ai-content-generator'),
                    'error_updating_title' => __('Error updating title.', 'gpt3-ai-content-generator'),
                    'no_suggestions_title' => __('No title suggestions generated or AI Error.', 'gpt3-ai-content-generator'),
                    'select_title' => __('Click a title to apply:', 'gpt3-ai-content-generator'),
                    'modal_title_excerpt' => __('Excerpt Suggestions', 'gpt3-ai-content-generator'),
                    'loading_excerpt' => __('Generating Excerpt Suggestions...', 'gpt3-ai-content-generator'),
                    'updating_excerpt' => __('Updating Excerpt...', 'gpt3-ai-content-generator'),
                    'error_loading_excerpt' => __('Error loading excerpt suggestions.', 'gpt3-ai-content-generator'),
                    'error_updating_excerpt' => __('Error updating excerpt.', 'gpt3-ai-content-generator'),
                    'no_suggestions_excerpt' => __('No excerpt suggestions generated or AI Error.', 'gpt3-ai-content-generator'),
                    'select_excerpt' => __('Click an excerpt to apply:', 'gpt3-ai-content-generator'),
                    'modal_title_meta' => __('Meta Description Suggestions', 'gpt3-ai-content-generator'),
                    'loading_meta' => __('Generating Meta Descriptions...', 'gpt3-ai-content-generator'),
                    'updating_meta' => __('Updating Meta Description...', 'gpt3-ai-content-generator'),
                    'error_loading_meta' => __('Error loading meta description suggestions.', 'gpt3-ai-content-generator'),
                    'error_updating_meta' => __('Error updating meta description.', 'gpt3-ai-content-generator'),
                    'no_suggestions_meta' => __('No meta description suggestions generated or AI Error.', 'gpt3-ai-content-generator'),
                    'select_meta' => __('Click a meta description to apply:', 'gpt3-ai-content-generator'),
                    'modal_title_tags' => __('Tag Suggestions', 'gpt3-ai-content-generator'),
                    'loading_tags' => __('Generating Tag Suggestions...', 'gpt3-ai-content-generator'),
                    'updating_tags' => __('Updating Tags...', 'gpt3-ai-content-generator'),
                    'error_loading_tags' => __('Error loading tag suggestions.', 'gpt3-ai-content-generator'),
                    'error_updating_tags' => __('Error updating tags.', 'gpt3-ai-content-generator'),
                    'no_suggestions_tags' => __('No tag suggestions generated or AI Error.', 'gpt3-ai-content-generator'),
                    'select_tags' => __('Click a tag set to apply:', 'gpt3-ai-content-generator'),
                    /* translators: %s is the name of the post type, e.g. "Post" or "Page" */
                    'loading_info_template' => __('Using <strong>%1$s</strong> (Model: <strong>%2$s</strong>, Temp: %3$s)', 'gpt3-ai-content-generator'),
                    'close' => __('Close', 'gpt3-ai-content-generator'),
                    'config_modal_title' => __('Configure AI Actions', 'gpt3-ai-content-generator'),
                    'add_new_action' => __('Add New', 'gpt3-ai-content-generator'),
                    'edit_action' => __('Edit', 'gpt3-ai-content-generator'),
                    'delete_action' => __('Delete', 'gpt3-ai-content-generator'),
                    'action_label' => __('Action Label', 'gpt3-ai-content-generator'),
                    'action_prompt' => __('Action Prompt', 'gpt3-ai-content-generator'),
                    'insert_position' => __('Position', 'gpt3-ai-content-generator'),
                    'use_default_position' => __('Use default', 'gpt3-ai-content-generator'),
                    'replace_selection' => __('Replace selection', 'gpt3-ai-content-generator'),
                    'insert_after' => __('Insert after', 'gpt3-ai-content-generator'),
                    'insert_before' => __('Insert before', 'gpt3-ai-content-generator'),
                    'reset_actions' => __('Reset to Defaults', 'gpt3-ai-content-generator'),
                    'confirm_reset_actions' => __('Reset all actions to the default set? This will replace current customizations.', 'gpt3-ai-content-generator'),
                    'actions_reset' => __('Actions reset to defaults.', 'gpt3-ai-content-generator'),
                    'save_action' => __('Save Action', 'gpt3-ai-content-generator'),
                    'saving_action' => __('Saving...', 'gpt3-ai-content-generator'),
                    'confirm_delete_action' => __('Are you sure you want to delete this action? This cannot be undone.', 'gpt3-ai-content-generator'),
                    'deleting_action' => __('Deleting...', 'gpt3-ai-content-generator'),
                    'action_deleted' => __('Action deleted.', 'gpt3-ai-content-generator'),
                    'action_saved' => __('Action saved.', 'gpt3-ai-content-generator'),
                    'loading_actions' => __('Loading actions...', 'gpt3-ai-content-generator'),
                    /* translators: %s is the name of the post type, e.g. "Post" or "Page" */
                    'prompt_placeholder_info' => __('Use %s as a placeholder for the selected text.', 'gpt3-ai-content-generator'),
                ],
                'settings_url' => admin_url('admin.php?page=wpaicg#settings'),
            ]);
            $post_enhancer_localized = true;
        }
    }
}

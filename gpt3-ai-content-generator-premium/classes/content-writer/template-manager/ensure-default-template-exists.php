<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/template-manager/ensure-default-template-exists.php
// Status: MODIFIED
// I have updated this file to create a personal, editable "Default Template" for each user instead of a single, global, un-editable one.

namespace WPAICG\ContentWriter\TemplateManagerMethods;

use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\ContentWriter\AIPKit_Content_Writer_Prompts;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Logic for ensuring a user-specific default template exists.
*
* @param \WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance The instance of the template manager.
*/
function ensure_default_template_exists_logic(\WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance)
{
    $wpdb = $managerInstance->get_wpdb();
    $table_name = $managerInstance->get_table_name();

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        return; // Do not create default templates for logged-out users/processes
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
    $default_template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d AND is_default = 1 AND template_type = 'content_writer' LIMIT 1", $current_user_id));

    if (!$default_template) {
        if (!class_exists(AIPKit_Providers::class) || !class_exists(AIPKIT_AI_Settings::class)) {
            return;
        }

        $default_provider_config = AIPKit_Providers::get_default_provider_config();
        $ai_parameters = AIPKIT_AI_Settings::get_ai_parameters();

        $provider_for_template = $default_provider_config['provider'] ?? 'OpenAI';

        $model_for_template = '';
        switch (strtolower($provider_for_template)) {
            case 'openai':
                $model_for_template = 'gpt-4.1-mini';
                break;
            case 'google':
                $model_for_template = 'gemini-2.5-flash';
                break;
            case 'openrouter':
                $model_for_template = 'anthropic/claude-3.7-sonnet';
                break;
            case 'azure':
            case 'deepseek':
            case 'ollama':
            default:
                $model_for_template = $default_provider_config['model'] ?? '';
                break;
        }

        $default_config = [
        'ai_provider' => $provider_for_template,
        'ai_model' => $model_for_template,
        'content_title' => '',
        'content_keywords' => '',
        'ai_temperature' => (string)($ai_parameters['temperature'] ?? 1.0),
        'content_max_tokens' => (string)($ai_parameters['max_completion_tokens'] ?? 4000),
        'post_type' => 'post',
        'post_author' => $current_user_id ?: 1,
        'post_status' => 'draft',
        'post_schedule_date' => '',
        'post_schedule_time' => '',
        'post_categories' => [],
        'prompt_mode' => 'custom',
        'custom_title_prompt' => AIPKit_Content_Writer_Prompts::get_default_title_prompt(),
        'custom_content_prompt' => AIPKit_Content_Writer_Prompts::get_default_content_prompt(),
        'generate_meta_description' => '1',
        'custom_meta_prompt' => AIPKit_Content_Writer_Prompts::get_default_meta_prompt(),
        'generate_focus_keyword' => '1',
        'custom_keyword_prompt' => AIPKit_Content_Writer_Prompts::get_default_keyword_prompt(),
        'generate_excerpt' => '1',
        'custom_excerpt_prompt' => AIPKit_Content_Writer_Prompts::get_default_excerpt_prompt(),
        'generate_tags' => '1',
        'custom_tags_prompt' => AIPKit_Content_Writer_Prompts::get_default_tags_prompt(),
        'cw_generation_mode' => 'single',
        'rss_feeds' => '',
        'rss_include_keywords' => '',
        'rss_exclude_keywords' => '',
        'gsheets_sheet_id' => '',
        'gsheets_credentials' => '',
        'url_list' => '',
        'generate_toc' => '0',
        'generate_images_enabled' => '0',
        'image_provider' => 'openai',
        'image_model' => 'gpt-image-1',
        'image_prompt' => AIPKit_Content_Writer_Prompts::get_default_image_prompt(),
        'image_count' => '1',
        'image_placement' => 'after_first_h2',
        'image_placement_param_x' => '2',
        'image_alignment' => 'none',
        'image_size' => 'large',
        'generate_featured_image' => '0',
        'featured_image_prompt' => AIPKit_Content_Writer_Prompts::get_default_featured_image_prompt(),
        'pexels_orientation' => 'none',
        'pexels_size' => 'none',
        'pexels_color' => '',
        'pixabay_orientation' => 'all',
        'pixabay_image_type' => 'all',
        'pixabay_category' => '',
        'enable_vector_store' => '0',
        'vector_store_provider' => 'openai',
        'openai_vector_store_ids' => [],
        'pinecone_index_name' => '',
        'qdrant_collection_name' => '',
        'vector_embedding_provider' => 'openai',
        'vector_embedding_model' => 'text-embedding-3-small',
        'vector_store_top_k' => '3',
    'vector_store_confidence_threshold' => '20',
        ];
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct insert to a custom table. Caches will be invalidated.
        $wpdb->insert(
            $table_name,
            [
            'user_id' => $current_user_id,
            'template_name' => __('Default Template', 'gpt3-ai-content-generator'),
            'template_type' => 'content_writer',
            'config' => wp_json_encode($default_config),
            'is_default' => 1,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1),
            'post_type' => $default_config['post_type'],
            'post_author' => $default_config['post_author'],
            'post_status' => $default_config['post_status'],
            'post_schedule' => null,
            'post_categories' => wp_json_encode([]),
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );
    } else {
        // Default template exists, check if it needs updating with new fields.
        $config = json_decode($default_template->config, true);
        $needs_update = false;

        if (!isset($config['generate_tags'])) {
            $config['generate_tags'] = '1';
            $needs_update = true;
        }

        if (!isset($config['custom_tags_prompt'])) {
            $config['custom_tags_prompt'] = AIPKit_Content_Writer_Prompts::get_default_tags_prompt();
            $needs_update = true;
        }

        if (!isset($config['generate_excerpt'])) {
            $config['generate_excerpt'] = '1';
            $needs_update = true;
        }
        if (!isset($config['custom_excerpt_prompt'])) {
            $config['custom_excerpt_prompt'] = AIPKit_Content_Writer_Prompts::get_default_excerpt_prompt();
            $needs_update = true;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct update to a custom table. Caches will be invalidated.
    if ($needs_update) {$wpdb->update(
                $table_name,
                ['config' => wp_json_encode($config)],
                ['id' => $default_template->id],
                ['%s'],
                ['%d']
            );
        }
    }
}
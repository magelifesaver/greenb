<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/actions/save-task/build-task-config-writing.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Ajax\Actions\SaveTask;

use WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Builds and validates the configuration for a 'content_writing' task.
* UPDATED: Now handles different generation modes (RSS, GSheets, URL) and saves their specific data.
* UPDATED: Now handles vector store settings.
*
* @param array $post_data The raw POST data.
* @return array|WP_Error The validated config array or WP_Error on failure.
*/
function build_task_config_writing_logic(array $post_data): array|WP_Error
{
    $content_writer_config = [];
    if (class_exists(AIPKit_Content_Writer_Template_Manager::class)) {
        // This list should ideally mirror the one in AIPKit_Content_Writer_Template_Manager for consistency.
        $allowed_keys_from_template_manager = [
            'ai_provider', 'ai_model', 'content_title_bulk', 'content_keywords',
            'ai_temperature', 'content_max_tokens', 'post_type', 'post_author',
            'post_status',
            'schedule_mode', 'smart_schedule_start_datetime', 'smart_schedule_interval_value', 'smart_schedule_interval_unit',
            'post_categories',
            'prompt_mode', 'custom_title_prompt', 'custom_content_prompt',
            'generate_meta_description', 'custom_meta_prompt',
            'generate_focus_keyword', 'custom_keyword_prompt',
            'generate_excerpt', 'custom_excerpt_prompt',
            'generate_tags', 'custom_tags_prompt',
            'cw_generation_mode', 'rss_feeds',
            'gsheets_sheet_id', 'gsheets_credentials',
            'url_list',
            'content_title',
            'generate_toc',
            'generate_seo_slug', // NEW: Add generate_seo_slug
            'generate_images_enabled', 'image_provider', 'image_model', 'image_prompt',
            'image_count', 'image_placement', 'image_placement_param_x', 'image_alignment', 'image_size',
            'generate_featured_image', 'featured_image_prompt',
            'pexels_orientation', 'pexels_size', 'pexels_color',
            'pixabay_orientation', 'pixabay_image_type', 'pixabay_category',
            'enable_vector_store', 'vector_store_provider', 'openai_vector_store_ids',
            'pinecone_index_name', 'qdrant_collection_name', 'vector_embedding_provider',
            'vector_embedding_model', 'vector_store_top_k',
            'rss_include_keywords', 'rss_exclude_keywords',
            'reasoning_effort',
        ];

        foreach ($allowed_keys_from_template_manager as $key) {
            if (isset($post_data[$key])) {
                if (in_array($key, ['content_title_bulk', 'custom_title_prompt', 'custom_content_prompt', 'custom_meta_prompt', 'custom_keyword_prompt', 'custom_excerpt_prompt', 'custom_tags_prompt', 'rss_feeds', 'url_list', 'image_prompt', 'featured_image_prompt', 'rss_include_keywords', 'rss_exclude_keywords', 'content_title', 'smart_schedule_start_datetime'], true)) {
                    $content_writer_config[$key] = sanitize_textarea_field(wp_unslash($post_data[$key]));
                } elseif ($key === 'gsheets_credentials') {
                    if (class_exists('\WPAICG\Lib\Utils\AIPKit_Google_Credentials_Handler')) {
                        // The handler returns an array or null, which will be properly JSON encoded later.
                        $content_writer_config[$key] = \WPAICG\Lib\Utils\AIPKit_Google_Credentials_Handler::process_credentials($post_data[$key]);
                    } else {
                        $content_writer_config[$key] = null;
                    }
        } elseif ($key === 'ai_provider' || $key === 'image_provider') {
                    $provider_raw = sanitize_text_field(wp_unslash($post_data[$key]));
                    $content_writer_config[$key] = match (strtolower($provider_raw)) {
            'openai' => 'OpenAI', 'openrouter' => 'OpenRouter', 'google' => 'Google', 'azure' => 'Azure', 'deepseek' => 'DeepSeek', 'ollama' => 'Ollama',
                        default => ucfirst(strtolower($provider_raw))
                    };
                } elseif (in_array($key, ['generate_meta_description', 'generate_focus_keyword', 'generate_excerpt', 'generate_tags', 'generate_toc', 'generate_images_enabled', 'generate_featured_image', 'enable_vector_store', 'generate_seo_slug'], true)) {
                    $content_writer_config[$key] = ($post_data[$key] === '1' || $post_data[$key] === true || $post_data[$key] === 1) ? '1' : '0';
                } elseif ($key === 'post_categories' && is_array($post_data[$key])) {
                    $content_writer_config[$key] = array_map('absint', $post_data[$key]);
                } elseif ($key === 'post_author' || in_array($key, ['image_count', 'image_placement_param_x', 'vector_store_top_k', 'smart_schedule_interval_value'], true)) {
                    $content_writer_config[$key] = absint($post_data[$key]);
                } elseif ($key === 'ai_temperature') {
                    $content_writer_config[$key] = (string)floatval($post_data[$key]);
                } elseif ($key === 'openai_vector_store_ids' && is_array($post_data[$key])) {
                    $content_writer_config[$key] = array_map('sanitize_text_field', $post_data[$key]);
                } elseif (in_array($key, ['schedule_mode', 'smart_schedule_interval_unit', 'reasoning_effort'], true)) {
                    $content_writer_config[$key] = sanitize_key($post_data[$key]);
                } elseif (is_string($post_data[$key])) {
                    $content_writer_config[$key] = sanitize_text_field(wp_unslash($post_data[$key]));
                } else {
                    $content_writer_config[$key] = $post_data[$key];
                }
            }
        }

        // Handle content_title mapping from bulk field
        if (!empty($content_writer_config['content_title_bulk'])) {
            $content_writer_config['content_title'] = $content_writer_config['content_title_bulk'];
            unset($content_writer_config['content_title_bulk']);
        }

        // --- ADDED: Store the generation mode ---
        $task_type = $post_data['task_type'] ?? 'content_writing_bulk';
        $mode = str_replace('content_writing_', '', $task_type);
        if ($mode === 'content_writing') {
            $mode = 'bulk';
        } // The base type means bulk
        $content_writer_config['cw_generation_mode'] = $mode;
        // --- END ADDED ---


        $content_writer_config['task_frequency'] = isset($post_data['task_frequency']) ? sanitize_key($post_data['task_frequency']) : 'daily';
        $content_writer_config['task_status_on_creation'] = isset($post_data['task_status']) ? sanitize_key($post_data['task_status']) : 'active';
    }
    return $content_writer_config;
}
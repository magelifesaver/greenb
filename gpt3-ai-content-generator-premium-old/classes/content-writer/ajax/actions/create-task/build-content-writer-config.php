<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/create-task/build-content-writer-config.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions\CreateTask;

use WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Builds and validates the specific configuration array for the content writing task.
* UPDATED: Removed guided mode fields.
*
* @param array $settings The raw POST data.
* @param string $task_frequency The sanitized task frequency.
* @param string $task_status The sanitized task status.
* @return array The sanitized content writer config array.
*/
function build_content_writer_config_logic(array $settings, string $task_frequency, string $task_status): array
{
    $content_writer_config = [];
    if (class_exists(AIPKit_Content_Writer_Template_Manager::class)) {
        // This list should ideally mirror the one in AIPKit_Content_Writer_Template_Manager for consistency.
        $allowed_keys_from_template_manager = [
            'ai_provider', 'ai_model', 'content_title_bulk', 'content_keywords',
            'ai_temperature', 'content_max_tokens', 'post_type', 'post_author',
            'post_status', 'post_schedule_date', 'post_schedule_time',
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
            'generate_images_enabled', 'image_provider', 'image_model', 'image_prompt',
            'image_count', 'image_placement', 'image_placement_param_x', 'image_alignment', 'image_size',
            'generate_featured_image', 'featured_image_prompt',
            'pexels_orientation', 'pexels_size', 'pexels_color',
            'pixabay_orientation', 'pixabay_image_type', 'pixabay_category',
            'enable_vector_store', 'vector_store_provider', 'openai_vector_store_ids',
            'pinecone_index_name', 'qdrant_collection_name', 'vector_embedding_provider',
            'vector_embedding_model', 'vector_store_top_k',
            'vector_store_confidence_threshold',
            'rss_include_keywords', 'rss_exclude_keywords',
            'reasoning_effort', // ADDED
        ];

        foreach ($allowed_keys_from_template_manager as $key) {
            if (isset($settings[$key])) {
                if (in_array($key, ['content_title_bulk', 'custom_title_prompt', 'custom_content_prompt', 'custom_meta_prompt', 'custom_keyword_prompt', 'custom_excerpt_prompt', 'custom_tags_prompt', 'rss_feeds', 'url_list', 'image_prompt', 'featured_image_prompt', 'rss_include_keywords', 'rss_exclude_keywords', 'content_title', 'smart_schedule_start_datetime'], true)) {
                    $content_writer_config[$key] = sanitize_textarea_field(wp_unslash($settings[$key]));
                } elseif ($key === 'gsheets_credentials') {
                    if (class_exists('\WPAICG\Lib\Utils\AIPKit_Google_Credentials_Handler')) {
                        // The handler returns an array or null, which will be properly JSON encoded later.
                        $content_writer_config[$key] = \WPAICG\Lib\Utils\AIPKit_Google_Credentials_Handler::process_credentials($settings[$key]);
                    } else {
                        $content_writer_config[$key] = null;
                    }
        } elseif ($key === 'ai_provider' || $key === 'image_provider') {
                    $provider_raw = sanitize_text_field(wp_unslash($settings[$key]));
                    $content_writer_config[$key] = match (strtolower($provider_raw)) {
            'openai' => 'OpenAI', 'openrouter' => 'OpenRouter', 'google' => 'Google', 'azure' => 'Azure', 'deepseek' => 'DeepSeek', 'ollama' => 'Ollama',
                        default => ucfirst(strtolower($provider_raw))
                    };
                } elseif (in_array($key, ['generate_meta_description', 'generate_focus_keyword', 'generate_excerpt', 'generate_tags', 'generate_toc', 'generate_images_enabled', 'generate_featured_image', 'enable_vector_store'], true)) {
                    $content_writer_config[$key] = ($settings[$key] === '1' || $settings[$key] === true || $settings[$key] === 1) ? '1' : '0';
                } elseif ($key === 'post_categories' && is_array($settings[$key])) {
                    $content_writer_config[$key] = array_map('absint', $settings[$key]);
                } elseif (in_array($key, ['post_author', 'image_count', 'image_placement_param_x', 'vector_store_top_k', 'smart_schedule_interval_value'], true)) {
                    $content_writer_config[$key] = absint($settings[$key]);
                } elseif ($key === 'vector_store_confidence_threshold') {
                    $raw = isset($settings[$key]) ? absint($settings[$key]) : 20;
                    $content_writer_config[$key] = max(0, min($raw, 100));
                } elseif ($key === 'ai_temperature') {
                    $content_writer_config[$key] = (string)floatval($settings[$key]);
                } elseif ($key === 'openai_vector_store_ids' && is_array($settings[$key])) {
                    $content_writer_config[$key] = array_map('sanitize_text_field', $settings[$key]);
                } elseif (in_array($key, ['post_type', 'post_status', 'prompt_mode', 'cw_generation_mode', 'image_provider', 'image_placement', 'image_alignment', 'image_size', 'vector_store_provider', 'vector_embedding_provider', 'pexels_orientation', 'pexels_size', 'pexels_color', 'pixabay_orientation', 'pixabay_image_type', 'pixabay_category', 'schedule_mode', 'smart_schedule_interval_unit', 'reasoning_effort'], true)) {
                    $content_writer_config[$key] = sanitize_key($settings[$key]);
                } elseif (is_string($settings[$key])) {
                    $content_writer_config[$key] = sanitize_text_field(wp_unslash($settings[$key]));
                } else {
                    $content_writer_config[$key] = $settings[$key];
                }
            }
        }

        // Handle content_title mapping from bulk field
        if (!empty($content_writer_config['content_title_bulk'])) {
            $content_writer_config['content_title'] = $content_writer_config['content_title_bulk'];
            unset($content_writer_config['content_title_bulk']);
        }


        $content_writer_config['task_frequency'] = $task_frequency;
        $content_writer_config['task_status_on_creation'] = $task_status;
    }
    return $content_writer_config;
}
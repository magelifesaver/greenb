<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/template-manager/sanitize-config.php
// Status: MODIFIED
// I have added 'tags' to the list of allowed config keys to fix a bug where the enhancer template would not save the state of the 'Tags' checkbox.

namespace WPAICG\ContentWriter\TemplateManagerMethods;

use WP_Error;

// Load all the new method logic files
$methods_path = __DIR__ . '/';
// No direct dependencies needed for this file's logic

if (!defined('ABSPATH')) {
    exit;
}

/**
* Sanitizes the configuration array for a template.
*
* @param \WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance The instance of the template manager.
* @param array $config The raw configuration array.
* @return array The sanitized configuration array.
*/
function sanitize_config_logic(\WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance, array $config): array
{
    $sanitized = [];
    $allowed_config_keys = $managerInstance->get_allowed_config_keys();


    foreach ($allowed_config_keys as $key) {
        if (isset($config[$key])) {
            if (in_array($key, ['content_title', 'content_title_bulk', 'custom_title_prompt', 'custom_content_prompt', 'custom_meta_prompt', 'custom_keyword_prompt', 'custom_excerpt_prompt', 'custom_tags_prompt', 'rss_feeds', 'url_list', 'image_prompt', 'featured_image_prompt', 'rss_include_keywords', 'rss_exclude_keywords', 'title_prompt', 'excerpt_prompt', 'content_prompt', 'meta_prompt', 'keyword_prompt'], true)) {
                $sanitized[$key] = sanitize_textarea_field(wp_unslash($config[$key]));
            } elseif (in_array($key, ['title', 'excerpt', 'content', 'meta', 'keyword', 'tags'], true) && is_array($config[$key])) {
                $sanitized_sub_array = [];
                if (isset($config[$key]['enabled'])) {
                    $sanitized_sub_array['enabled'] = ($config[$key]['enabled'] === '1' || $config[$key]['enabled'] === true) ? '1' : '0';
                }
                if (isset($config[$key]['prompt'])) {
                    $sanitized_sub_array['prompt'] = sanitize_textarea_field(wp_unslash($config[$key]['prompt']));
                }
                $sanitized[$key] = $sanitized_sub_array;
            } elseif ($key === 'gsheets_credentials') {
                if (class_exists('\WPAICG\Lib\Utils\AIPKit_Google_Credentials_Handler')) {
                    // The handler returns an array or null, which will be properly JSON encoded later when the whole config is saved.
                    $sanitized[$key] = \WPAICG\Lib\Utils\AIPKit_Google_Credentials_Handler::process_credentials($config[$key]);
                } else {
                    $sanitized[$key] = null;
                }
            } elseif ($key === 'ai_temperature') {
                $sanitized[$key] = (string)floatval($config[$key]);
            } elseif (in_array($key, ['content_max_tokens', 'image_count', 'image_placement_param_x', 'vector_store_top_k'], true)) {
                $sanitized[$key] = absint($config[$key]);
            } elseif ($key === 'vector_store_confidence_threshold') {
                $raw = isset($config[$key]) ? absint($config[$key]) : 20;
                $sanitized[$key] = max(0, min($raw, 100));
            } elseif (in_array($key, ['generate_meta_description', 'generate_focus_keyword', 'generate_excerpt', 'generate_tags', 'generate_toc', 'generate_images_enabled', 'generate_featured_image', 'enable_vector_store', 'update_title', 'update_excerpt', 'update_content', 'update_meta'], true)) {
                $sanitized[$key] = ($config[$key] === '1' || $config[$key] === true || $config[$key] === 1) ? '1' : '0';
            } elseif ($key === 'reasoning_effort') {
                // Sanitize reasoning_effort to only allow valid values
                $valid_reasoning_efforts = ['minimal', 'low', 'medium', 'high'];
                $sanitized[$key] = in_array($config[$key], $valid_reasoning_efforts, true) ? $config[$key] : 'low';
            } elseif (in_array($key, ['post_type', 'post_status', 'ai_provider', 'prompt_mode', 'cw_generation_mode', 'image_provider', 'image_placement', 'image_alignment', 'image_size', 'vector_store_provider', 'vector_embedding_provider', 'pexels_orientation', 'pexels_size', 'pexels_color', 'pixabay_orientation', 'pixabay_image_type', 'pixabay_category'], true)) {
                $sanitized[$key] = sanitize_key($config[$key]);
            } elseif (is_string($config[$key])) {
                $sanitized[$key] = sanitize_text_field(wp_unslash($config[$key]));
            } else {
                $sanitized[$key] = $config[$key];
            }
        }
    }
    return $sanitized;
}
<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/actions/save-task/build-task-config-enhancement.php
// Status: MODIFIED
// I have updated this file to read the unprefixed keys from the form submission, which aligns with the data remapping performed by the frontend JavaScript.

namespace WPAICG\AutoGPT\Ajax\Actions\SaveTask;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Builds and validates the configuration for an 'enhance_existing_content' task.
*
* @param array $post_data The raw POST data.
* @return array|WP_Error The validated config array or WP_Error on failure.
*/
function build_task_config_enhancement_logic(array $post_data): array|WP_Error
{
    $task_config = [];
    $task_config['post_types'] = isset($post_data['post_types']) && is_array($post_data['post_types']) ? array_map('sanitize_key', $post_data['post_types']) : [];
    $task_config['post_categories'] = isset($post_data['post_categories']) && is_array($post_data['post_categories']) ? array_map('absint', $post_data['post_categories']) : [];
    $task_config['post_authors'] = isset($post_data['post_authors']) && is_array($post_data['post_authors']) ? array_map('absint', $post_data['post_authors']) : [];
    $task_config['post_statuses'] = isset($post_data['post_statuses']) && is_array($post_data['post_statuses']) ? array_map('sanitize_key', $post_data['post_statuses']) : ['publish'];

    // Fields to enhance
    $task_config['update_title'] = isset($post_data['update_title']) ? '1' : '0';
    $task_config['update_excerpt'] = isset($post_data['update_excerpt']) ? '1' : '0';
    $task_config['update_meta'] = isset($post_data['update_meta']) ? '1' : '0';
    $task_config['update_content'] = isset($post_data['update_content']) ? '1' : '0';

    // Prompts
    $task_config['title_prompt'] = isset($post_data['title_prompt']) ? sanitize_textarea_field(wp_unslash($post_data['title_prompt'])) : '';
    $task_config['excerpt_prompt'] = isset($post_data['excerpt_prompt']) ? sanitize_textarea_field(wp_unslash($post_data['excerpt_prompt'])) : '';
    $task_config['meta_prompt'] = isset($post_data['meta_prompt']) ? sanitize_textarea_field(wp_unslash($post_data['meta_prompt'])) : '';
    $task_config['content_prompt'] = isset($post_data['content_prompt']) ? sanitize_textarea_field(wp_unslash($post_data['content_prompt'])) : '';

    // AI Settings
    $provider_raw = $post_data['ai_provider'] ?? 'openai';
    $task_config['ai_provider'] = match (strtolower($provider_raw)) {
        'openai' => 'OpenAI', 'openrouter' => 'OpenRouter', 'google' => 'Google', 'azure' => 'Azure', 'deepseek' => 'DeepSeek', 'ollama' => 'Ollama',
        default => ucfirst(strtolower($provider_raw))
    };
    $task_config['ai_model'] = $post_data['ai_model'] ?? '';
    $task_config['ai_temperature'] = isset($post_data['ai_temperature']) ? floatval($post_data['ai_temperature']) : 1.0;
    $task_config['content_max_tokens'] = isset($post_data['content_max_tokens']) ? absint($post_data['content_max_tokens']) : 4000;
    $task_config['reasoning_effort'] = isset($post_data['reasoning_effort']) ? sanitize_key($post_data['reasoning_effort']) : 'low';

    // Task Frequency
    $task_config['task_frequency'] = isset($post_data['task_frequency']) ? sanitize_key($post_data['task_frequency']) : 'daily';

    // One-time run flag
    $task_config['enhance_existing_now_flag'] = isset($post_data['enhance_existing_now_flag']) ? '1' : '0';

    // --- START: NEW Vector Store Settings ---
    $task_config['enable_vector_store'] = isset($post_data['enable_vector_store']) && $post_data['enable_vector_store'] === '1' ? '1' : '0';
    if ($task_config['enable_vector_store'] === '1') {
        $task_config['vector_store_provider'] = isset($post_data['vector_store_provider']) ? sanitize_key($post_data['vector_store_provider']) : 'openai';
        $task_config['vector_store_top_k'] = isset($post_data['vector_store_top_k']) ? absint($post_data['vector_store_top_k']) : 3;

        if ($task_config['vector_store_provider'] === 'openai') {
            $task_config['openai_vector_store_ids'] = isset($post_data['openai_vector_store_ids']) && is_array($post_data['openai_vector_store_ids']) ? array_map('sanitize_text_field', $post_data['openai_vector_store_ids']) : [];
        } elseif ($task_config['vector_store_provider'] === 'pinecone') {
            $task_config['pinecone_index_name'] = isset($post_data['pinecone_index_name']) ? sanitize_text_field($post_data['pinecone_index_name']) : '';
        } elseif ($task_config['vector_store_provider'] === 'qdrant') {
            $task_config['qdrant_collection_name'] = isset($post_data['qdrant_collection_name']) ? sanitize_text_field($post_data['qdrant_collection_name']) : '';
        }

        if ($task_config['vector_store_provider'] === 'pinecone' || $task_config['vector_store_provider'] === 'qdrant') {
            $task_config['vector_embedding_provider'] = isset($post_data['vector_embedding_provider']) ? sanitize_key($post_data['vector_embedding_provider']) : 'openai';
            $task_config['vector_embedding_model'] = isset($post_data['vector_embedding_model']) ? sanitize_text_field($post_data['vector_embedding_model']) : '';
        }
    }
    // --- END: NEW ---

    // Validation
    if (empty($task_config['post_types'])) {
        return new WP_Error('missing_post_types_enhance', __('Please select at least one post type to enhance.', 'gpt3-ai-content-generator'));
    }
    if ($task_config['update_title'] !== '1' && $task_config['update_excerpt'] !== '1' && $task_config['update_meta'] !== '1' && $task_config['update_content'] !== '1') {
        return new WP_Error('no_enhancement_selected', __('Please select at least one field to enhance.', 'gpt3-ai-content-generator'));
    }

    return $task_config;
}
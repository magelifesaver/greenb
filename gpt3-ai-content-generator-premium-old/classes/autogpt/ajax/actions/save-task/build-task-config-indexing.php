<?php

namespace WPAICG\AutoGPT\Ajax\Actions\SaveTask;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Builds and validates the configuration for a 'content_indexing' task.
*
* @param array $post_data The raw POST data.
* @return array|WP_Error The validated config array or WP_Error on failure.
*/
function build_task_config_indexing_logic(array $post_data): array|WP_Error
{
    $task_config = [];
    $task_config['post_types'] = isset($post_data['post_types']) && is_array($post_data['post_types']) ? array_map('sanitize_key', $post_data['post_types']) : [];
    $task_config['target_store_provider'] = isset($post_data['target_store_provider']) ? sanitize_key($post_data['target_store_provider']) : 'openai';
    $task_config['target_store_id'] = isset($post_data['target_store_id']) ? sanitize_text_field($post_data['target_store_id']) : '';
    $task_config['indexing_frequency'] = isset($post_data['task_frequency']) ? sanitize_key($post_data['task_frequency']) : 'daily';
    $task_config['index_existing_now_flag'] = isset($post_data['index_existing_now_flag']) ? '1' : '0';
    $task_config['only_new_updated_flag'] = isset($post_data['only_new_updated_flag']) ? '1' : '0';

    if (empty($task_config['post_types'])) {
        return new WP_Error('missing_post_types', __('Please select at least one post type for content indexing.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }
    if (empty($task_config['target_store_id'])) {
        return new WP_Error('missing_target_store', __('Target vector store/index is required for content indexing.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    if ($task_config['target_store_provider'] === 'pinecone' || $task_config['target_store_provider'] === 'qdrant') {
        // The frontend already split the provider and model.
        $task_config['embedding_provider'] = isset($post_data['embedding_provider']) ? sanitize_key($post_data['embedding_provider']) : null;
        $task_config['embedding_model'] = isset($post_data['embedding_model']) ? sanitize_text_field($post_data['embedding_model']) : null;

        if (empty($task_config['embedding_provider']) || empty($task_config['embedding_model'])) {
            return new WP_Error('missing_embedding_config', __('An embedding model is required.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
    }
    return $task_config;
}

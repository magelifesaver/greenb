<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/actions/save-task/build-task-config-comment-reply.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Ajax\Actions\SaveTask;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds and validates the configuration for a 'community_reply_comments' task.
 *
 * @param array $post_data The raw POST data.
 * @return array|WP_Error The validated config array or WP_Error on failure.
 */
function build_task_config_comment_reply_logic(array $post_data): array|WP_Error
{
    $task_config = [];
    // AI & Prompt Settings
    $provider_raw = $post_data['cc_ai_provider'] ?? 'openai';
    $task_config['ai_provider'] = match (strtolower($provider_raw)) {
        'openai' => 'OpenAI',
        'openrouter' => 'OpenRouter',
        'google' => 'Google',
        'azure' => 'Azure',
        'deepseek' => 'DeepSeek',
        'ollama' => 'Ollama',
        default => ucfirst(strtolower($provider_raw))
    };

    $task_config['ai_model'] = $post_data['cc_ai_model'] ?? '';
    $task_config['ai_temperature'] = isset($post_data['cc_ai_temperature']) ? floatval($post_data['cc_ai_temperature']) : 1.0;
    $task_config['content_max_tokens'] = isset($post_data['cc_content_max_tokens']) ? absint($post_data['cc_content_max_tokens']) : 4000;
    $task_config['reasoning_effort'] = isset($post_data['cc_reasoning_effort']) ? sanitize_key($post_data['cc_reasoning_effort']) : 'low';
    $task_config['custom_content_prompt'] = isset($post_data['cc_custom_content_prompt']) ? sanitize_textarea_field(wp_unslash($post_data['cc_custom_content_prompt'])) : '';

    // Comment-specific settings
    $task_config['post_types_for_comments'] = isset($post_data['post_types_for_comments']) && is_array($post_data['post_types_for_comments']) ? array_map('sanitize_key', $post_data['post_types_for_comments']) : [];
    $task_config['reply_action'] = isset($post_data['reply_action']) && in_array($post_data['reply_action'], ['approve', 'hold']) ? $post_data['reply_action'] : 'approve';
    $task_config['no_reply_to_replies'] = isset($post_data['no_reply_to_replies']) ? '1' : '0';

    // Filters
    $task_config['include_keywords'] = isset($post_data['include_keywords']) ? sanitize_textarea_field(wp_unslash($post_data['include_keywords'])) : '';
    $task_config['exclude_keywords'] = isset($post_data['exclude_keywords']) ? sanitize_textarea_field(wp_unslash($post_data['exclude_keywords'])) : '';

    // Task Frequency
    $task_config['task_frequency'] = isset($post_data['task_frequency']) ? sanitize_key($post_data['task_frequency']) : 'hourly';

    // Validation
    if (empty($task_config['ai_provider']) || empty($task_config['ai_model'])) {
        return new WP_Error('missing_ai_config_comments', __('AI Provider and Model are required.', 'gpt3-ai-content-generator'));
    }
    if (empty($task_config['post_types_for_comments'])) {
        return new WP_Error('missing_post_types_comments', __('Please select at least one post type to monitor for comments.', 'gpt3-ai-content-generator'));
    }
    if (empty($task_config['custom_content_prompt'])) {
        return new WP_Error('missing_prompt_comments', __('The reply prompt cannot be empty.', 'gpt3-ai-content-generator'));
    }

    return $task_config;
}
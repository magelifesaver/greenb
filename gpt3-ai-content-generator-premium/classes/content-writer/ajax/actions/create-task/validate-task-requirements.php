<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/create-task/validate-task-requirements.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions\CreateTask;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Validates that the built content writer config has the required fields.
*
* @param array $config The built content writer config array.
* @return true|WP_Error True on success, WP_Error on failure.
*/
function validate_task_requirements_logic(array $config): bool|WP_Error
{
    $generation_mode = $config['cw_generation_mode'] ?? 'single';

    if ($generation_mode === 'rss') {
        if (empty($config['rss_feeds'])) {
            return new WP_Error('missing_rss_feeds', __('RSS Feed URLs are required for this task type.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
    } elseif ($generation_mode === 'gsheets') {
        if (empty($config['gsheets_sheet_id']) || empty($config['gsheets_credentials'])) {
            return new WP_Error('missing_gsheets_config', __('Google Sheet ID and Credentials are required for this task type.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
    } elseif ($generation_mode === 'url') { // NEW
        if (empty($config['url_list'])) {
            return new WP_Error('missing_url_list', __('Website URLs are required for this task type.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
    } elseif ($generation_mode === 'task' || $generation_mode === 'csv') {
        if (empty($config['content_title'])) {
            return new WP_Error('missing_content_title_cw', __('Content Title/Topic is required for this task type.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
    }

    if (empty($config['ai_provider']) || empty($config['ai_model'])) {
        return new WP_Error('missing_ai_config_cw', __('AI Provider and Model are required for content writing task.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }
    if (($config['prompt_mode'] ?? 'standard') === 'custom' && empty($config['custom_content_prompt'])) {
        return new WP_Error('missing_custom_content_prompt', __('Custom Content Prompt cannot be empty when in Custom Prompt mode.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }
    return true;
}

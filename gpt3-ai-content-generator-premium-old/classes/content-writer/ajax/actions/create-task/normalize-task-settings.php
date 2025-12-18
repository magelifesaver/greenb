<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/create-task/normalize-task-settings.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions\CreateTask;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Normalizes and sanitizes basic task settings like name, frequency, and status from raw POST data.
*
* @param array $settings The raw POST data.
* @return array An array containing 'task_name', 'task_frequency', and 'task_status'.
*/
function normalize_task_settings_logic(array $settings): array
{
    $task_name = isset($settings['task_name']) ? sanitize_text_field(wp_unslash($settings['task_name'])) : '';

    if (empty($task_name)) {
        $first_title_line = isset($settings['content_title']) ? explode("\n", $settings['content_title'])[0] : 'Untitled ' . time();
        $task_name = 'Automated Content: ' . sanitize_text_field(wp_unslash($first_title_line));
    }

    $task_frequency = isset($settings['task_frequency']) && in_array($settings['task_frequency'], ['one-time', 'aipkit_five_minutes', 'aipkit_fifteen_minutes', 'aipkit_thirty_minutes', 'hourly', 'twicedaily', 'daily', 'weekly'])
    ? sanitize_key($settings['task_frequency'])
    : 'daily';

    $task_status = isset($settings['task_status']) && in_array($settings['task_status'], ['active', 'paused'])
    ? sanitize_key($settings['task_status'])
    : 'active';

    return [
    'task_name' => $task_name,
    'task_frequency' => $task_frequency,
    'task_status' => $task_status,
    ];
}
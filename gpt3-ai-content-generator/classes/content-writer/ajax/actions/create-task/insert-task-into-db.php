<?php

namespace WPAICG\ContentWriter\Ajax\Actions\CreateTask;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Inserts the new task into the custom database table.
*
* @param string $task_name The sanitized task name.
* @param string $task_type The type of the task.
* @param array $config The built and validated content writer config.
* @param string $task_status The sanitized task status ('active' or 'paused').
* @return int|WP_Error The ID of the newly inserted task, or a WP_Error on failure.
*/
function insert_task_into_db_logic(string $task_name, string $task_type, array $config, string $task_status): int|WP_Error
{
    global $wpdb;
    $tasks_table_name = $wpdb->prefix . 'aipkit_automated_tasks';

    $task_data = [
    'task_name' => $task_name,
    'task_type' => $task_type,
    'task_config' => wp_json_encode($config),
    'status' => $task_status,
    'created_at' => current_time('mysql', 1),
    'updated_at' => current_time('mysql', 1),
    ];
    $task_formats = ['%s', '%s', '%s', '%s', '%s', '%s'];
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct insert to a custom table. Caches will be invalidated.
    $inserted = $wpdb->insert($tasks_table_name, $task_data, $task_formats);

    if ($inserted === false) {
        return new WP_Error('db_insert_error', __('Failed to create automated task.', 'gpt3-ai-content-generator'), ['status' => 500]);
    }
    return $wpdb->insert_id;
}

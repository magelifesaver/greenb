<?php

namespace WPAICG\AutoGPT\Ajax\Actions\SaveTask;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Inserts or updates a task in the database.
*
* @param string $task_name The name of the task.
* @param string $task_type The type of the task.
* @param array $task_config The task's configuration data.
* @param string $task_status The status ('active' or 'paused').
* @param int $task_id The task ID (0 for new tasks).
* @return int|WP_Error The ID of the saved task, or a WP_Error on failure.
*/
function save_task_to_database_logic(string $task_name, string $task_type, array $task_config, string $task_status, int $task_id): int|WP_Error
{
    global $wpdb;
    $tasks_table_name = $wpdb->prefix . 'aipkit_automated_tasks';

    $data = [
    'task_name' => $task_name,
    'task_type' => $task_type,
    'task_config' => wp_json_encode($task_config),
    'status' => $task_status,
    'updated_at' => current_time('mysql', 1),
    ];
    $formats = ['%s', '%s', '%s', '%s', '%s'];

    if ($task_id > 0) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct update to a custom table. Caching is handled at the read level.
        $result = $wpdb->update($tasks_table_name, $data, ['id' => $task_id], $formats, ['%d']);
        if ($result === false) {
            return new WP_Error('db_error_update_task', __('Failed to update task.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        return $task_id;
    } else {
        $data['created_at'] = current_time('mysql', 1);
        $formats[] = '%s';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Direct insert into a custom table.
        $result = $wpdb->insert($tasks_table_name, $data, $formats);
        if ($result === false) {
            return new WP_Error('db_error_insert_task', __('Failed to save new task.', 'gpt3-ai-content-generator'), ['status' => 500]);
        }
        return $wpdb->insert_id;
    }
}

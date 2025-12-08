<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/actions/run-now/validate-task-and-permissions.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Ajax\Actions\RunNow;

use WPAICG\AutoGPT\Ajax\AIPKit_Run_Automated_Task_Now_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validates the request for running a task now.
 * Checks permissions, task ID, and task status.
 *
 * @param AIPKit_Run_Automated_Task_Now_Action $handler The handler instance.
 * @return array|WP_Error The task data array on success, or a WP_Error on failure.
 */
function validate_task_and_permissions_logic(AIPKit_Run_Automated_Task_Now_Action $handler): array|WP_Error
{
    // Check permissions
    $permission_check = $handler->check_module_access_permissions('autogpt', $handler::NONCE_ACTION);
    if (is_wp_error($permission_check)) {
        return $permission_check;
    }

    // Get and validate task ID
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in check_module_access_permissions().
    $task_id = isset($_POST['task_id']) ? absint($_POST['task_id']) : 0;

    if (empty($task_id)) {
        return new WP_Error('missing_task_id_run_now', __('Task ID is required.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    // Fetch and validate the task from the database
    global $wpdb;
    $tasks_table_name = $wpdb->prefix . 'aipkit_automated_tasks';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caching is handled at the read level.
    $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tasks_table_name} WHERE id = %d", $task_id), ARRAY_A);

    if (!$task) {
        return new WP_Error('task_not_found_run', __('Task not found.', 'gpt3-ai-content-generator'), ['status' => 404]);
    }

    if ($task['status'] !== 'active') {
        return new WP_Error('task_not_active_run', __('Task must be active to run now.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    return $task;
}

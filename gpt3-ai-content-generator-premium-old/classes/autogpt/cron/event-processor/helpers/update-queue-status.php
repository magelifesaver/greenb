<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/helpers/update-queue-status.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Cron\EventProcessor\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Updates the status and error message of a specific queue item.
 *
 * @param int $itemId The ID of the queue item.
 * @param string $status The new status ('processing', 'completed', 'failed', 'success').
 * @param string|null $errorMessage The error message, if status is 'failed'.
 * @return void
 */
function update_queue_status_logic(int $itemId, string $status, ?string $errorMessage = null): void
{
    global $wpdb;
    $queue_table_name = $wpdb->prefix . 'aipkit_automated_task_queue';

    $update_data = [];
    $formats = [];

    // --- MODIFIED: Handle 'error' status and map it to 'failed' in the database ---
    if ($status === 'success') {
        $update_data['status'] = 'completed'; // Set final DB status to 'completed'
        $update_data['error_message'] = $errorMessage; // Store the success message (which has post ID)
        $formats = ['%s', '%s'];
    } else {
        if ($status === 'error') {
            $update_data['status'] = 'failed'; // Standardize DB status to 'failed'
        } else {
            $update_data['status'] = $status;
        }
        $formats[] = '%s';

        if ($status === 'processing') {
            $update_data['last_attempt_time'] = current_time('mysql', 1);
            $formats[] = '%s';
        } elseif ($status === 'failed' || $status === 'error') {
            $update_data['error_message'] = $errorMessage;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct update to a custom table. Caches will be invalidated.
            $wpdb->query($wpdb->prepare("UPDATE " . esc_sql($queue_table_name) . " SET attempts = attempts + 1 WHERE id = %d", $itemId));
            $formats[] = '%s';
        }
    }
    // --- END MODIFICATION ---
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct update to a custom table. Caches will be invalidated.
    $wpdb->update(
        $queue_table_name,
        $update_data,
        ['id' => $itemId],
        $formats,
        ['%d']
    );
}

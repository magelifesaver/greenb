<?php

namespace WPAICG\AutoGPT\Ajax;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX request for deleting items from the automated task queue based on status.
 */
class AIPKit_Delete_Automated_Task_Queue_Items_By_Status_Action extends AIPKit_Automated_Task_Base_Ajax_Action
{
    public function handle_request()
    {
        $permission_check = $this->check_module_access_permissions('autogpt', self::NONCE_ACTION);
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        global $wpdb;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method
        $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'all';

        if (empty($status)) {
            $this->send_wp_error(new WP_Error('missing_status', __('Status filter is required.', 'gpt3-ai-content-generator')), 400);
            return;
        }

        $where_clause = '';
        $prepare_args = [];

        if ($status !== 'all') {
            $where_clause = " WHERE status = %s";
            $prepare_args[] = $status;
        }

        $query = "DELETE FROM {$this->queue_table_name}" . $where_clause;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Reason: This is a direct query for deletion, caching is not applicable here.
        $result = $wpdb->query($wpdb->prepare($query, $prepare_args));

        if ($result === false) {
            $this->send_wp_error(new WP_Error('db_error_delete_queue_items', __('Failed to delete queue items.', 'gpt3-ai-content-generator')), 500);
        } else {
            /* translators: %d: Number of deleted items */
            wp_send_json_success(['message' => sprintf(__('%d queue items deleted successfully.', 'gpt3-ai-content-generator'), $result)]);
        }
    }
}

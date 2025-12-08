<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/class-aipkit-get-automated-task-queue-items-action.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Ajax;

use WP_Error;
use WP_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX request for getting items from the automated task queue.
 */
class AIPKit_Get_Automated_Task_Queue_Items_Action extends AIPKit_Automated_Task_Base_Ajax_Action
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
        $current_page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $items_per_page = 15;
        $offset = ($current_page - 1) * $items_per_page;

        // Search, Filter, and Sort parameters
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method
        $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method
        $status_filter = isset($_POST['status_filter']) ? sanitize_key(wp_unslash($_POST['status_filter'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method
        $orderby_col = isset($_POST['orderby']) ? sanitize_key(wp_unslash($_POST['orderby'])) : 'q.added_at';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method
        $order_dir_raw = isset($_POST['order']) ? sanitize_key(wp_unslash($_POST['order'])) : 'DESC';
        $order_dir = in_array(strtoupper($order_dir_raw), ['ASC', 'DESC'], true) ? strtoupper($order_dir_raw) : 'DESC';

        // Whitelist columns for ordering to prevent SQL injection
        $allowed_orderby = ['t.task_name', 'q.task_type', 'q.status', 'q.attempts', 'q.added_at', 'q.last_attempt_time', 'q.target_identifier'];
        if (!in_array($orderby_col, $allowed_orderby)) {
            $orderby_col = 'q.added_at';
        }

        $where_clauses = [];
        $prepare_args = [];

        if (!empty($search_term)) {
            $where_clauses[] = "(q.target_identifier LIKE %s OR t.task_name LIKE %s)";
            $prepare_args[] = '%' . $wpdb->esc_like($search_term) . '%';
            $prepare_args[] = '%' . $wpdb->esc_like($search_term) . '%';
        }

        if (!empty($status_filter) && $status_filter !== 'all') {
            $where_clauses[] = "q.status = %s";
            $prepare_args[] = $status_filter;
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = " WHERE " . implode(' AND ', $where_clauses);
        }

        $total_items_query = "SELECT COUNT(*) FROM {$this->queue_table_name} q LEFT JOIN {$this->tasks_table_name} t ON q.task_id = t.id" . $where_sql;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Reason: This is a direct query for counting, caching is not applicable here.
        $total_items = $wpdb->get_var($wpdb->prepare($total_items_query, $prepare_args));

        $prepare_args_for_select = $prepare_args;
        $prepare_args_for_select[] = $items_per_page;
        $prepare_args_for_select[] = $offset;
        $query = "SELECT q.*, t.task_name FROM {$this->queue_table_name} q LEFT JOIN {$this->tasks_table_name} t ON q.task_id = t.id" . $where_sql . " ORDER BY " . esc_sql($orderby_col) . " " . esc_sql($order_dir) . " LIMIT %d OFFSET %d";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Reason: This is a direct query for selecting items, caching is not applicable here.
        $items = $wpdb->get_results($wpdb->prepare($query, $prepare_args_for_select), ARRAY_A);

        $enriched_items = [];
        if ($items) {
            foreach ($items as $item) {
                $item_config = json_decode($item['item_config'] ?? '[]', true);

                // Add generated_post_id key and parse from success message if applicable
                $item['generated_post_id'] = null;
                // --- MODIFIED: Check for 'completed' status ---
                if ($item['task_type'] === 'content_writing' && $item['status'] === 'completed' && !empty($item['error_message'])) {
                    // --- END MODIFICATION ---
                    if (preg_match('/\(ID: (\d+)\)/', $item['error_message'], $matches)) {
                        $item['generated_post_id'] = (int)$matches[1];
                    }
                }

                // Determine target_title for display
                if ($item['task_type'] === 'content_indexing' || $item['task_type'] === 'enhance_existing_content') {
                    $item['target_title'] = get_the_title(absint($item['target_identifier']));
                } elseif ($item['task_type'] === 'community_reply_comments') {
                    $item['target_title'] = 'Comment #' . absint($item['target_identifier']);
                } elseif (str_starts_with($item['task_type'], 'content_writing') && !empty($item_config['content_title'])) {
                    $item['target_title'] = $item_config['content_title'];
                } else {
                    $item['target_title'] = $item['target_identifier'];
                }

                // Expose scheduled time for content writing items (if any) so UI can display it under Added At column.
                if (str_starts_with($item['task_type'], 'content_writing') && !empty($item_config['scheduled_gmt_time'])) {
                    $item['scheduled_gmt_time'] = $item_config['scheduled_gmt_time'];
                }
                $enriched_items[] = $item;
            }
        }

        wp_send_json_success([
            'items' => $enriched_items,
            'pagination' => [
                'total_items' => (int) $total_items,
                'total_pages' => ceil($total_items / $items_per_page),
                'current_page' => $current_page,
                'per_page' => $items_per_page,
            ]
        ]);
    }
}

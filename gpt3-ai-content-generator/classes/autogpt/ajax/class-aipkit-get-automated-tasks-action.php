<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/class-aipkit-get-automated-tasks-action.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Ajax;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX request for getting all automated tasks.
 */
class AIPKit_Get_Automated_Tasks_Action extends AIPKit_Automated_Task_Base_Ajax_Action
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
        $items_per_page = 10;
        $offset = ($current_page - 1) * $items_per_page;

        // Search and Sorting parameters
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method
        $search_term = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method
        $orderby_col = isset($_POST['orderby']) ? sanitize_key(wp_unslash($_POST['orderby'])) : 'created_at';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method
        $order_dir_raw = isset($_POST['order']) ? sanitize_key(wp_unslash($_POST['order'])) : 'DESC';
        $order_dir = in_array(strtoupper($order_dir_raw), ['ASC', 'DESC'], true) ? strtoupper($order_dir_raw) : 'DESC';


        // Whitelist columns for ordering to prevent SQL injection
        $allowed_orderby = ['task_name', 'task_type', 'status', 'last_run_time', 'next_run_time', 'created_at'];
        if (!in_array($orderby_col, $allowed_orderby)) {
            $orderby_col = 'created_at';
        }

        $where_clauses = [];
        $prepare_args = [];

        if (!empty($search_term)) {
            $where_clauses[] = "task_name LIKE %s";
            $prepare_args[] = '%' . $wpdb->esc_like($search_term) . '%';
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = " WHERE " . implode(' AND ', $where_clauses);
        }

        $total_items_query = "SELECT COUNT(*) FROM {$this->tasks_table_name}" . $where_sql;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Reason: This is a direct query for counting, caching is not applicable here.
        $total_items = $wpdb->get_var($wpdb->prepare($total_items_query, $prepare_args));

        $prepare_args_for_select = $prepare_args;
        $prepare_args_for_select[] = $items_per_page;
        $prepare_args_for_select[] = $offset;
        $query = "SELECT * FROM {$this->tasks_table_name}" . $where_sql . " ORDER BY " . esc_sql($orderby_col) . " " . esc_sql($order_dir) . " LIMIT %d OFFSET %d";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Reason: This is a direct query for selecting items, caching is not applicable here.
        $tasks = $wpdb->get_results($wpdb->prepare($query, $prepare_args_for_select), ARRAY_A);


        wp_send_json_success([
            'tasks' => $tasks ?: [],
            'pagination' => [
                'total_items' => (int) $total_items,
                'total_pages' => ceil($total_items / $items_per_page),
                'current_page' => $current_page,
                'per_page' => $items_per_page,
            ]
        ]);
    }
}

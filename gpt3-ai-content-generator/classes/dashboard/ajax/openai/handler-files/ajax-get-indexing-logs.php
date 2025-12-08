<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/openai/handler-files/ajax-get-indexing-logs.php
// Status: NEW FILE

namespace WPAICG\Dashboard\Ajax\OpenAI\HandlerFiles;

use WPAICG\Dashboard\Ajax\AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for fetching OpenAI indexing logs from the local database.
 * Called by AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler::ajax_get_openai_indexing_logs().
 *
 * @param AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_get_indexing_logs_logic(AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler $handler_instance): void
{
    $wpdb = $handler_instance->get_wpdb();
    $data_source_table_name = $handler_instance->get_data_source_table_name();

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $store_id = isset($_POST['store_id']) ? sanitize_text_field(wp_unslash($_POST['store_id'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $logs_per_page = 20;
    $offset = ($page - 1) * $logs_per_page;

    if (empty($store_id)) {
        $handler_instance->send_wp_error(new WP_Error('missing_store_id_logs', __('OpenAI Vector Store ID is required to fetch logs.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $cache_key = 'openai_logs_' . sanitize_key($store_id) . '_page_' . $page;
    $cache_group = 'aipkit_vector_logs';
    $logs = wp_cache_get($cache_key, $cache_group);

    $total_logs_count_cache_key = 'openai_logs_count_' . sanitize_key($store_id);
    $total_logs = wp_cache_get($total_logs_count_cache_key, $cache_group);

    if (false === $total_logs) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- $data_source_table_name is safe.
        $total_logs = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$data_source_table_name} WHERE provider = 'OpenAI' AND vector_store_id = %s", $store_id));
        wp_cache_set($total_logs_count_cache_key, $total_logs, $cache_group, MINUTE_IN_SECONDS * 5);
    }
    
    if (false === $logs) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $data_source_table_name is safe.
        $logs = $wpdb->get_results($wpdb->prepare("SELECT id, timestamp, status, message, indexed_content, post_id, post_title, file_id, user_id FROM {$data_source_table_name} WHERE provider = 'OpenAI' AND vector_store_id = %s ORDER BY timestamp DESC LIMIT %d OFFSET %d", $store_id, $logs_per_page, $offset), ARRAY_A);
        wp_cache_set($cache_key, $logs, $cache_group, MINUTE_IN_SECONDS * 5); // Cache for 5 minutes
    }


    if ($wpdb->last_error) {
        $handler_instance->send_wp_error(new WP_Error('db_query_error_openai_logs', __('Failed to fetch OpenAI indexing logs.', 'gpt3-ai-content-generator'), ['status' => 500]));
        return;
    }

    wp_send_json_success([
        'logs' => $logs ?: [],
        'pagination' => [
            'total_logs'  => $total_logs,
            'total_pages'  => ceil($total_logs / $logs_per_page),
            'current_page' => $page,
        ]
    ]);
}
<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/pinecone/handler-indexes/ajax-get-indexing-logs.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes;

use WP_Error;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Pinecone_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for fetching Pinecone indexing logs.
 * Called by AIPKit_Vector_Store_Pinecone_Ajax_Handler::ajax_get_pinecone_indexing_logs().
 *
 * @param AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_get_indexing_logs_logic(AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance): void {
    $wpdb = $handler_instance->get_wpdb();
    $data_source_table_name = $handler_instance->get_data_source_table_name();

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $index_name = isset($_POST['index_name']) ? sanitize_text_field(wp_unslash($_POST['index_name'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $logs_per_page = 20;
    $offset = ($page - 1) * $logs_per_page;
    
    if (empty($index_name)) {
        $handler_instance->send_wp_error(new WP_Error('missing_index_name_logs', __('Pinecone index name is required to fetch logs.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $cache_key = 'pinecone_logs_' . sanitize_key($index_name) . '_page_' . $page;
    $cache_group = 'aipkit_vector_logs';
    $logs = wp_cache_get($cache_key, $cache_group);

    $total_logs_count_cache_key = 'pinecone_logs_count_' . sanitize_key($index_name);
    $total_logs = wp_cache_get($total_logs_count_cache_key, $cache_group);

    if (false === $total_logs) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- $data_source_table_name is safe.
        $total_logs = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$data_source_table_name} WHERE provider = 'Pinecone' AND vector_store_id = %s", $index_name));
        wp_cache_set($total_logs_count_cache_key, $total_logs, $cache_group, MINUTE_IN_SECONDS * 5);
    }
    
    if (false === $logs) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $data_source_table_name is safe.
    $logs = $wpdb->get_results($wpdb->prepare("SELECT id, timestamp, status, message, indexed_content, post_id, embedding_provider, embedding_model, file_id, batch_id FROM {$data_source_table_name} WHERE provider = 'Pinecone' AND vector_store_id = %s ORDER BY timestamp DESC LIMIT %d OFFSET %d", $index_name, $logs_per_page, $offset), ARRAY_A);
        wp_cache_set($cache_key, $logs, $cache_group, MINUTE_IN_SECONDS * 5); // Cache for 5 minutes
    }


    if ($wpdb->last_error) {
        $handler_instance->send_wp_error(new WP_Error('db_query_error_pinecone_logs', __('Failed to fetch Pinecone indexing logs.', 'gpt3-ai-content-generator'), ['status' => 500]));
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
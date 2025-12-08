<?php 
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/openai/fn-stores-log-entry.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax\OpenAI;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logs an entry for OpenAI vector store related events.
 *
 * @param \wpdb $wpdb WordPress database object.
 * @param string $data_source_table_name The name of the data source log table.
 * @param array $log_data Data for the log entry.
 *                        Expected keys: 'vector_store_id', 'vector_store_name', 'status', 'message'.
 *                        Optional keys: 'user_id', 'timestamp', 'post_id', 'post_title',
 *                                       'file_id', 'batch_id', 'embedding_provider', 'embedding_model',
 *                                       'indexed_content', 'source_type_for_log'.
 * @return void
 */
function _aipkit_openai_vs_stores_log_vector_store_event_logic(\wpdb $wpdb, string $data_source_table_name, array $log_data): void {
    $defaults = [
        'user_id' => get_current_user_id(),
        'timestamp' => current_time('mysql', 1),
        'provider' => 'OpenAI', // Specific to OpenAI stores for this logger
        'vector_store_id' => 'unknown_store_id',
        'vector_store_name' => null,
        'post_id' => null,
        'post_title' => null,
        'status' => 'info',
        'message' => '',
        'indexed_content' => null,
        'file_id' => null,
        'batch_id' => null,
        'embedding_provider' => null,
        'embedding_model' => null,
        'source_type_for_log' => null,
    ];
    $data_to_insert = wp_parse_args($log_data, $defaults);

    $source_type = $data_to_insert['source_type_for_log'] ?? ($data_to_insert['post_id'] ? 'wordpress_post' : 'action');
    $should_truncate = true;
    // For actions like create/delete store, indexed_content is usually null or not primary.
    // For other specific operations (like text add, handled by file logger), full content might be logged there.
    if (in_array($source_type, ['action_create_store', 'action_delete_store', 'action_search_store'])) {
        $should_truncate = true; // Not expecting large content for these actions in this log
    }

    if ($should_truncate && is_string($data_to_insert['indexed_content']) && mb_strlen($data_to_insert['indexed_content']) > 1000) {
        $data_to_insert['indexed_content'] = mb_substr($data_to_insert['indexed_content'], 0, 997) . '...';
    }
    unset($data_to_insert['source_type_for_log']);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Necessary insert operation into a custom table for logging. Cache is invalidated below.
    $result = $wpdb->insert($data_source_table_name, $data_to_insert);

    // Invalidate the log list cache for this store after a new entry is added.
    if ($result && !empty($data_to_insert['vector_store_id'])) {
        $cache_key = 'openai_logs_' . sanitize_key($data_to_insert['vector_store_id']);
        wp_cache_delete($cache_key, 'aipkit_vector_logs');
    }
}
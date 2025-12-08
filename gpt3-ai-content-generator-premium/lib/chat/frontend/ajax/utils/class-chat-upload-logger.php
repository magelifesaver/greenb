<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/frontend/ajax/utils/class-chat-upload-logger.php
// Status: NEW FILE

namespace WPAICG\Lib\Chat\Frontend\Ajax\Utils;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ChatUploadLogger
 *
 * Handles logging file upload events to the aipkit_vector_data_source table.
 */
class ChatUploadLogger
{
    private $wpdb;
    private $data_source_table_name;

    public function __construct(\wpdb $wpdb, string $data_source_table_name)
    {
        $this->wpdb = $wpdb;
        $this->data_source_table_name = $data_source_table_name;
    }

    /**
     * Logs an entry for vector store related events.
     *
     * @param array $log_data Data for the log entry.
     *                        Expected keys: 'provider', 'vector_store_id', 'status', 'message'.
     *                        Optional: 'user_id', 'vector_store_name', 'file_id', 'source_type_for_log', etc.
     */
    public function log_event(array $log_data): void
    {
        $defaults = [
            'user_id'             => get_current_user_id(),
            'timestamp'           => current_time('mysql', 1),
            'provider'            => 'UnknownProvider',
            'vector_store_id'     => 'unknown_store',
            'vector_store_name'   => null,
            'post_id'             => null,
            'post_title'          => null,
            'status'              => 'info',
            'message'             => '',
            'indexed_content'     => null,
            'file_id'             => null,
            'batch_id'            => null,
            'embedding_provider'  => null,
            'embedding_model'     => null,
            'source_type_for_log' => 'chat_file_upload', // Default for this logger
        ];
        $data_to_insert = wp_parse_args($log_data, $defaults);

        // Content for 'chat_file_upload' is typically the filename, not full content.
        // No specific truncation logic needed here unless a very long filename is passed.
        if (is_string($data_to_insert['indexed_content']) && mb_strlen($data_to_insert['indexed_content']) > 1000) {
            $data_to_insert['indexed_content'] = mb_substr($data_to_insert['indexed_content'], 0, 997) . '...';
        }
        unset($data_to_insert['source_type_for_log']);

        $result = $this->wpdb->insert($this->data_source_table_name, $data_to_insert);
    }
}

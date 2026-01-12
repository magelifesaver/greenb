<?php
/**
 * Filepath: sfwf/index/table-options.php
 * ---------------------------------------------------------------------------
 * Creates the custom plugin-scoped options table using Workflow schema format.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function sfwf_create_options_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'aaa_wf_options';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        option_key VARCHAR(255) NOT NULL,
        option_value LONGTEXT NOT NULL,
        autoload BOOLEAN DEFAULT TRUE,
        scope VARCHAR(64) DEFAULT 'global',
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY option_key_scope (option_key, scope)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

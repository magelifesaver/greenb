<?php
/**
 * File Path: /aaa-order-workflow/includes/announcements/class-aaa-oc-annc-table-installer.php
 *
 * Purpose:
 * - Creates/updates tables for workflow announcements and user acknowledgements.
 * - Safe to call on admin_init; uses dbDelta with $wpdb->prefix for multisite support.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Announcements_Table_Installer {

    private const DEBUG_THIS_FILE = false;
    
    public static function maybe_install() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_ann = $wpdb->prefix . 'aaa_oc_announcements';
        $table_usr = $wpdb->prefix . 'aaa_oc_announcement_user';

        $sql = [];

        // Announcements master table
        $sql[] = "CREATE TABLE {$table_ann} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            start_at DATETIME DEFAULT NULL,
            end_at DATETIME DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_active (is_active),
            KEY idx_start (start_at),
            KEY idx_end (end_at),
            KEY idx_created_by (created_by)
        ) {$charset_collate};";

        // Per-user acknowledgement table
        $sql[] = "CREATE TABLE {$table_usr} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            announcement_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            seen_at DATETIME DEFAULT NULL,
            accepted_at DATETIME DEFAULT NULL,
            accepted TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY uniq_announcement_user (announcement_id, user_id),
            KEY idx_user (user_id),
            KEY idx_accept (accepted)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $sql as $q ) {
            dbDelta( $q );
        }

        if ( self::DEBUG_THIS_FILE && function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log('[ANN] Tables ensured: ' . $table_ann . ', ' . $table_usr);
        }
    }
}

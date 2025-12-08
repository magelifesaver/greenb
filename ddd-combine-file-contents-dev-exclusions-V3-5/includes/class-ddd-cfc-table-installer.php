<?php
/**
 * File Path: includes/class-ddd-cfc-table-installer.php
 * Purpose: Create the ls_file_index table on activation, without relying on dbDelta() for alterations.
 */

defined( 'ABSPATH' ) || exit;

class DDD_CFC_Table_Installer {

    /**
     * Register activation hook.
     */
    public static function init() {
        register_activation_hook( DDD_CFC_PLUGIN_FILE, [ __CLASS__, 'create_table' ] );
    }

    /**
     * Create the ls_file_index table if it doesn’t already exist.
     */
    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'ls_file_index';
        $charset = $wpdb->get_charset_collate();

        // Only create if missing
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
            return;
        }

        // Build CREATE TABLE SQL with IF NOT EXISTS
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `plugin_slug` VARCHAR(191) NOT NULL,
            `path` TEXT NOT NULL,
            `is_dir` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) {$charset};
        ";

        // Execute directly—no dbDelta()
        $wpdb->query( $sql );
    }
}

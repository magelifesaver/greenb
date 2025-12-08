<?php
/**
 * File: /wp-content/plugins/aaa-wf-user-index-manager/index/class-aaa-wfuim-table-installer.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM:DEBUG] table-installer loaded');

class AAA_WFUIM_Table_Installer {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'aaa_wfuim_user_index';
    }

    public static function exists() {
        global $wpdb;
        $table = self::table_name();
        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        $ok = ( $found === $table );
        if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM:DEBUG] table exists? '. ($ok?'yes':'no') .' ['.$table.']');
        return $ok;
    }

    public static function ensure() {
        if ( ! self::exists() ) {
            if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM:DEBUG] ensure() -> install()');
            self::install();
        }
    }

    public static function install() {
        global $wpdb;
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            user_id BIGINT(20) UNSIGNED NOT NULL,
            display_name VARCHAR(200) NULL,
            user_email VARCHAR(190) NULL,
            billing_address TEXT NULL,
            shipping_address TEXT NULL,
            lat DECIMAL(10,6) NULL,
            lng DECIMAL(10,6) NULL,
            meta_json LONGTEXT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (user_id),
            KEY user_email (user_email),
            KEY lat_lng (lat,lng),
            KEY updated_at (updated_at)
        ) $charset;";
        dbDelta( $sql );
        if ( AAA_WFUIM_DEBUG ) error_log('[WFUIM:DEBUG] dbDelta complete: '. $table );
    }

    /** --------- Extra Column Support ---------- */

    protected static function parse_extras( $settings ) {
        $raw = (string)($settings['extra_columns'] ?? '');
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)));
        $out = [];
        foreach ( $lines as $line ) {
            // column_name|meta_key|type(optional)
            $parts = array_map('trim', explode('|', $line));
            if ( count($parts) < 2 ) continue;
            $col  = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($parts[0])); // safe col
            $meta = $parts[1];
            $type = strtoupper($parts[2] ?? 'VARCHAR(190)');
            if ( ! in_array($type, ['VARCHAR(190)','TEXT','DECIMAL(12,6)','INT(11)'], true) ) {
                $type = 'VARCHAR(190)';
            }
            $out[] = ['col'=>$col,'meta'=>$meta,'type'=>$type];
        }
        return $out;
    }

    protected static function column_exists( $table, $column ) {
        global $wpdb;
        $sql = $wpdb->prepare("SHOW COLUMNS FROM `$table` LIKE %s", $column);
        return (bool) $wpdb->get_var($sql);
    }

    public static function ensure_extra_columns( $settings ) {
        global $wpdb;
        $table = self::table_name();
        if ( ! self::exists() ) return;

        $extras = self::parse_extras( $settings );
        foreach ( $extras as $ex ) {
            if ( ! self::column_exists($table, $ex['col']) ) {
                $sql = "ALTER TABLE `$table` ADD `{$ex['col']}` {$ex['type']} NULL";
                $wpdb->query( $sql );
                if ( AAA_WFUIM_DEBUG ) error_log("[WFUIM:DEBUG] added column {$ex['col']} ({$ex['type']})");
            }
        }
    }
}

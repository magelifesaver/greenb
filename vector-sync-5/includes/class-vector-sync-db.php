<?php
/**
 * Vector Sync Database handler
 *
 * Creates and maintains a custom table for plugin settings.  Storing
 * configuration outside of the `wp_options` table allows administrators to
 * manage plugin data separately and avoids conflicts with other plugins.  The
 * table structure is simple: a single row containing a JSON encoded
 * settings object.  Additional rows could be added for future expansion.
 */
class Vector_Sync_DB {
    /**
     * Create the settings table if it does not exist.  Called on activation.
     */
    public static function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name      = self::table_name();
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            settings LONGTEXT NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        // Ensure there is at least one row.
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        if ( ! $count ) {
            $wpdb->insert( $table_name, array( 'settings' => wp_json_encode( array() ) ) );
        }
    }

    /**
     * Get the current settings array from the custom table.
     *
     * @return array Settings array.
     */
    public static function get_settings() {
        global $wpdb;
        $row = $wpdb->get_row( 'SELECT settings FROM ' . self::table_name() . ' LIMIT 1', ARRAY_A );
        if ( $row && ! empty( $row['settings'] ) ) {
            $data = json_decode( $row['settings'], true );
            if ( is_array( $data ) ) {
                return $data;
            }
        }
        return array();
    }

    /**
     * Update the settings in the custom table.
     *
     * @param array $settings Settings array.
     * @return bool Whether the update succeeded.
     */
    public static function update_settings( array $settings ) {
        global $wpdb;
        $table = self::table_name();
        $json  = wp_json_encode( $settings );
        $result = $wpdb->update( $table, array( 'settings' => $json ), array( 'id' => 1 ) );
        return false !== $result;
    }

    /**
     * Table name helper with prefix.
     *
     * @return string Fully qualified table name.
     */
    private static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'vector_sync_settings';
    }
}
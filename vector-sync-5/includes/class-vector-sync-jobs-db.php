<?php
/**
 * Jobs Database
 *
 * Provides CRUD operations for the vector sync jobs table. Each job
 * corresponds to a single vector sync configuration saved from the
 * custom post type meta box.  The table stores a row keyed by the
 * job's post ID and contains a serialized settings array.  Using a
 * dedicated table avoids cluttering wp_postmeta with large payloads
 * and makes it straightforward to retrieve all jobs for scheduling.
 */
class Vector_Sync_Jobs_DB {
    /**
     * Create the jobs table if it does not already exist.  The table
     * includes an id column equal to the post ID and a JSON field to
     * store job settings.  Using BIGINT for id supports large numbers
     * of posts.  The table is created using dbDelta for safety.
     */
    public static function create_table() {
        global $wpdb;
        $table_name = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL,
            settings LONGTEXT NOT NULL,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Determine the table name with WordPress's DB prefix.
     *
     * @return string Fully qualified table name.
     */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'vector_sync_jobs';
    }

    /**
     * Retrieve the settings array for a job.  If the job has not been
     * configured, returns an empty array.
     *
     * @param int $job_id Post ID of the job.
     * @return array Job settings.
     */
    public static function get_job_settings( $job_id ) {
        global $wpdb;
        $table_name = self::table_name();
        $row = $wpdb->get_var( $wpdb->prepare( "SELECT settings FROM {$table_name} WHERE id = %d", $job_id ) );
        if ( null === $row ) {
            return array();
        }
        $settings = json_decode( $row, true );
        return is_array( $settings ) ? $settings : array();
    }

    /**
     * Save or update the settings for a job.  Uses a REPLACE INTO query
     * to either insert or update the row.  Settings are stored as
     * JSON with wp_json_encode.
     *
     * @param int   $job_id   Post ID of the job.
     * @param array $settings Settings array to store.
     * @return void
     */
    public static function save_job_settings( $job_id, array $settings ) {
        global $wpdb;
        $table_name = self::table_name();
        $wpdb->replace(
            $table_name,
            array(
                'id'       => $job_id,
                'settings' => wp_json_encode( $settings ),
            ),
            array( '%d', '%s' )
        );
    }

    /**
     * Delete a job's settings from the table.  Called when a job
     * post is permanently deleted.
     *
     * @param int $job_id Post ID of the job.
     */
    public static function delete_job_settings( $job_id ) {
        global $wpdb;
        $table_name = self::table_name();
        $wpdb->delete( $table_name, array( 'id' => $job_id ), array( '%d' ) );
    }
}
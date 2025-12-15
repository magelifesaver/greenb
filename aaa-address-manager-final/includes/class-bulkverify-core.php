<?php
// Core routines for user address verification queue processing. Provides table
// creation, geocoding, user meta updates and batch processing.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_BulkVerify_Core {
    // Constants describing the cron hook, table name, batch size and re‑schedule delay.
    public const CRON_HOOK = 'aaa_adbc2_process_queue'; public const TABLE = 'aaa_adbc2_jobs'; public const BATCH_LIMIT = 50; public const RESCHEDULE_SECONDS = 15;

    // Register the table creation and cron processing hooks.
    public static function init() : void {
        add_action( 'plugins_loaded', [ __CLASS__, 'ensure_table' ] );
        add_action( self::CRON_HOOK, [ __CLASS__, 'process_batch' ] );
        /*
         * On activation we ensure the queue table is created. Use the absolute
         * path to this plugin’s main file. dirname(__DIR__) refers to the
         * plugin root (one level up from includes). Passing the correct
         * filename ensures WordPress calls this hook when the plugin is
         * activated via the Plugins screen.
         */
        register_activation_hook( dirname( __DIR__ ) . '/aaa-address-manager.php', [ __CLASS__, 'ensure_table' ] );
    }

    // Create the jobs table if not present.
    public static function ensure_table() : void {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) unsigned NOT NULL,
            scope VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'queued',
            message TEXT NULL,
            created_at DATETIME NOT NULL,
            processed_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY idx_status (status),
            KEY idx_created (created_at)
        ) {$charset};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // Geocode an address via Sunshine filter or Google API. Returns [lat,lng,status].
    public static function geocode( string $address ) : array {
        $opts = get_option( 'aaa_adbc_settings', [] );
        if ( ! empty( $opts['use_sunshine'] ) ) {
            $coords = apply_filters( 'aaa_adbc_geocode', null, $address );
            if ( is_array( $coords ) && ! empty( $coords[0] ) && ! empty( $coords[1] ) ) {
                return [ $coords[0], $coords[1], 'OK' ];
            }
        }
        $api = '';
        if ( defined( 'AAA_GOOGLE_API_KEY' ) && AAA_GOOGLE_API_KEY ) {
            $api = AAA_GOOGLE_API_KEY;
        } elseif ( ! empty( $opts['google_api_key'] ) ) {
            $api = $opts['google_api_key'];
        }
        if ( ! $api ) {
            return [ '', '', 'No API key' ];
        }
        $url = add_query_arg( [
            'address'    => rawurlencode( $address ),
            'key'        => $api,
            'components' => 'country:US|administrative_area:CA',
        ], 'https://maps.googleapis.com/maps/api/geocode/json' );
        $r = wp_remote_get( $url, [ 'timeout' => 8 ] );
        if ( is_wp_error( $r ) ) {
            return [ '', '', $r->get_error_message() ];
        }
        $j = json_decode( wp_remote_retrieve_body( $r ), true );
        if ( ! isset( $j['status'] ) || 'OK' !== $j['status'] ) {
            $msg = $j['status'] ?? 'ERR';
            if ( ! empty( $j['error_message'] ) ) {
                $msg .= ' - ' . $j['error_message'];
            }
            return [ '', '', $msg ];
        }
        $loc = $j['results'][0]['geometry']['location'] ?? [];
        return [ $loc['lat'] ?? '', $loc['lng'] ?? '', 'OK' ];
    }

    // Verify a user’s address for a given scope and update meta. Throws on failure.
    public static function verify_user_scope( int $uid, string $scope ) : void {
        $scopes = ( 'both' === $scope ) ? [ 'billing', 'shipping' ] : [ $scope ];
        foreach ( $scopes as $s ) {
            $addr = [
                get_user_meta( $uid, "{$s}_address_1", true ),
                get_user_meta( $uid, "{$s}_address_2", true ),
                get_user_meta( $uid, "{$s}_city", true ),
                get_user_meta( $uid, "{$s}_state", true ),
                get_user_meta( $uid, "{$s}_postcode", true ),
                get_user_meta( $uid, "{$s}_country", true ),
            ];
            $addr_str = trim( implode( ', ', array_filter( array_map( 'trim', $addr ) ) ) );
            if ( '' === $addr_str ) {
                update_user_meta( $uid, '_aaa_am_verify_failed', 'yes' );
                throw new Exception( "No {$s} address for user {$uid}" );
            }
            [ $lat, $lng, $status ] = self::geocode( $addr_str );
            if ( $lat && $lng ) {
                update_user_meta( $uid, "_wc_{$s}/aaa-delivery-blocks/latitude", $lat );
                update_user_meta( $uid, "_wc_{$s}/aaa-delivery-blocks/longitude", $lng );
                update_user_meta( $uid, "_wc_{$s}/aaa-delivery-blocks/coords-verified", 'yes' );
                delete_user_meta( $uid, '_aaa_am_verify_failed' );
            } else {
                update_user_meta( $uid, "_wc_{$s}/aaa-delivery-blocks/coords-verified", 'no' );
                update_user_meta( $uid, '_aaa_am_verify_failed', 'yes' );
                throw new Exception( 'Geocode failed: ' . $status );
            }
        }
    }

    // Process up to BATCH_LIMIT queued jobs and schedule the next run if needed.
    public static function process_batch() : void {
        global $wpdb;
        self::ensure_table();
        $table = $wpdb->prefix . self::TABLE;
        $jobs  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status='queued' ORDER BY id ASC LIMIT %d", self::BATCH_LIMIT ) );
        if ( empty( $jobs ) ) {
            return;
        }
        foreach ( $jobs as $job ) {
            $status = 'success';
            $msg    = '';
            try {
                self::verify_user_scope( (int) $job->user_id, (string) $job->scope );
            } catch ( Throwable $e ) {
                $status = 'failed';
                $msg    = $e->getMessage();
            }
            $wpdb->update( $table, [
                'status'       => $status,
                'message'      => $msg,
                'processed_at' => current_time( 'mysql' ),
            ], [ 'id' => (int) $job->id ] );
        }
        $remain = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status='queued'" );
        if ( $remain > 0 && ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_single_event( time() + self::RESCHEDULE_SECONDS, self::CRON_HOOK );
        }
    }

    // Return count of remaining queued jobs.
    public static function count_remaining_jobs() : int {
        global $wpdb;
        self::ensure_table();
        $table = $wpdb->prefix . self::TABLE;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status='queued'" );
    }
}
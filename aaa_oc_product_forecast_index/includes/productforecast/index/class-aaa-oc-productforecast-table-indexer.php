<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/index/class-aaa-oc-productforecast-table-indexer.php
 * Purpose: Upsert/purge + bulk reindex for ProductForecast index.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_ProductForecast_Table_Indexer {

    protected static function table() : string {
        return AAA_OC_ProductForecast_Helpers::table_index();
    }

    protected static function log_table() : string {
        return AAA_OC_ProductForecast_Helpers::table_log();
    }

    public static function upsert_now( int $product_id, string $src = '' ) : void {
        global $wpdb;

        if ( ! class_exists( 'AAA_OC_ProductForecast_Table_Installer' ) ) {
            return;
        }
        AAA_OC_ProductForecast_Table_Installer::maybe_install();

        $row = AAA_OC_ProductForecast_Row_Builder::build_row( $product_id );
        if ( empty( $row ) ) {
            return;
        }

        // Build formats dynamically.
        $fmt = [];
        foreach ( $row as $v ) {
            if ( is_int( $v ) ) {
                $fmt[] = '%d';
            } elseif ( is_float( $v ) ) {
                $fmt[] = '%f';
            } elseif ( $v === null ) {
                $fmt[] = '%s';
            } else {
                $fmt[] = '%s';
            }
        }

        $wpdb->replace( self::table(), $row, $fmt );

        // Optional log entry (only when debug enabled).
        if ( defined( 'AAA_OC_PRODUCTFORECAST_DEBUG' ) && AAA_OC_PRODUCTFORECAST_DEBUG ) {
            $wpdb->insert(
                self::log_table(),
                [
                    'product_id' => $product_id,
                    'action'     => 'upsert',
                    'message'    => $src ? 'Upsert from ' . $src : 'Upsert',
                    'context'    => $src ? wp_json_encode( [ 'src' => $src ] ) : null,
                    'created_at' => current_time( 'mysql' ),
                ],
                [ '%d', '%s', '%s', '%s', '%s' ]
            );
        }
    }

    public static function purge( int $product_id, string $src = '' ) : void {
        global $wpdb;
        $wpdb->delete( self::table(), [ 'product_id' => $product_id ], [ '%d' ] );

        if ( defined( 'AAA_OC_PRODUCTFORECAST_DEBUG' ) && AAA_OC_PRODUCTFORECAST_DEBUG ) {
            $wpdb->insert(
                self::log_table(),
                [
                    'product_id' => $product_id,
                    'action'     => 'purge',
                    'message'    => $src ? 'Purge from ' . $src : 'Purge',
                    'context'    => $src ? wp_json_encode( [ 'src' => $src ] ) : null,
                    'created_at' => current_time( 'mysql' ),
                ],
                [ '%d', '%s', '%s', '%s', '%s' ]
            );
        }
    }

    /**
     * Bulk reindex all products.
     * User said they will handle bulk manually; still providing this utility.
     */
    public static function reindex_all() : void {
        $q = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => [ 'publish', 'private' ],
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ]);

        foreach ( (array) $q->posts as $pid ) {
            self::upsert_now( (int) $pid, 'bulk' );
        }
    }
}

<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-index.php
 * ---------------------------------------------------------------------------
 * Manages a dedicated forecast index table. The table is used to track which
 * products need to be rebuilt, record when each product was last processed, and
 * support more efficient partial updates.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Index {

    /**
     * Returns the fully prefixed table name.
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'aaa_oc_product_forecast_index';
    }

    /**
     * Creates the forecast index table on plugin activation.
     */
    public static function create_table() {
        global $wpdb;
        $table_name     = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            product_id BIGINT UNSIGNED NOT NULL,
            flagged TINYINT(1) NOT NULL DEFAULT 0,
            last_processed DATETIME NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (product_id)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Marks all reorder-enabled products as needing a rebuild.
     * This inserts or updates rows with flagged = 1. Use this when you want
     * to run a full rebuild via the scheduler.
     */
    public static function mark_all_flagged() {
        $args = [
            'status' => [ 'publish', 'private' ],
            'type'   => [ 'simple', 'variation' ],
            'limit'  => -1,
            'return' => 'ids',
        ];
        $products = wc_get_products( $args );
        if ( empty( $products ) ) {
            return;
        }
        global $wpdb;
        $table = self::get_table_name();
        foreach ( $products as $pid ) {
            $enabled = get_post_meta( $pid, 'forecast_enable_reorder', true );
            if ( $enabled !== 'yes' ) {
                continue;
            }
            // Use REPLACE to insert or update the flagged state.
            $wpdb->replace( $table, [
                'product_id'    => $pid,
                'flagged'       => 1,
                'last_processed' => null,
            ], [ '%d', '%d', '%s' ] );
        }
    }

    /**
     * Flags a single product for a rebuild. If the row exists it's updated; if
     * not it is created.
     *
     * @param int $product_id
     */
    public static function flag_product( $product_id ) {
        if ( ! $product_id ) {
            return;
        }
        global $wpdb;
        $table = self::get_table_name();
        $wpdb->replace( $table, [
            'product_id' => $product_id,
            'flagged'    => 1,
            'last_processed' => null,
        ], [ '%d', '%d', '%s' ] );
    }

    /**
     * Updates a product's index entry after it has been processed. This sets
     * flagged = 0 and records the last_processed timestamp.
     *
     * @param int $product_id
     */
    public static function update_product_index( $product_id ) {
        if ( ! $product_id ) {
            return;
        }
        global $wpdb;
        $table = self::get_table_name();
        $wpdb->replace( $table, [
            'product_id'    => $product_id,
            'flagged'       => 0,
            'last_processed' => current_time( 'mysql' ),
        ], [ '%d', '%d', '%s' ] );
    }

    /**
     * Retrieves a list of product IDs that have been flagged for rebuild.
     *
     * @param int $limit Optional limit on number of products to return.
     * @return array
     */
    public static function get_flagged_products( $limit = 0 ) {
        global $wpdb;
        $table = self::get_table_name();
        $sql   = "SELECT product_id FROM {$table} WHERE flagged = 1";
        if ( $limit > 0 ) {
            $sql .= $wpdb->prepare( ' LIMIT %d', $limit );
        }
        $ids = $wpdb->get_col( $sql );
        return array_map( 'intval', (array) $ids );
    }

    /**
     * Retrieves the last processed timestamp for a given product.
     *
     * @param int $product_id
     * @return string|null Date/time when the product was last processed or null if not found.
     */
    public static function get_last_processed( $product_id ) {
        if ( ! $product_id ) {
            return null;
        }
        global $wpdb;
        $table = self::get_table_name();
        return $wpdb->get_var( $wpdb->prepare( "SELECT last_processed FROM {$table} WHERE product_id = %d", $product_id ) );
    }

    /**
     * Clears the flagged state for an array of product IDs after processing.
     *
     * @param array $product_ids
     */
    public static function clear_flags( $product_ids ) {
        if ( empty( $product_ids ) ) {
            return;
        }
        global $wpdb;
        $table = self::get_table_name();
        $ids   = implode( ',', array_map( 'intval', $product_ids ) );
        $wpdb->query( "UPDATE {$table} SET flagged = 0 WHERE product_id IN ({$ids})" );
    }
}
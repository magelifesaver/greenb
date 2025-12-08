<?php
/**
 * File: plugins/ddd-atum-reader/includes/class-ddd-atum-logs.php
 * Purpose: Encapsulate queries and helpers for ATUM logs rendering.
 * Dependencies: Uses $wpdb; called by admin sections to get data.
 * Needed by: includes/admin/section/section-content.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DDD_ATUM_Logs {

    /**
     * Fetch ATUM logs by product name (searches serialized data field).
     * Returns an array of stdClass rows with parsed fields for table display.
     */
    public static function get_logs_by_product_name( $product_name ) {
        global $wpdb;
        if ( ! $product_name ) { return []; }

        $like = '%' . $wpdb->esc_like( $product_name ) . '%';

        // Important: we extract common fields directly in SQL for performance.
        $sql = $wpdb->prepare( "
            SELECT l.id AS log_id,
                   l.`time` AS unix_ts,
                   FROM_UNIXTIME(l.`time`) AS log_time,
                   l.entry,
                   u.ID AS user_id,
                   u.display_name AS display_name,
                   CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(l.data,'s:8:\"order_id\";i:',-1),';',1) AS UNSIGNED) AS order_id,
                   CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(l.data,'s:10:\"product_id\";i:',-1),';',1) AS UNSIGNED) AS product_id,
                   CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(l.data,'s:9:\"old_stock\";i:',-1),';',1) AS SIGNED) AS old_stock,
                   CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(l.data,'s:9:\"new_stock\";i:',-1),';',1) AS SIGNED) AS new_stock,
                   CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(l.data,'s:3:\"qty\";i:',-1),';',1) AS SIGNED) AS qty
            FROM {$wpdb->prefix}atum_logs l
            LEFT JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
            WHERE l.data LIKE %s
            ORDER BY l.`time` DESC
        ", $like );

        $rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        // Map entry -> human label here (kept in PHP so we can tweak without SQL changes)
        $map = self::event_map();

        foreach ( $rows as $r ) {
            $key = isset( $r->entry ) ? $r->entry : '';
            $r->event_label = isset( $map[ $key ] ) ? $map[ $key ] : 'Other (' . $key . ')';

            // Movement = old - new (positive = out; negative = in)
            $has_numbers = is_numeric( $r->old_stock ) && is_numeric( $r->new_stock );
            $r->movement = $has_numbers ? ( (int)$r->old_stock - (int)$r->new_stock ) : null;
        }

        return $rows;
    }

    /**
     * Canonical event mapping for staff-friendly labels.
     */
    public static function event_map() {
        return [
            'wc-order-stock-level'             => 'Order Item Sold',
            'wc-order-add-product'             => 'Order Item Added',
            'wc-order-edit-order-item'         => 'Order Item Edited',
            'woocommerce_restore_order_stock'  => 'Order Item Returned',
            'mi-inventory-edit'                => 'Inventory Changed',
            'mi-product-edit'                  => 'Product Edited',
            'atum-purchase-order'              => 'Purchase Order Created/Updated',
            'atum-purchase-order-receipt'      => 'Delivery Received',
            'supplier-change'                  => 'Supplier Changed',
            'supplier-price-change'            => 'Supplier Price Updated',
            'settings-change'                  => 'Settings Changed',
            'system'                           => 'System Event',
            'background-task'                  => 'Background Task',
            'atum-export'                      => 'Data Export',
            'atum-import'                      => 'Data Import',
        ];
    }
}

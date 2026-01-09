<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_ATUM_Logs {
    public static function table_exists(): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'atum_logs';
        $like = $wpdb->esc_like( $table );
        $found = $wpdb->get_var( "SHOW TABLES LIKE '{$like}'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
        return (string) $found === $table;
    }

    public static function get_logs_by_product_name( $product_name, int $limit = 500 ): array {
        global $wpdb;
        $product_name = trim( (string) $product_name );
        if ( $product_name === '' || ! self::table_exists() ) {
            return [];
        }

        $limit = max( 1, min( 5000, (int) $limit ) );
        $like = '%' . $wpdb->esc_like( $product_name ) . '%';

        $sql = $wpdb->prepare(
            "
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
            LIMIT %d
        ",
            $like,
            $limit
        );

        $rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $map = self::event_map();
        foreach ( $rows as $r ) {
            $key = isset( $r->entry ) ? (string) $r->entry : '';
            $r->event_label = isset( $map[ $key ] ) ? $map[ $key ] : 'Other (' . $key . ')';

            $has_numbers = is_numeric( $r->old_stock ) && is_numeric( $r->new_stock );
            $r->movement = $has_numbers ? ( (int) $r->old_stock - (int) $r->new_stock ) : null;
        }

        return $rows;
    }

    public static function event_map(): array {
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

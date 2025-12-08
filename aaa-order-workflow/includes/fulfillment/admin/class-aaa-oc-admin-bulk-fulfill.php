<?php
/**
 * File: /aaa-order-workflow/includes/fulfillment/admin/class-aaa-oc-admin-bulk-fulfill.php
 * Purpose: Adds a bulk action on the Orders list to mark selected orders as Fully Picked.
 * Effect:
 *   - Writes _aaa_picked_items (map) and _aaa_fulfillment_status = fully_picked
 *   - Writes aaa_oc_order_index.picked_items (array-of-objects JSON) and fulfillment_status = fully_picked
 *   - Adds an order note for audit
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Admin_Bulk_Fulfill {

    private static $report = ['ok' => 0, 'skipped' => 0];

    public static function init() {
        if ( ! is_admin() ) return;
        add_filter( 'bulk_actions-edit-shop_order',        [ __CLASS__, 'register_action' ] );
        add_filter( 'handle_bulk_actions-edit-shop_order', [ __CLASS__, 'handle_action' ], 10, 3 );
        add_action( 'admin_notices',                       [ __CLASS__, 'notice' ] );
    }

    /** Add the action to the Orders bulk dropdown */
    public static function register_action( $actions ) {
        $actions['aaa_oc_bulk_fulfill'] = 'Workflow: Mark Fulfillment → Fully Picked';
        return $actions;
    }

    /**
     * Handle the bulk action
     * @param string $redirect_to
     * @param string $doaction
     * @param array<int> $order_ids
     * @return string
     */
    public static function handle_action( $redirect_to, $doaction, $order_ids ) {
        if ( $doaction !== 'aaa_oc_bulk_fulfill' || empty( $order_ids ) ) return $redirect_to;

        global $wpdb;
        $index_table = $wpdb->prefix . 'aaa_oc_order_index';

        foreach ( $order_ids as $order_id ) {
            if ( ! current_user_can( 'edit_shop_order', $order_id ) ) { self::$report['skipped']++; continue; }

            $order = wc_get_order( $order_id );
            if ( ! $order ) { self::$report['skipped']++; continue; }

            // Build meta map (sku/new_sku => picked qty) and array-of-objects for index
            $sku_map = [];
            $rows    = [];

            foreach ( $order->get_items( 'line_item' ) as $item ) {
                if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) continue;

                $qty     = (int) $item->get_quantity();
                $product = $item->get_product();
                $sku     = $product ? (string) $product->get_sku() : '';
                $new_sku = $product ? get_post_meta( $product->get_id(), 'lkd_wm_new_sku', true ) : '';
                $key     = $sku ?: ( $new_sku ?: ( 'PID-' . $item->get_product_id() ) );

                // Write both keys where helpful so future lookups succeed
                $sku_map[ $key ] = $qty;
                if ( $new_sku && $new_sku !== $key ) {
                    $sku_map[ $new_sku ] = $qty;
                }

                $rows[] = [ 'sku' => $key, 'picked' => $qty, 'max' => $qty ];
            }

            // Persist to order meta (source of truth for reindexers)
            update_post_meta( $order_id, '_aaa_picked_items', $sku_map );
            update_post_meta( $order_id, '_aaa_fulfillment_status', 'fully_picked' );

            // Update/order index row (UI reads this immediately)
            $picked_json = wp_json_encode( $rows );

            $wpdb->update(
                $index_table,
                [ 'fulfillment_status' => 'fully_picked', 'picked_items' => $picked_json ],
                [ 'order_id' => $order_id ],
                [ '%s', '%s' ],
                [ '%d' ]
            );

            // If index row missing, create via indexer then update again
            if ( $wpdb->rows_affected === 0 && class_exists( 'AAA_OC_Indexing' ) ) {
                ( new AAA_OC_Indexing() )->index_order( $order_id );
                $wpdb->update(
                    $index_table,
                    [ 'fulfillment_status' => 'fully_picked', 'picked_items' => $picked_json ],
                    [ 'order_id' => $order_id ],
                    [ '%s', '%s' ],
                    [ '%d' ]
                );
            }

            // Audit note
            $user = wp_get_current_user();
            $when = current_time( 'mysql' );
            $order->add_order_note(
                sprintf(
                    'Workflow bulk: marked Fully Picked by %s at %s.',
                    ( $user && $user->exists() ) ? $user->user_login : 'system',
                    $when
                )
            );
            $order->save();

            self::$report['ok']++;
        }

        // surface results
        $redirect_to = add_query_arg(
            [
                'aaa_oc_bulk_fulfill_done' => 1,
                'ok'       => self::$report['ok'],
                'skipped'  => self::$report['skipped'],
            ],
            $redirect_to
        );

        return $redirect_to;
    }

    /** Admin success notice */
    public static function notice() {
        if ( empty( $_GET['aaa_oc_bulk_fulfill_done'] ) ) return;
        $ok      = intval( $_GET['ok'] ?? 0 );
        $skipped = intval( $_GET['skipped'] ?? 0 );
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo esc_html( sprintf( 'Workflow Bulk Fulfillment: %d orders marked Fully Picked. %d skipped.', $ok, $skipped ) );
        echo '</p></div>';
    }
}

AAA_OC_Admin_Bulk_Fulfill::init();

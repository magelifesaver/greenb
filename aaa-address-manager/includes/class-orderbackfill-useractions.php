<?php
/**
 * Bulk actions for order backfilling.
 *
 * Adds custom bulk actions to the WooCommerce Orders list that allow
 * administrators to backfill missing billing/shipping coordinates on
 * selected orders. After processing, a query arg is appended so that
 * an admin notice can display how many scopes were backfilled.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OrderBackfill_UserActions {
    /**
     * Hook into WooCommerce Orders bulk actions.
     */
    public static function init() : void {
        add_filter( 'bulk_actions-edit-shop_order', [ __CLASS__, 'register_actions' ] );
        add_filter( 'handle_bulk_actions-edit-shop_order', [ __CLASS__, 'handle_action' ], 10, 3 );
        add_action( 'admin_notices', [ __CLASS__, 'admin_notice' ] );
    }

    /**
     * Register our custom bulk actions for orders.
     *
     * @param array $actions Default actions.
     * @return array Modified actions.
     */
    public static function register_actions( array $actions ) : array {
        $actions['aaa_am_backfill_billing']  = __( 'Backfill Billing Coords (AM)', 'aaa-address-manager' );
        $actions['aaa_am_backfill_shipping'] = __( 'Backfill Shipping Coords (AM)', 'aaa-address-manager' );
        $actions['aaa_am_backfill_both']     = __( 'Backfill Billing + Shipping (AM)', 'aaa-address-manager' );
        return $actions;
    }

    /**
     * Handle the selected bulk action on orders and invoke backfilling.
     *
     * @param string $redirect Original redirect URL.
     * @param string $action   Selected action key.
     * @param array  $order_ids Order IDs selected.
     * @return string Modified redirect URL.
     */
    public static function handle_action( string $redirect, string $action, array $order_ids ) : string {
        $valid = [ 'aaa_am_backfill_billing', 'aaa_am_backfill_shipping', 'aaa_am_backfill_both' ];
        if ( ! in_array( $action, $valid, true ) ) {
            return $redirect;
        }
        $scope = str_replace( 'aaa_am_backfill_', '', $action );
        $count = AAA_OrderBackfill_Core::process_orders( $order_ids, $scope );
        // Append query arg so notice can show.
        return add_query_arg( 'aaa_am_backfill_done', $count, $redirect );
    }

    /**
     * Display an admin notice for backfill results on orders page.
     */
    public static function admin_notice() : void {
        if ( isset( $_GET['aaa_am_backfill_done'] ) ) {
            $n = intval( $_GET['aaa_am_backfill_done'] );
            echo '<div class="updated"><p>' . esc_html( sprintf( _n( '%d order scope backfilled.', '%d order scopes backfilled.', $n, 'aaa-address-manager' ), $n ) ) . '</p></div>';
        }
    }
}
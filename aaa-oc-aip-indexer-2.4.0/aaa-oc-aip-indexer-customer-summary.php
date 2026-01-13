<?php
/**
 * Writes `aip_customer_summary` to user meta and copies to order meta (COMPLETED ORDERS ONLY).
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-2.1.0/aaa-oc-aip-indexer-customer-summary.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_AIP_INDEXER_CUSTOMER_SUMMARY_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_CUSTOMER_SUMMARY_LOADED', true );

class AAA_OC_AIP_Indexer_Customer_Summary {

    private static $ran = [];

    public static function init() {
        add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'maybe_sync_customer' ], 10, 1 );
    }

    public static function maybe_sync_customer( $order ) {
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( (int) $order );
        }
        if ( ! $order instanceof WC_Order ) {
            return;
        }
        if ( $order->get_status() !== 'completed' ) {
            return;
        }

        $order_id    = $order->get_id();
        $customer_id = (int) $order->get_customer_id();
        if ( $customer_id <= 0 ) {
            return;
        }

        $key = $customer_id . ':' . $order_id;
        if ( isset( self::$ran[ $key ] ) ) {
            return;
        }
        self::$ran[ $key ] = true;

        self::sync_customer( $customer_id, $order_id );
    }

    public static function sync_customer( $customer_id, $order_id = null ) {
        $user = get_user_by( 'id', (int) $customer_id );
        if ( ! $user ) {
            return;
        }

        $first_name = get_user_meta( $customer_id, 'billing_first_name', true );
        $last_name  = get_user_meta( $customer_id, 'billing_last_name', true );
        $name       = trim( $first_name . ' ' . $last_name );
        $email      = get_user_meta( $customer_id, 'billing_email', true );
        $phone      = get_user_meta( $customer_id, 'billing_phone', true );

        $order_count    = function_exists( 'wc_get_customer_order_count' ) ? (int) wc_get_customer_order_count( $customer_id ) : 0;
        $lifetime_spend = function_exists( 'wc_get_customer_total_spent' ) ? (float) wc_get_customer_total_spent( $customer_id ) : 0.0;
        $avg_order      = $order_count > 0 ? $lifetime_spend / $order_count : 0.0;

        $last_order_date = '';
        if ( function_exists( 'wc_get_orders' ) ) {
            $last_orders = wc_get_orders( [
                'customer_id' => $customer_id,
                'status'      => array_keys( wc_get_order_statuses() ),
                'orderby'     => 'date',
                'order'       => 'DESC',
                'limit'       => 1,
            ] );
            if ( $last_orders ) {
                $last = $last_orders[0];
                $dt   = $last->get_date_completed() ?: $last->get_date_created();
                if ( $dt ) {
                    $last_order_date = $dt->date( 'Y-m-d' );
                }
            }
        }

        $parts   = [];
        $parts[] = 'Customer ID: ' . (int) $customer_id;
        if ( $name ) { $parts[] = 'Name: ' . $name; }
        if ( $email ) { $parts[] = 'Email: ' . $email; }
        if ( $phone ) { $parts[] = 'Phone: ' . $phone; }
        $parts[] = 'Orders: ' . $order_count;
        $parts[] = 'Total spent: $' . number_format( $lifetime_spend, 2 );
        $parts[] = 'Average order: $' . number_format( $avg_order, 2 );
        if ( $last_order_date ) { $parts[] = 'Last order: ' . $last_order_date; }

        $summary = implode( ' | ', $parts );

        $existing_user = (string) get_user_meta( $customer_id, 'aip_customer_summary', true );
        if ( $existing_user !== $summary ) {
            update_user_meta( $customer_id, 'aip_customer_summary', $summary );
        }

        if ( $order_id ) {
            $existing_order = (string) get_post_meta( $order_id, 'aip_customer_summary', true );
            if ( $existing_order !== $summary ) {
                update_post_meta( $order_id, 'aip_customer_summary', $summary );
            }
        }

        if ( defined( 'AAA_OC_AIP_INDEXER_LOGGING_ENABLED' ) && AAA_OC_AIP_INDEXER_LOGGING_ENABLED ) {
            error_log( '[CUSTOMER SUMMARY] Updated summary for customer ' . (int) $customer_id );
        }
    }
}

AAA_OC_AIP_Indexer_Customer_Summary::init();

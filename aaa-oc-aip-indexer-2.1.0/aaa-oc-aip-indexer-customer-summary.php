<?php
/**
 * Customer summary generator for the AAA OC AIP Indexer Bridge.
 *
 * Builds a plain‑text summary of a customer's purchase history and
 * contact information.  The summary includes the customer ID,
 * name, email, phone, number of completed orders, lifetime spend,
 * average order value and last order date.  This summary is
 * stored as user meta under the public key `aip_customer_summary` and
 * also copied to each order as `aip_customer_summary` so the AIP
 * plugin can index it.  Keeping this logic in its own file
 * preserves the wide‑and‑thin architecture and allows it to be
 * activated only when needed.
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-customer-summary.php
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_AIP_INDEXER_CUSTOMER_SUMMARY_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_CUSTOMER_SUMMARY_LOADED', true );

// Local debug toggle for this module.
if ( ! defined( 'AAA_OC_AIP_INDEXER_CUSTOMER_SUMMARY_DEBUG' ) ) {
    define( 'AAA_OC_AIP_INDEXER_CUSTOMER_SUMMARY_DEBUG', true );
}

/**
 * Handles generation of customer summaries.
 */
class AAA_OC_AIP_Indexer_Customer_Summary {

    /**
     * Registers WordPress hooks for generating customer summaries.
     */
    public static function init() {
        // When a new order is created at checkout.
        add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'maybe_sync_customer' ], 10, 2 );
        // When an existing order is updated.
        add_action( 'woocommerce_update_order', [ __CLASS__, 'maybe_sync_customer' ], 10, 1 );
    }

    /**
     * Trigger customer summary generation for the order's customer.
     *
     * @param int|\WC_Order $order Order ID or object.
     */
    public static function maybe_sync_customer( $order ) {
        // Normalise to WC_Order.
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }
        if ( ! $order instanceof WC_Order ) {
            return;
        }
        $customer_id = $order->get_customer_id();
        if ( ! $customer_id || $customer_id <= 0 ) {
            return;
        }
        self::sync_customer( $customer_id, $order->get_id() );
    }

    /**
     * Builds and stores a summary for the given customer ID.
     *
     * @param int  $customer_id User ID for the customer.
     * @param int|null $order_id    Optional order ID to copy the summary to.
     */
    public static function sync_customer( $customer_id, $order_id = null ) {
        $user = get_user_by( 'id', $customer_id );
        if ( ! $user ) {
            return;
        }
        // Basic customer details.
        $first_name = get_user_meta( $customer_id, 'billing_first_name', true );
        $last_name  = get_user_meta( $customer_id, 'billing_last_name', true );
        $name       = trim( $first_name . ' ' . $last_name );
        $email      = get_user_meta( $customer_id, 'billing_email', true );
        $phone      = get_user_meta( $customer_id, 'billing_phone', true );

        // Purchase metrics using WooCommerce helper functions.
        $order_count = function_exists( 'wc_get_customer_order_count' ) ? (int) wc_get_customer_order_count( $customer_id ) : 0;
        $lifetime_spend = function_exists( 'wc_get_customer_total_spent' ) ? (float) wc_get_customer_total_spent( $customer_id ) : 0.0;
        $avg_order = $order_count > 0 ? $lifetime_spend / $order_count : 0.0;
        // Get last completed order date.
        $last_order_date = '';
        if ( function_exists( 'wc_get_orders' ) ) {
            $last_orders = wc_get_orders( [
                'customer_id' => $customer_id,
                'status'      => array_keys( wc_get_order_statuses() ),
                'orderby'     => 'date',
                'order'       => 'DESC',
                'limit'       => 1,
            ] );
            if ( ! empty( $last_orders ) ) {
                $last = $last_orders[0];
                $completed = $last->get_date_completed();
                if ( $completed ) {
                    $last_order_date = $completed->date( 'Y-m-d' );
                } else {
                    $created = $last->get_date_created();
                    if ( $created ) {
                        $last_order_date = $created->date( 'Y-m-d' );
                    }
                }
            }
        }

        // Build summary parts.
        $parts = [];
        $parts[] = 'Customer ID: ' . $customer_id;
        if ( $name ) {
            $parts[] = 'Name: ' . $name;
        }
        if ( $email ) {
            $parts[] = 'Email: ' . $email;
        }
        if ( $phone ) {
            $parts[] = 'Phone: ' . $phone;
        }
        $parts[] = 'Orders: ' . $order_count;
        $parts[] = 'Total spent: $' . number_format( $lifetime_spend, 2 );
        $parts[] = 'Average order: $' . number_format( $avg_order, 2 );
        if ( $last_order_date ) {
            $parts[] = 'Last order: ' . $last_order_date;
        }
        $summary = implode( ' | ', $parts );

        // Save summary to user meta.
        update_user_meta( $customer_id, 'aip_customer_summary', $summary );
        // Also copy to the order if provided so the AIP indexer can see it.
        if ( $order_id ) {
            update_post_meta( $order_id, 'aip_customer_summary', $summary );
        }

        // Debug logging.
        if ( AAA_OC_AIP_INDEXER_CUSTOMER_SUMMARY_DEBUG ) {
            error_log( '[CUSTOMER SUMMARY] Updated summary for customer ' . $customer_id );
        }
    }
}

// Bootstrap the customer summary module.
AAA_OC_AIP_Indexer_Customer_Summary::init();
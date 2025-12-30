<?php
/**
 * Debug module for the AAA OC AIP Indexer Bridge.
 *
 * Provides an admin page to inspect the order query used by the bridge and
 * the generated summary meta.  Administrators can optionally run a bulk
 * synchronisation over a date range.  All output is sanitized and no
 * sensitive data is exposed beyond what is already stored in orders.
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-debug.php
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_AIP_INDEXER_DEBUG_MODULE_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_DEBUG_MODULE_LOADED', true );

// Local debug toggle for this file.
if ( ! defined( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE' ) ) {
    define( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE', true );
}

/**
 * Debug class for the AIP Order bridge.
 */
class AAA_OC_AIP_Indexer_Debug {

    /**
     * Bootstraps the admin menu and bulk sync handler.
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_post_aaa_oc_aip_sync_orders', [ __CLASS__, 'handle_bulk_sync' ] );
    }

    /**
     * Registers a submenu under the AIP plugin’s top level menu.
     */
    public static function add_menu() {
        add_submenu_page(
            'wp-ai-content-generator',
            __( 'AIP Order Debug', 'aaa-oc-aip-indexer' ),
            __( 'AIP Order Debug', 'aaa-oc-aip-indexer' ),
            'manage_options',
            'aaa-oc-aip-order-debug',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Renders the debug page with query info and a sample of the generated
     * summary field.  Allows users to specify a start and end date for the
     * query and bulk sync.
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div class="wrap">';
        // Notice after bulk sync.
        if ( isset( $_GET['aaa_oc_aip_sync_orders_complete'] ) ) {
            $synced = absint( $_GET['aaa_oc_aip_sync_orders_complete'] );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( __( 'Bulk sync complete. Updated %d orders.', 'aaa-oc-aip-indexer' ), $synced ) ) . '</p></div>';
        }
        echo '<h1>' . esc_html__( 'AIP Order Query Debug', 'aaa-oc-aip-indexer' ) . '</h1>';

        // Fetch date range from request (sanitized).
        $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : gmdate( 'Y-m-d', strtotime( '-90 days' ) );
        $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';

        // Build the query args to mirror the bridge.
        $date_query = [ [ 'after' => $start_date, 'inclusive' => true ] ];
        if ( $end_date ) {
            $date_query[0]['before'] = $end_date;
        }
        $args = [
            'post_type'   => 'shop_order',
            'post_status' => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-failed', 'wc-completed' ],
            'date_query'  => $date_query,
            'fields'      => 'ids',
        ];
        $query = new WP_Query( $args );
        echo '<p><strong>' . esc_html__( 'Query Arguments', 'aaa-oc-aip-indexer' ) . ':</strong></p>';
        echo '<pre>' . esc_html( print_r( $args, true ) ) . '</pre>';
        echo '<p><strong>' . esc_html__( 'Orders Found', 'aaa-oc-aip-indexer' ) . ':</strong> ' . esc_html( $query->found_posts ) . '</p>';
        if ( $query->posts ) {
            $sample_ids = array_map( 'intval', array_slice( $query->posts, 0, 10 ) );
            echo '<p><strong>' . esc_html__( 'Sample Order IDs', 'aaa-oc-aip-indexer' ) . ':</strong> ' . esc_html( implode( ', ', $sample_ids ) ) . '</p>';
            // Show summary field for first sample order.
            $first_id = $sample_ids[0];
            $summary  = get_post_meta( $first_id, 'aip_order_summary', true );
            $cust_id  = get_post_meta( $first_id, '_customer_user', true );
            $email    = get_post_meta( $first_id, '_billing_email', true );
            echo '<p><strong>' . esc_html__( 'Sample Summary', 'aaa-oc-aip-indexer' ) . ':</strong></p>';
            echo '<pre>' . esc_html( $summary ) . '</pre>';
            echo '<p><strong>' . esc_html__( 'Customer ID', 'aaa-oc-aip-indexer' ) . ':</strong> ' . esc_html( $cust_id ) . '</p>';
            echo '<p><strong>' . esc_html__( 'Billing Email', 'aaa-oc-aip-indexer' ) . ':</strong> ' . esc_html( $email ) . '</p>';
        }

        // Form for date range query.
        echo '<hr />';
        echo '<h2>' . esc_html__( 'Run Query', 'aaa-oc-aip-indexer' ) . '</h2>';
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="aaa-oc-aip-order-debug" />';
        echo '<table class="form-table"><tr><th>' . esc_html__( 'Start Date', 'aaa-oc-aip-indexer' ) . '</th><td><input type="date" name="start_date" value="' . esc_attr( $start_date ) . '" /></td></tr>';
        echo '<tr><th>' . esc_html__( 'End Date', 'aaa-oc-aip-indexer' ) . '</th><td><input type="date" name="end_date" value="' . esc_attr( $end_date ) . '" /></td></tr></table>';
        submit_button( __( 'Run Query', 'aaa-oc-aip-indexer' ), 'secondary', 'submit', false );
        echo '</form>';

        // Bulk sync form with date range.  Only start date is mandatory.
        echo '<hr />';
        echo '<h2>' . esc_html__( 'Bulk Sync Orders', 'aaa-oc-aip-indexer' ) . '</h2>';
        echo '<p>' . esc_html__( 'Populate summaries for existing orders within the selected date range.', 'aaa-oc-aip-indexer' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="aaa_oc_aip_sync_orders" />';
        wp_nonce_field( 'aaa_oc_aip_sync_orders_nonce', 'aaa_oc_aip_sync_orders_nonce_field' );
        echo '<table class="form-table"><tr><th>' . esc_html__( 'Start Date', 'aaa-oc-aip-indexer' ) . '</th><td><input type="date" name="sync_start_date" value="' . esc_attr( $start_date ) . '" /></td></tr>';
        echo '<tr><th>' . esc_html__( 'End Date', 'aaa-oc-aip-indexer' ) . '</th><td><input type="date" name="sync_end_date" value="' . esc_attr( $end_date ) . '" /></td></tr></table>';
        submit_button( __( 'Run Sync Now', 'aaa-oc-aip-indexer' ), 'primary', 'submit', false );
        echo '</form>';
        echo '</div>';

        // Log query details if debugging.
        if ( AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE ) {
            error_log( '[DEBUG] Order query args: ' . json_encode( $args ) . ' | Found: ' . $query->found_posts );
        }
    }

    /**
     * Handles bulk synchronisation over a date range.
     * Writes new summaries to all orders matching the provided range.
     */
    public static function handle_bulk_sync() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'aaa-oc-aip-indexer' ) );
        }
        // Verify nonce.
        if ( ! isset( $_POST['aaa_oc_aip_sync_orders_nonce_field'] ) || ! wp_verify_nonce( $_POST['aaa_oc_aip_sync_orders_nonce_field'], 'aaa_oc_aip_sync_orders_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'aaa-oc-aip-indexer' ) );
        }
        // Dates for filtering.
        $start_date = isset( $_POST['sync_start_date'] ) ? sanitize_text_field( $_POST['sync_start_date'] ) : gmdate( 'Y-m-d', strtotime( '-90 days' ) );
        $end_date   = isset( $_POST['sync_end_date'] ) ? sanitize_text_field( $_POST['sync_end_date'] ) : '';
        $date_query = [ [ 'after' => $start_date, 'inclusive' => true ] ];
        if ( $end_date ) {
            $date_query[0]['before'] = $end_date;
        }
        $args = [
            'post_type'   => 'shop_order',
            'post_status' => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-failed', 'wc-completed' ],
            'date_query'  => $date_query,
            'fields'      => 'ids',
            'posts_per_page' => -1,
        ];
        $query = new WP_Query( $args );
        $count = 0;
        if ( $query->posts ) {
            foreach ( $query->posts as $order_id ) {
                AAA_OC_AIP_Indexer_Order_Meta::sync_meta( $order_id );
                $count++;
            }
        }
        $redirect = add_query_arg( [ 'aaa_oc_aip_sync_orders_complete' => $count ], admin_url( 'admin.php?page=aaa-oc-aip-order-debug' ) );
        wp_safe_redirect( $redirect );
        exit;
    }
}

// Initialise the debug module.
AAA_OC_AIP_Indexer_Debug::init();
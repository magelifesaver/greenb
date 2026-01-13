<?php
/**
 * Admin debug + manual bulk sync (completed orders only).
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-2.1.0/aaa-oc-aip-indexer-debug.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_AIP_INDEXER_DEBUG_MODULE_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_DEBUG_MODULE_LOADED', true );

class AAA_OC_AIP_Indexer_Debug {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_post_aaa_oc_aip_sync_orders', [ __CLASS__, 'handle_bulk_sync' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'wp-ai-content-generator',
            'AIP Order Debug',
            'AIP Order Debug',
            'manage_options',
            'aaa-oc-aip-order-debug',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : gmdate( 'Y-m-d', strtotime( '-90 days' ) );
        $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';

        $date_query = [ [ 'after' => $start_date, 'inclusive' => true ] ];
        if ( $end_date ) {
            $date_query[0]['before'] = $end_date;
        }

        $args = [
            'post_type'   => 'shop_order',
            'post_status' => [ 'wc-completed' ],
            'date_query'  => $date_query,
            'fields'      => 'ids',
        ];

        $query = new WP_Query( $args );

        echo '<div class="wrap"><h1>AIP Order Debug</h1>';

        if ( isset( $_GET['aaa_oc_aip_sync_orders_complete'] ) ) {
            $synced = absint( $_GET['aaa_oc_aip_sync_orders_complete'] );
            echo '<div class="notice notice-success is-dismissible"><p>Bulk sync complete. Updated ' . esc_html( $synced ) . ' orders.</p></div>';
        }

        echo '<p><strong>Completed orders found:</strong> ' . esc_html( (int) $query->found_posts ) . '</p>';
        echo '<pre>' . esc_html( print_r( $args, true ) ) . '</pre>';

        $sample_ids = $query->posts ? array_map( 'intval', array_slice( $query->posts, 0, 5 ) ) : [];
        if ( $sample_ids ) {
            $first_id = $sample_ids[0];
            $summary  = (string) get_post_meta( $first_id, 'aip_order_summary', true );
            echo '<p><strong>Sample IDs:</strong> ' . esc_html( implode( ', ', $sample_ids ) ) . '</p>';
            echo '<p><strong>Sample Summary (first ID):</strong></p><pre>' . esc_html( $summary ) . '</pre>';
        }

        echo '<hr><h2>Run Query</h2><form method="get">';
        echo '<input type="hidden" name="page" value="aaa-oc-aip-order-debug" />';
        echo '<p>Start: <input type="date" name="start_date" value="' . esc_attr( $start_date ) . '"> ';
        echo 'End: <input type="date" name="end_date" value="' . esc_attr( $end_date ) . '"> ';
        submit_button( 'Run Query', 'secondary', 'submit', false );
        echo '</p></form>';

        echo '<hr><h2>Bulk Sync (Completed Orders Only)</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="aaa_oc_aip_sync_orders" />';
        wp_nonce_field( 'aaa_oc_aip_sync_orders_nonce', 'aaa_oc_aip_sync_orders_nonce_field' );
        echo '<p>Start: <input type="date" name="sync_start_date" value="' . esc_attr( $start_date ) . '"> ';
        echo 'End: <input type="date" name="sync_end_date" value="' . esc_attr( $end_date ) . '"> ';
        submit_button( 'Run Sync Now', 'primary', 'submit', false );
        echo '</p></form></div>';
    }

    public static function handle_bulk_sync() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'No permission.' );
        }
        if ( ! isset( $_POST['aaa_oc_aip_sync_orders_nonce_field'] ) || ! wp_verify_nonce( $_POST['aaa_oc_aip_sync_orders_nonce_field'], 'aaa_oc_aip_sync_orders_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        $start_date = isset( $_POST['sync_start_date'] ) ? sanitize_text_field( $_POST['sync_start_date'] ) : gmdate( 'Y-m-d', strtotime( '-90 days' ) );
        $end_date   = isset( $_POST['sync_end_date'] ) ? sanitize_text_field( $_POST['sync_end_date'] ) : '';

        $date_query = [ [ 'after' => $start_date, 'inclusive' => true ] ];
        if ( $end_date ) {
            $date_query[0]['before'] = $end_date;
        }

        $query = new WP_Query( [
            'post_type'      => 'shop_order',
            'post_status'    => [ 'wc-completed' ],
            'date_query'     => $date_query,
            'fields'         => 'ids',
            'posts_per_page' => -1,
        ] );

        $count = 0;
        foreach ( (array) $query->posts as $order_id ) {
            AAA_OC_AIP_Indexer_Order_Meta::sync_meta( (int) $order_id );
            AAA_OC_AIP_Indexer_Order_Items::sync_items( (int) $order_id );
            AAA_OC_AIP_Indexer_Customer_Summary::maybe_sync_customer( (int) $order_id );
            $count++;
        }

        wp_safe_redirect( add_query_arg(
            [ 'aaa_oc_aip_sync_orders_complete' => $count ],
            admin_url( 'admin.php?page=aaa-oc-aip-order-debug' )
        ) );
        exit;
    }
}

AAA_OC_AIP_Indexer_Debug::init();

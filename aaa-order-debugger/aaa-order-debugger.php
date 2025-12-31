<?php
/**
 * Plugin Name: AAA Order Debugger
 * Description: Debug tool to view order JSON and selected workflow tables (order index, payment index, fulfillment logs, options).
 * Version: 1.1.3
 * Author: Workflow
 * File Path: /wp-content/plugins/aaa-order-debugger/aaa-order-debugger.php
 */

// Exit if accessed directly and define a version constant.
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_ORDER_DEBUGGER_VERSION' ) ) { define( 'AAA_ORDER_DEBUGGER_VERSION', '1.1.3' ); }

class AAA_Order_Debugger {

    /** Initialise hooks. */
    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_post_aaa_order_debugger', [ __CLASS__, 'handle' ] );
    }

    /** Add submenu under WooCommerce. */
    public static function menu() : void {
        add_submenu_page(
            'woocommerce',
            __( 'Order Debugger', 'aaa-order-debugger' ),
            __( 'Order Debugger', 'aaa-order-debugger' ),
            'manage_woocommerce',
            'aaa-order-debugger',
            [ __CLASS__, 'page' ]
        );
    }

    /** Render the settings page. */
    public static function page() : void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Order Debugger', 'aaa-order-debugger' ) . '</h1>';
        echo '<p>' . esc_html__( 'Enter an order ID to dump order JSON, order index, payment index, fulfillment logs and options.', 'aaa-order-debugger' ) . '</p>';
        echo '<form method="post" target="_blank" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'aaa_order_debugger' );
        echo '<input type="hidden" name="action" value="aaa_order_debugger">';
        echo '<input type="number" name="order_id" placeholder="Order ID" style="width:160px;" required> ';
        submit_button( __( 'Run Debug', 'aaa-order-debugger' ), 'primary', '', false );
        echo '</form>';
        echo '</div>';
    }

    /** Handle the form submission. */
    public static function handle() : void {
        check_admin_referer( 'aaa_order_debugger' );
        global $wpdb;

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        // Begin wrapper and heading.
        echo '<div class="wrap"><h1>' . esc_html__( 'Debugger Results', 'aaa-order-debugger' ) . '</h1>';

        if ( ! $order_id ) {
            echo '<p>' . esc_html__( 'No order ID provided.', 'aaa-order-debugger' ) . '</p>';
            echo '</div>';
            exit;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            echo '<p>' . esc_html__( 'Order not found.', 'aaa-order-debugger' ) . '</p>';
            echo '</div>';
            exit;
        }

        echo '<h2>' . esc_html__( 'Order #', 'aaa-order-debugger' ) . esc_html( $order_id ) . '</h2>';
        // Top copy button
        echo '<p><button type="button" class="button aaa-debug-copy-btn" onclick="aaaCopyDebugger(this)">' . esc_html__( 'Copy All', 'aaa-order-debugger' ) . '</button></p>';
        echo '<div id="aaa-debug-container" style="white-space:pre-wrap;font-family:monospace;font-size:13px;">';

        // 1. Full REST JSON of the order.
        $json = self::get_order_rest_json( $order_id );
        echo '=== WC REST /wc/v3/orders/' . esc_html( $order_id ) . " ===\n";
        echo esc_html( $json ) . "\n\n";

        // 2. Order index row.
        $order_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aaa_oc_order_index WHERE order_id = %d", $order_id ), ARRAY_A );
        if ( ! $order_row ) {
            $order_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aaa_wf_order_index_live WHERE order_id = %d", $order_id ), ARRAY_A );
        }
        echo '=== ORDER INDEX ROW ===\n';
        echo esc_html( print_r( $order_row, true ) ) . "\n\n";

        // 3. Payment index row.
        $pay_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aaa_oc_payment_index WHERE order_id = %d", $order_id ), ARRAY_A );
        if ( ! $pay_row ) {
            $pay_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}aaa_wf_wfpay_payment WHERE order_id = %d", $order_id ), ARRAY_A );
        }
        echo '=== PAYMENT INDEX ROW ===\n';
        echo esc_html( print_r( $pay_row, true ) ) . "\n\n";

        // 4. Fulfillment logs (may return multiple rows).
        $fulfillment_logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aaa_oc_fulfillment_logs WHERE order_id = %d", $order_id ), ARRAY_A );
        echo '=== FULFILLMENT LOGS ===\n';
        echo esc_html( print_r( $fulfillment_logs, true ) ) . "\n\n";

        // 5. Options table dump (mask secrets and exclude daily_count/api_key rows).
        $options = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}aaa_oc_options", ARRAY_A );
        $filtered = [];
        foreach ( $options as $opt ) {
            $key = isset( $opt['option_key'] ) ? $opt['option_key'] : '';
            // Skip daily_order_count* and any key containing api_key
            if ( $key !== '' && ( stripos( $key, 'daily_order_count' ) === 0 || stripos( $key, 'api_key' ) !== false ) ) {
                continue;
            }
            if ( $key === 'aaa_wf_ai_openai_key' ) {
                $opt['option_value'] = '[MASKED]';
            }
            $filtered[] = $opt;
        }
        echo '=== ' . esc_html( $wpdb->prefix . 'aaa_oc_options' ) . ' ===\n';
        echo esc_html( print_r( $filtered, true ) ) . "\n\n";

        echo '</div>'; // End of debug container.

        // Copy button without alert; toggle text while copying.
        echo '<p><button type="button" class="button aaa-debug-copy-btn" onclick="aaaCopyDebugger(this)">' . esc_html__( 'Copy All', 'aaa-order-debugger' ) . '</button></p>';

        // Inline script for copy functionality (condensed).
        echo '<script>function aaaCopyDebugger(btn){var c=document.getElementById("aaa-debug-container");if(!c)return;var t=c.innerText||c.textContent||"",b=btn||document.querySelector(".aaa-debug-copy-btn"),o=b?b.textContent:"";if(b){b.disabled=true;b.textContent="Copying..."}navigator.clipboard.writeText(t).finally(function(){if(b){b.disabled=false;b.textContent=o||"Copy All";}});}</script>';

        echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=aaa-order-debugger' ) ) . '" class="button">&larr; ' . esc_html__( 'Back', 'aaa-order-debugger' ) . '</a></p>';
        echo '</div>';
        exit;
    }

    /** Fetch the internal WC REST order JSON. @param int $order_id Order ID. @return string */
    private static function get_order_rest_json( int $order_id ) : string {
        // Ensure WC REST controller exists.
        if ( ! class_exists( 'WC_REST_Orders_V3_Controller' ) && ! class_exists( 'WC_REST_Orders_V2_Controller' ) ) {
            return wp_json_encode( [ 'error' => 'WooCommerce REST controller not available.' ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        }
        $request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id );
        $request->set_param( 'context', 'edit' );
        $response = rest_do_request( $request );
        if ( is_wp_error( $response ) ) {
            return wp_json_encode( [ 'error' => 'REST request failed', 'message' => $response->get_error_message() ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        }
        $data = method_exists( $response, 'get_data' ) ? $response->get_data() : [];
        return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }
}

// Initialise plugin.
AAA_Order_Debugger::init();
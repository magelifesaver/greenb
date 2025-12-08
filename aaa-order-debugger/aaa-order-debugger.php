<?php
/**
 * Plugin Name: AAA Order Debugger
 * Description: Debug tool to view order JSON + workflow tables (order index, payment index, etc.).
 * Version: 1.0.3
 * Author: Workflow
 * File Path: /wp-content/plugins/aaa-order-debugger/aaa-order-debugger.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_Order_Debugger {

    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_post_aaa_order_debugger', [ __CLASS__, 'handle' ] );
    }

    public static function menu() : void {
        add_submenu_page(
            'woocommerce',
            'Order Debugger',
            'Order Debugger',
            'manage_woocommerce',
            'aaa-order-debugger',
            [ __CLASS__, 'page' ]
        );
    }

    public static function page() : void {
        echo '<div class="wrap"><h1>Order Debugger</h1>';
        echo '<p>Enter an order ID to dump the WC REST JSON plus all Workflow tables.</p>';

        // Open results in new tab
        echo '<form method="post" target="_blank" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'aaa_order_debugger' );
        echo '<input type="hidden" name="action" value="aaa_order_debugger">';
        echo '<input type="number" name="order_id" placeholder="Order ID" style="width:160px;"> ';
        submit_button( 'Run Debug', 'primary', '', false );
        echo '</form></div>';
    }

    public static function handle() : void {
        check_admin_referer( 'aaa_order_debugger' );
        global $wpdb;

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

        echo '<div class="wrap"><h1>Debugger Results</h1>';

        if ( ! $order_id ) {
            echo '<p>No order ID provided.</p></div>';
            exit;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            echo '<p>Order not found.</p></div>';
            exit;
        }

        echo '<h2>Order #' . esc_html( $order_id ) . '</h2>';

        // Main text container – everything inside this is copied
        echo '<div id="aaa-debug-container" style="white-space:pre-wrap;font-family:monospace;font-size:13px;">';

        /*
         * 1) FULL WC REST JSON (/wc/v3/orders/{id})
         * --------------------------------------------------
         */
        $json = self::get_order_rest_json( $order_id );
        echo "=== WC REST /wc/v3/orders/{$order_id} ===\n";
        echo esc_html( $json ) . "\n\n";

        /*
         * 2) EXISTING WORKFLOW TABLE DUMPS
         * --------------------------------------------------
         * Keep your existing logic here: order index, payment index,
         * any other Workflow tables, ending with the options table.
         */

        // Example: Order Index (OC or Workflow V2)
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aaa_oc_order_index WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );
        if ( ! $row ) {
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}aaa_wf_order_index_live WHERE order_id = %d",
                    $order_id
                ),
                ARRAY_A
            );
        }
        echo "=== ORDER INDEX ROW ===\n";
        echo esc_html( print_r( $row, true ) ) . "\n\n";

        // Example: Payment Index (OC Payment or WFPay snapshot)
        $pay = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aaa_oc_payment_index WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );
        if ( ! $pay ) {
            $pay = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->base_prefix}aaa_wf_wfpay_payment WHERE order_id = %d",
                    $order_id
                ),
                ARRAY_A
            );
        }
        echo "=== PAYMENT INDEX ROW ===\n";
        echo esc_html( print_r( $pay, true ) ) . "\n\n";

        // TODO: keep / extend your other workflow table dumps here
        //       (delivery index, fulfillment, wfpay logs, OWF live index, options, etc.)

        echo "</div>"; // end #aaa-debug-container

        // Copy button – no dialog, just label change
        echo '<p><button type="button" class="button aaa-debug-copy-btn" onclick="aaaCopyDebugger(this)">Copy All</button></p>';

        // Inline JS for copy behaviour
        echo "<script>
        function aaaCopyDebugger(btn){
            var container = document.getElementById('aaa-debug-container');
            if (!container) return;
            var text = container.innerText || container.textContent || '';
            var button = btn || document.querySelector('.aaa-debug-copy-btn');
            var original = button ? button.textContent : '';

            if (button) {
                button.disabled = true;
                button.textContent = 'Copying...';
            }

            navigator.clipboard.writeText(text).finally(function(){
                if (button) {
                    button.disabled = false;
                    button.textContent = original || 'Copy All';
                }
            });
        }
        </script>";

        echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=aaa-order-debugger' ) ) . '">&larr; Back</a></p></div>';
        exit;
    }

    /**
     * Fetch the internal WC REST representation of the order:
     * GET /wc/v3/orders/{id} (using rest_do_request, no HTTP round-trip).
     */
    private static function get_order_rest_json( int $order_id ) : string {
        if ( ! class_exists( 'WC_REST_Orders_V3_Controller' ) && ! class_exists( 'WC_REST_Orders_V2_Controller' ) ) {
            return wp_json_encode(
                [ 'error' => 'WooCommerce REST controller not available on this site.' ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
        }

        // Build the REST request to the exact endpoint
        $request = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id );
        $request->set_param( 'context', 'edit' );

        $response = rest_do_request( $request );

        if ( $response instanceof WP_Error || ( $response && method_exists( $response, 'is_error' ) && $response->is_error() ) ) {
            $message = $response instanceof WP_Error
                ? $response->get_error_message()
                : 'Unknown REST error';
            return wp_json_encode(
                [ 'error' => 'REST request failed', 'message' => $message ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
        }

        // Get raw data from REST response (matches the JSON body of the endpoint)
        $data = $response->get_data();

        return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }
}

AAA_Order_Debugger::init();

<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Order_Debugger {
    public static function settings(): array {
        $d = [ 'enabled' => 0, 'debug_enabled' => 0 ];
        $s = DDD_DT_Options::get( 'ddd_order_debugger_settings', [], 'global' );
        return is_array( $s ) ? array_merge( $d, $s ) : $d;
    }

    public static function init() {
        if ( ! is_admin() ) {
            return;
        }
        add_action( 'admin_post_ddd_dt_order_debugger', [ __CLASS__, 'handle' ] );
    }

    public static function handle() {
        check_admin_referer( 'ddd_dt_order_debugger' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        if ( empty( self::settings()['enabled'] ) ) {
            wp_die( 'Order Debugger module is disabled.' );
        }
        if ( ! function_exists( 'wc_get_order' ) ) {
            wp_die( 'WooCommerce not available.' );
        }

        global $wpdb;
        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

        echo '<div class="wrap"><h1>Order Debugger Results</h1>';
        if ( ! $order_id ) {
            echo '<p>No order ID provided.</p></div>';
            exit;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            echo '<p>Order not found.</p></div>';
            exit;
        }

        DDD_DT_Logger::write( 'order_debugger', 'run', [ 'order_id' => $order_id ] );

        echo '<h2>Order #' . esc_html( $order_id ) . '</h2><div id="ddd-dt-odb-container" style="white-space:pre-wrap;font-family:monospace;font-size:13px;">';

        $json = self::get_order_rest_json( $order_id );
        $json_array = json_decode( $json, true ) ?: [];
        $summary = self::build_workflow_summary( $json_array );

        echo "=== WORKFLOW SUMMARY ===\n";
        foreach ( $summary as $label => $value ) {
            $display = ( $value === '' || $value === null ) ? '—' : $value;
            echo esc_html( "$label: $display" ) . "\n";
        }

        echo "\n=== WC REST /wc/v3/orders/{$order_id} ===\n" . esc_html( $json ) . "\n\n";

        $index = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aaa_oc_order_index WHERE order_id = %d", $order_id ), ARRAY_A );
        if ( ! $index ) $index = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aaa_wf_order_index_live WHERE order_id = %d", $order_id ), ARRAY_A );
        echo "=== ORDER INDEX ROW ===\n" . esc_html( print_r( $index, true ) ) . "\n\n";

        $pay = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aaa_oc_payment_index WHERE order_id = %d", $order_id ), ARRAY_A );
        if ( ! $pay ) $pay = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}aaa_wf_wfpay_payment WHERE order_id = %d", $order_id ), ARRAY_A );
        echo "=== PAYMENT INDEX ROW ===\n" . esc_html( print_r( $pay, true ) ) . "\n\n";

        $like = $wpdb->esc_like( $wpdb->prefix . 'aaa_oc_' ) . '%';
        $tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
        $skip = [ $wpdb->prefix . 'aaa_oc_order_index', $wpdb->prefix . 'aaa_oc_payment_index' ];

        foreach ( (array) $tables as $table ) {
            if ( in_array( $table, $skip, true ) ) continue;
            echo '=== ' . esc_html( $table ) . " ===\n";
            $has_col = $wpdb->get_col( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'order_id' ) );
            $rows = ! empty( $has_col ) ? $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d", $order_id ), ARRAY_A ) : $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );
            echo esc_html( print_r( $rows, true ) ) . "\n\n";
        }

        echo '</div><p><button type="button" class="button" onclick="dddDtCopyOdb(this)">Copy All</button></p>';
        echo "<script>function dddDtCopyOdb(btn){var c=document.getElementById('ddd-dt-odb-container');if(!c) return;var text=c.innerText||c.textContent||'';var b=btn;var orig=b?b.textContent:'';if(b){b.disabled=true;b.textContent='Copying...';}navigator.clipboard.writeText(text).finally(function(){if(b){b.disabled=false;b.textContent=orig||'Copy All';}});}</script>";
        echo '<p><a href="' . esc_url( admin_url( 'tools.php?page=ddd-dev-tools&tab=order_debugger' ) ) . '">&larr; Back</a></p></div>';
        exit;
    }

    private static function get_order_rest_json( int $order_id ): string {
        if ( ! class_exists( 'WC_REST_Orders_V3_Controller' ) && ! class_exists( 'WC_REST_Orders_V2_Controller' ) ) {
            return wp_json_encode( [ 'error' => 'WooCommerce REST controller not available on this site.' ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        }
        $request = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id );
        $request->set_param( 'context', 'edit' );
        $response = rest_do_request( $request );

        if ( $response instanceof WP_Error || ( $response && method_exists( $response, 'is_error' ) && $response->is_error() ) ) {
            $message = $response instanceof WP_Error ? $response->get_error_message() : 'Unknown REST error';
            return wp_json_encode( [ 'error' => 'REST request failed', 'message' => $message ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        }
        return wp_json_encode( $response->get_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }

    private static function build_workflow_summary( array $data ): array {
        $meta = ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) ) ? $data['meta_data'] : [];
        $get = function( string $key ) use ( $meta ) {
            foreach ( $meta as $m ) {
                if ( isset( $m['key'] ) && $m['key'] === $key ) {
                    return is_array( $m['value'] ) ? wp_json_encode( $m['value'] ) : (string) $m['value'];
                }
            }
            return '';
        };

        $notes = $get( 'payment_admin_notes' );
        $real = '';
        if ( $notes && preg_match( '/Real Payment:\s*[^→]*→\s*([A-Za-z0-9 _-]+)/u', $notes, $m ) ) {
            $real = trim( $m[1] );
        }

        return [
            'Payment Status' => $get( 'aaa_oc_payment_status' ),
            'Real Payment'   => $real,
            'Cash'           => $get( 'aaa_oc_cash_amount' ),
            'Zelle'          => $get( 'aaa_oc_zelle_amount' ),
            'Venmo'          => $get( 'aaa_oc_venmo_amount' ),
            'CashApp'        => $get( 'aaa_oc_cashapp_amount' ),
            'ApplePay'       => $get( 'aaa_oc_applepay_amount' ),
            'Credit Card'    => $get( 'aaa_oc_creditcard_amount' ),
            'EPayment Total' => $get( 'aaa_oc_epayment_total' ),
            'Payment Total'  => $get( 'aaa_oc_payrec_total' ),
            'Order Balance'  => $get( 'aaa_oc_order_balance' ),
            'ePayment Tip'   => $get( 'epayment_tip' ),
            'Total Order Tip'=> $get( 'total_order_tip' ),
            'Driver User ID' => $get( 'lddfw_driverid' ),
            'Order Source'   => $get( '_wc_order_attribution_source_type' ),
            'Created Via'    => isset( $data['created_via'] ) ? (string) $data['created_via'] : '',
        ];
    }
}

<?php
// Admin page for manual order backfilling. Offers filters and a table of orders.

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OrderBackfill_Admin {
    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'register_page' ] );
    }
    public static function register_page() : void {
        add_submenu_page( 'woocommerce', __( 'Backfill Orders', 'aaa-address-manager' ), __( 'Backfill Orders', 'aaa-address-manager' ), 'manage_woocommerce', 'aaa-am-backfill-orders', [ __CLASS__, 'render_page' ] );
    }
    public static function render_page() : void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        // Process submissions.
        if ( isset( $_POST['aaa_am_backfill_orders'], $_POST['order_ids'] ) && check_admin_referer( 'aaa_am_backfill_page' ) ) {
            $scope = sanitize_text_field( $_POST['scope'] ?? 'billing' );
            $ids   = array_map( 'intval', (array) $_POST['order_ids'] );
            $count = AAA_OrderBackfill_Core::process_orders( $ids, $scope );
            echo '<div class="updated"><p>' . esc_html( sprintf( _n( '%d order scope backfilled.', '%d order scopes backfilled.', $count, 'aaa-address-manager' ), $count ) ) . '</p></div>';
        }
        // Query filters.
        $include_special = ! empty( $_GET['include_special'] );
        $orderby   = sanitize_key( $_GET['orderby'] ?? 'date' );
        $order     = ( strtolower( $_GET['order'] ?? 'DESC' ) === 'asc' ) ? 'ASC' : 'DESC';
        $status    = sanitize_key( $_GET['status'] ?? '' );
        $missing   = sanitize_key( $_GET['missing'] ?? '' );
        $date_from = sanitize_text_field( $_GET['date_from'] ?? '' );
        $date_to   = sanitize_text_field( $_GET['date_to'] ?? '' );
        // Build query args.
        $query_args = [ 'limit' => 100, 'orderby' => $orderby, 'order' => $order, 'return' => 'ids' ];
        if ( $status ) { $query_args['status'] = $status; }
        if ( $date_from ) { $query_args['date_created'] = '>=' . $date_from; }
        if ( $date_to ) { $query_args['date_created'] = ( $query_args['date_created'] ?? '' ) . '<=' . $date_to; }
        $orders = wc_get_orders( $query_args );
        echo '<div class="wrap"><h1>' . esc_html__( 'Backfill Orders', 'aaa-address-manager' ) . '</h1>';
        // Render filter form in one echo to save lines.
        $toggle_url = $include_special ? remove_query_arg( 'include_special' ) : add_query_arg( 'include_special', 1 );
        echo '<form method="get" style="margin-bottom:15px;">'
            .'<input type="hidden" name="page" value="aaa-am-backfill-orders" />'
            .'<label>' . esc_html__( 'Status:', 'aaa-address-manager' ) . ' <select name="status"><option value="">' . esc_html__( 'All', 'aaa-address-manager' ) . '</option>';
        foreach ( wc_get_order_statuses() as $slug => $label ) {
            $value = str_replace( 'wc-', '', $slug );
            echo '<option value="' . esc_attr( $value ) . '" ' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></label> '
            .'<label>' . esc_html__( 'Missing:', 'aaa-address-manager' ) . ' <select name="missing"><option value="">' . esc_html__( 'All', 'aaa-address-manager' ) . '</option>'
            .'<option value="billing" ' . selected( $missing, 'billing', false ) . '>' . esc_html__( 'Billing only', 'aaa-address-manager' ) . '</option>'
            .'<option value="shipping" ' . selected( $missing, 'shipping', false ) . '>' . esc_html__( 'Shipping only', 'aaa-address-manager' ) . '</option>'
            .'<option value="both" ' . selected( $missing, 'both', false ) . '>' . esc_html__( 'Both', 'aaa-address-manager' ) . '</option>'
            .'</select></label> '
            .'<label>' . esc_html__( 'Date From:', 'aaa-address-manager' ) . ' <input type="date" name="date_from" value="' . esc_attr( $date_from ) . '" /></label> '
            .'<label>' . esc_html__( 'Date To:', 'aaa-address-manager' ) . ' <input type="date" name="date_to" value="' . esc_attr( $date_to ) . '" /></label> '
            .'<button class="button">' . esc_html__( 'Apply Filters', 'aaa-address-manager' ) . '</button> '
            .'<a class="button" href="' . esc_url( $toggle_url ) . '">' . ( $include_special ? esc_html__( 'Hide refunds & drafts', 'aaa-address-manager' ) : esc_html__( 'Include refunds & drafts', 'aaa-address-manager' ) ) . '</a>'
            .'</form>';
        // Prepare sorting callback.
        $base_url = remove_query_arg( [ 'orderby', 'order' ] );
        $col_link = function ( $col ) use ( $orderby, $order, $base_url ) {
            $new_order = ( $orderby === $col && 'ASC' === $order ) ? 'DESC' : 'ASC';
            return esc_url( add_query_arg( [ 'orderby' => $col, 'order' => $new_order ], $base_url ) );
        };
        // Begin orders table.
        echo '<form method="post">';
        wp_nonce_field( 'aaa_am_backfill_page' );
        echo '<table class="widefat striped"><thead><tr>'
            .'<th><input type="checkbox" id="aaa-am-orders-checkall" /></th>'
            .'<th><a href="' . $col_link( 'id' ) . '">' . esc_html__( 'Order', 'aaa-address-manager' ) . '</a></th>'
            .'<th><a href="' . $col_link( 'status' ) . '">' . esc_html__( 'Status', 'aaa-address-manager' ) . '</a></th>'
            .'<th><a href="' . $col_link( 'billing_last_name' ) . '">' . esc_html__( 'Customer', 'aaa-address-manager' ) . '</a></th>'
            .'<th><a href="' . $col_link( 'date' ) . '">' . esc_html__( 'Date', 'aaa-address-manager' ) . '</a></th>'
            .'<th>' . esc_html__( 'Billing City', 'aaa-address-manager' ) . '</th>'
            .'<th>' . esc_html__( 'Billing Coords', 'aaa-address-manager' ) . '</th>'
            .'<th>' . esc_html__( 'Shipping City', 'aaa-address-manager' ) . '</th>'
            .'<th>' . esc_html__( 'Shipping Coords', 'aaa-address-manager' ) . '</th>'
            .'</tr></thead><tbody>';
        foreach ( $orders as $oid ) {
            $order = wc_get_order( $oid );
            if ( ! $order ) { continue; }
            $is_refund = ( 'shop_order_refund' === $order->get_type() );
            $is_draft  = ( 'draft' === $order->get_status() );
            if ( ( $is_refund || $is_draft ) && ! $include_special ) { continue; }
            $b_lat = $order->get_meta( '_wc_billing/aaa-delivery-blocks/latitude' );
            $b_lng = $order->get_meta( '_wc_billing/aaa-delivery-blocks/longitude' );
            $s_lat = $order->get_meta( '_wc_shipping/aaa-delivery-blocks/latitude' );
            $s_lng = $order->get_meta( '_wc_shipping/aaa-delivery-blocks/longitude' );
            if ( 'billing' === $missing && ( $b_lat && $b_lng ) ) { continue; }
            if ( 'shipping' === $missing && ( $s_lat && $s_lng ) ) { continue; }
            if ( 'both' === $missing && ( $b_lat && $b_lng && $s_lat && $s_lng ) ) { continue; }
            $customer = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
            echo '<tr>'
                .'<td><input type="checkbox" name="order_ids[]" value="' . esc_attr( $oid ) . '" /></td>'
                .'<td><a href="' . esc_url( get_edit_post_link( $oid ) ) . '">#' . esc_html( $oid ) . '</a></td>'
                .'<td>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</td>'
                .'<td>' . esc_html( $customer ) . '</td>'
                .'<td>' . esc_html( $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '' ) . '</td>'
                .'<td>' . esc_html( $order->get_billing_city() ) . '</td>'
                .'<td>' . ( $b_lat && $b_lng ? '✅' : '❌' ) . '</td>'
                .'<td>' . esc_html( $order->get_shipping_city() ) . '</td>'
                .'<td>' . ( $s_lat && $s_lng ? '✅' : '❌' ) . '</td>'
                .'</tr>';
        }
        echo '</tbody></table>'
            .'<p><select name="scope">'
            .'<option value="billing">' . esc_html__( 'Billing only', 'aaa-address-manager' ) . '</option>'
            .'<option value="shipping">' . esc_html__( 'Shipping only', 'aaa-address-manager' ) . '</option>'
            .'<option value="both">' . esc_html__( 'Billing + Shipping', 'aaa-address-manager' ) . '</option>'
            .'</select> '
            .'<button type="submit" name="aaa_am_backfill_orders" class="button button-primary">' . esc_html__( 'Backfill Selected', 'aaa-address-manager' ) . '</button></p>'
            .'</form>'
            .'<script>(function(){var all=document.getElementById("aaa-am-orders-checkall"); if(all){ all.addEventListener("change", function(){ document.querySelectorAll("input[name=\"order_ids[]\"]").forEach(function(cb){ cb.checked = all.checked; }); }); }})();</script>'
            .'</div>';
    }
}
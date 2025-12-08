<?php
/**
 * File: /plugins/aaa-adbc-order-backfill/aaa-adbc-order-backfill.php
 * Plugin Name: AAA ADBC Order Backfill
 * Description: Bulk action + Admin page to backfill missing billing/shipping coords on WooCommerce orders using Google Geocode API.
 * Version: 1.1.0
 * Author: Workflow Delivery
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_ADBC_Order_Backfill {
    const DEBUG_THIS_FILE = true;
    const API_KEY         = ''; // optional override; falls back to delivery_global option

    public static function init() {
        // Bulk actions on Orders grid
        add_filter( 'bulk_actions-edit-shop_order', [ __CLASS__, 'register_actions' ] );
        add_filter( 'handle_bulk_actions-edit-shop_order', [ __CLASS__, 'handle_action' ], 10, 3 );

        // Admin submenu page
        add_action( 'admin_menu', [ __CLASS__, 'register_page' ] );
    }

    public static function register_actions( $actions ) {
        $actions['aaa_adbc_backfill_billing']  = 'Backfill Billing Coords';
        $actions['aaa_adbc_backfill_shipping'] = 'Backfill Shipping Coords';
        $actions['aaa_adbc_backfill_both']     = 'Backfill Billing + Shipping Coords';
        return $actions;
    }

    public static function handle_action( $redirect, $action, $order_ids ) {
        if ( ! in_array( $action, [
            'aaa_adbc_backfill_billing',
            'aaa_adbc_backfill_shipping',
            'aaa_adbc_backfill_both'
        ], true ) ) {
            return $redirect;
        }
        $scope = str_replace( 'aaa_adbc_backfill_', '', $action );
        $count = self::process_orders( $order_ids, $scope );
        return add_query_arg( 'aaa_adbc_backfill_done', $count, $redirect );
    }

    private static function process_orders( array $order_ids, string $scope ) : int {
        $count = 0;
        foreach ( array_map( 'intval', $order_ids ) as $oid ) {
            $order = wc_get_order( $oid );
            if ( ! $order ) continue;
            $scopes = ( $scope === 'both' ) ? [ 'billing', 'shipping' ] : [ $scope ];
            foreach ( $scopes as $s ) {
                $ok = self::backfill_order_scope( $order, $s );
                if ( $ok ) $count++;
            }
            $order->save();
        }
        return $count;
    }

    private static function backfill_order_scope( WC_Order $order, string $scope ) : bool {
        $addr = [
            $order->{"get_{$scope}_address_1"}(),
            $order->{"get_{$scope}_address_2"}(),
            $order->{"get_{$scope}_city"}(),
            $order->{"get_{$scope}_state"}(),
            $order->{"get_{$scope}_postcode"}(),
            $order->{"get_{$scope}_country"}(),
        ];
        $addr_str = trim( implode( ', ', array_filter( array_map( 'trim', $addr ) ) ) );
        if ( $addr_str === '' ) return false;

        [$lat,$lng,$status] = self::geocode( $addr_str );
        if ( ! $lat || ! $lng ) return false;

        // save to order meta
        $order->update_meta_data( "_wc_{$scope}/aaa-delivery-blocks/latitude",  $lat );
        $order->update_meta_data( "_wc_{$scope}/aaa-delivery-blocks/longitude", $lng );
        $order->update_meta_data( "_wc_{$scope}/aaa-delivery-blocks/coords-verified", 'yes' );

        // also save to user if available
        if ( $uid = $order->get_user_id() ) {
            update_user_meta( $uid, "_wc_{$scope}/aaa-delivery-blocks/latitude",  $lat );
            update_user_meta( $uid, "_wc_{$scope}/aaa-delivery-blocks/longitude", $lng );
            update_user_meta( $uid, "_wc_{$scope}/aaa-delivery-blocks/coords-verified", 'yes' );
        }

        self::log( "Backfilled {$scope}", [ 'order' => $order->get_id(), 'lat'=>$lat, 'lng'=>$lng ] );
        return true;
    }

    private static function geocode( string $address ) : array {
        $opts = get_option( 'delivery_global', [] );
        $api  = self::API_KEY ?: ( $opts['google_geocode_api_key'] ?? '' );
        if ( ! $api ) return [ '', '', 'No API key' ];

        $url = add_query_arg( [
            'address' => rawurlencode( $address ),
            'key'     => $api,
        ], 'https://maps.googleapis.com/maps/api/geocode/json' );

        $r = wp_remote_get( $url, [ 'timeout' => 8 ] );
        if ( is_wp_error( $r ) ) return [ '', '', $r->get_error_message() ];
        $j = json_decode( wp_remote_retrieve_body( $r ), true );
        if ( ! isset( $j['status'] ) || $j['status'] !== 'OK' ) {
            return [ '', '', $j['status'] ?? 'ERR' ];
        }
        $loc = $j['results'][0]['geometry']['location'] ?? [];
        return [ $loc['lat'] ?? '', $loc['lng'] ?? '', 'OK' ];
    }

    private static function log( $msg, $ctx=[] ) {
        if ( ! self::DEBUG_THIS_FILE || ! defined('WP_DEBUG') || ! WP_DEBUG ) return;
        $line = '[AAA-ADBC-Order-Backfill] '.$msg;
        if ( $ctx ) $line .= ' | '.wp_json_encode($ctx);
        error_log( $line );
    }

    /* ───────── Admin Page ───────── */
    public static function register_page() {
        add_submenu_page(
            'woocommerce',
            'Backfill Orders',
            'Backfill Orders',
            'manage_woocommerce',
            'aaa-adbc-backfill-orders',
            [ __CLASS__, 'render_page' ]
        );
    }

public static function render_page() {
    if ( isset($_POST['aaa_backfill_orders']) && ! empty($_POST['order_ids']) && check_admin_referer('aaa_adbc_backfill_page') ) {
        $scope = sanitize_text_field($_POST['scope'] ?? 'billing');
        $ids   = array_map('intval', (array)$_POST['order_ids']);
        $count = self::process_orders( $ids, $scope );
        echo '<div class="updated"><p>Backfilled '.esc_html($count).' order scopes.</p></div>';
    }

    // Query args
    $include_special = ! empty($_GET['include_special']);
    $orderby   = sanitize_key($_GET['orderby'] ?? 'date');
    $order     = ( strtolower($_GET['order'] ?? 'DESC') === 'asc' ) ? 'ASC' : 'DESC';
    $status    = sanitize_key($_GET['status'] ?? '');
    $missing   = sanitize_key($_GET['missing'] ?? ''); // billing, shipping, both
    $date_from = sanitize_text_field($_GET['date_from'] ?? '');
    $date_to   = sanitize_text_field($_GET['date_to'] ?? '');

    $query_args = [
        'limit'   => 100,
        'orderby' => $orderby,
        'order'   => $order,
        'return'  => 'ids',
    ];
    if ( $status ) {
        $query_args['status'] = $status;
    }
    if ( $date_from ) {
        $query_args['date_created'] = '>=' . $date_from;
    }
    if ( $date_to ) {
        $query_args['date_created'] = ($query_args['date_created'] ?? '') . '<=' . $date_to;
    }

    $orders = wc_get_orders($query_args);

    echo '<div class="wrap"><h1>Backfill Orders</h1>';

    // Filter form
    echo '<form method="get" style="margin-bottom:15px;">';
    echo '<input type="hidden" name="page" value="aaa-adbc-backfill-orders" />';
    echo '<label>Status: <select name="status"><option value="">All</option>';
    foreach ( wc_get_order_statuses() as $slug => $label ) {
        printf('<option value="%s" %s>%s</option>',
            esc_attr(str_replace('wc-','',$slug)),
            selected($status, str_replace('wc-','',$slug), false),
            esc_html($label)
        );
    }
    echo '</select></label> ';

    echo '<label>Missing: <select name="missing">';
    echo '<option value="">All</option>';
    echo '<option value="billing" '.selected($missing,'billing',false).'>Billing only</option>';
    echo '<option value="shipping" '.selected($missing,'shipping',false).'>Shipping only</option>';
    echo '<option value="both" '.selected($missing,'both',false).'>Both</option>';
    echo '</select></label> ';

    echo '<label>Date From: <input type="date" name="date_from" value="'.esc_attr($date_from).'"></label> ';
    echo '<label>Date To: <input type="date" name="date_to" value="'.esc_attr($date_to).'"></label> ';

    echo '<button class="button">Apply Filters</button> ';
    echo $include_special
        ? '<a class="button" href="'.esc_url(remove_query_arg('include_special')).'">Hide refunds & drafts</a>'
        : '<a class="button" href="'.esc_url(add_query_arg('include_special',1)).'">Include refunds & drafts</a>';
    echo '</form>';

    // Sortable headers helper
    $base_url = remove_query_arg(['orderby','order']);
    $col_link = function($col) use ($orderby,$order,$base_url) {
        $new_order = ($orderby === $col && $order === 'ASC') ? 'DESC' : 'ASC';
        $url = add_query_arg(['orderby'=>$col,'order'=>$new_order], $base_url);
        return esc_url($url);
    };

    echo '<form method="post">';
    wp_nonce_field('aaa_adbc_backfill_page');
    echo '<table class="widefat striped"><thead><tr>
            <th><input type="checkbox" id="checkall"></th>
            <th><a href="'.$col_link('id').'">Order</a></th>
            <th><a href="'.$col_link('status').'">Status</a></th>
            <th><a href="'.$col_link('billing_last_name').'">Customer</a></th>
            <th><a href="'.$col_link('date').'">Date</a></th>
            <th>Billing City</th><th>Billing Coords</th>
            <th>Shipping City</th><th>Shipping Coords</th>
          </tr></thead><tbody>';

    foreach ( $orders as $oid ) {
        $order = wc_get_order( $oid );
        if ( ! $order ) continue;

        // Skip refunds/drafts unless allowed
        $is_refund = ( $order->get_type() === 'shop_order_refund' );
        $is_draft  = ( $order->get_status() === 'draft' );
        if ( ( $is_refund || $is_draft ) && ! $include_special ) continue;

        $b_lat = $order->get_meta('_wc_billing/aaa-delivery-blocks/latitude');
        $b_lng = $order->get_meta('_wc_billing/aaa-delivery-blocks/longitude');
        $s_lat = $order->get_meta('_wc_shipping/aaa-delivery-blocks/latitude');
        $s_lng = $order->get_meta('_wc_shipping/aaa-delivery-blocks/longitude');

        // Filter: missing coords
        if ( $missing === 'billing' && ( $b_lat && $b_lng ) ) continue;
        if ( $missing === 'shipping' && ( $s_lat && $s_lng ) ) continue;
        if ( $missing === 'both' && ( $b_lat && $b_lng && $s_lat && $s_lng ) ) continue;

        $customer_name = trim( $order->get_billing_first_name().' '.$order->get_billing_last_name() );

        echo '<tr>';
        echo '<td><input type="checkbox" name="order_ids[]" value="'.esc_attr($oid).'"></td>';
        echo '<td><a href="'.esc_url(get_edit_post_link($oid)).'">#'.$oid.'</a></td>';
        echo '<td>'.esc_html( wc_get_order_status_name( $order->get_status() ) ).'</td>';
        echo '<td>'.esc_html($customer_name).'</td>';
        echo '<td>'.esc_html($order->get_date_created()->date('Y-m-d')).'</td>';
        echo '<td>'.esc_html($order->get_billing_city()).'</td>';
        echo '<td>'.($b_lat && $b_lng ? "✅" : "❌").'</td>';
        echo '<td>'.esc_html($order->get_shipping_city()).'</td>';
        echo '<td>'.($s_lat && $s_lng ? "✅" : "❌").'</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<p>
            <select name="scope">
              <option value="billing">Billing only</option>
              <option value="shipping">Shipping only</option>
              <option value="both">Billing + Shipping</option>
            </select>
            <button type="submit" name="aaa_backfill_orders" class="button button-primary">Backfill Selected</button>
          </p>';
    echo '</form>';

    echo '<script>
    (function(){
        var all = document.getElementById("checkall");
        if(!all) return;
        all.addEventListener("change", function(){
            document.querySelectorAll(\'input[name="order_ids[]"]\').forEach(function(cb){ cb.checked = all.checked; });
        });
    })();
    </script>';

    echo '</div>';
}
}

AAA_ADBC_Order_Backfill::init();

// Notices for bulk action
add_action( 'admin_notices', function(){
    if ( isset($_GET['aaa_adbc_backfill_done']) ) {
        $n = intval($_GET['aaa_adbc_backfill_done']);
        echo '<div class="updated"><p>'.esc_html($n).' order scopes backfilled with coords.</p></div>';
    }
});

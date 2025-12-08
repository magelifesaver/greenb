<?php
/**
 * Plugin Name:       AAA OC Scheduled Deliveries List (live)
 * Plugin URI:        https://example.com
 * Description:       Shortcode [scheduled_deliveries_list] listing all orders with status "scheduled". Shows delivery date/time and payment status.
 * Version:           1.0.6
 * Author:            Your Name
 * Text Domain:       aaa-oc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Scheduled_Deliveries {

    public static function init() {
        add_shortcode( 'scheduled_deliveries_list', [ __CLASS__, 'shortcode_list' ] );
    }

    public static function shortcode_list( $atts ) {
        return '<div class="aaa-oc-scheduled-shortcode">' . self::generate_list_html() . '</div>';
    }

    private static function generate_list_html() {
        // Use WP timezone for "today"
        $today = date_i18n( 'Y-m-d' );

        // Inline style for highlighting today's rows
        $style = '<style>
            .aaa-oc-scheduled-table tr.today-delivery { background-color: #e6ffe6; }
        </style>';

        // Fetch all WooCommerce orders with status "scheduled" (qualifier ONLY)
$orders = wc_get_orders( [
    'status' => [ 'scheduled', 'wc-scheduled' ], // cover both forms
    'type'   => 'shop_order',
    'limit'  => -1,
] );

        $rows = [];

        foreach ( $orders as $order ) {
            /** @var WC_Order $order */
            $id = $order->get_id();

            // Current metas in use
            $date = get_post_meta( $id, 'delivery_date_formatted', true ); // e.g. "2025-08-11"
            $time = get_post_meta( $id, 'delivery_time', true );          // e.g. "4:00 pm"

            // Optional fallback to "delivery_time_range" -> "4:00 pm - 5:00 pm"
            if ( empty( $time ) ) {
                $range = get_post_meta( $id, 'delivery_time_range', true );
                if ( is_string( $range ) && preg_match( '/from\s+(.+?)\s+to\s+(.+)/i', $range, $m ) ) {
                    $time = trim( $m[1] ) . ' - ' . trim( $m[2] );
                }
            }

            // Payment status from your meta
            $payment_status = get_post_meta( $id, 'aaa_oc_payment_status', false );

            $rows[] = [
                'date'           => is_string( $date ) ? $date : '',
                'time'           => is_string( $time ) ? $time : '',
                'order_id'       => $id,
                'customer'       => $order->get_formatted_billing_full_name(),
                'payment_status' => sanitize_text_field( $payment_status ),
                'link'           => admin_url( 'post.php?post=' . $id . '&action=edit' ),
            ];
        }

        // Sort by date then time; empty values go last
        usort( $rows, function( $a, $b ) {
            $ad = $a['date'] ?: '9999-12-31';
            $bd = $b['date'] ?: '9999-12-31';
            if ( $ad === $bd ) {
                // normalize time to HH:MM for sorting (use start time if a range)
                $norm = function( $t ) {
                    if ( ! $t ) return '99:99';
                    // If range like "4:00 pm - 5:00 pm", take first time
                    if ( preg_match( '/^\s*(\d{1,2}:\d{2}\s*[ap]m)/i', $t, $m ) ) {
                        return date( 'H:i', strtotime( $m[1] ) );
                    }
                    return date( 'H:i', strtotime( $t ) );
                };
                return strcmp( $norm($a['time']), $norm($b['time']) );
            }
            return strcmp( $ad, $bd );
        } );

        if ( empty( $rows ) ) {
            return $style . '<p>No orders found with status “scheduled”.</p>';
        }

        ob_start();
        echo $style;
        ?>
        <table class="widefat aaa-oc-scheduled-table" cellspacing="0">
            <thead>
                <tr>
                    <th>Delivery Date</th>
                    <th>Delivery Time</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $rows as $r ) :
                $is_today = ( ! empty( $r['date'] ) && $r['date'] === $today );
                $date_out = $r['date'] ? date_i18n( 'M j, Y', strtotime( $r['date'] ) ) : '—';
                $time_out = $r['time'] ?: '—';
            ?>
                <tr class="<?php echo $is_today ? 'today-delivery' : ''; ?>">
                    <td><?php echo esc_html( $date_out ); ?></td>
                    <td><?php echo esc_html( $time_out ); ?></td>
                    <td><a href="<?php echo esc_url( $r['link'] ); ?>">#<?php echo esc_html( $r['order_id'] ); ?></a></td>
                    <td><?php echo esc_html( $r['customer'] ); ?></td>
                    <td><?php echo esc_html( $r['payment_status'] ? ucfirst( $r['payment_status'] ) : '—' ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
}

AAA_OC_Scheduled_Deliveries::init();

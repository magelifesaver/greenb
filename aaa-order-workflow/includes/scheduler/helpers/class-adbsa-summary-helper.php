<?php
/**
 * File: helpers/class-adbsa-summary-helper.php
 * Purpose: Render a unified delivery summary (Thank You, My Account, Emails).
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'ADBSA_Summary_Helper' ) ) :

final class ADBSA_Summary_Helper {

    /**
     * Build HTML summary block from an order.
     *
     * Reads:
     *  - delivery_date_locale | delivery_date_formatted | delivery_date (ts)
     *  - delivery_time_range  | _wc_other/adbsa/delivery-time
     */
    public static function render_from_order( WC_Order $order ) : string {
        $tz   = wp_timezone();
        $date = (string) $order->get_meta( 'delivery_date_locale' );

        if ( $date === '' ) {
            $date = (string) $order->get_meta( 'delivery_date_formatted' );
        }
        if ( $date === '' ) {
            $ts = (int) $order->get_meta( 'delivery_date' );
            if ( $ts ) $date = wp_date( 'l, F j, Y', $ts, $tz );
        }

        $range = (string) $order->get_meta( 'delivery_time_range' );
        if ( $range === '' ) {
            $range = (string) $order->get_meta( '_wc_other/adbsa/delivery-time' );
        }

        if ( $date === '' && $range === '' ) return '';

        ob_start();
        echo '<div class="adbsa-summary" style="margin:15px 0;padding:12px;border:1px solid #eee;">';
        echo '<strong>Delivery</strong><br>';
        if ( $date )  echo '<div>Date: ' . esc_html( $date )  . '</div>';
        if ( $range ) echo '<div>Time: ' . esc_html( $range ) . '</div>';
        echo '</div>';
        return ob_get_clean();
    }
}

endif;

<?php
/**
 * File: helpers/class-adbsa-delivery-field-renderer.php
 * Purpose: Shared date + time pickers for Admin Metabox and Workflow Board.
 *          Ensures <input type="time"> values are "HH:mm".
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'ADBSA_Delivery_Field_Renderer' ) ) :

final class ADBSA_Delivery_Field_Renderer {

    /** Convert loose time into 24h HH:mm (returns '' if not parseable) */
    private static function to_h24( string $s, DateTimeZone $tz ) : string {
        $s = trim( strtolower( $s ) );
        if ( $s === '' || strpos( $s, 'closing' ) !== false ) return '';
        $dt = DateTime::createFromFormat( 'g:i a', $s, $tz );
        if ( ! $dt ) $dt = DateTime::createFromFormat( 'H:i', $s, $tz );
        return $dt ? $dt->format( 'H:i' ) : '';
    }

    /**
     * Accepts:
     *   "From 1:10 pm - To 2:15 pm"
     *   "From 1:10 pm to 2:15 pm"
     *   "1:10 pm|From 1:10 pm to 2:15 pm"  (legacy; left part ignored)
     * Returns ["HH:MM","HH:MM"] or ["",""] if not parseable.
     */
    private static function parse_range_to_h24( string $range, DateTimeZone $tz ) : array {
        $src = trim( $range );
        if ( $src === '' ) return ['', ''];

        if ( strpos( $src, '|' ) !== false ) {
            $parts = explode( '|', $src, 2 );
            $src   = trim( $parts[1] ?? $parts[0] );
        }

        $from = $to = '';
        // tolerate: From X - To Y   OR   From X to Y   (dash optional)
        if ( preg_match( '/from\s+(.+?)\s*(?:-|–|—)?\s*to\s+(.+)/i', $src, $m ) ) {
            $from = self::to_h24( $m[1], $tz );
            $to   = self::to_h24( $m[2], $tz );
        }
        return [ $from, $to ];
    }

    /**
     * Render controls. $prefix controls field names:
     *   "{$prefix}_date", "{$prefix}_from", "{$prefix}_to"
     */
    public static function render_fields( WC_Order $order, string $prefix = 'adbsa' ) {
        $tz = wp_timezone();

        $dateYmd = (string) $order->get_meta( 'delivery_date_formatted' );
        if ( $dateYmd === '' ) {
            $dateYmd = (string) $order->get_meta( '_wc_other/adbsa/delivery-date' );
        }

        $timeRange = (string) $order->get_meta( 'delivery_time_range' );
        if ( $timeRange === '' ) {
            $timeRange = (string) $order->get_meta( '_wc_other/adbsa/delivery-time' );
        }

        list( $fromH24, $toH24 ) = self::parse_range_to_h24( $timeRange, $tz );

        echo '<div class="adbsa-delivery-fields" style="display:grid; gap:8px;">';

        echo '<label>Delivery Date</label>';
        echo '<input type="date" name="' . esc_attr( $prefix ) . '_date" value="' . esc_attr( $dateYmd ) . '" />';

        echo '<label>From</label>';
        echo '<input type="time" name="' . esc_attr( $prefix ) . '_from" value="' . esc_attr( $fromH24 ) . '" />';

        echo '<label>To</label>';
        echo '<input type="time" name="' . esc_attr( $prefix ) . '_to" value="' . esc_attr( $toH24 ) . '" />';

        echo '</div>';
    }
}

endif;

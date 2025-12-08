<?php
/**
 * File: helpers/class-adbsa-delivery-normalizer.php
 * Purpose: Normalize Delivery Date/Time for all entry points (checkout, admin, board).
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'ADBSA_Delivery_Normalizer' ) ) :

final class ADBSA_Delivery_Normalizer {

    /**
     * @param string $dateYmd  Y-m-d
     * @param string $fromVal  HH:mm or g:i a
     * @param string $toVal    HH:mm or g:i a
     * @return array           metas to write
     */
    public static function normalize( string $dateYmd, string $fromVal, string $toVal ) : array {
        $tz  = wp_timezone();
        $out = [];

        // ---- DATE ----
        $ts = $dateYmd ? strtotime( $dateYmd . ' 00:00:00 ' . wp_timezone_string() ) : 0;
        if ( $ts ) {
            $out['delivery_date']                  = $ts;
            $out['delivery_date_formatted']        = gmdate( 'Y-m-d', $ts );
            $out['delivery_date_locale']           = wp_date( 'l, F j, Y', $ts, $tz );
            $out['_wc_other/adbsa/delivery-date']  = gmdate( 'Y-m-d', $ts );
        }

        // ---- TIME ----
        if ( $fromVal && $toVal ) {
            $fTs   = strtotime( $fromVal );
            $tTs   = strtotime( $toVal );
            $from12 = $fTs ? wp_date( 'g:i a', $fTs, $tz ) : $fromVal;
            $to12   = $tTs ? wp_date( 'g:i a', $tTs, $tz ) : $toVal;

            // Canonical string you requested:
            $range  = sprintf( 'From %s - To %s', $from12, $to12 );

            $out['delivery_time']                  = $from12;
            $out['delivery_time_range']            = $range;
            $out['_wc_other/adbsa/delivery-time']  = $range; // no pipes anywhere
        }

        return $out;
    }
}

endif;

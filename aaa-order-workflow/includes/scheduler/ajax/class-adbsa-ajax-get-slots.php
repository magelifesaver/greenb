<?php
/**
 * File: includes/scheduler/ajax/class-adbsa-ajax-get-slots.php
 * Purpose: Return current slot options (Same-Day or Scheduled) for JS refresh.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_nopriv_adbsa_get_slots', 'adbsa_get_slots' );
add_action( 'wp_ajax_adbsa_get_slots',        'adbsa_get_slots' );

function adbsa_get_slots() {
    $mode = sanitize_key( $_REQUEST['mode'] ?? '' );

    if ( $mode === 'sameday' && class_exists( 'ADBSA_Delivery_SameDay' ) ) {
        $result = [
            'mode'   => 'sameday',
            'dates'  => [], // Same-Day = today only
            'times'  => ADBSA_Delivery_SameDay::build_slots(),
        ];
    } elseif ( $mode === 'scheduled' && class_exists( 'ADBSA_Delivery_Scheduled' ) ) {
        $result = [
            'mode'   => 'scheduled',
            'dates'  => ADBSA_Delivery_Scheduled::build_date_options(),
            'times'  => ADBSA_Delivery_Scheduled::build_time_options(),
        ];
    } else {
        $result = [ 'mode' => 'none', 'dates' => [], 'times' => [] ];
    }

    wp_send_json_success( $result );
}

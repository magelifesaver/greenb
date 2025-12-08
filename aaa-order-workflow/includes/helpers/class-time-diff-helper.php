<?php
/**
 * File Path: aaa-order-workflow/includes/helpers/class-time-diff-helper.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AAA_OC_TimeDiff_Helper {

    public static function my_granular_time_diff( $from_timestamp, $to_timestamp = 0 ) {
        if ( 0 === $to_timestamp ) {
            $to_timestamp = current_time('timestamp');
        }
        $diff_in_seconds = abs( $to_timestamp - $from_timestamp );
        if ( $diff_in_seconds < 60 ) {
            return 'just now';
        }
        $total_minutes = floor( $diff_in_seconds / 60 );
        $hours         = floor( $total_minutes / 60 );
        $minutes       = $total_minutes % 60;

        if ( $hours > 0 && $minutes > 0 ) {
            return "{$hours} hour" . ( $hours > 1 ? 's' : '' ) . " {$minutes} min ago";
        } elseif ( $hours > 0 ) {
            return "{$hours} hour" . ( $hours > 1 ? 's' : '' ) . " ago";
        } else {
            return "{$minutes} min ago";
        }
    }
}

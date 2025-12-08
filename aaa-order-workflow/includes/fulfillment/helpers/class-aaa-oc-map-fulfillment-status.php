<?php
/**
 * File Path: /aaa-order-workflow/includes/helpers/class-aaa-oc-map-fulfillment-status.php
 *
 * Purpose:
 * Central mapping of fulfillment statuses into pill label + color.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Map_Fulfillment_Status {

    /**
     * Map fulfillment status into label + background color.
     * @return array { label: string, bg: string }
     */
    public static function get( $raw ) {
        $status = strtolower( trim( (string) $raw ) );

        $map = [
            'not_picked'   => [ 'label' => 'NOT PICKED', 'bg' => '#d9534f' ], // red
            'fully_picked' => [ 'label' => 'PACKED',     'bg' => '#28a745' ], // green
        ];

        return $map[ $status ] ?? [
            'label' => strtoupper( $status ?: 'UNKNOWN' ),
            'bg'    => '#999',
        ];
    }
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Delivery_Index {

    public static function update_delivery_data( $order_or_id ) {
        global $wpdb;
        $order = is_a( $order_or_id, 'WC_Order' ) ? $order_or_id : wc_get_order( $order_or_id );
        if ( ! $order ) {
            return;
        }
        $order_id = $order->get_id();

        // For the existing columns: daily_order_number, shipping_method, driver_id,
        // plus the date/time meta fields you had in the big table
        $daily_order_number = (int) get_post_meta( $order_id, '_daily_order_number', true );
        $driver_meta        = get_post_meta( $order_id, 'lddfw_driverid', true );

        $shipping_method = implode( ', ', array_map( function( $sm ) {
            return $sm->get_method_title();
        }, $order->get_shipping_methods() ) );

        $delivery_time       = get_post_meta( $order_id, 'delivery_time', true );
        $delivery_time_range = get_post_meta( $order_id, 'delivery_time_range', true );
        if ( is_array($delivery_time_range) ) {
            $delivery_time_range = implode( ', ', $delivery_time_range );
        }
        $delivery_date_formatted = get_post_meta( $order_id, 'delivery_date_formatted', true );

        $lddfw_delivery_date = get_post_meta( $order_id, '_lddfw_delivery_date', true );
        $lddfw_delivery_time = get_post_meta( $order_id, '_lddfw_delivery_time', true );
        $lddfw_driverid      = get_post_meta( $order_id, 'lddfw_driverid', true );

        $data = [
            'daily_order_number'    => $daily_order_number,
            'shipping_method'       => $shipping_method,
            'driver_id'             => $driver_meta ? (int) $driver_meta : null,
            'delivery_time'         => $delivery_time,
            'delivery_time_range'   => $delivery_time_range,
            'delivery_date_formatted' => $delivery_date_formatted,
            'lddfw_delivery_date'   => $lddfw_delivery_date,
            'lddfw_delivery_time'   => $lddfw_delivery_time,
            'lddfw_driverid'        => is_numeric($lddfw_driverid) ? (int) $lddfw_driverid : null,
        ];

        $table_name = $wpdb->prefix . 'aaa_oc_order_index';
        $wpdb->update(
            $table_name,
            $data,
            [ 'order_id' => $order_id ],
            null,
            [ '%d' ]
        );
    }
}

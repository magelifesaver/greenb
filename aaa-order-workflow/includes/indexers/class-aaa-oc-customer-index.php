<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Customer_Index
 * 
 * Updates the customer-related columns in the single 'aaa_oc_order_index' table.
 */
class AAA_OC_Customer_Index {

    /**
     * Updates the customer data in the aaa_oc_order_index table for a specific order.
     *
     * @param WC_Order|int $order_or_id The order object or ID
     */
    public static function update_customer_data( $order_or_id ) {
        global $wpdb;

        // Ensure we have a valid WC_Order object
        $order = is_a( $order_or_id, 'WC_Order' ) ? $order_or_id : wc_get_order( $order_or_id );
        if ( ! $order ) {
            return;
        }
        $order_id = $order->get_id();

        // Gather all your user/customer data. (Sample from your main index code.)
        $customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();
        $customer_note  = $order->get_customer_note();

        // Example: pulling from user meta for these
        $user_id = $order->get_user_id();

        $lkd_upload_med    = $user_id ? get_user_meta( $user_id, 'afreg_additional_4630', true ) : '';
        $lkd_upload_selfie = $user_id ? get_user_meta( $user_id, 'afreg_additional_4627', true ) : '';
        $lkd_upload_id     = $user_id ? get_user_meta( $user_id, 'afreg_additional_4626', true ) : '';
        // ...and so on...

        // Possibly prepend upload URL if needed:
        $lkd_upload_med    = self::maybe_prepend_upload_url( $lkd_upload_med );
        $lkd_upload_selfie = self::maybe_prepend_upload_url( $lkd_upload_selfie );
        $lkd_upload_id     = self::maybe_prepend_upload_url( $lkd_upload_id );

        // Also gather aggregated warnings, special needs, etc. (if you want that here)
        // ...
        
        // Example data array (only the columns relevant to "customer" info):
        $data = [
            'customer_name'  => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'customer_note'  => $customer_note,

            'lkd_upload_med'    => $lkd_upload_med,
            'lkd_upload_selfie' => $lkd_upload_selfie,
            'lkd_upload_id'     => $lkd_upload_id,
            // etc. fill in the rest as needed
        ];

        // Finally update the single big table
        $table_name = $wpdb->prefix . 'aaa_oc_order_index';
        $wpdb->update(
            $table_name,
            $data,
            [ 'order_id' => $order_id ],
            null,
            [ '%d' ] // 'order_id' is integer in the WHERE clause
        );
    }

    /**
     * Example helper from your code
     */
    private static function maybe_prepend_upload_url( $filename ) {
        if ( empty( $filename ) ) {
            return '';
        }
        if ( preg_match('#^https?://#', $filename ) ) {
            return $filename;
        }
        $basePath = site_url('/wp-content/uploads/sites/9/addify_registration_uploads/');
        $filename = ltrim( $filename, '/' );
        return $basePath . $filename;
    }
}

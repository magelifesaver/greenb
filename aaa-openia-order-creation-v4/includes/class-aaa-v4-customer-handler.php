<?php
// File: /aaa-openia-order-creation-v4/includes/class-aaa-v4-customer-handler.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_V4_Customer_Handler {

    /**
     * Normalize phone number: keep only 10 digits.
     */
    public static function normalize_phone_number( $phone ) {
        $phone = preg_replace( '/\D/', '', $phone );
        return substr( $phone, -10 );
    }

    /**
     * Try to find existing customer by email and/or phone.
     * @return array|false [
     *     'user_id' => int,
     *     'matched_by' => 'email' or 'phone',
     *     'first_name' => string,
     *     'last_name' => string,
     *     'phone' => string
     * ]
     */
    public static function find_existing_customer( $email, $phone = '' ) {
        if ( $email ) {
            $customer = get_user_by( 'email', $email );
            if ( $customer ) {
                AAA_V4_Logger::log( "Found customer by email: {$email} (User ID {$customer->ID})" );
                return [
                    'user_id'    => $customer->ID,
                    'matched_by' => 'email',
                    'first_name' => get_user_meta( $customer->ID, 'first_name', true ),
                    'last_name'  => get_user_meta( $customer->ID, 'last_name', true ),
                    'phone'      => get_user_meta( $customer->ID, 'billing_phone', true ),
                ];
            }
        }

        if ( $phone ) {
            $phone = self::normalize_phone_number( $phone );
            $users = get_users( [
                'meta_key'   => 'billing_phone',
                'meta_value' => $phone,
                'number'     => 1,
                'fields'     => ['ID']
            ] );
            if ( ! empty( $users ) ) {
                $customer = get_user_by( 'ID', $users[0]->ID );
                AAA_V4_Logger::log( "Found customer by phone: {$phone} (User ID {$customer->ID})" );
                return [
                    'user_id'    => $customer->ID,
                    'matched_by' => 'phone',
                    'first_name' => get_user_meta( $customer->ID, 'first_name', true ),
                    'last_name'  => get_user_meta( $customer->ID, 'last_name', true ),
                    'phone'      => get_user_meta( $customer->ID, 'billing_phone', true ),
                ];
            }
        }

        return false;
    }

    /**
     * Create a new WooCommerce customer account.
     */
    public static function create_customer( $email, $full_name = '', $phone = '', $address = '' ) {
        $password    = wp_generate_password();
        $customer_id = wp_create_user( $email, $password, $email );

        if ( is_wp_error( $customer_id ) ) {
            return false;
        }

        $first_name = '';
        $last_name  = '';
        if ( $full_name ) {
            $name_parts = explode( ' ', trim( $full_name ), 2 );
            $first_name = $name_parts[0];
            $last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';
        }

        wp_update_user( [
            'ID'         => $customer_id,
            'role'       => 'customer',
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ]);

        $phone = self::normalize_phone_number( $phone );

        update_user_meta( $customer_id, 'billing_phone', $phone );
        update_user_meta( $customer_id, 'billing_address_1', $address );
        update_user_meta( $customer_id, 'shipping_address_1', $address );

        AAA_V4_Logger::log( "Created new customer: {$email} (User ID {$customer_id})" );

        return $customer_id;
    }
}
?>

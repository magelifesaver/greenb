<?php
// File: /aaa-openia-order-creation-v4/includes/ajax-relookup-customer.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper: build full payload (billing + shipping + coords + afreg fields)
 */
if ( ! function_exists( 'aaa_v4_build_user_payload' ) ) {
    function aaa_v4_build_user_payload( int $uid, string $matched_by = '', bool $email_mismatch = false, string $message = '' ) : array {
        $u = get_user_by( 'id', $uid );

        return [
            'user_id'        => $uid,
            'first_name'     => get_user_meta( $uid, 'first_name', true ),
            'last_name'      => get_user_meta( $uid, 'last_name', true ),
            'email'          => $u ? $u->user_email : '',
            'phone'          => get_user_meta( $uid, 'billing_phone', true ),
            'matched_by'     => $matched_by,
            'profile_url'    => admin_url( 'user-edit.php?user_id=' . $uid ),
            'email_mismatch' => $email_mismatch,
            'message'        => $message,

            // Billing
            'billing_address_1' => get_user_meta( $uid, 'billing_address_1', true ),
            'billing_address_2' => get_user_meta( $uid, 'billing_address_2', true ),
            'billing_city'      => get_user_meta( $uid, 'billing_city', true ),
            'billing_state'     => get_user_meta( $uid, 'billing_state', true ),
            'billing_postcode'  => get_user_meta( $uid, 'billing_postcode', true ),
            'billing_country'   => get_user_meta( $uid, 'billing_country', true ),

            // Shipping
            'shipping_address_1' => get_user_meta( $uid, 'shipping_address_1', true ),
            'shipping_address_2' => get_user_meta( $uid, 'shipping_address_2', true ),
            'shipping_city'      => get_user_meta( $uid, 'shipping_city', true ),
            'shipping_state'     => get_user_meta( $uid, 'shipping_state', true ),
            'shipping_postcode'  => get_user_meta( $uid, 'shipping_postcode', true ),
            'shipping_country'   => get_user_meta( $uid, 'shipping_country', true ),

            // Coords (Blocks additional fields)
            'billing_lat'       => get_user_meta( $uid, '_wc_billing/aaa-delivery-blocks/latitude',        true ),
            'billing_lng'       => get_user_meta( $uid, '_wc_billing/aaa-delivery-blocks/longitude',       true ),
            'billing_verified'  => get_user_meta( $uid, '_wc_billing/aaa-delivery-blocks/coords-verified', true ) ?: 'no',

            'shipping_lat'      => get_user_meta( $uid, '_wc_shipping/aaa-delivery-blocks/latitude',        true ),
            'shipping_lng'      => get_user_meta( $uid, '_wc_shipping/aaa-delivery-blocks/longitude',       true ),
            'shipping_verified' => get_user_meta( $uid, '_wc_shipping/aaa-delivery-blocks/coords-verified', true ) ?: 'no',

            // afreg
            'afreg_additional_4532' => get_user_meta( $uid, 'afreg_additional_4532', true ),
            'afreg_additional_4623' => get_user_meta( $uid, 'afreg_additional_4623', true ),
            'afreg_additional_4625' => get_user_meta( $uid, 'afreg_additional_4625', true ),
            'afreg_additional_4626' => get_user_meta( $uid, 'afreg_additional_4626', true ),
            'afreg_additional_4627' => get_user_meta( $uid, 'afreg_additional_4627', true ),
            'afreg_additional_4630' => get_user_meta( $uid, 'afreg_additional_4630', true ),
        ];
    }
}

add_action( 'wp_ajax_aaa_v4_relookup_customer', function() {

    // 1) Direct selection by user_id (from pick list)
    if ( isset( $_POST['user_id'] ) && is_numeric( $_POST['user_id'] ) ) {
        $user_id  = intval( $_POST['user_id'] );
        $userdata = get_userdata( $user_id );
        if ( ! $userdata ) {
            wp_send_json_error( [ 'message' => 'User not found.' ] );
        }
        wp_send_json_success( aaa_v4_build_user_payload( $user_id, 'picker', false, '' ) );
    }

    // 2) Gather email/phone
    $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
    $phone = isset( $_POST['phone'] ) ? preg_replace( '/\D+/', '', $_POST['phone'] ) : '';

    if ( empty( $email ) && empty( $phone ) ) {
        wp_send_json_error( [ 'message' => 'Please provide an email or phone to lookup.' ] );
    }

    // 3) Find by email
    $user_by_email = false;
    if ( ! empty( $email ) ) {
        $found = get_user_by( 'email', $email );
        if ( $found ) {
            $user_by_email = [
                'user_id'    => $found->ID,
                'matched_by' => 'email',
            ];
        }
    }

    // 4) All users whose billing_phone == $phone
    $phone_matches = [];
    if ( ! empty( $phone ) ) {
        $users = get_users( [
            'meta_key'   => 'billing_phone',
            'meta_value' => $phone,
            'fields'     => 'ID',
        ] );
        $phone_matches = array_values( array_unique( array_map( 'intval', $users ) ) );
    }

    // 5) We have an email user
    if ( $user_by_email ) {
        $email_id = $user_by_email['user_id'];

        // 5A) Multiple phone matches → return a pick-list (unchanged logic)
        if ( count( $phone_matches ) > 1 ) {
            $matches_list = [];
            foreach ( $phone_matches as $uid ) {
                $u = get_user_by( 'id', $uid );
                $matches_list[] = [
                    'user_id'           => $uid,
                    'first_name'        => get_user_meta( $uid, 'first_name', true ),
                    'last_name'         => get_user_meta( $uid, 'last_name', true ),
                    'email'             => $u ? $u->user_email : '',
                    'phone'             => get_user_meta( $uid, 'billing_phone', true ),
                    'billing_address_1' => get_user_meta( $uid, 'billing_address_1', true ),
                    'billing_city'      => get_user_meta( $uid, 'billing_city', true ),
                    'billing_state'     => get_user_meta( $uid, 'billing_state', true ),
                    'billing_postcode'  => get_user_meta( $uid, 'billing_postcode', true ),
                    'profile_url'       => admin_url( 'user-edit.php?user_id=' . $uid ),
                    'afreg_additional_4532' => get_user_meta( $uid, 'afreg_additional_4532', true ),
                    'afreg_additional_4623' => get_user_meta( $uid, 'afreg_additional_4623', true ),
                    'afreg_additional_4625' => get_user_meta( $uid, 'afreg_additional_4625', true ),
                    'afreg_additional_4626' => get_user_meta( $uid, 'afreg_additional_4626', true ),
                    'afreg_additional_4627' => get_user_meta( $uid, 'afreg_additional_4627', true ),
                    'afreg_additional_4630' => get_user_meta( $uid, 'afreg_additional_4630', true ),
                ];
            }

            wp_send_json_success( [
                'code'    => 'multiple_phone_matches',
                'message' => 'Multiple accounts share this phone. Please pick the correct one:',
                'matches' => $matches_list,
            ] );
        }

        // 5B) One phone match and it's a different user → return that phone user with email_mismatch = true
        if ( count( $phone_matches ) === 1 && $phone_matches[0] !== $email_id ) {
            $uid = $phone_matches[0];
            wp_send_json_success( aaa_v4_build_user_payload(
                $uid,
                'phone',
                true,
                'Phone belongs to a different user – overwrite fields with that account?'
            ) );
        }

        // 5C) Otherwise return the email user
        wp_send_json_success( aaa_v4_build_user_payload( $email_id, 'email', false, '' ) );
    }

    // 6) No email user, but phone matches exist
    if ( empty( $user_by_email ) && ! empty( $phone_matches ) ) {
        // 6A) Multiple phone matches → pick list (unchanged)
        if ( count( $phone_matches ) > 1 ) {
            $matches_list = [];
            foreach ( $phone_matches as $uid ) {
                $u = get_user_by( 'id', $uid );
                $matches_list[] = [
                    'user_id'           => $uid,
                    'first_name'        => get_user_meta( $uid, 'first_name', true ),
                    'last_name'         => get_user_meta( $uid, 'last_name', true ),
                    'email'             => $u ? $u->user_email : '',
                    'phone'             => get_user_meta( $uid, 'billing_phone', true ),
                    'billing_address_1' => get_user_meta( $uid, 'billing_address_1', true ),
                    'billing_city'      => get_user_meta( $uid, 'billing_city', true ),
                    'billing_state'     => get_user_meta( $uid, 'billing_state', true ),
                    'billing_postcode'  => get_user_meta( $uid, 'billing_postcode', true ),
                    'profile_url'       => admin_url( 'user-edit.php?user_id=' . $uid ),
                    'afreg_additional_4532' => get_user_meta( $uid, 'afreg_additional_4532', true ),
                    'afreg_additional_4623' => get_user_meta( $uid, 'afreg_additional_4623', true ),
                    'afreg_additional_4625' => get_user_meta( $uid, 'afreg_additional_4625', true ),
                    'afreg_additional_4626' => get_user_meta( $uid, 'afreg_additional_4626', true ),
                    'afreg_additional_4627' => get_user_meta( $uid, 'afreg_additional_4627', true ),
                    'afreg_additional_4630' => get_user_meta( $uid, 'afreg_additional_4630', true ),
                ];
            }
            wp_send_json_success( [
                'code'    => 'multiple_phone_matches',
                'message' => 'Multiple accounts share this phone. Please pick the correct one:',
                'matches' => $matches_list,
            ] );
        }

        // 6B) Exactly one phone match → return that user
        if ( count( $phone_matches ) === 1 ) {
            $uid = $phone_matches[0];
            wp_send_json_success( aaa_v4_build_user_payload( $uid, 'phone', false, '' ) );
        }
    }

    // 7) No matches at all
    wp_send_json_error( [ 'message' => 'No matching customer found.' ] );
} );

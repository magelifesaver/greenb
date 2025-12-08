<?php
// File: /includes/ajax-use-address.php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_aaa_v4_get_shipping_address', function () {
    $email = sanitize_email($_POST['email'] ?? '');

    if (empty($email)) {
        wp_send_json_error(['message' => 'Missing email address.']);
    }

    $user = get_user_by('email', $email);
    if (! $user) {
        wp_send_json_error(['message' => 'No customer found for that email.']);
    }

    $fields = [
        'shipping_address_1', 'shipping_address_2', 'shipping_city',
        'shipping_state', 'shipping_postcode', 'shipping_country'
    ];

    $data = [];
    foreach ($fields as $field) {
        $data[$field] = get_user_meta($user->ID, $field, true);
    }

    wp_send_json_success($data);
});
add_action('wp_ajax_aaa_v4_lookup_by_phone', function () {
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    if (empty($phone)) {
        wp_send_json_error(['message' => 'Phone number required']);
    }

    $normalized = preg_replace('/\D/', '', $phone);
    $users = get_users([
        'meta_key'   => 'billing_phone',
        'meta_value' => $normalized,
        'number'     => 1
    ]);

    if (! $users || empty($users[0])) {
        wp_send_json_error(['message' => 'No match found for phone.']);
    }

    $user = $users[0];
    wp_send_json_success([
        'first_name' => get_user_meta($user->ID, 'first_name', true),
        'last_name'  => get_user_meta($user->ID, 'last_name', true),
        'email'      => $user->user_email,
        'user_id'    => $user->ID
    ]);
});

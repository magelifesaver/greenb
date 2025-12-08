<?php
// File: /includes/ajax-upload-handler.php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_aaa_v4_upload_image', 'aaa_v4_upload_image_handler');

function aaa_v4_upload_image_handler() {
    if ( ! current_user_can('manage_woocommerce') ) {
        error_log('[AAA Upload] Unauthorized attempt');
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    if ( empty($_FILES['file']) || empty($_POST['upload_field']) || empty($_POST['user_id']) ) {
        error_log('[AAA Upload] Missing input. FILES=' . print_r($_FILES, true) . ' POST=' . print_r($_POST, true));
        wp_send_json_error(['message' => 'Missing file, field, or user ID']);
    }

    $user_id = (int) $_POST['user_id'];
    $field   = sanitize_text_field($_POST['upload_field']);
    error_log("[AAA Upload] User $user_id uploading for field $field");

    require_once ABSPATH . 'wp-admin/includes/file.php';
    $upload = wp_handle_upload($_FILES['file'], ['test_form' => false]);

    error_log('[AAA Upload] wp_handle_upload result: ' . print_r($upload, true));

    if ( ! empty($upload['url']) && ! empty($upload['file']) ) {
        $filename = basename($upload['file']);
        update_user_meta($user_id, $field, $filename);
        error_log("[AAA Upload] Success. Saved $filename for user $user_id in $field");
        wp_send_json_success(['url' => $upload['url']]);
    } else {
        error_log('[AAA Upload] Upload failed. Upload array: ' . print_r($upload, true));
        wp_send_json_error(['message' => 'Upload failed']);
    }
}

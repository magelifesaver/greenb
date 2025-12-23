<?php
/**
 * File: wp-content/plugins/uuu-disable-user-email/uuu-disable-user-email.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Per-file debug toggle (default true for development).
if ( ! defined( 'UUU_DUE_DEBUG_THIS_FILE' ) ) {
    define( 'UUU_DUE_DEBUG_THIS_FILE', true );
}

define( 'UUU_DUE_META_KEY', 'uuu_disable_emails' );

/**
 * Plugin Name:       UUU Disable User Email
 * Description:       Suppresses outgoing WP emails to users that have user meta `uuu_disable_emails` enabled. Multisite-safe; network activate.
 * Network:           true
 * Version:           1.0.0
 * Author:            Webmaster Workflow
 * Text Domain:       uuu-disable-user-email
 */

register_activation_hook( __FILE__, 'uuu_due_on_activate' );
add_filter( 'wp_mail', 'uuu_due_filter_wp_mail', 10, 1 );
add_filter( 'pre_wp_mail', 'uuu_due_pre_wp_mail_cancel_if_flagged', 10, 2 );

add_action( 'network_admin_menu', 'uuu_due_register_network_settings_page' );
add_filter( 'network_admin_plugin_action_links_' . plugin_basename( __FILE__ ), 'uuu_due_add_settings_link' );

function uuu_due_on_activate() {
    if ( UUU_DUE_DEBUG_THIS_FILE ) {
        error_log( '[UUU Disable User Email] Activated. Meta key used: ' . UUU_DUE_META_KEY );
    }
}

/**
 * Remove recipients that belong to a user with uuu_disable_emails enabled.
 * IMPORTANT: must always return an array (never false) to avoid WP core notices/errors.
 */
function uuu_due_filter_wp_mail( $mail_args ) {
    if ( ! is_array( $mail_args ) || empty( $mail_args['to'] ) ) {
        return $mail_args;
    }

    $to_list   = is_array( $mail_args['to'] ) ? $mail_args['to'] : explode( ',', (string) $mail_args['to'] );
    $remaining = array();

    foreach ( $to_list as $recipient ) {
        $recipient = trim( (string) $recipient );
        if ( '' === $recipient ) {
            continue;
        }

        $user = get_user_by( 'email', $recipient );
        if ( $user ) {
            $flag = get_user_meta( (int) $user->ID, UUU_DUE_META_KEY, true );
            if ( ! empty( $flag ) ) {
                if ( UUU_DUE_DEBUG_THIS_FILE ) {
                    error_log( '[UUU Disable User Email] Suppressed recipient: ' . $recipient . ' (user_id=' . $user->ID . ')' );
                }
                continue;
            }
        }

        $remaining[] = $recipient;
    }

    // If empty after filtering, mark for cancellation in pre_wp_mail (safe short-circuit).
    if ( empty( $remaining ) ) {
        $mail_args['to'] = array();
        $mail_args['headers'] = isset( $mail_args['headers'] ) ? $mail_args['headers'] : array();
        $mail_args['headers'][] = 'X-UUU-DUE-CANCEL: 1';

        if ( UUU_DUE_DEBUG_THIS_FILE ) {
            error_log( '[UUU Disable User Email] All recipients suppressed; flagged email for cancellation.' );
        }

        return $mail_args;
    }

    $mail_args['to'] = $remaining;
    return $mail_args;
}

/**
 * Cancel email if we flagged it in wp_mail filter.
 * pre_wp_mail safely short-circuits wp_mail when returning a non-null value (false cancels).
 */
function uuu_due_pre_wp_mail_cancel_if_flagged( $pre, $atts ) {
    $headers = isset( $atts['headers'] ) ? $atts['headers'] : array();
    $headers = is_array( $headers ) ? $headers : explode( "\n", (string) $headers );

    foreach ( $headers as $h ) {
        if ( false !== stripos( (string) $h, 'X-UUU-DUE-CANCEL:' ) ) {
            if ( UUU_DUE_DEBUG_THIS_FILE ) {
                error_log( '[UUU Disable User Email] Email cancelled via pre_wp_mail.' );
            }
            return false;
        }
    }

    return $pre;
}

/**
 * Network admin settings page (info + meta key reminder).
 */
function uuu_due_register_network_settings_page() {
    add_submenu_page(
        'settings.php',
        'UUU Disable User Email',
        'UUU Disable User Email',
        'manage_network_options',
        'uuu-disable-user-email',
        'uuu_due_render_network_settings_page'
    );
}

function uuu_due_add_settings_link( $links ) {
    $url = network_admin_url( 'settings.php?page=uuu-disable-user-email' );
    $links[] = '<a href="' . esc_url( $url ) . '">Settings</a>';
    return $links;
}

function uuu_due_render_network_settings_page() {
    if ( ! current_user_can( 'manage_network_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'uuu-disable-user-email' ) );
    }

    echo '<div class="wrap">';
    echo '<h1>UUU Disable User Email</h1>';
    echo '<p>This plugin suppresses outgoing emails to any user that has the following user meta enabled:</p>';
    echo '<p><code>' . esc_html( UUU_DUE_META_KEY ) . '</code> = <code>1</code> (or any non-empty value)</p>';
    echo '<p>Bulk-edit the user meta across accounts using your preferred user bulk editor tool or WP-CLI.</p>';
    echo '</div>';
}

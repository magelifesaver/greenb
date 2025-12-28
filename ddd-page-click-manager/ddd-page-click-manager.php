<?php
/*
 * Plugin Name:       DDD Page Click Manager
 * Description:       Logs wrong‑site page button clicks and notifies the site admin by email. Designed for network‑wide install and per‑site activation.
 * Version:           1.0.0
 * Author:            Workflow AI
 * Requires at least: 5.3
 * Tested up to:      6.5
 * Network:           true
 *
 * This plugin exposes a small REST endpoint which receives POST requests from the wrong‑site
 * landing page. When a visitor clicks a navigation button, a JavaScript snippet
 * on that page sends a payload to this endpoint. The plugin validates the token,
 * logs the click details to the PHP error log and optionally emails the site admin.
 *
 * To avoid spamming, email notifications are rate‑limited by IP, destination and reason.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * SECURITY TOKEN
 *
 * Change this constant to a unique random string. The same value must be set in the
 * HTML snippet on the wrong‑site page (TRACK_TOKEN). The token prevents
 * unauthorised access to the logging endpoint.
 */
define( 'DDD_PCM_TOKEN', 'dddpcm_c4ec38f9c4e7' );

/**
 * EMAIL SETTINGS
 *
 * Set DDD_PCM_EMAIL_ENABLED to true to receive email notifications when a click
 * event occurs. Set to false to disable emails and rely solely on debug.log.
 * Adjust DDD_PCM_EMAIL_COOLDOWN_SECONDS to control how often emails may be sent
 * for the same origin/destination/reason combination.
 */
define( 'DDD_PCM_EMAIL_ENABLED', true );
define( 'DDD_PCM_EMAIL_COOLDOWN_SECONDS', 3600 ); // 1 hour cooldown per unique entry
define( 'DDD_PCM_EMAIL_SUBJECT', 'Wrong‑site navigation click detected' );

// Register the REST endpoint.
add_action( 'rest_api_init', function () {
    register_rest_route(
        'ddd-pcm/v1',
        '/log',
        array(
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => 'ddd_pcm_handle_log',
        )
    );
} );

/**
 * REST callback to handle click logs.
 *
 * @param WP_REST_Request $request Incoming request.
 * @return WP_REST_Response Response object.
 */
function ddd_pcm_handle_log( WP_REST_Request $request ) {
    $data  = (array) $request->get_json_params();
    $token = isset( $data['token'] ) ? sanitize_text_field( $data['token'] ) : '';

    // Verify token.
    if ( empty( $token ) || $token !== DDD_PCM_TOKEN ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'Invalid token' ), 403 );
    }

    // Gather and sanitise payload.
    $dest_url    = isset( $data['dest_url'] ) ? esc_url_raw( $data['dest_url'] ) : '';
    $reason      = isset( $data['reason'] ) ? sanitize_text_field( $data['reason'] ) : '';
    $current_url = isset( $data['current_url'] ) ? esc_url_raw( $data['current_url'] ) : '';
    $referrer    = isset( $data['referrer'] ) ? esc_url_raw( $data['referrer'] ) : '';
    $user_agent  = isset( $data['user_agent'] ) ? sanitize_text_field( $data['user_agent'] ) : '';
    $utc_time    = isset( $data['utc_time'] ) ? sanitize_text_field( $data['utc_time'] ) : gmdate( 'c' );

    // Capture visitor IP address if available.
    $ip = '';
    if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
    }

    // Build the log entry array.
    $entry = array(
        'utc_time'    => $utc_time,
        'ip'          => $ip,
        'dest_url'    => $dest_url,
        'reason'      => $reason,
        'current_url' => $current_url,
        'referrer'    => $referrer,
        'user_agent'  => $user_agent,
        'site'        => array(
            'blog_id' => function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0,
            'home'    => home_url(),
        ),
    );

    // Log to error_log (WP debug log if enabled).
    error_log( '[DDD_PCM] Click event: ' . wp_json_encode( $entry ) );

    // Optionally send email to admin with rate limiting.
    if ( DDD_PCM_EMAIL_ENABLED ) {
        ddd_pcm_maybe_email_admin( $entry );
    }

    return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * Rate‑limited email notification.
 *
 * Uses a transient to ensure that repeated clicks from the same visitor to the same
 * destination and reason do not trigger multiple emails within the cooldown period.
 *
 * @param array $entry Log entry data.
 */
function ddd_pcm_maybe_email_admin( array $entry ) {
    $key_raw       = (string) $entry['ip'] . '|' . (string) $entry['dest_url'] . '|' . (string) $entry['reason'];
    $transient_key = 'ddd_pcm_email_' . substr( md5( $key_raw ), 0, 12 );

    if ( get_transient( $transient_key ) ) {
        return;
    }

    set_transient( $transient_key, 1, DDD_PCM_EMAIL_COOLDOWN_SECONDS );

    $to      = get_option( 'admin_email' );
    $subject = DDD_PCM_EMAIL_SUBJECT;
    $body    = "Wrong‑site navigation click detected\n\n";
    foreach ( $entry as $k => $v ) {
        if ( is_array( $v ) ) {
            $body .= $k . ': ' . wp_json_encode( $v ) . "\n";
        } else {
            $body .= $k . ': ' . $v . "\n";
        }
    }
    wp_mail( $to, $subject, $body );
}

<?php
/**
 * Core blocking logic for DDD Block User IP.
 *
 * This module hooks into WordPress early (init action) to log each visitor's
 * request and determine whether they should be served a 403 response. It
 * honours the safe list, manual block list and optional country block
 * configuration. The actual IP logging is delegated to includes/log.php.
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Maybe block the current request based on safe/manual/country rules.
 *
 * This function runs on every request via the `init` action at a high
 * priority (0). It resolves the client IP, geolocates it, logs the hit,
 * then checks the following conditions in order:
 *   1. If the IP is in the safe list → allow.
 *   2. If the current user is an admin visiting wp-admin → allow.
 *   3. If the IP is in the manual block list → block.
 *   4. If auto block is enabled and the visitor's country is not the
 *      allowed country → block.
 *
 * Blocking is performed via wp_die() with a 403 status.
 */
function ddd_buip_maybe_block_ip() {
    // Do nothing if REMOTE_ADDR is missing (unlikely) to avoid blocking legitimate traffic.
    if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
        return;
    }
    // Never block admins in wp-admin to avoid lockouts.
    if ( is_admin() && current_user_can( 'manage_options' ) ) {
        return;
    }
    // Resolve the client IP, respecting proxy headers where possible.
    $ip = ddd_buip_get_client_ip();
    if ( ! $ip ) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // Perform geolocation; this may return empty country/state.
    $geo = ddd_buip_geolocate_ip( $ip );
    // Log the request before any blocking occurs. This ensures we capture
    // evidence of hits even if the IP is immediately denied.
    ddd_buip_log_hit( $ip, $geo );
    // Safe list overrides everything. If the IP is safe, allow.
    if ( ddd_buip_is_in_safe_list( $ip ) ) {
        return;
    }
    // Manual block list.
    $manual_block = ddd_buip_is_in_manual_block_list( $ip );
    // Auto country blocking.
    $auto    = (int) get_option( 'ddd_buip_auto_block', 0 );
    $country = isset( $geo['country'] ) ? strtoupper( $geo['country'] ) : '';
    $allowed = strtoupper( get_option( 'ddd_buip_allowed_country', 'US' ) );
    $country_block = ( $auto && $country && $country !== $allowed );
    // If either manual or country block applies, block the request.
    if ( $manual_block || $country_block ) {
        ddd_buip_log( 'Blocked IP ' . $ip . ' (manual=' . ( $manual_block ? '1' : '0' ) . ', country=' . $country . ')' );
        wp_die(
            __( 'Access forbidden.', 'ddd-block-user-ip' ),
            __( 'Forbidden', 'ddd-block-user-ip' ),
            array( 'response' => 403 )
        );
    }
}

// Hook into `init` with a high priority to ensure the block decision runs as early as possible.
add_action( 'init', 'ddd_buip_maybe_block_ip', 0 );

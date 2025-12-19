<?php
/**
 * Helper functions for DDD Block User IP.
 *
 * This file contains common utility functions used throughout the plugin, such as
 * debug logging, list management, IP extraction and geolocation. Breaking
 * functionality out of the main plugin file helps keep each file short and
 * self‑contained in accordance with the wide‑and‑thin architecture principle.
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Write a debug message to the error log if DDD_BUIP_DEBUG is enabled.
 *
 * @param string $message The message to log.
 */
function ddd_buip_log( $message ) {
    if ( DDD_BUIP_DEBUG && function_exists( 'error_log' ) ) {
        error_log( '[ddd-block-user-ip] ' . $message );
    }
}

/**
 * Retrieve a list of IP addresses from a given option name. The option
 * is stored as a newline separated string; this helper normalises it into
 * an associative array keyed by IP for fast lookup.
 *
 * @param string $option_name The option key to read from.
 * @return array<string,string> Array of IPs keyed by the same IP.
 */
function ddd_buip_get_ip_list( $option_name ) {
    $raw = get_option( $option_name, '' );
    if ( '' === $raw ) {
        return array();
    }
    $lines = preg_split( '/\r\n|\r|\n/', $raw );
    $ips   = array();
    if ( is_array( $lines ) ) {
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( '' !== $line ) {
                $ips[ $line ] = $line;
            }
        }
    }
    return $ips;
}

/**
 * Save a list of IP addresses back into an option. The array passed in may
 * contain duplicates; they will be normalised to unique values.
 *
 * @param string   $option_name The option key to write to.
 * @param string[] $ips         List of IPs to store.
 */
function ddd_buip_save_ip_list( $option_name, $ips ) {
    if ( empty( $ips ) ) {
        update_option( $option_name, '' );
        return;
    }
    $unique_ips = array_unique( array_values( $ips ) );
    update_option( $option_name, implode( "\n", $unique_ips ) );
}

/**
 * Check whether a given IP is in the manual block list.
 *
 * @param string $ip IPv4 or IPv6 address.
 * @return bool True if the IP is manually blocked.
 */
function ddd_buip_is_in_manual_block_list( $ip ) {
    $list = ddd_buip_get_ip_list( 'ddd_buip_ips' );
    return isset( $list[ $ip ] );
}

/**
 * Check whether a given IP is in the safe list.
 *
 * @param string $ip IPv4 or IPv6 address.
 * @return bool True if the IP is whitelisted.
 */
function ddd_buip_is_in_safe_list( $ip ) {
    $list = ddd_buip_get_ip_list( 'ddd_buip_safe_ips' );
    return isset( $list[ $ip ] );
}

/**
 * Resolve the client IP address accounting for proxy headers. Some hosts
 * (e.g. Cloudflare, load balancers) forward the original client IP via
 * HTTP headers. This helper inspects a small set of common headers and
 * falls back to REMOTE_ADDR if none are present. Sanitises the result
 * using filter_var.
 *
 * @return string The detected client IP address.
 */
function ddd_buip_get_client_ip() {
    $ip_sources = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    );
    foreach ( $ip_sources as $header ) {
        if ( ! empty( $_SERVER[ $header ] ) ) {
            $ip_raw = $_SERVER[ $header ];
            // HTTP_X_FORWARDED_FOR may contain multiple addresses.
            if ( false !== strpos( $ip_raw, ',' ) ) {
                $ip_raw = explode( ',', $ip_raw )[0];
            }
            $ip = trim( $ip_raw );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }
    }
    return '';
}

/**
 * Geolocate an IP using WooCommerce's geolocation API if available. Returns
 * an associative array with country and state keys, or empty values on
 * failure. Errors are silently logged if debugging is enabled.
 *
 * @param string $ip IP address to geolocate.
 * @return array{country:string,state:string} Country/state codes.
 */
function ddd_buip_geolocate_ip( $ip ) {
    $result = array( 'country' => '', 'state' => '' );
    if ( class_exists( 'WC_Geolocation' ) ) {
        try {
            $geo = new WC_Geolocation();
            $loc = $geo->geolocate_ip( $ip );
            if ( is_array( $loc ) ) {
                $result['country'] = isset( $loc['country'] ) ? $loc['country'] : '';
                $result['state']   = isset( $loc['state'] ) ? $loc['state'] : '';
            }
        } catch ( Exception $e ) {
            ddd_buip_log( 'Geolocation error: ' . $e->getMessage() );
        }
    }
    return $result;
}

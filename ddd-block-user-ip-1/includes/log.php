<?php
/**
 * Logging and database management for DDD Block User IP.
 *
 * This module is responsible for creating the custom log table on plugin
 * activation and recording hits with a simple abuse score. Keeping these
 * operations isolated from the rest of the plugin improves clarity and
 * testability. The table stores only metadata and does not indicate
 * whether an IP was blocked or not; block decisions are computed at
 * runtime.
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Install the custom IP log table on plugin activation.
 */
function ddd_buip_install() {
    global $wpdb;
    $table   = $wpdb->prefix . 'ddd_buip_ip_log';
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ip VARCHAR(45) NOT NULL,
        hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
        last_seen DATETIME NOT NULL,
        last_path VARCHAR(255) DEFAULT '',
        last_ua VARCHAR(255) DEFAULT '',
        country VARCHAR(2) DEFAULT '',
        score INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY ip (ip)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}


/**
 * Record an IP hit into the log table and increment its abuse score.
 * Scoring heuristics are intentionally simple: one point per hit, plus
 * additional weight if the request comes from an unallowed country or
 * sensitive path or has an empty user agent. This function does not
 * perform any blocking; it merely records data for reporting.
 *
 * @param string $ip  IP address.
 * @param array  $geo Geo array with 'country' key.
 */
function ddd_buip_log_hit( $ip, $geo ) {
    global $wpdb;
    $table   = $wpdb->prefix . 'ddd_buip_ip_log';
    $path    = isset( $_SERVER['REQUEST_URI'] ) ? substr( wp_unslash( $_SERVER['REQUEST_URI'] ), 0, 250 ) : '';
    $ua      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), 0, 250 ) : '';
    $country = isset( $geo['country'] ) ? $geo['country'] : '';
    $row     = $wpdb->get_row( $wpdb->prepare( "SELECT id, hits, score FROM $table WHERE ip = %s", $ip ) );
    $hits    = $row ? (int) $row->hits + 1 : 1;
    $score   = $row ? (int) $row->score : 0;
    // Basic scoring heuristics.
    $score += 1; // each hit adds one.
    $allowed = strtoupper( get_option( 'ddd_buip_allowed_country', 'US' ) );
    if ( $country && strtoupper( $country ) !== $allowed ) {
        $score += 5;
    }
    $sensitive_paths = array( '/wp-login.php', '/xmlrpc.php' );
    foreach ( $sensitive_paths as $p ) {
        if ( false !== strpos( $path, $p ) ) {
            $score += 3;
            break;
        }
    }
    if ( '' === $ua ) {
        $score += 2;
    }
    $data = array(
        'ip'        => $ip,
        'hits'      => $hits,
        'last_seen' => current_time( 'mysql' ),
        'last_path' => $path,
        'last_ua'   => $ua,
        'country'   => $country,
        'score'     => $score,
    );
    if ( $row ) {
        $wpdb->update( $table, $data, array( 'id' => $row->id ) );
    } else {
        $wpdb->insert( $table, $data );
    }
}

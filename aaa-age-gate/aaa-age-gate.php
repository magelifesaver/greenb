<?php
/**
 * File Path: /wp-content/plugins/aaa-age-gate/aaa-age-gate.php
 * Plugin Name: AAA Age Gate (XHV98)
 * Description: Simple age verification modal for logged-out visitors. Logged-in users bypass automatically.
 * Version: 1.0.0
 * Author: AAA Workflow
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Per-file debug toggle. Leave true in dev; set false in production.
 */
if ( ! defined( 'AAA_AGE_GATE_DEBUG_THIS_FILE' ) ) {
    define( 'AAA_AGE_GATE_DEBUG_THIS_FILE', false );
}
if ( ! function_exists( 'aaa_age_gate_log' ) ) {
    function aaa_age_gate_log( $msg ) {
        if ( AAA_AGE_GATE_DEBUG_THIS_FILE && function_exists( 'error_log' ) ) {
            error_log( '[AAA_Age_Gate] ' . $msg );
        }
    }
}

/**
 * Core constants.
 */
define( 'AAA_AGE_GATE_VER', '1.0.0' );
define( 'AAA_AGE_GATE_SLUG', 'aaa-age-gate' );
define( 'AAA_AGE_GATE_URL', plugin_dir_url( __FILE__ ) );
define( 'AAA_AGE_GATE_PATH', plugin_dir_path( __FILE__ ) );
define( 'AAA_AGE_GATE_COOKIE', 'aaa_age_gate_ok' ); // value "1" means accepted

/**
 * Should gate be active on this request?
 */
function aaa_age_gate_should_gate() {
    if ( is_user_logged_in() ) return false;            // logged-in bypass
    if ( is_admin() ) return false;                     // wp-admin bypass
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return false;
    if ( defined( 'DOING_CRON' ) && DOING_CRON ) return false;

    // Don’t gate login/registration/lost-password screens
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
    if ( false !== strpos( $request_uri, 'wp-login.php' ) ) return false;

    // If cookie present and "1", skip
    if ( isset( $_COOKIE[ AAA_AGE_GATE_COOKIE ] ) && '1' === $_COOKIE[ AAA_AGE_GATE_COOKIE ] ) {
        return false;
    }

    return true;
}

/**
 * Enqueue assets only when needed.
 */
add_action( 'wp_enqueue_scripts', function() {
    if ( ! aaa_age_gate_should_gate() ) {
        aaa_age_gate_log( 'Bypass; no assets enqueued.' );
        return;
    }

    wp_enqueue_style(
        'aaa-age-gate',
        AAA_AGE_GATE_URL . 'assets/css/aaa-age-gate.css',
        [],
        AAA_AGE_GATE_VER
    );

    wp_enqueue_script(
        'aaa-age-gate',
        AAA_AGE_GATE_URL . 'assets/js/aaa-age-gate.js',
        [],
        AAA_AGE_GATE_VER,
        true
    );

    // Localize strings + config
    $data = [
        'cookieName'   => AAA_AGE_GATE_COOKIE,
        'cookieDays'   => 365,
        'declineUrl'   => apply_filters( 'aaa_age_gate_decline_url', 'https://www.google.com' ),
        'heading'      => apply_filters( 'aaa_age_gate_heading', 'Adult Content Ahead' ),
        'message'      => apply_filters(
            'aaa_age_gate_message',
            'This website contains age-restricted content. By entering, you confirm you are 18+ (or the legal age in your location) and are not offended by such material. If you are under 18 or it is illegal in your area, please exit now.'
        ),
        'acceptLabel'  => apply_filters( 'aaa_age_gate_accept_label', 'I am 18+ — Enter' ),
        'declineLabel' => apply_filters( 'aaa_age_gate_decline_label', 'Exit' ),
    ];
    wp_add_inline_script( 'aaa-age-gate', 'window.AAA_AGE_GATE = ' . wp_json_encode( $data ) . ';', 'before' );

    aaa_age_gate_log( 'Assets enqueued.' );
}, 20 );

/**
 * Print modal container late in the footer (only when gating).
 */
add_action( 'wp_footer', function() {
    if ( ! aaa_age_gate_should_gate() ) return;

    // Basic container; JS injects text/labels and opens it.
    ?>
    <div id="aaa-age-gate-overlay" class="aaa-age-gate-overlay" aria-hidden="true">
        <div class="aaa-age-gate-modal" role="dialog" aria-modal="true" aria-labelledby="aaa-age-gate-title">
            <h2 id="aaa-age-gate-title"></h2>
            <p id="aaa-age-gate-message"></p>
            <div class="aaa-age-gate-actions">
                <button type="button" id="aaa-age-gate-accept" class="aaa-age-gate-btn accept"></button>
                <a id="aaa-age-gate-decline" class="aaa-age-gate-btn decline" href="#" rel="nofollow noopener"></a>
            </div>
        </div>
    </div>
    <?php
    aaa_age_gate_log( 'Overlay printed.' );
}, 100 );

/**
 * Developer note — customize text without editing the plugin:
 *
 * add_filter('aaa_age_gate_heading', fn() => 'WARNING: ADULT CONTENT!');
 * add_filter('aaa_age_gate_message', fn() => 'Your custom message…');
 * add_filter('aaa_age_gate_accept_label', fn() => 'Enter (18+)');
 * add_filter('aaa_age_gate_decline_label', fn() => 'Leave');
 * add_filter('aaa_age_gate_decline_url', fn() => home_url('/goodbye'));
 */

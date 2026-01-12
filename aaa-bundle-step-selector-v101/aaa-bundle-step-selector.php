<?php
/**
 * Plugin Name: AAA Bundle Step Selector
 * Description: Two-step product selector shortcode with per-step max quantity limits and optional free-item cart pricing.
 * Version: 1.0.1
 * Author: Webmaster Workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AAA_BSS_VERSION', '1.0.1' );
define( 'AAA_BSS_FILE', __FILE__ );
define( 'AAA_BSS_BASENAME', plugin_basename( __FILE__ ) );
define( 'AAA_BSS_PATH', plugin_dir_path( __FILE__ ) );
define( 'AAA_BSS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Lightweight debug logger (writes to wp-content/debug.log when WP_DEBUG_LOG is enabled).
 * Default: enabled (development).
 */
if ( ! function_exists( 'aaa_bss_log' ) ) {
    function aaa_bss_log( $message, $context = [] ) {
        $debug_this_file = true;
        if ( ! $debug_this_file ) { return; }

        $prefix = '[AAA-BSS] ';
        $line = is_string( $message ) ? $message : wp_json_encode( $message );

        if ( ! empty( $context ) && is_array( $context ) ) {
            $line .= ' | ' . wp_json_encode( $context );
        }

        error_log( $prefix . $line );
    }
}

require_once AAA_BSS_PATH . 'includes/class-aaa-bss-settings.php';
require_once AAA_BSS_PATH . 'includes/class-aaa-bss-shortcode.php';
require_once AAA_BSS_PATH . 'includes/class-aaa-bss-cart.php';

final class AAA_BSS_Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'notice_missing_wc' ] );
            return;
        }

        new AAA_BSS_Settings();
        new AAA_BSS_Shortcode();
        new AAA_BSS_Cart();
    }

    public function notice_missing_wc() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        echo '<div class="notice notice-error"><p>' .
            esc_html__( 'AAA Bundle Step Selector requires WooCommerce to be active.', 'aaa-bss' ) .
        '</p></div>';
    }
}

add_action( 'plugins_loaded', function() {
    AAA_BSS_Plugin::instance()->init();
} );

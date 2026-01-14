<?php
/**
 * Plugin Name: AAA Bundle Step Selector (Hardcoded)
 * Description: Hardcoded 2-step promo selector with banner trigger, adds items to cart and triggers Fast Cart.
 * Version: 1.1.0
 * Author: Webmaster Workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AAA_BSS_VERSION', '1.1.0' );
define( 'AAA_BSS_FILE', __FILE__ );
define( 'AAA_BSS_BASENAME', plugin_basename( __FILE__ ) );
define( 'AAA_BSS_PATH', plugin_dir_path( __FILE__ ) );
define( 'AAA_BSS_URL', plugin_dir_url( __FILE__ ) );

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

require_once AAA_BSS_PATH . 'includes/class-aaa-bss-config.php';
require_once AAA_BSS_PATH . 'includes/class-aaa-bss-ui.php';
require_once AAA_BSS_PATH . 'includes/class-aaa-bss-ajax.php';
require_once AAA_BSS_PATH . 'includes/class-aaa-bss-cart.php';

final class AAA_BSS_Plugin {
    public function init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'notice_missing_wc' ] );
            return;
        }

        new AAA_BSS_UI();
        new AAA_BSS_AJAX();
        new AAA_BSS_Cart();

        add_filter( 'plugin_action_links_' . AAA_BSS_BASENAME, [ $this, 'plugin_links' ] );
    }

    public function plugin_links( $links ) {
        $links[] = '<span style="opacity:.75;">Hardcoded promo</span>';
        return $links;
    }

    public function notice_missing_wc() {
        if ( ! current_user_can( 'activate_plugins' ) ) { return; }
        echo '<div class="notice notice-error"><p>' .
            esc_html__( 'AAA Bundle Step Selector requires WooCommerce to be active.', 'aaa-bss' ) .
        '</p></div>';
    }
}

add_action( 'plugins_loaded', function() {
    ( new AAA_BSS_Plugin() )->init();
} );

<?php
/**
 * Plugin Name: AAA FCI – Fast Cart & Header Login Guards
 * Description: Fast Cart iframe cleanup + Kadence header login drawer restore after failed login.
 * File Path: /wp-content/plugins/aaa-fci/aaa-fci-fastcart-assets-loader.php
 * Version: 1.3.1
 * Author: Webmaster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'AAA_FCI_DEBUG' ) ) {
    define( 'AAA_FCI_DEBUG', false );
}

define( 'AAA_FCI_VER', '1.3.1' );

/** -------- Fast Cart (iframe) assets -------- */
function aaa_fci_enqueue_fastcart_assets() {
    if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
        return;
    }

    $base = plugin_dir_url( __FILE__ ) . 'assets/';
    wp_enqueue_style ( 'aaa-fci-fastcart-iframe', $base . 'css/aaa-fci-fastcart-iframe.css', array(), AAA_FCI_VER );
    wp_enqueue_script( 'aaa-fci-flag',            $base . 'js/aaa-fci-flag.js',              array(), AAA_FCI_VER, true );
    wp_enqueue_script( 'aaa-fci-guard',           $base . 'js/aaa-fci-login-guard.js',       array(), AAA_FCI_VER, true );

    if ( AAA_FCI_DEBUG ) {
        error_log( '[AAA_FCI] Fast Cart iframe assets loaded' );
    }
}
add_action( 'wfc_load_checkout_scripts', 'aaa_fci_enqueue_fastcart_assets' );
add_action( 'wfc_load_cart_scripts',     'aaa_fci_enqueue_fastcart_assets' );

/** -------- Frontend (non-iframe) assets (Kadence drawer restore) -------- */
function aaa_fci_enqueue_front_assets() {
    if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
        return;
    }

    $base = plugin_dir_url( __FILE__ ) . 'assets/';
    wp_enqueue_style ( 'aaa-fci-frontend', $base . 'css/aaa-fci-frontend.css', array(), AAA_FCI_VER );
    wp_enqueue_script( 'aaa-fci-kadence-restore', $base . 'js/aaa-fci-kadence-restore.js', array(), AAA_FCI_VER, true );

    if ( AAA_FCI_DEBUG ) {
        error_log( '[AAA_FCI] Frontend Kadence restore loaded' );
    }
}
add_action( 'wp_enqueue_scripts', 'aaa_fci_enqueue_front_assets', 20 );

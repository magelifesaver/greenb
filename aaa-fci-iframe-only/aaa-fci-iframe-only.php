<?php
/**
 * Plugin Name: AAA FCI – Iframe Only (Fast Cart Header/Footer Cleaner)
 * Description: Load CSS only inside Barn2 Fast Cart iframe to hide theme header/footer. Nothing else.
 * File Path: /wp-content/plugins/aaa-fci-iframe-only/aaa-fci-iframe-only.php
 * Version: 1.0.0
 * Author: AAA Workflow
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue CSS from *inside* Fast Cart iframe contexts only.
 * Barn2 fires these hooks from within the iframe.
 */
function aaa_fci_iframe_only_enqueue() {
	$handle = 'aaa-fci-iframe-only';
	$src    = plugin_dir_url( __FILE__ ) . 'assets/css/iframe-only.css';
	wp_enqueue_style( $handle, $src, [], '1.0.0' );
}
add_action( 'wfc_load_checkout_scripts', 'aaa_fci_iframe_only_enqueue' );
add_action( 'wfc_load_cart_scripts',     'aaa_fci_iframe_only_enqueue' );

<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Barn2\woocommerce-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'wc_fast_cart_settings', [] );

if ( ! isset( $settings['delete_data'] ) || $settings['delete_data'] !== 'yes' ) {
	return;
}

$options_to_delete = [
	'wc_fast_cart_settings',
	'barn2_plugin_license_311420',
	'barn2_plugin_promo_311420',
	'barn2_plugin_review_banner_311420',
];

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

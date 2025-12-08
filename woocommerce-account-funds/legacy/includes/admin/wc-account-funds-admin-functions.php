<?php
/**
 * Admin functions
 *
 * @package WC_Account_Funds/Admin/Functions
 * @since   2.3.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gets the current screen ID.
 *
 * @internal
 *
 * @since 2.3.7
 * @deprecated 4.0.0
 *
 * @return string|false
 */
function wc_account_funds_get_current_screen_id() {
	$screen_id = false;

	// It may not be available.
	if ( function_exists( 'get_current_screen' ) ) {
		$screen    = get_current_screen();
		$screen_id = isset( $screen, $screen->id ) ? $screen->id : false;
	}

	// Get the value from the request.
	if ( ! $screen_id && ! empty( $_REQUEST['screen'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$screen_id = wc_clean( wp_unslash( $_REQUEST['screen'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	return $screen_id;
}

/**
 * Gets the screen name of orders page in wp-admin.
 *
 * @internal
 *
 * @since 2.9.0
 * @deprecated 4.0.0
 *
 * @return string
 */
function wc_account_funds_get_order_admin_screen() : string {

	if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
		return \Automattic\WooCommerce\Utilities\OrderUtil::get_order_admin_screen();
	}

	return 'shop_order';
}

/**
 * Gets if we are in the plugin settings page or not.
 *
 * @internal
 *
 * @since 2.6.0
 * @deprecated 4.0.0
 *
 * @return bool
 */
function wc_account_funds_is_settings_page() : bool {

	wc_deprecated_function( __FUNCTION__, '4.0.0' );

	// phpcs:disable WordPress.Security.NonceVerification
	return ( is_admin() &&
		isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] &&
		isset( $_GET['tab'] ) && 'account_funds' === $_GET['tab']
	);
	// phpcs:enable WordPress.Security.NonceVerification
}

/**
 * Gets the dismiss url for a notice.
 *
 * @internal
 *
 * @since 2.3.7
 * @deprecated 4.0.0
 *
 * @param string $notice
 * @param mixed $base_url
 * @return string
 */
function wc_account_funds_get_notice_dismiss_url( string $notice, $base_url = false ) :  string {

	wc_deprecated_function( __FUNCTION__, '4.0.0' );

	return wp_nonce_url( add_query_arg( 'wc-hide-notice', $notice, $base_url ), 'woocommerce_hide_notices_nonce', '_wc_notice_nonce' );
}

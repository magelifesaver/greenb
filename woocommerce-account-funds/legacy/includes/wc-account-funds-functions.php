<?php
/**
 * Useful functions for the plugin.
 *
 * @since 2.2.0
 * @deprecated 4.0.0
 */

defined( 'ABSPATH' ) or exit;

// include core functions
require 'wc-account-funds-order-functions.php';

/**
 * Gets the suffix for the script filenames.
 *
 * @intenal
 *
 * @since 2.2.0
 * @deprecated 4.0.0
 *
 * @return string the scripts suffix
 */
function wc_account_funds_get_scripts_suffix() : string {

	wc_deprecated_function( __FUNCTION__, '4.0.0' );

	return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
}

/**
 * What type of request is this?
 *
 * @internal
 *
 * @since 2.2.0
 * @deprecated 4.0.0
 *
 * @param string $type admin, ajax, cron, rest_api or frontend
 * @return bool
 */
function wc_account_funds_is_request( string $type ) : bool {
	$is_request = false;

	switch ( $type ) {
		case 'admin':
			$is_request = is_admin();
			break;
		case 'ajax':
			$is_request = defined( 'DOING_AJAX' );
			break;
		case 'cron':
			$is_request = defined( 'DOING_CRON' );
			break;
		case 'frontend':
			$is_request = ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! wc_account_funds_is_request( 'rest_api' );
			break;
		case 'rest_api':
			if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
				$rest_prefix = trailingslashit( rest_get_url_prefix() );
				$is_request  = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}

			break;
	}

	if ( has_filter( 'wc_account_funds_is_request' ) ) {
		wc_deprecated_hook( 'wc_account_funds_is_request', '4.0.0' );
	}

	/**
	 * Filters if the request is of the specified type.
	 *
	 * @since 2.2.0
	 *
	 * @param bool $is_request whether the request is of the specified type
	 * @param string $type the request type
	 */
	return (bool) apply_filters( 'wc_account_funds_is_request', $is_request, $type );
}

/**
 * Gets templates passing attributes and including the file.
 *
 * @internal
 *
 * @since 2.2.0
 * @deprecated 4.0.0
 *
 * @param string $template_name the template name
 * @param array $args Optional. The template arguments.
 * @return void
 */
function wc_account_funds_get_template( string $template_name, array $args = [] ) : void {

	wc_get_template( $template_name, $args, '', \WC_ACCOUNT_FUNDS_PATH . 'templates/' );
}

/**
 * Gets whether the current user has the capability to accomplish the specified action.
 *
 * @since 2.7.0
 * @deprecated 4.0.0
 *
 * @param string $action the action name
 * @param mixed ...$args additional parameters to pass to the callback functions
 * @return bool
 */
function wc_account_funds_current_user_can( string $action, ...$args ) : bool {

	wc_deprecated_function( __FUNCTION__, '4.0.0', "current_user_can( 'manage_woocommerce' )" );

	if ( has_filter( "wc_account_funds_current_user_can_{$action}" ) ) {
		wc_deprecated_hook( "wc_account_funds_current_user_can_{$action}", '4.0.0', "current_user_can( 'manage_woocommerce' )" );
	}

	/**
	 * Filters whether the current user has the capability to accomplish the specified action.
	 *
	 * The dynamic portion of the hook name, $action, refers to the action to accomplish.
	 *
	 * @since 2.7.0
	 * @deprecated 4.0.0
	 *
	 * @param bool $has_capability whether the current user has the capability
	 * @param mixed ...$args Additional parameters to pass to the callback functions.
	 */
	return apply_filters( "wc_account_funds_current_user_can_{$action}", current_user_can( 'manage_woocommerce' ), ...$args );
}

/**
 * Gets the name to refer to the account funds.
 *
 * @since 2.8.0
 * @deprecated 4.0.0
 *
 * @return string
 */
function wc_get_account_funds_name() : string {

	wc_deprecated_function( __FUNCTION__, '4.0.0', 'wc_account_funds_store_credit_label()' );

	return wc_account_funds_store_credit_label( 'singular' );
}

/**
 * Gets the placeholder text.
 *
 * @since 2.8.0
 * @deprecated 4.0.0
 *
 * @param string[] $placeholders placeholder list
 * @return string
 */
function wc_account_funds_get_placeholder_text( array $placeholders ) : string {

	wc_deprecated_function( __FUNCTION__, '4.0.0' );

	return sprintf(
		/* translators: Placeholders: %s - list of available placeholders */
		__( 'Available placeholders: %s', 'woocommerce' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		'<code>' . esc_html( implode( '</code>, <code>', $placeholders ) ) . '</code>'
	);
}

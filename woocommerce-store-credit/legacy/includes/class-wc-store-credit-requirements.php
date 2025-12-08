<?php

defined( 'ABSPATH' ) or exit;

/**
 * Legacy requirements handler.
 *
 * @since 3.6.0
 * @deprecated 5.0.0
 */
class WC_Store_Credit_Requirements {

	/**
	 * Minimum PHP version required.
	 */
	const MINIMUM_PHP_VERSION = '5.6';

	/**
	 * Minimum WordPress version required.
	 */
	const MINIMUM_WP_VERSION = '4.9';

	/**
	 * Minimum WooCommerce version required.
	 */
	const MINIMUM_WC_VERSION = '3.7';

	/**
	 * Init.
	 *
	 * @since 3.6.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function init() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Checks the plugin requirements.
	 *
	 * @since 3.6.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	protected static function check_requirements() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Gets if the minimum PHP version requirement is satisfied.
	 *
	 * @since 3.6.0
	 * @deprecated 5.0.0
	 *
	 * @return bool
	 */
	public static function is_php_compatible() : bool {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=' ) );
	}

	/**
	 * Gets if the minimum WordPress version requirement is satisfied.
	 *
	 * @since 3.6.0
	 * @deprecated 5.0.0
	 *
	 * @return bool
	 */
	public static function is_wp_compatible() : bool {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return ( version_compare( get_bloginfo( 'version' ), self::MINIMUM_WP_VERSION, '>=' ) );
	}

	/**
	 * Gets if the minimum WooCommerce version requirement is satisfied.
	 *
	 * @since 3.6.0
	 * @deprecated 5.0.0
	 *
	 * @return bool
	 */
	public static function is_wc_compatible() : bool {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return ( version_compare( get_option( 'woocommerce_db_version' ), self::MINIMUM_WC_VERSION, '>=' ) );
	}

	/**
	 * Gets if the WooCommerce plugin is active.
	 *
	 * @since 4.4.0
	 * @deprecated 5.0.0
	 *
	 * @return bool
	 */
	public static function is_wc_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Outputs the plugin requirements errors.
	 *
	 * @since 3.6.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function admin_notices() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Gets if the plugin requirements are satisfied.
	 *
	 * @since 3.6.0
	 * @deprecated 5.0.0
	 *
	 * @return bool
	 */
	public static function are_satisfied() : bool {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return true;
	}

}

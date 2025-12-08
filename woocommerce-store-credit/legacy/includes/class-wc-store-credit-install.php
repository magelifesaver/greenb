<?php
/**
 * Installation related functions and actions
 *
 * Inspired in the WC_Install class.
 *
 * @package WC_Store_Credit
 * @since   2.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Legacy lifecycle handler.
 *
 * @since 2.4.0
 * @deprecated 5.0.0
 */
class WC_Store_Credit_Install {

	/**
	 * Init installation.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function init() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Get the database updates.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return array
	 */
	public static function get_db_updates() : array {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return [];
	}

	/**
	 * Init background updates.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function init_background_updater() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Check the plugin version and run the updater is necessary.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function check_version() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'wc_store_credit_version' ), WC_STORE_CREDIT_VERSION, '<' ) ) {

			/**
			 * Fires when the plugin update finished.
			 *
			 * @since 2.4.0
			 */
			do_action( 'wc_store_credit_updated' );
		}
	}

	/**
	 * Install actions when an update button is clicked within the admin area.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function install_actions() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Add installer/updater notices + styles if needed.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function add_notices() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Adds the update notices.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function update_notice() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Adds notices with the features of the new version of this plugin.
	 *
	 * @since 3.2.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function add_feature_notices() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Init installation.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function install() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Registers custom endpoints.
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function add_endpoints() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Update database version to current.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function update_db_version() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Gets if it exists any coupon from older versions of this plugin in the database.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return bool
	 */
	public static function exists_older_coupons() : bool {
		global $wpdb;

		wc_deprecated_function( __METHOD__, '5.0.0' );

		$count = $wpdb->get_var(
			"SELECT COUNT(*)
			 FROM $wpdb->posts AS posts
			 LEFT JOIN $wpdb->postmeta AS meta on posts.ID = meta.post_id
			 WHERE posts.post_type = 'shop_coupon' AND
			       meta.meta_key   = 'discount_type' AND
			       meta.meta_value = 'store_credit'"
		);

		return ( 0 < (int) $count );
	}

	/**
	 * Database updated.
	 *
	 * @since 2.4.2
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function updated() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

}

<?php
/**
 * Account Funds for WooCommerce Admin.
 *
 * @since   2.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * WC_Account_Funds_Admin.
 */
class WC_Account_Funds_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'includes' ] );
	}

	/**
	 * Include any classes we need within admin.
	 *
	 * @since 2.3.7
	 */
	public function includes() {

		include_once 'wc-account-funds-admin-functions.php';
		include_once 'class-wc-account-funds-admin-refunds.php';
	}

}

new WC_Account_Funds_Admin();

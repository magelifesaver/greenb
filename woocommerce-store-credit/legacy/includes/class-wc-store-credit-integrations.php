<?php
/**
 * Handles the plugin integrations.
 *
 * @package WC_Store_Credit
 * @since   4.1.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Legacy class for handling the plugin integrations.
 *
 * @since 4.1.0
 * @deprecated 5.0.0
 */
class WC_Store_Credit_Integrations {

	/** @var class-string[] */
	protected array $integrations = [];

	/**
	 * Constructor.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0
	 */
	public function __construct() {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Registers the plugin integrations.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public function register_integrations() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Init integrations.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public function init_integrations() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

}

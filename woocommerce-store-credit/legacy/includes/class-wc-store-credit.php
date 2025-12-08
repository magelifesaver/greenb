<?php

defined( 'ABSPATH' ) or exit;

if ( ! trait_exists( 'WC_Store_Credit_Singleton_Trait' ) ) {
	require_once dirname( WC_STORE_CREDIT_FILE ) . '/legacy/includes/traits/trait-wc-store-credit-singleton.php';
}

/**
 * Store Credit for WooCommerce Class.
 *
 * @since 3.0.0
 * @deprecated 5.0.0
 */
final class WC_Store_Credit {

	use WC_Store_Credit_Singleton_Trait;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0
	 */
	protected function __construct() {

		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define constants.
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public function define_constants() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Includes the necessary files.
	 *
	 * @since 3.0.0
	 * @internal
	 *
	 * @return void
	 */
	public function includes() : void {
		/**
		 * Interfaces.
		 */
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/interfaces/interface-wc-store-credit-integration.php';

		/**
		 * Core classes.
		 */
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/wc-store-credit-functions.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-autoloader.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/wc-store-credit-functions.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-install.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-coupons.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-products.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-emails.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-order.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-order-details.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-order-query.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-paypal.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-rest-api.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-integrations.php';

		if ( wc_store_credit_is_request( 'admin' ) ) {
			include_once WC_STORE_CREDIT_PATH . 'legacy/includes/admin/class-wc-store-credit-admin.php';
		}

		if ( wc_store_credit_is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}
	}

	/**
	 * Includes required frontend files.
	 *
	 * @since 3.0.0
	 * @internal
	 *
	 * @return void
	 */
	public function frontend_includes() : void {

		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-session.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-cart.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-my-account.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-product-addons.php';
		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-frontend-scripts.php';
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function init_hooks() : void {

		add_action( 'woocommerce_loaded', [ $this, 'wc_loaded' ] );
		add_action( 'after_setup_theme', [ $this, 'include_template_functions' ], 15 );
	}

	/**
	 * Init plugin.
	 *
	 * @since 3.0.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public function init() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Declares compatibility with the WC features.
	 *
	 * @since 4.2.4
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public function declare_compatibility() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Load more functionality after WC has been initialized.
	 *
	 * @since 3.0.0
	 * @internal
	 *
	 * @return void
	 */
	public function wc_loaded() : void {

		if ( class_exists( 'WC_Abstract_Privacy' ) ) {
			include_once WC_STORE_CREDIT_PATH . 'legacy/includes/class-wc-store-credit-privacy.php';
		}
	}

	/**
	 * Includes the Template Functions - This makes them pluggable by plugins and themes.
	 *
	 * @since 4.2.0
	 * @internal
	 *
	 * @return void
	 */
	public function include_template_functions() : void {

		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/wc-store-credit-template-functions.php';
	}

}

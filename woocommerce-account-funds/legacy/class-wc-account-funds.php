<?php

/**
 * Legacy Account funds for WooCommerce loader.
 *
 * @since 1.0.0
 * @deprecated 4.0.0
 */
class WC_Account_Funds {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @deprecated 4.0.0
	 */
	public function __construct() {

		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Includes the necessary files.
	 *
	 * @since 2.2.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	private function includes() : void {

		/**
		 * Class autoloader.
		 */
		include_once \WC_ACCOUNT_FUNDS_PATH . 'legacy/includes/class-wc-account-funds-autoloader.php';

		/**
		 * Core classes.
		 */
		include_once \WC_ACCOUNT_FUNDS_PATH . 'legacy/includes/wc-account-funds-functions.php';
		include_once \WC_ACCOUNT_FUNDS_PATH . 'legacy/includes/class-wc-account-funds-emails.php';
		include_once \WC_ACCOUNT_FUNDS_PATH . 'legacy/includes/class-wc-account-funds-my-account.php';

		if ( is_admin() ) {
			include_once \WC_ACCOUNT_FUNDS_PATH . 'legacy/includes/admin/class-wc-account-funds-admin.php';
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.2.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	private function init_hooks() : void {

		add_action( 'widgets_init', [ $this, 'widgets_init' ] );
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Load Widget.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function widgets_init() : void {

		include_once \WC_ACCOUNT_FUNDS_PATH . 'legacy/includes/class-wc-account-funds-widget.php';
	}

	/**
	 * Init plugin.
	 *
	 * @since 1.0.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	public function init() : void {

		$this->admin_init();
	}

	/**
	 * Load admin.
	 *
	 * @since 1.0.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	public function admin_init() : void {

		if ( ! is_admin() ) {
			return;
		}

		include_once \WC_ACCOUNT_FUNDS_PATH . 'legacy/includes/class-wc-account-funds-reports.php';
	}

	/**
	 * Get a users funds amount.
	 *
	 * @since 1.0.0
	 * @deprecated 4.0.0
	 *
	 * @param int|null $user_id
	 * @param bool $formatted
	 * @param int $exclude_order_id
	 * @return string|float
	 */
	public static function get_account_funds( ?int $user_id = null, bool $formatted = true, int $exclude_order_id = 0 ) {
		$funds   = 0;
		$user_id = ( $user_id ? $user_id : get_current_user_id() );

		if ( ! $user_id ) {
			return $formatted ? wc_price( $funds ) : $funds;
		}

		$wallet = \Kestrel\Account_Funds\Store_Credit\Wallet::get( $user_id );

		return $formatted ? wc_price( $wallet->available_balance( $exclude_order_id ) ) : $wallet->available_balance( $exclude_order_id );
	}

	/**
	 * Add account funds-related data stores.
	 *
	 * @since 2.1.3
	 * @deprecated 4.0.0
	 *
	 * @param array<mixed>|mixed $data_stores data stores
	 * @return array<mixed>|mixed data stores
	 */
	public function add_data_stores( $data_stores ) {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return $data_stores;
	}

}

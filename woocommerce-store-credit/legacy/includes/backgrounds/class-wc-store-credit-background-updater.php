<?php

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'WC_Store_Credit_Background_Process', false ) ) {
	include_once WC_STORE_CREDIT_PATH . 'legacy/includes/abstracts/abstract-wc-store-credit-background-process.php';
}

/**
 * Background Updater.
 *
 * @since 2.4.0
 * @deprecated 5.0.0
 */
class WC_Store_Credit_Background_Updater extends WC_Store_Credit_Background_Process {

	/**
	 * Initiate new background process.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 */
	public function __construct() {

		$this->action = 'updater';

		wc_deprecated_function( __METHOD__, '5.0.0' );

		parent::__construct();
	}

	/**
	 * Dispatch updater.
	 *
	 * Updater will still run via cron job if this fails for any reason.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public function dispatch() {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		$dispatched = parent::dispatch();

		if ( is_wp_error( $dispatched ) ) {
			wc_store_credit_log( sprintf( 'Unable to dispatch Store Credit for WooCommerce updater: %s', $dispatched->get_error_message() ), 'error', 'wc_store_credit_db_updates' );
		}
	}

	/**
	 * Handle cron healthcheck.
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public function handle_cron_healthcheck() {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		if ( $this->is_process_running() ) {
			// Background process already running.
			return;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();
			return;
		}

		$this->handle();
	}

	/**
	 * Schedule fallback event.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Is the updater running?
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return bool
	 */
	public function is_updating() {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return false === $this->is_queue_empty();
	}

	/**
	 * Task.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @param string $callback Update callback function.
	 * @return string|bool
	 */
	protected function task( $callback ) {

		include_once WC_STORE_CREDIT_PATH . 'legacy/includes/wc-store-credit-update-functions.php';

		$result = false;

		if ( is_callable( $callback ) ) {
			wc_store_credit_log( sprintf( 'Running %s callback', $callback ), 'info', 'wc_store_credit_db_updates' );
			$result = (bool) call_user_func( $callback );

			if ( $result ) {
				wc_store_credit_log( sprintf( '%s callback needs to run again', $callback ), 'info', 'wc_store_credit_db_updates' );
			} else {
				wc_store_credit_log( sprintf( 'Finished running %s callback', $callback ), 'info', 'wc_store_credit_db_updates' );
			}
		} else {
			wc_store_credit_log( sprintf( 'Could not find %s callback', $callback ), 'notice', 'wc_store_credit_db_updates' );
		}

		return $result ? $callback : false;
	}

	/**
	 * Complete.
	 *
	 * @since 2.4.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	protected function complete() {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		wc_store_credit_log( 'Data update complete', 'info', 'wc_store_credit_db_updates' );

		parent::complete();

		/**
		 * Fires when the plugin updater finished.
		 *
		 * @since 2.4.2
		 */
		do_action( 'wc_store_credit_updater_complete' );
	}

}

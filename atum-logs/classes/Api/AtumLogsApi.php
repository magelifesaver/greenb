<?php
/**
 * The Atum Action Logs' API class
 *
 * @since       0.0.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 */

namespace AtumLogs\Api;

defined( 'ABSPATH' ) || die;

use AtumLogs\Api\Extenders\Logs;


class AtumLogsApi {

	/**
	 * The singleton instance holder
	 *
	 * @var AtumLogsApi
	 */
	private static $instance;

	/**
	 * ProductLevelsApi constructor
	 *
	 * @since 1.3.6
	 */
	private function __construct() {

		// Load the WC API extenders.
		$this->load_extenders();

	}

	/**
	 * Load the ATUM Action Logs API extenders (all those that are extending an existing WC endpoint)
	 *
	 * @since 0.0.1
	 */
	public function load_extenders() {

		Logs::get_instance();

	}


	/****************************
	 * Instance methods
	 ****************************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LOGS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LOGS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return AtumLogsApi instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

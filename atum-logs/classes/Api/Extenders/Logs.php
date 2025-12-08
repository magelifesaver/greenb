<?php
/**
 * Extender for the ATUM tools. Adds the Atum Action Logs' toold to this endpoint.
 *
 * @since       1.3.6
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumLogs\Api
 * @subpackage  Extenders
 */

namespace AtumLogs\Api\Extenders;

defined( 'ABSPATH' ) || die;


class Logs {

	/**
	 * The singleton instance holder
	 *
	 * @var Logs $instance
	 */
	private static $instance;

	/**
	 * Tools constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		add_action( 'rest_api_init', array( $this, 'register_action_logs' ), 0 );

	}

	/**
	 * Hooks for api actions
	 *
	 * @since 1.0.7
	 */
	public function register_action_logs() {

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
	 * @return Logs instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

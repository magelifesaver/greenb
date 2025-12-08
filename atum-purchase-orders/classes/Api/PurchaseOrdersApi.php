<?php
/**
 * The Purchase Orders' API class
 *
 * @since       1.2.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumPO\Api
 */

namespace AtumPO\Api;

defined( 'ABSPATH' ) || die;

use AtumPO\Api\Extenders\PurchaseOrder;


class PurchaseOrdersApi {

	/**
	 * The singleton instance holder
	 *
	 * @var PurchaseOrdersApi
	 */
	private static $instance;

	/**
	 * PurchaseOrdersApi constructor
	 *
	 * @since 1.2.1
	 */
	private function __construct() {

		// Load the WC API extenders.
		$this->load_extenders();

	}

	/**
	 * Load the ATUM Product Levels API extenders (all those that are extending an existing WC endpoint)
	 *
	 * @since 1.2.1
	 */
	public function load_extenders() {

		PurchaseOrder::get_instance();

	}


	/****************************
	 * Instance methods
	 ****************************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return PurchaseOrdersApi instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

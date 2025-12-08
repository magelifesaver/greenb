<?php
/**
 * Extender for the Purchase Orders
 * Adds the PO features to this endpoint.
 *
 * @since       1.2.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumPO\Api
 * @subpackage  Extenders
 */

namespace AtumPO\Api\Extenders;

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\Globals;

class PurchaseOrder {

	/**
	 * The singleton instance holder
	 *
	 * @var PurchaseOrder
	 */
	private static $instance;

	/**
	 * Internal POExtended meta keys
	 *
	 * @var array
	 */
	private $extended_data_keys = [
		'purchaser_country',
		'purchaser_state',
		'has_taxes_enabled',
		'number',
		'ship_via',
		'supplier_code',
		'supplier_currency',
		'supplier_discount',
		'purchaser_address',
		'purchaser_city',
		'purchaser_postal_code',
		'price_num_decimals',
		'exchange_rate',
	];

	/**
	 * PurchaseOrder constructor
	 *
	 * @since 1.2.1
	 */
	private function __construct() {

		// Add PO Pro statuses to valid API statuses.
		add_filter( 'atum/api/atum_orders/statuses', array( $this, 'get_api_statuses' ) );

		// Add extra internal keys.
		add_filter( 'atum/api/atum_purchase_order/rest_data_keys', array( $this, 'get_api_data_keys' ) );

		// Add properties to item schema.
		add_filter( 'atum/api/atum_purchase_order/item_schema', array( $this, 'get_api_item_schema' ) );

	}

	/**
	 * Add PO Pro statuses to valid API statuses
	 *
	 * @since 1.2.1
	 *
	 * @param array $statuses
	 *
	 * @return array
	 */
	public function get_api_statuses( $statuses ) {

		return array_unique( array_merge( $statuses, array_keys( Globals::get_statuses() ) ) );
	}

	/**
	 * Add POExtended properties to internal PO data
	 *
	 * @since 1.2.2
	 *
	 * @param array $keys
	 *
	 * @return array
	 */
	public function get_api_data_keys( $keys ) {
		return array_merge( $keys, $this->extended_data_keys );
	}

	/**
	 * Add POExtended properties to internal PO data
	 *
	 * @since 1.2.2
	 *
	 * @param array $schema
	 *
	 * @return array
	 */
	public function get_api_item_schema( $schema ) {

		$schema['properties']['purchaser_country']     = array(
			'description' => __( 'The country where the purchaser is.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['purchaser_state']       = array(
			'description' => __( 'The state where the purchaser is.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['purchaser_city']        = array(
			'description' => __( 'The city where the purchaser is.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['purchaser_address']     = array(
			'description' => __( 'The address of the purchaser.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['purchaser_postal_code'] = array(
			'description' => __( 'The zip code for the purchaser address.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['has_taxes_enabled']     = array(
			'description' => __( 'Whether the Purchase Order has the taxes enabled or not.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'boolean',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['number']                = array(
			'description' => __( 'The number/name of the Purchase Order.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['ship_via']              = array(
			'description' => __( 'The ship via field value.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['supplier_code']         = array(
			'description' => __( 'The code/id for the supplier.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['supplier_currency']     = array(
			'description' => __( 'The currency used by the supplier.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['supplier_discount']     = array(
			'description' => __( 'The discount to be applied to the items from the supplier.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'number',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['price_num_decimals']    = array(
			'description' => __( 'The amount of decimal positions in the prices.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'number',
			'context'     => array( 'view', 'edit' ),
		);
		$schema['properties']['exchange_rate']         = array(
			'description' => __( 'The exchange rate for the supplier currency.', ATUM_PO_TEXT_DOMAIN ),
			'type'        => 'number',
			'context'     => array( 'view', 'edit' ),
		);

		return $schema;
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
	 * @return PurchaseOrder instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

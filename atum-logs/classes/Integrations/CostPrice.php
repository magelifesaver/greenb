<?php
/**
 * CostPrice + Atum Action Logs integration
 *
 * @since       1.4.6
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumLogs
 * @subpackage  Integrations
 */

namespace AtumLogs\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderModel;
use AtumCost\Models\ExtraCost;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;
use AtumPickPack\PickPackOrders\Exports\PPOExport;
use AtumPickPack\PickPackOrders\Items\PPOItemProduct;
use AtumPickPack\PickPackOrders\Models\PickPackOrder;
use AtumPickPack\PickPackOrders\Models\PPOItem;
use AtumPickPack\PickPackOrders\PickPackOrders;


class CostPrice {

	/**
	 * The singleton instance holder
	 *
	 * @var CostPrice
	 */
	private static $instance;

	/**
	 * PickPack singleton constructor
	 *
	 * @since 1.4.6
	 */
	private function __construct() {

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}

		// Pick Pack texts.
		add_filter( 'atum/logs/is_product_bom', array( $this, 'is_product_bom' ), 10, 2 );

		// Get cost name.
		add_filter( 'atum/logs/get_bom_name', array( $this, 'get_bom_name' ), 10, 2 );

	}

	/**
	 * Register the hooks for the admin side
	 *
	 * @since 1.4.6
	 */
	public function register_admin_hooks() {

	}

	/**
	 * Check if the BOM is a product or an extra cost
	 *
	 * @since 1.4.6
	 *
	 * @param bool  $check
	 * @param array $bom_data
	 *
	 * @return bool
	 */
	public function is_product_bom( $check, $bom_data ) {

		if ( in_array( $bom_data['bom_type'], [ 'extra_cost', 'extra_cost_cat' ] ) ) {
			return FALSE;
		}

		return $check;
	}

	/**
	 * Get the extra cost name
	 *
	 * @since 1.4.6
	 *
	 * @param string $name
	 * @param int    $bom_id
	 *
	 * @return string
	 */
	public function get_bom_name( $name, $bom_id ) {

		if ( ! $bom_id ) {
			return $name;
		}

		$cost = new ExtraCost( $bom_id );
		return $cost->name;
	}


	/********************
	 * Instance methods
	 ********************/

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
	 * @return CostPrice instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

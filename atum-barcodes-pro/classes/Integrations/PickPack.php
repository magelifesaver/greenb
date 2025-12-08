<?php
/**
 * Handle the Pick & Pack customizations for Barcodes PRO.
 *
 * @package     AtumBarcodes\Integrations
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       1.0.5
 */

namespace AtumBarcodes\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumListTables\AtumListTable;
use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Models\Interfaces\AtumProductInterface;
use AtumBarcodes\Inc\Helpers;
use AtumMultiInventory\Models\Inventory;
use AtumMultiInventory\Inc\Helpers as AtumMIHelpers;
use AtumPickPack\PickPackOrders\Items\PPOItemProduct;
use AtumPickPack\PickPackOrders\ListTables\PackingListTable;
use AtumPickPack\PickPackOrders\ListTables\PickingListTable;
use AtumPickPack\PickPackOrders\PickPackOrders;


class PickPack {

	/**
	 * The singleton instance holder
	 *
	 * @var PickPack
	 */
	private static $instance;

	/**
	 * PickPack singleton constructor
	 *
	 * @since 1.0.5
	 */
	private function __construct() {

		if ( is_admin() ) {

			// Add extra columns.
			add_filter( 'atum/pick_pack/picking_list_table/table_columns', array( $this, 'add_barcode_column' ) );
			add_filter( 'atum/pick_pack/packing_list_table/table_columns', array( $this, 'add_barcode_column' ) );

			// Add hidden columns.
			add_filter( 'atum/pick_pack/picking_list_hidden_columns', array( $this, 'add_picking_list_hidden_columns' ) );

			// Barcode column for the picking list.
			add_filter( 'atum/list_table/column_source_object/column_barcode', array( $this, 'picking_barcode_override_class' ), 10, 3 );

			// Add the barcode to the pick-pack reports.
			add_filter( 'atum/pick_pack/views_reports/pick_item_data', array( $this, 'add_barcode_data_to_reports' ), 10, 2 );
			add_action( 'atum/pick_pack/views_reports/after_pick_item_row', array( $this, 'add_barcode_column_to_reports' ), 10, 2 );
			add_action( 'atum/pick_pack/views_reports/after_pick_mi_item_row', array( $this, 'add_mi_barcode_column_to_reports' ), 10, 2 ); /* Multi-Inventory support */
			add_action( 'atum/pick_pack/views_reports/after_picking_list_header', array( $this, 'add_barcode_column_header_to_reports' ) );

		}

	}

	/**
	 * Add the Barcode column to Picking and Packing lists.
	 *
	 * @since 1.0.5
	 *
	 * @param array $table_columns
	 *
	 * @return array
	 */
	public function add_barcode_column( $table_columns ) {

		$columns = array();

		foreach ( $table_columns as $column_name => $column_description ) {
			$columns[ $column_name ] = $column_description;

			if ( 'calc_type' === $column_name ) {
				$columns['barcode'] = __( 'Barcode', ATUM_BARCODES_TEXT_DOMAIN );
			}
		}

		return $columns;
	}

	/**
	 * Add the Barcode hidden column to PickingListTable.
	 *
	 * @since 1.0.5
	 *
	 * @param array $table_columns
	 *
	 * @return array
	 */
	public function add_picking_list_hidden_columns( $table_columns ) {
		return array_merge( $table_columns, [ 'barcode' ] );
	}

	/**
	 * Return this object to retrieve the right column methods for the barcode
	 *
	 * @since 1.0.5
	 *
	 * @param AtumListTable $list_table
	 * @param object        $item
	 *
	 * @return object
	 */
	public function picking_barcode_override_class( $list_table, $item ) {

		if ( $list_table instanceof PickingListTable || $list_table instanceof PackingListTable ) {
			return $this;
		}

		return $list_table;

	}

	/**
	 * Display barcode for items in Picking List.
	 *
	 * @since 1.0.5
	 *
	 * @param \WC_Product|Inventory             $item
	 * @param boolean                           $editable
	 * @param PickingListTable|PackingListTable $list_table
	 *
	 * @return string
	 */
	public function column_barcode( $item, $editable, $list_table ) {

		$output = AtumListTable::EMPTY_COL;

		/* Multi-Inventory support */
		if ( $item instanceof Inventory ) {

			$order_type_id = AtumGlobals::get_order_type_id( PickPackOrders::POST_TYPE );

			foreach ( $list_table->get_pickpack_items() as $ppo_item ) {

				$product_id = $ppo_item->get_variation_id() ?: $ppo_item->get_product_id();

				if ( $product_id === $item->product_id ) {

					$product = AtumHelpers::get_atum_product( $product_id, TRUE );

					if ( $product->is_type( 'variation' ) ) {
						$parent_product = AtumHelpers::get_atum_product( $product->get_parent_id(), TRUE );
						$barcode_type   = $parent_product->get_barcode_type();
					}
					else {
						$barcode_type = $product->get_barcode_type();
					}

					$inventories = Inventory::get_order_item_inventories( $ppo_item->get_id(), $order_type_id );

					foreach ( $inventories as $inventory ) {

						if ( absint( $inventory->inventory_id ) === absint( $item->id ) ) {

							$extra_data = maybe_unserialize( $inventory->extra_data );

							if ( $extra_data && isset( $extra_data['barcode'] ) && $extra_data['barcode'] ) {

								$barcode = $extra_data['barcode'];

								if ( $barcode ) {

									$barcode = Helpers::generate_barcode( $barcode, array(
										'type'      => $barcode_type,
										'show_text' => TRUE,
									) );

								}

								$output = $barcode;

							}

						}

					}

				}

			}

		}
		else {

			$product = $item instanceof PPOItemProduct ? $item->get_product() : $item;
			$item    = AtumHelpers::get_atum_product( $product, TRUE );

			if ( $item instanceof \WC_Product ) {

				if (
					$item->is_type( 'variable' ) ||
					/* Multi-Inventory support */
					( Addons::is_addon_active( 'multi_inventory' ) && AtumMIHelpers::has_multi_inventory( $item ) )
				) {
					return $output;
				}

				$barcode = $item->get_barcode();

				if ( $barcode ) {
					$barcode = Helpers::generate_barcode( $barcode, array(
						'type'      => $item->get_barcode_type(),
						'show_text' => TRUE,
					) );

				}

				$output = $barcode ?: AtumListTable::EMPTY_COL;

			}

		}

		return $output;

	}

	/**
	 * Add the barcode data to the reports.
	 *
	 * @since 1.0.5
	 *
	 * @param array       		   $data
	 * @param AtumProductInterface $product
	 *
	 * @return array
	 */
	public function add_barcode_data_to_reports( $data, $product ) {

		if ( $product instanceof \WC_Product ) {

			if (
				$product->is_type( 'variable' ) ||
				/* Multi-Inventory support */
				( Addons::is_addon_active( 'multi_inventory' ) && AtumMIHelpers::has_multi_inventory( $product ) )
			) {
				return $data;
			}

			$barcode = $product->get_barcode();

			if ( $barcode ) {
				$barcode = Helpers::generate_barcode( $barcode, array(
					'type'      => $product->get_barcode_type(),
					'show_text' => TRUE,
				) );

			}

			$data['barcode'] = $barcode ?: AtumListTable::EMPTY_COL;

		}

		return $data;

	}

	/**
	 * Add the barcode column to the reports.
	 *
	 * @since 1.0.5
	 *
	 * @param array                $item
	 * @param AtumProductInterface $product
	 */
	public function add_barcode_column_to_reports( $item, $product ) {

		if ( ! isset( $item['barcode'] ) ) {
			return;
		}

		echo '<td class="column-_barcode">
				<span>' . $item['barcode'] . '</span>
			</td>';

	}

	/**
	 * Add the barcode column to the Multi-Inventory reports.
	 * Multi-Inventory support.
	 *
	 * @since 1.0.5
	 *
	 * @param array $inventory_data
	 * @param int   $inventory_id
	 */
	public function add_mi_barcode_column_to_reports( $inventory_data, $inventory_id ) {

		$inventory = AtumMIHelpers::get_inventory( $inventory_id );
		$barcode   = AtumListTable::EMPTY_COL;

		if ( $inventory->exists() ) {

			$inv_barcode = $inventory->barcode;
			$product     = AtumHelpers::get_atum_product( $inventory->product_id, TRUE );

			if ( $inv_barcode ) {
				$barcode = Helpers::generate_barcode( $inv_barcode, array(
					'type'      => $product->get_barcode_type(),
					'show_text' => TRUE,
				) );
			}
		}

		echo '<td class="column-_barcode">
				<span>' . $barcode . '</span>
			</td>';

	}

	/**
	 * Add the barcode column header to the reports.
	 *
	 * @since 1.0.5
	 */
	public function add_barcode_column_header_to_reports() {
		echo '<th class="column-_barcode">' . esc_html__( 'Barcode', ATUM_BARCODES_TEXT_DOMAIN ) . '</th>';
	}


	/********************
	 * Instance methods
	 ********************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PICK_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PICK_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return PickPack instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

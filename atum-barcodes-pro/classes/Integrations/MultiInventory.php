<?php
/**
 * Handle the Multi-Inventory integration for Barcodes PRO.
 *
 * @package     AtumBarcodes
 * @subpackage  Integrations
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.1.5
 */

namespace AtumBarcodes\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumHelpGuide;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals as AtumGlobals;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use AtumBarcodes\Inc\Helpers;
use AtumBarcodes\Inc\Hooks;
use AtumMultiInventory\Inc\Helpers as MIHelpers;
use AtumMultiInventory\Models\Inventory;
use Atum\Inc\Helpers as AtumHelpers;
use AtumST\StockTakes\StockTakes;


class MultiInventory {

	/**
	 * The singleton instance holder
	 *
	 * @var MultiInventory
	 */
	private static $instance;

	/**
	 * MultiInventory singleton constructor
	 *
	 * @since 0.1.5
	 */
	private function __construct() {

		if ( is_admin() ) {

			// Add the barcodes to the inventories.
			add_action( 'atum/multi_inventory/after_barcode_field', array( $this, 'add_barcode_to_inventories' ) );

			// Do not add the barcode to variations with MI enabled.
			add_filter( 'atum/barcodes_pro/add_barcode_to_variations', array( $this, 'do_not_add_barcode_to_mi_variations' ), 10, 2 );

			// Return the MI barcodes for all the variations with MI enabled.
			add_filter( 'atum/barcodes_pro/load_variation_barcode', array( $this, 'maybe_load_variation_mi_barcodes' ), 10, 2 );

			// Show the inventories' barcodes on products with MI enabled.
			add_filter( 'atum/load_view/' . ATUM_BARCODES_PATH . 'views/meta-boxes/barcodes', array( $this, 'mi_product_barcodes_meta_box_view' ), 10, 2 );

			// Check whether to bypass the barcode for items with MI enabled within documents.
			add_filter( 'atum/barcodes_pro/bypass_document_item_barcode', array( $this, 'maybe_bypass_document_item_barcode' ), 10, 4 );

			// Show the inventories' barcode images on ATUM list tables.
			if ( 'yes' === AtumHelpers::get_option( 'bp_list_table_barcodes', 'yes' ) ) {
				add_filter( 'atum/multi_inventory/list_tables/args_barcode', array( Hooks::get_instance(), 'barcode_imgs_in_list_tables' ), 10, 2 );
			}

			// Allow searching by inventory barcodes on orders lists.
			add_filter( 'atum/barcodes_pro/orders/matching_orders', array( $this, 'search_orders_by_mi_barcodes' ), 10, 3 );

			// Allow searching by inventory barcodes on Stock Takes list.
			add_filter( 'atum/barcodes_pro/stock_takes/matching_orders', array( $this, 'search_stock_takes_by_mi_barcodes' ), 10, 3 );

			// Add customizations for the Barcodes PRO help guides for the products with MI enabled.
			add_filter( 'atum/help_guides/guide_steps', array( $this, 'add_mi_product_help_guide_steps' ), 10, 2 );

		}

	}

	/**
	 * Add the barcode to the inventories
	 *
	 * @since 0.1.5
	 *
	 * @param Inventory $inventory
	 */
	public function add_barcode_to_inventories( $inventory ) {

		$inv_barcode = $inventory->barcode;

		if ( $inv_barcode ) {

			$product = AtumHelpers::get_atum_product( $inventory->product_id, TRUE );

			if ( $product->is_type( 'variation' ) ) {
				$parent_product = AtumHelpers::get_atum_product( $product->get_parent_id(), TRUE );
				$barcode_type   = $parent_product->get_barcode_type();
			}
			else {
				$barcode_type = $product->get_barcode_type();
			}

			$barcode_img = Helpers::generate_barcode( $inv_barcode, [
				'type'      => $barcode_type,
				'show_text' => FALSE,
			] );

			?>
			<div class="atum-barcode">

				<?php if ( is_wp_error( $barcode_img ) ) : ?>
					<div class="alert alert-danger"><?php echo esc_html( $barcode_img->get_error_message() ?: __( 'Error generating the barcode. Please check if it was entered correctly', ATUM_BARCODES_TEXT_DOMAIN ) ) ?></div>
				<?php elseif ( $barcode_img ) : ?>
					<?php echo $barcode_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>

			</div>
			<?php

		}

	}

	/**
	 * Do not add the barcode to variations with MI enabled
	 *
	 * @since 0.1.5
	 *
	 * @param bool     $add
	 * @param \WP_Post $variation
	 *
	 * @return bool
	 */
	public function do_not_add_barcode_to_mi_variations( $add, $variation ) {

		if (
			MIHelpers::is_product_multi_inventory_compatible( $variation->ID ) &&
			'yes' === MIHelpers::get_product_multi_inventory_status( $variation->ID )
		) {
			$add = FALSE;
		}

		return $add;

	}

	/**
	 * Return the MI barcodes for all the variations with MI enabled
	 *
	 * @since 0.1.5
	 *
	 * @param array       $barcode_data
	 * @param \WC_Product $variation
	 *
	 * @return array
	 */
	public function maybe_load_variation_mi_barcodes( $barcode_data, $variation ) {

		if (
			MIHelpers::is_product_multi_inventory_compatible( $variation ) &&
			'yes' === MIHelpers::get_product_multi_inventory_status( $variation )
		) {

			$inventories        = MIHelpers::get_product_inventories_sorted( $variation->get_id() );
			$variable           = AtumHelpers::get_atum_product( $variation->get_parent_id(), TRUE );
			$barcode_type       = $variable->get_barcode_type();
			$barcode_data['mi'] = [];

			foreach ( $inventories as $inventory ) {

				$barcode = $inventory->barcode;

				if ( $barcode ) {
					$barcode_data['mi'][ $inventory->id ] = array(
						'name'       => $inventory->name,
						'barcode'    => $barcode,
						'barcodeImg' => Helpers::generate_barcode( $barcode, [ 'type' => $barcode_type ] ),
					);
				}

			}

		}

		return $barcode_data;

	}

	/**
	 * Show the inventories' barcodes on products with MI enabled
	 *
	 * @since 0.1.6
	 *
	 * @param string $view
	 * @param array  $args
	 *
	 * @return string
	 */
	public function mi_product_barcodes_meta_box_view( $view, $args ) {

		if ( ! empty( $args['post'] ) && $args['post'] instanceof \WP_Post ) {

			if (
				MIHelpers::is_product_multi_inventory_compatible( $args['post']->ID ) &&
				'yes' === MIHelpers::get_product_multi_inventory_status( $args['post']->ID )
			) {
				return ATUM_BARCODES_PATH . 'views/meta-boxes/mi-barcodes';
			}

		}

		return $view;

	}

	/**
	 * Check whether to bypass the barcode for items with MI enabled within documents.
	 *
	 * @since 0.2.5
	 *
	 * @param bool                                 $bypass
	 * @param \WC_Product                          $product
	 * @param \WC_Order_Item_Product|POItemProduct $item
	 * @param \WC_Order|PurchaseOrder              $order
	 *
	 * @return bool
	 */
	public function maybe_bypass_document_item_barcode( $bypass, $product, $item, $order ) {

		if (
			MIHelpers::is_product_multi_inventory_compatible( $product->get_id() ) &&
			'yes' === MIHelpers::get_product_multi_inventory_status( $product->get_id() )
		) {

			$bypass          = TRUE;
			$type            = $order instanceof AtumOrderModel ? $order->get_post_type() : $order->get_type();
			$inventory_items = Inventory::get_order_item_inventories( $item->get_id(), AtumGlobals::get_order_type_id( $type ) );
			$inv_barcodes    = [];

			foreach ( $inventory_items as $inventory_item ) {

				$inventory = MIHelpers::get_inventory( $inventory_item->inventory_id );

				if ( $inventory->barcode && ! in_array( $inventory->barcode, $inv_barcodes ) ) {
					$inv_barcodes[] = $inventory->barcode;
				}

			}

			if ( ! empty( $inv_barcodes ) ) {

				$product = AtumHelpers::get_atum_product( $product, TRUE );

				foreach ( $inv_barcodes as $inv_barcode ) {

					$barcode      = $inv_barcode;
					$barcode_type = $product->get_barcode_type();

					$barcode_img = Helpers::generate_barcode( $barcode, apply_filters( 'atum/barcodes_pro/document_item_barcode_options', [ 'type' => $barcode_type ], $item, $order ) );

					if ( $barcode_img && ! is_wp_error( $barcode_img ) ) {
						echo '<br>' . $barcode_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

				}

			}

		}

		return $bypass;

	}

	/**
	 * Allow searching by inventory barcodes on orders lists.
	 *
	 * @since 1.0.0
	 *
	 * @param int[]  $matching_orders
	 * @param string $barcode
	 * @param int    $order_type_id
	 *
	 * @return int[]
	 */
	public function search_orders_by_mi_barcodes( $matching_orders, $barcode, $order_type_id ) {

		if ( $barcode ) {

			global $wpdb;

			$inventory_meta_table   = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;
			$inventory_orders_table = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;
			$order_items_table      = $order_type_id === AtumGlobals::get_order_type_id() ? 'woocommerce_order_items' : AtumOrderPostType::ORDER_ITEMS_TABLE;

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$mi_barcodes_orders = $wpdb->get_col( $wpdb->prepare( "
				SELECT aoi.order_id FROM {$wpdb->prefix}{$order_items_table} aoi 
				LEFT JOIN $inventory_orders_table aio ON (aoi.order_item_id = aio.order_item_id AND aio.order_type = %d)
				LEFT JOIN $inventory_meta_table aim ON (aio.inventory_id = aim.inventory_id)
				WHERE aim.barcode = %s
			", $order_type_id, $barcode ) );
			// phpcs:enable

			$matching_orders = array_unique( array_merge( $matching_orders, $mi_barcodes_orders ) );

		}

		return $matching_orders;

	}

	/**
	 * Allow searching by inventory barcodes on Stock Takes list.
	 *
	 * @since 1.0.0
	 *
	 * @param int[]  $matching_orders
	 * @param string $barcode
	 * @param int    $order_type_id
	 *
	 * @return int[]
	 */
	public function search_stock_takes_by_mi_barcodes( $matching_orders, $barcode, $order_type_id ) {

		if ( $barcode ) {

			global $wpdb;

			$st_table             = $wpdb->prefix . StockTakes::STOCK_TAKES_TABLE;
			$inventory_meta_table = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;
			$barcode_like_term    = "%$barcode%";

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$mi_barcodes_orders = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT st.st_id 
				FROM $st_table st		
				WHERE ( st.item_id IN (
					SELECT inventory_id FROM $inventory_meta_table WHERE barcode LIKE %s
				)
				OR st.st_id LIKE %s ) AND st.is_inventory = 1",
				$barcode_like_term, $barcode_like_term
			) );
			// phpcs:enable

			$matching_orders = array_unique( array_merge( $matching_orders, $mi_barcodes_orders ) );

		}

		return $matching_orders;

	}

	/**
	 * Add customizations for the Barcodes PRO help guides for the products with MI enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $guide_steps
	 * @param string $guide
	 *
	 * @return array
	 */
	public function add_mi_product_help_guide_steps( $guide_steps, $guide ) {

		global $post;

		// Avoid entering here again.
		remove_action( 'atum/help_guides/guide_steps', array( $this, 'add_mi_product_help_guide_steps' ) );

		if ( $post instanceof \WP_Post && 'product' === $post->post_type ) {

			if ( str_contains( $guide, 'atum-barcodes-pro/help-guides/products.json' ) && MIHelpers::get_product_multi_inventory_status( $post->ID ) === 'yes' ) {
				$atum_help_guide = AtumHelpGuide::get_instance();
				$guide_steps     = $atum_help_guide->get_guide_steps( ATUM_BARCODES_PATH . 'help-guides/products-mi.json' );
			}
			elseif ( str_contains( $guide, 'atum-barcodes-pro/help-guides/products-variable.json' ) ) {
				$atum_help_guide = AtumHelpGuide::get_instance();
				$guide_steps     = $atum_help_guide->get_guide_steps( ATUM_BARCODES_PATH . 'help-guides/products-variable-mi.json' );
			}

		}

		return $guide_steps;

	}


	/********************
	 * Instance methods
	 ********************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_BARCODES_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_BARCODES_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return MultiInventory instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

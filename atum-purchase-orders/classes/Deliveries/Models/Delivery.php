<?php
/**
 * The model class for the Delivery objects
 *
 * @package         AtumPO\Deliveries
 * @subpackage      Models
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.1
 */

namespace AtumPO\Deliveries\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Abstracts\AtumPOOrders\Models\AtumPOOrder;
use AtumPO\Deliveries\Deliveries;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Deliveries\Items\DeliveryItemProductInventory;
use AtumPO\Integrations\MultiInventory;
use AtumPO\Models\POExtended;


/**
 * Class Delivery
 *
 * Meta-props available through the __get magic method:
 *
 * @property int    $document_number
 * @property array  $files
 * @property string $name
 * @property int    $po
 */
class Delivery extends AtumPOOrder {

	/**
	 * Quantities expected for every PO item
	 *
	 * @var array
	 */
	private static $expected_qtys = array();

	/**
	 * Quantities already delivered in other deliveries for every PO item
	 *
	 * @var array
	 */
	private static $already_in_qtys = array();

	/**
	 * Quantities delivered on the actual deliveru for every PO item
	 *
	 * @var array
	 */
	private static $delivered_qtys = array();

	/**
	 * Quantities pending to be delivered for every PO item
	 *
	 * @var array
	 */
	private static $pending_qtys = array();

	/**
	 * Quantities of delivery items linked to missing PO items
	 *
	 * @var array
	 */
	private static $missing_items_qtys = array();

	/**
	 * Array to store the metadata to add/update
	 * NOTE: We are just replacing the parent meta instead of extending them because we don't need some meta.
	 *
	 * @var array
	 */
	protected $meta = [
		'name'            => '',
		'status'          => 'publish', // We don't need any custom status here.
		'date_created'    => '',
		'document_number' => '',
		'total'           => NULL,
		'po'              => NULL,
		'files'           => [],
	];

	/**
	 * The default line item type
	 *
	 * @var string
	 */
	protected $line_item_type = 'delivery_item';

	/**
	 * The default line item group
	 *
	 * @var string
	 */
	protected $line_item_group = 'delivery_items';

	/**
	 * Save the Delivery data to the database
	 *
	 * @since 0.9.1
	 *
	 * @param bool $including_meta Optional. Whether to save the meta too.
	 *
	 * @return int|\WP_Error order ID or an error
	 */
	public function save( $including_meta = TRUE ) {

		if ( ! $this->po ) {
			return new \WP_Error( 'invalid_po', __( 'The Delivery cannot be saved without a parent PO', ATUM_PO_TEXT_DOMAIN ) );
		}

		return parent::save( $including_meta );

	}

	/**
	 * Perform actions after saving a delivery.
	 *
	 * @since 0.9.1
	 *
	 * @param string $action
	 */
	public function after_save( $action ) {

		// Set the post_parent and auto-title after saving.
		if ( 'create' === $action ) {

			// When setting a delivery name automatically, we must wait until the post is saved to get its ID.
			if ( ! $this->name ) {

				$data         = [];
				$where        = [ 'ID' => $this->id ];
				$data_format  = [ '%d' ];
				$where_format = [ '%d' ];

				/* translators: the delivery ID */
				$auto_title         = sprintf( __( 'Delivery %d', ATUM_PO_TEXT_DOMAIN ), $this->get_next_delivery_number() );
				$data['post_title'] = $auto_title;
				$data_format[]      = '%s';
				$data['post_name']  = sanitize_title( $auto_title . '-' . time() ); // Unique slug.
				$data_format[]      = '%s';

				global $wpdb;
				$wpdb->update( $wpdb->posts, $data, $where, $data_format, $where_format );

			}

			parent::after_save( $action );

		}

	}

	/**
	 * Add a product line item to the Delivery
	 *
	 * @since 0.9.1
	 *
	 * @param  \WC_Product $product
	 * @param  int|float   $qty
	 * @param  array       $props
	 *
	 * @return DeliveryItemProduct The product item added to the delivery
	 */
	public function add_product( $product, $qty = NULL, $props = array() ) {

		if ( $product instanceof \WC_Product ) {

			$default_args = array(
				'name'         => $product->get_name(),
				'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
				'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
				'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
				'quantity'     => $qty,
			);

		}
		else {

			$default_args = array(
				'quantity' => $qty,
			);

		}

		$props      = wp_parse_args( $props, $default_args );
		$item_class = $this->get_items_class( $this->line_item_group );

		/**
		 * Variable definition
		 *
		 * @var DeliveryItemProduct $item
		 */
		$item = new $item_class();
		$item->set_props( $props );
		$item->set_atum_order_id( $this->id );
		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/**
	 * Delete the delivery
	 *
	 * @since 0.9.5
	 *
	 * @param bool $force_delete
	 */
	public function delete( $force_delete = FALSE ) {

		$delivery_items = $this->get_items();

		// Remove each item separately, so we can restore the stock for all of them (if necessary).
		foreach ( $delivery_items as $delivery_item ) {
			$this->remove_item( $delivery_item->get_id() );
		}

		// The deliveries have no trash, so they must be deleted permanently.
		parent::delete( TRUE );

	}

	/**
	 * Remove item from this delivery
	 *
	 * @since 0.9.21
	 *
	 * @param int $item_id
	 */
	public function remove_item( $item_id ) {

		$delivery_item = $this->get_atum_order_item( $item_id );

		if ( ! $delivery_item || ! $this->get_items_key( $delivery_item ) ) {
			return;
		}

		// If this item's stock was increased previously, undo the change.
		if ( 'yes' === $delivery_item->get_stock_changed() ) {
			$this->change_product_stock( $delivery_item, $delivery_item->get_quantity(), 'decrease' );
		}

		do_action( 'atum/purchase_orders_pro/delivery/after_restock_delivery_item', $this, $delivery_item, 'decrease' );

		$po_item_id         = $delivery_item->get_po_item_id();
		$delivery_item_type = $delivery_item->get_type();
		$product_id         = $delivery_item->get_product_id();
		$inventory_id       = 'delivery_item_inventory' === $delivery_item_type ? $delivery_item->get_inventory_id() : NULL;

		// Just continue deleting the item in the default way.
		parent::remove_item( $item_id );

		do_action( 'atum/purchase_orders_pro/delivery/after_remove_item', $this, $po_item_id, $delivery_item_type, $product_id, $inventory_id );

	}

	/**
	 * Change the stock for any delivery item
	 *
	 * @since 0.9.5
	 *
	 * @param DeliveryItemProduct $delivery_item  The delivery item to change.
	 * @param int|float           $quantity       The quantity amount to change.
	 * @param string              $action         Possible values: 'increase', 'decrease', 'set'.
	 */
	public function change_product_stock( $delivery_item, $quantity, $action ) {

		$product = $delivery_item->get_product();

		do_action( 'atum/purchase_orders_pro/delivery/before_stock_change', $delivery_item, $quantity, $action, $this );

		if ( apply_filters( 'atum/purchase_orders_pro/delivery/allow_change_stock', TRUE, $product ) ) {

			$returned_items = $delivery_item->get_returned_qty();
			$old_stock      = $product->get_stock_quantity();
			$po 		    = $this->get_po_object();

			// Increasing is the normal behavior, so only set the stock_changed flag for this action.
			if ( 'increase' === $action ) {

				$delivery_item->set_stock_changed( TRUE );

				// Restore the returned items (if any).
				$delivery_item->set_returned_qty( $returned_items <= $quantity ? 0 : ( $returned_items - $quantity ) );
				$delivery_item->save();

			}
			// Decreasing is the restoring behavior, so disable the stock_changed flag for this action.
			elseif ( 'decrease' === $action && 'yes' === $delivery_item->get_stock_changed() ) {

				$delivery_item_qty = $delivery_item->get_quantity();
				$returned_items   += $quantity;
				$delivery_item->set_returned_qty( $returned_items );

				if ( $delivery_item_qty <= $returned_items ) {
					$delivery_item->set_stock_changed( FALSE );
					//$quantity = $delivery_item->get_quantity() - $returned_items; // Only discount the remaining items.
				}

				$delivery_item->save();

			}

			$new_stock = wc_update_product_stock( $product, $quantity, $action );

			// Add the PO order note.
			if ( 'increase' === $action ) {
				$note = __( 'Stock levels increased:', ATUM_PO_TEXT_DOMAIN );
			}
			else {
				$note = __( 'Stock levels reduced:', ATUM_PO_TEXT_DOMAIN );
			}

			$note .= ' ' . $delivery_item->get_name() . ' ' . $old_stock . '&rarr;' . $new_stock;

			$note = apply_filters( 'atum/atum_order/add_stock_change_note', $note, $product, $action, $quantity ); // Using the original ATUM hook.

			$note_id = $po->add_order_note( $note );

			// Only inventory logs should execute this function.
			AtumHelpers::save_order_note_meta( $note_id, [
				'action'        => "{$action}_stock",
				'item_name'     => $delivery_item->get_name(),
				'product_id'    => $product->get_id(),
				'old_stock'     => $old_stock,
				'new_stock'     => $new_stock,
				'stock_change'  => $quantity,
				'order_type'    => 3,
				'order_item_id' => $delivery_item->get_po_item_id(),
			] );

		}

		do_action( 'atum/purchase_orders_pro/delivery/after_stock_change', $delivery_item, $quantity, $action, $this );

	}


	/*********
	 * GETTERS
	 *********/

	/**
	 * Get the title for the Delivery post
	 *
	 * @since 0.9.1
	 *
	 * @return string
	 */
	public function get_title() {

		if ( $this->name ) {
			$title = $this->name;
		}
		elseif ( ! empty( $this->post->post_title ) ) {
			$title = $this->post->post_title;
		}
		else {

			/* translators: the delivery ID */
			$title = sprintf( __( 'Delivery %d', ATUM_PO_TEXT_DOMAIN ), $this->get_next_delivery_number() );

		}

		return apply_filters( 'atum/purchase_orders_pro/delivery_title', $title );

	}

	/**
	 * Get the the next delivery number to use in the current PO and auto-increment it later (optionally).
	 *
	 * @since 0.9.21
	 *
	 * @param bool $save_next
	 *
	 * @return int
	 */
	private function get_next_delivery_number( $save_next = TRUE ) {

		$po                   = $this->get_po_object();
		$next_delivery_number = $po->deliveries_counter;

		// Auto-increment and save.
		if ( $save_next ) {
			$po->set_deliveries_counter( $next_delivery_number + 1 );
			$po->save_meta();
		}

		return $next_delivery_number;

	}
	
	/**
	 * Get the Delivery's post type
	 *
	 * @since 0.9.1
	 *
	 * @return string
	 */
	public function get_post_type() {
		return Deliveries::POST_TYPE;
	}

	/**
	 * Get a delivery item
	 *
	 * @since 0.9.1
	 *
	 * @param \WC_Order_Item|object|int $item
	 *
	 * @return \WC_Order_Item|DeliveryItemProduct|DeliveryItemProductInventory|false
	 */
	public function get_atum_order_item( $item = NULL ) {

		if ( $item instanceof \WC_Order_Item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item $item
			 */
			$item_type = $item->get_type();
			$id        = $item->get_id();
		}
		elseif ( is_object( $item ) && ! empty( $item->order_item_type ) ) {
			$id        = $item->order_item_id;
			$item_type = $item->order_item_type;
		}
		elseif ( is_numeric( $item ) && ! empty( $this->items ) ) {

			$id = $item;

			foreach ( $this->items as $group => $group_items ) {

				foreach ( $group_items as $item_id => $stored_item ) {
					// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					if ( $id == $item_id ) {
						$item_type = $this->group_to_type( $group );
						break 2;
					}
				}

			}

		}
		else {
			$id = $item_type = FALSE;
		}

		if ( $id && isset( $item_type ) && $item_type ) {

			$classname = apply_filters( 'atum/purchase_orders_pro/delivery/item_class_name', '\\AtumPO\\Deliveries\\Items\\DeliveryItemProduct', $id, $item_type );

			if ( $classname && class_exists( $classname ) ) {

				try {
					return new $classname( $id );
				} catch ( \Exception $e ) {
					return FALSE;
				}

			}

		}

		return FALSE;

	}

	/**
	 * Get key for where a certain item type is stored in items prop
	 *
	 * @since 0.9.1
	 *
	 * @param \WC_Order_Item $item  Delivery item object.
	 *
	 * @return string
	 */
	protected function get_items_key( $item ) {
		return apply_filters( 'atum/purchase_orders_pro/delivery/get_items_key', $this->line_item_group, $item ); // We only use products and inventories on deliveries.
	}

	/**
	 * This method is the inverse of the get_items_key method
	 * Gets the ATUM Order item's class given its key
	 *
	 * @since 0.9.1
	 *
	 * @param string $items_key The items key.
	 *
	 * @return string
	 */
	protected function get_items_class( $items_key ) {
		return apply_filters( 'atum/purchase_orders_pro/delivery/get_items_class', '\\AtumPO\\Deliveries\\Items\\DeliveryItemProduct', $items_key ); // We only have one kind of item here.
	}

	/**
	 * Convert a type to a types group
	 *
	 * @since 0.9.2
	 *
	 * @param string $type
	 *
	 * @return string group
	 */
	protected function type_to_group( $type ) {

		$type_to_group = (array) apply_filters( 'atum/purchase_orders_pro/delivery/item_type_to_group', array(
			$this->line_item_type => $this->line_item_group,
		) );

		return $type_to_group[ $type ] ?? '';

	}

	/**
	 * Convert a type of group to a type
	 *
	 * @since 0.9.4
	 *
	 * @param string $group
	 *
	 * @return string Type
	 */
	protected function group_to_type( $group ) {

		$group_to_type = (array) apply_filters( 'atum/purchase_orders_pro/delivery/item_group_to_type', array(
			$this->line_item_group => $this->line_item_type,
		) );

		return $group_to_type[ $group ] ?? '';

	}

	/**
	 * Getter to collect all the Delivery data within an array
	 *
	 * @since 0.9.1
	 *
	 * @return array
	 */
	public function get_data() {

		return array(
			'id'              => $this->id,
			'name'            => $this->get_title(),
			'date_created'    => wc_string_to_datetime( $this->date_created ),
			'document_number' => $this->document_number,
			'files'           => $this->files,
			'total'           => $this->total,
			'items'           => $this->get_items(),
		);

	}

	/**
	 * Calculate the delivery items qtys for the table figures
	 *
	 * @since 0.9.2
	 *
	 * @param DeliveryItemProduct[]|DeliveryItemProductInventory[] $delivery_items
	 * @param POExtended                                           $po
	 * @param int                                                  $delivery_id
	 *
	 * @return array
	 */
	public static function calculate_delivery_items_qtys( $delivery_items, $po, $delivery_id = 0 ) {

		if ( empty( $delivery_items ) ) {
			return [];
		}

		$cache_key           = AtumCache::get_cache_key( 'calc_delivey_items_qtys', [ $delivery_id ?: $delivery_items, $po->get_id() ] );
		$delivery_items_qtys = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $delivery_items_qtys;
		}

		$delivered_total = $already_in_total = $pending_total = $missing_total = array(
			'delivery_item'           => 0,
			'delivery_item_inventory' => 0, // Multi-Inventory compatibility.
		);

		foreach ( $delivery_items as $delivery_item ) {

			$po_item_id         = $delivery_item->get_po_item_id();
			$delivery_item_id   = $delivery_item->get_id();
			$delivery_item_type = $delivery_item->get_type();

			// Get expected quantities.
			self::$expected_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ] = $delivery_item->get_expected_qty();

			$expected_qtys = self::$expected_qtys;

			// Get already in quantities.
			self::$already_in_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ] = self::get_delivered_items_sum( $delivery_item );

			$already_in_qtys = self::$already_in_qtys;

			$already_in_total[ $delivery_item_type ] += $already_in_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ];

			// Get missing quantities (delivery items linked to missing PO items).
			if ( ! $po->get_item( $po_item_id ) ) {

				self::$missing_items_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ] = $delivery_item->get_quantity();

				$missing_qtys                          = self::$missing_items_qtys;
				$missing_total[ $delivery_item_type ] += $missing_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ];
				$delivered_qtys                        = self::$delivered_qtys;

			}
			// Get delivered quantities.
			else {

				self::$delivered_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ] = $delivery_item->get_quantity();

				$delivered_qtys                          = self::$delivered_qtys;
				$delivered_total[ $delivery_item_type ] += $delivered_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ];
				$missing_qtys                            = self::$missing_items_qtys;

			}

			// Get pending quantities.
			self::$pending_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ] = $expected_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ] - self::get_delivered_items_sum( $delivery_item, TRUE );

			$pending_qtys = self::$pending_qtys;

			$pending_total[ $delivery_item_type ] += $pending_qtys[ $delivery_item_type ][ $po_item_id ][ $delivery_item_id ];

		}

		$delivery_items_qtys = compact(
			'expected_qtys',
			'already_in_qtys',
			'delivered_qtys',
			'pending_qtys',
			'delivered_total',
			'already_in_total',
			'pending_total',
			'missing_qtys',
			'missing_total'
		);

		AtumCache::set_cache( $cache_key, $delivery_items_qtys, ATUM_PO_TEXT_DOMAIN );

		return $delivery_items_qtys;

	}

	/**
	 * Calculate the sum of delivered items
	 *
	 * @since 0.9.4
	 *
	 * @param DeliveryItemProduct|DeliveryItemProductInventory $delivery_item The delivery item.
	 * @param bool                                             $count_current Optional. Only needed for delivery item inventories.
	 *
	 * @return float|int
	 */
	private static function get_delivered_items_sum( $delivery_item, $count_current = FALSE ) {

		$delivery_item_type = $delivery_item->get_type();
		$po_item_id         = $delivery_item->get_po_item_id();

		// Regular delivery item.
		if ( 'delivery_item' === $delivery_item_type ) {

			return isset( self::$delivered_qtys[ $delivery_item_type ], self::$delivered_qtys[ $delivery_item_type ][ $po_item_id ] ) ?
				array_sum( self::$delivered_qtys[ $delivery_item_type ][ $po_item_id ] ) : 0;

		}
		/* Multi-Inventory compatibility */
		elseif ( 'delivery_item_inventory' === $delivery_item_type ) {

			/**
			 * Variable definition
			 *
			 * @var Delivery $delivery
			 */
			$delivery = $delivery_item->get_order();

			if ( $delivery->po ) {

				$delivery_inventory_items_qty_sum = MultiInventory::get_po_delivery_inventory_items_qty_sum( $delivery->po );

				// Find the delivered inventory items matching the current delivery item's inventory_id.
				$inventory_id = $delivery_item->get_inventory_id();

				if ( ! isset( $delivery_inventory_items_qty_sum ) ) {
					return 0;
				}

				return $count_current ? $delivery_inventory_items_qty_sum[ $inventory_id ] : $delivery_inventory_items_qty_sum[ $inventory_id ] - $delivery_item->get_quantity();

			}

		}

		return 0;

	}

	/*********
	 * SETTERS
	 *********/

	/**
	 * Setter for the Delivery's name
	 *
	 * @since 0.9.1
	 *
	 * @param string $name
	 * @param bool   $skip_change
	 */
	public function set_name( $name, $skip_change = FALSE ) {

		$name = sanitize_text_field( $name );

		if ( $this->name !== $name ) {

			if ( ! $skip_change ) {
				$this->register_change( 'name' );
			}

			$this->set_meta( 'name', $name );

		}

	}

}

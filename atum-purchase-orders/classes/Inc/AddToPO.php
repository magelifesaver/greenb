<?php
/**
 * Add items in bulk to a new PO
 *
 * @package     AtumPO\Inc
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.9.9
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || exit;

use Atum\Addons\Addons;
use Atum\Components\AtumListTables\AtumListTable;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Inc\Globals as AtumGlobals;
use Atum\StockCentral\StockCentral;
use Atum\Suppliers\Supplier;
use AtumLevels\ManufacturingCentral\ManufacturingCentral;
use AtumPO\Models\POExtended;

class AddToPO {

	/**
	 * The singleton instance holder
	 *
	 * @var AddToPO
	 */
	private static $instance;

	/**
	 * AddToPO singleton constructor.
	 *
	 * @since 0.9.9
	 */
	private function __construct() {

		// Add bulk actions to SC.
		add_filter( 'atum/list_table/bulk_actions', array( $this, 'add_list_table_bulk_actions' ), 10, 2 );

		// Enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Retrieve the items table for the modal.
		add_action( 'wp_ajax_atum_po_get_add_to_po_items', array( $this, 'get_modal_items' ) );

		// Add the selected items to PO.
		add_action( 'wp_ajax_atum_po_add_items_to_po', array( $this, 'add_items_to_po' ) );

		// Add a bulk action to the orders list table and process the action.
		if ( AtumHelpers::is_using_cot_list() ){
			add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_wc_orders_bulk_action' ) );
			add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_create_pos_bulk_action' ), 10, 3 );
		}
		else {
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_wc_orders_bulk_action' ) );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_create_pos_bulk_action' ), 10, 3 );
		}

		// Show and admin notice after the POS have been created through the WC Orders bulk action.
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );

	}

	/**
	 * Add bulk actions to list tables
	 *
	 * @since 0.9.8
	 *
	 * @param array         $bulk_actions
	 * @param AtumListTable $list_table
	 */
	public function add_list_table_bulk_actions( $bulk_actions, $list_table ) {

		$bulk_actions['add_to_po'] = __( 'Add to PO', ATUM_PO_TEXT_DOMAIN );

		return $bulk_actions;

	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 0.9.9
	 *
	 * @param string $hook
	 */
	public function enqueue_admin_scripts( $hook ) {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// WooCommerce order edit or SC or MC pages.
		if (
			str_contains( $screen_id, StockCentral::UI_SLUG ) ||
			( Addons::is_addon_active( 'product_levels' ) && str_contains( $screen_id, ManufacturingCentral::UI_SLUG ) )
		) {

			wp_enqueue_style( 'atum-po-add-to-po', ATUM_PO_URL . 'assets/css/atum-po-add-to-po.css', [ 'atum-list' ], ATUM_PO_VERSION );

			wp_register_script( 'atum-po-add-to-po', ATUM_PO_URL . 'assets/js/build/atum-po-add-to-po.js', [ 'atum-list' ], ATUM_PO_VERSION, TRUE );

			wp_localize_script( 'atum-po-add-to-po', 'atumAddToPOVars', array(
				'addItems'                => __( 'Add items to Purchase Order(s)', ATUM_PO_TEXT_DOMAIN ),
				'addToPONonce'            => wp_create_nonce( 'atum-po-add-to-po' ),
				'createPO'                => __( 'Create PO', ATUM_PO_TEXT_DOMAIN ),
				'createPOs'               => __( 'Create POs', ATUM_PO_TEXT_DOMAIN ),
				'noProductsFound'         => __( 'No products found with the specified criteria', ATUM_PO_TEXT_DOMAIN ),
				'minimumStockToAdd'       => Helpers::get_minimum_quantity_to_add(),
				'noSupplierProductsFound' => __( 'No products found with the specified criteria for this supplier', ATUM_PO_TEXT_DOMAIN ),
				'ok'                      => __( 'OK', ATUM_PO_TEXT_DOMAIN ),
				'poCreated'               => __( 'PO created successfully', ATUM_PO_TEXT_DOMAIN ),
				'posCreated'              => __( 'POs created successfully', ATUM_PO_TEXT_DOMAIN ),
				'supplier'                => __( 'Supplier', ATUM_PO_TEXT_DOMAIN ),
			) );

			wp_enqueue_script( 'atum-po-add-to-po' );

		}

	}

	/**
	 * Prepare the items table for the modal
	 *
	 * @since 0.9.9
	 */
	public function get_modal_items() {

		check_ajax_referer( 'atum-po-add-to-po', 'security' );

		if ( empty( $_POST['items'] ) || ! is_array( $_POST['items'] ) ) {
			wp_send_json_error( __( 'Please, select the items you want to add to the PO', ATUM_PO_TEXT_DOMAIN ) );
		}

		$posted_items = $_POST['items'];
		$items        = $added_items = [];

		foreach ( $posted_items as $item_id_str ) {

			$item_id = absint( $item_id_str );
			$product = AtumHelpers::get_atum_product( $item_id );

			// TODO: WE SHOULD EXCLUDE INHERITABLES THAT DO NOT APPLY BUT WHAT IF SOME VARIABLES ARE MANAGING THE STOCK AT PARENT LEVEL?
			if (
				! in_array( $item_id, $added_items ) && $product instanceof \WC_Product &&
				! in_array( $product->get_type(), AtumGlobals::get_inheritable_product_types() )
			) {
				$items[]       = $product;
				$added_items[] = $item_id;
			}

			if ( ! is_numeric( $item_id_str ) ) {
				// Allow handling the coming item externally (from add-ons).
				$items = apply_filters( 'atum/purchase_orders_pro/get_add_to_po_items', $items, $item_id_str ); // We must pass the $_POST variable here because we plan to alter it later.
			}

		}

		$allow_adding = TRUE;

		wp_send_json_success( AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/add-to-po/add-to-po-modal', compact( 'items', 'allow_adding' ) ) );

	}

	/**
	 * Add items to PO from the modal
	 *
	 * @since 0.9.9
	 */
	public function add_items_to_po() {

		check_ajax_referer( 'atum-po-add-to-po', 'security' );

		if ( empty( $_POST['items'] ) || ! is_array( $_POST['items'] ) || empty( $_POST['form_data'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		parse_str( $_POST['form_data'], $form_data );
		$multiple_pos = isset( $form_data['multiple_pos'] ) && 'yes' === $form_data['multiple_pos'];
		$pos_messages = [];

		// Create a single PO.
		if ( ! $multiple_pos ) {

			$po             = $this->create_po( $_POST['items'], $form_data['qty'] );
			$po_id          = $po->get_id();
			$pos_messages[] = '<a href="' . get_edit_post_link( $po_id ) . '" target="_blank">' . $po_id . '</a>';

		}
		// Create multiple POs (one per supplier).
		else {

			$supplier_items = [];

			foreach ( $_POST['items'] as $id ) {

				if ( ! is_numeric( $id ) ) {
					$supplier_items = apply_filters( 'atum/purchase_orders_pro/add_items_to_po/supplier_items', $supplier_items, $id );
					continue;
				}

				$product = AtumHelpers::get_atum_product( absint( $id ) );

				if ( ! $product instanceof \WC_Product ) {
					continue;
				}

				$supplier_id                                = $product->get_supplier_id();
				$supplier_items[ absint( $supplier_id ) ][] = $id;

			}

			// Create one PO per supplier from the multidimensional array.
			foreach ( $supplier_items as $items ) {
				$po             = $this->create_po( $items, $form_data['qty'] );
				$po_id          = $po->get_id();
				$pos_messages[] = '<a href="' . get_edit_post_link( $po_id ) . '" target="_blank">' . $po_id . '</a>';
			}

		}

		wp_send_json_success( sprintf(
			/* translators: PO IDs */
			_n( 'New purchase order (%s) created using the selected items.', 'New POs created using the selected items: %s', count( $pos_messages ), ATUM_PO_TEXT_DOMAIN ),
			implode( ', ', $pos_messages )
		) );

	}

	/**
	 * Create a new PO programmatically for the passed items
	 *
	 * @since 0.9.9
	 *
	 * @param int[]         $items
	 * @param float[]|int[] $qtys
	 *
	 * @return POExtended
	 */
	private function create_po( $items, $qtys ) {

		$po        = new POExtended();
		$suppliers = $products = [];

		// We need to set the supplier's data first so the taxes and discount are used when adding the products thereafter.
		foreach ( $items as $id ) {

			if ( ! is_numeric( $id ) ) {
				continue;
			}

			$product = AtumHelpers::get_atum_product( absint( $id ) );

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$suppliers[] = $product->get_supplier_id();

			// Store the valid products, so we can add them to the PO later.
			if ( isset( $qtys[ $id ] ) ) {
				$products[] = array(
					'product' => $product,
					'qty'     => $qtys[ $id ],
				);
			}

		}

		// If all the items belong to the same supplier, add all the supplier info.
		$suppliers = array_unique( array_filter( $suppliers ) );

		if ( count( $suppliers ) === 1 ) {
			$supplier = new Supplier( current( $suppliers ) );
			$po->set_supplier( $supplier->id );
			$po->set_supplier_code( $supplier->code );
			$po->set_supplier_discount( $supplier->discount );
			$po->set_supplier_tax_rate( $supplier->tax_rate );
			$po->set_supplier_currency( $supplier->currency );
			$po->set_description( $supplier->description );
		}

		// Add the products.
		foreach ( $products as $product_data ) {

			$qty = (float) $product_data['qty'];

			if ( $qty > 0 ) {
				$po->add_product( $product_data['product'], $qty );
			}

		}

		$po->set_date_created( AtumHelpers::get_wc_time( AtumHelpers::get_current_timestamp() ) );
		$po->save();
		$po = apply_filters( 'atum/purchase_orders_pro/bulk_add_to_po', $po, $items, $qtys );
		$po->calculate_totals();

		return $po;

	}

	/**
	 * Add a bulk action to the WC orders list table
	 *
	 * @since 0.9.10
	 *
	 * @param string[] $actions
	 *
	 * @return string[]
	 */
	public function add_wc_orders_bulk_action( $actions ) {

		$actions['atum_create_pos'] = __( '[ATUM] Create POs from orders', ATUM_PO_TEXT_DOMAIN );

		return $actions;
	}

	/**
	 * Handle the WC Orders bulk action to create POs
	 *
	 * @since 0.9.10
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of IDs.
	 *
	 * @return string
	 */
	public function handle_create_pos_bulk_action( $redirect_to, $action, $ids ) {

		if ( 'atum_create_pos' === $action ) {

			$ids     = array_reverse( array_map( 'absint', $ids ) );
			$created = $this->create_pos_from_orders( $ids );

			$redirect_to = add_query_arg(
				array(
					'bulk_action' => 'atum_pos_created',
					'created'     => $created,
					'ids'         => join( ',', $ids ),
				),
				$redirect_to
			);

		}

		return $redirect_to;

	}

	/**
	 * Fix the prices for the specified orders
	 *
	 * @since 0.9.10
	 *
	 * @param int[] $order_ids Optional. Only passed when processing a bulk action.
	 *
	 * @return int
	 */
	private function create_pos_from_orders( $order_ids = [] ) {

		$created = 0;

		foreach ( $order_ids as $order_id ) {

			$order = new \WC_Order( $order_id );
			$items = $order->get_items();

			if ( ! empty( $items ) ) {

				$product_ids = $item_ids = $item_qtys = [];

				foreach ( $items as $item ) {

					/**
					 * Variable definition
					 *
					 * @var \WC_Order_Item_Product $item
					 */
					$item_product_id               = $item->get_variation_id() ?: $item->get_product_id();
					$product_ids[]                 = $item_product_id;
					$item_ids[]                    = $item->get_id();
					$item_qtys[ $item_product_id ] = $item->get_quantity();

				}

				do_action_ref_array( 'atum/purchase_orders_pro/create_pos_from_orders/before_create_po', [ $order, $item_ids, &$product_ids, &$item_qtys ] );

				$po = $this->create_po( $product_ids, $item_qtys );
				$po->set_sales_order_number( $order->get_id() );

				$customer_id = $order->get_customer_id();

				if ( $customer_id ) {
					$customer = new \WC_Customer( $customer_id );
					$po->set_customer_name( $customer->get_display_name() );
				}
				else {
					$po->set_customer_name( $order->get_formatted_billing_full_name() );
				}

				// Check if we have to import the customer shipping address too.
				if ( 'yes' === AtumHelpers::get_option( 'po_copy_shipping_address', 'no' ) ) {
					$po->set_purchaser_country( $order->get_shipping_country() );
					$po->set_purchaser_state( $order->get_shipping_state() );
					$po->set_purchaser_city( $order->get_shipping_city() );
					$po->set_purchaser_address( $order->get_shipping_address_1() );
					$po->set_purchaser_address_2( $order->get_shipping_address_2() );
					$po->set_purchaser_postal_code( $order->get_shipping_postcode() );
				}

				$po->save();
				$created++;

			}

		}

		return $created;

	}

	/**
	 * Show an admin notice when the POs have been created through the WC Orders bulk action
	 *
	 * @since 0.9.10
	 */
	public function bulk_admin_notices() {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Bail out if not on shop order list page.
		if ( ! in_array( $screen_id, [ 'edit-shop_order', 'woocommerce_page_wc-orders' ] ) || ! isset( $_REQUEST['bulk_action'] ) ) {
			return;
		}

		$number      = isset( $_REQUEST['created'] ) ? absint( $_REQUEST['created'] ) : 0;
		$bulk_action = wc_clean( wp_unslash( $_REQUEST['bulk_action'] ) );

		if ( 'atum_pos_created' === $bulk_action ) {

			if ( $number ) {
				$message = _n( 'The PO for the selected order was created successfully. ', 'The POs for the selected orders were created successfully. ', $number, ATUM_PO_TEXT_DOMAIN );
				/* translators: %s: POs list URL */
				$message .= sprintf( __( "<a href='%s'>Go to the POs list.</a>", ATUM_PO_TEXT_DOMAIN ), admin_url( 'admin.php?page=atum-purchase-orders' ) );
			}
			else {
				$message = __( 'No POs were created. Something wrong happened', ATUM_PO_TEXT_DOMAIN );
			}

			echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';

		}

	}


	/*******************
	 * Instance methods
	 *******************/

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
	 * @return AddToPO instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

<?php
/**
 * Handle the meta boxes for Purchase Orders.
 *
 * @package     AtumPO\MetaBoxes
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.0.1
 */

namespace AtumPO\MetaBoxes;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumHelpGuide;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Deliveries\Deliveries;
use AtumPO\Deliveries\DeliveryLocations;
use AtumPO\Inc\Globals;
use AtumPO\Inc\Helpers;
use AtumPO\Invoices\Invoices;
use AtumPO\Models\POExtended;
use Atum\Inc\Helpers as AtumHelpers;


class POMetaBoxes {

	/**
	 * The singleton instance holder
	 *
	 * @var POMetaBoxes
	 */
	private static $instance;

	/**
	 * Purchase Orders post type
	 *
	 * @var string
	 */
	private $po_post_type;

	/**
	 * The PurchaseOrders instance
	 *
	 * @var PurchaseOrders
	 */
	private $pos_instance;

	/**
	 * POMetaBoxes singleton constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		if ( is_admin() ) {

			$this->po_post_type = PurchaseOrders::get_post_type();
			$this->pos_instance = PurchaseOrders::get_instance();

			// Replace the ATUM's meta boxes with the PRO ones.
			remove_action( "add_meta_boxes_{$this->po_post_type}", array( $this->pos_instance, 'add_meta_boxes' ), 30 );
			add_action( "add_meta_boxes_{$this->po_post_type}", array( $this, 'add_meta_boxes' ), 1 );

			// Enqueue scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 12 );

			// Add the JS templates to the PO.
			add_action( 'atum/purchase_orders_pro/after_items_meta_box', array( $this, 'add_js_templates' ) );

			// Add extra fields to the screen options tab.
			add_filter( 'screen_options_show_submit', array( $this, 'screen_options' ), 10, 2 );

			$this->override_po_views();

		}

	}

	/**
	 * Override the ATUM's PO views
	 *
	 * @since 0.2.0
	 */
	private function override_po_views() {

		// Override the PO's data meta box view.
		add_filter( 'atum/load_view/meta-boxes/purchase-order/data', function ( $view ) {

			if ( ! Helpers::is_po_post() ) {
				return $view;
			}

			return ATUM_PO_PATH . 'views/meta-boxes/po-data/data';

		} );

		// Override the PO's data meta box args.
		add_filter( 'atum/load_view_args/meta-boxes/purchase-order/data', function ( $args ) {

			if ( ! Helpers::is_po_post() ) {
				return $args;
			}

			// Replace the coming PO with its extended version.
			if ( ! $args['atum_order'] instanceof POExtended ) {
				$args['atum_order'] = new POExtended( Helpers::get_po_id() );
			}

			return $args;

		} );

		// Override the PO items view.
		add_filter( 'atum/load_view/meta-boxes/atum-order/items', function ( $view ) {

			if ( ! Helpers::is_po_post() ) {
				return $view;
			}

			do_action( 'atum/purchase_orders_pro/before_load_items' );

			return ATUM_PO_PATH . 'views/meta-boxes/po-items/items';

		} );

		// Override the PO items args.
		add_filter( 'atum/load_view_args/meta-boxes/atum-order/items', function ( $args ) {

			if ( ! Helpers::is_po_post() ) {
				return $args;
			}

			// Replace the coming PO with its extended version.
			if ( ! $args['atum_order'] instanceof POExtended ) {
				$args['atum_order'] = new POExtended( Helpers::get_po_id() );
			}

			return $args;

		} );

		// Override the PO notes view.
		add_filter( 'atum/load_view/meta-boxes/atum-order/notes', function ( $view ) {

			if ( ! Helpers::is_po_post() ) {
				return $view;
			}

			return ATUM_PO_PATH . 'views/meta-boxes/comments/notes';

		} );

		// Override the PO notes args.
		add_filter( 'atum/load_view_args/meta-boxes/atum-order/notes', function ( $args ) {

			if ( ! Helpers::is_po_post() ) {
				return $args;
			}

			// Replace the coming PO with its extended version.
			if ( ! isset( $args['atum_order'] ) ) {
				$args['atum_order'] = AtumHelpers::get_atum_order_model( Helpers::get_po_id(), TRUE, PurchaseOrders::POST_TYPE );
			}
			elseif ( ! $args['atum_order'] instanceof POExtended ) {
				$args['atum_order'] = new POExtended( Helpers::get_po_id() );
			}

			$args['unread'] = Helpers::get_po_notifications( array(
				'atum_order_id' => Helpers::get_po_id(),
				'status'        => 'unread',
			) );

			$args['targeted'] = Helpers::get_po_notifications( array(
				'atum_order_id' => Helpers::get_po_id(),
				'status'        => 'all',
				'target'        => 'user',
			) );

			return $args;

		} );

		// Override the PO single note view.
		add_filter( 'atum/load_view/meta-boxes/atum-order/note', function ( $view ) {

			if ( ! Helpers::is_po_post() ) {
				return $view;
			}

			return ATUM_PO_PATH . 'views/meta-boxes/comments/note';

		} );

		// Override the PO notes args.
		add_filter( 'atum/load_view_args/meta-boxes/atum-order/note', function ( $args ) {

			if ( ! Helpers::is_po_post() ) {
				return $args;
			}

			$po = $this->pos_instance->get_current_atum_order( Helpers::get_po_id(), TRUE );

			// Add the unread comments list.
			$args['unread']      = Helpers::get_po_notifications( array(
				'atum_order_id' => Helpers::get_po_id(),
				'status'        => 'unread',
			) );
			$args['targeted']    = Helpers::get_po_notifications( array(
				'atum_order_id' => Helpers::get_po_id(),
				'status'        => 'all',
				'target'        => 'user',
			) );
			$args['is_editable'] = $po->is_editable();

			return $args;

		} );
	}

	/**
	 * Add the ATUM Order's meta boxes
	 *
	 * @since 1.2.9
	 */
	public function add_meta_boxes() {

		global $post, $post_type;

		$is_returning_po = PurchaseOrders::POST_TYPE === $post_type && in_array( $post->post_status, [ 'atum_returning', 'atum_returned' ] );

		// Data meta box.
		add_meta_box(
			'atum_order_data',
			__( 'Purchase Order', ATUM_PO_TEXT_DOMAIN ),
			array( $this->pos_instance, 'show_data_meta_box' ),
			$this->po_post_type,
			'normal',
			'high'
		);

		// Items meta box.
		add_meta_box(
			'atum_order_items',
			! $is_returning_po ? __( 'PO Items', ATUM_PO_TEXT_DOMAIN ) : __( 'Returning PO Items', ATUM_PO_TEXT_DOMAIN ),
			array( $this->pos_instance, 'show_items_meta_box' ),
			$this->po_post_type,
			'normal',
			'high'
		);

		// Deliveries meta box.
		if ( ! $is_returning_po ) {

			add_meta_box(
				'po_deliveries',
				__( 'PO Deliveries', ATUM_PO_TEXT_DOMAIN ),
				array( $this, 'show_deliveries_meta_box' ),
				$this->po_post_type,
				'normal',
				'high'
			);

		}

		// Invoices meta box.
		if ( ! $is_returning_po ) {

			add_meta_box(
				'po_invoices',
				__( 'PO Invoices', ATUM_PO_TEXT_DOMAIN ),
				array( $this, 'show_invoices_meta_box' ),
				$this->po_post_type,
				'normal',
				'high'
			);

		}

		// Files meta box.
		add_meta_box(
			'po_files',
			! $is_returning_po ? __( 'PO Files', ATUM_PO_TEXT_DOMAIN ) : __( 'Returning PO Files', ATUM_PO_TEXT_DOMAIN ),
			array( $this, 'show_files_meta_box' ),
			$this->po_post_type,
			'normal',
			'high'
		);

		// Notes meta box.
		if ( AtumCapabilities::current_user_can( 'read_order_notes' ) ) {

			add_meta_box(
				'po_notes',
				! $is_returning_po ? __( 'PO Comments', ATUM_PO_TEXT_DOMAIN ) : __( 'Returning PO Comments', ATUM_PO_TEXT_DOMAIN ),
				array( $this->pos_instance, 'show_notes_meta_box' ),
				$this->po_post_type,
				'normal',
				'high'
			);

		}

		do_action( 'atum/purchase_orders_pro/after_po_meta_boxes' );

		// Remove old atum_order_notes meta box.
		remove_meta_box( 'atum_order_notes', $this->po_post_type, 'side' );

		// Remove unneeded WP meta boxes.
		remove_meta_box( 'commentsdiv', $this->po_post_type, 'normal' );
		remove_meta_box( 'commentstatusdiv', $this->po_post_type, 'normal' );
		remove_meta_box( 'slugdiv', $this->po_post_type, 'normal' );
		remove_meta_box( 'submitdiv', $this->po_post_type, 'side' );

		// Use our own placeholders for product images.
		add_filter( 'woocommerce_placeholder_img', array( '\Atum\Inc\Helpers', 'get_product_image_placeholder'), 10, 3 );

		// Add a special class to the smaller meta boxes.
		add_filter( "postbox_classes_{$this->po_post_type}_po_logs", array( $this, 'add_resizable_meta_box_classes' ) );
		add_filter( "postbox_classes_{$this->po_post_type}_po_notes", array( $this, 'add_resizable_meta_box_classes' ) );

	}

	/**
	 * Enqueue the PO scripts
	 *
	 * @since 0.0.1
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		if ( in_array( $hook, [ 'post-new.php', 'post.php' ] ) && Helpers::is_po_post() ) {

			global $post;

			wp_register_style( 'splide', ATUM_PO_URL . 'assets/css/vendor/splide.min.css', [], ATUM_PO_VERSION );
			wp_register_style( 'atum-purchase-orders', ATUM_PO_URL . 'assets/css/atum-po-orders.css', [ 'atum-orders', 'splide' ], ATUM_PO_VERSION );
			wp_register_script( 'atum-purchase-orders', ATUM_PO_URL . 'assets/js/build/atum-po-orders.js', [ 'atum-orders' ], ATUM_PO_VERSION, TRUE );

			$po = new POExtended( $post->ID ); // NOTE: Do not use the get_atum_order_model here as it was causing issues to some users.

			$delivery_locations = DeliveryLocations::get_locations();

			$default_country   = get_option( 'woocommerce_default_country', '' );
			$countries         = WC()->countries;
			$default_city      = $countries->get_base_city();
			$default_adress    = $countries->get_base_address();
			$default_address_2 = $countries->get_base_address_2();
			$default_postcode  = $countries->get_base_postcode();

			if ( 'yes' === AtumHelpers::get_option( 'same_ship_address', 'yes' ) ) {

				$country_state = wc_format_country_state_string( AtumHelpers::get_option( 'country', $default_country ) );

				$store_details = array(
					'name'        => AtumHelpers::get_option( 'company_name', '' ),
					'address'     => AtumHelpers::get_option( 'address_1', $default_adress ),
					'address2'    => AtumHelpers::get_option( 'address_2', $default_address_2 ),
					'city'        => AtumHelpers::get_option( 'city', $default_city ),
					'country'     => $country_state['country'] ?? '',
					'postal_code' => AtumHelpers::get_option( 'zip', $default_postcode ),
				);

			}
			else {

				$country_state = wc_format_country_state_string( AtumHelpers::get_option( 'ship_country', $default_country ) );

				$store_details = array(
					'name'        => AtumHelpers::get_option( 'ship_to', '' ),
					'address'     => AtumHelpers::get_option( 'ship_address_1', $default_adress ),
					'address2'    => AtumHelpers::get_option( 'ship_address_2', $default_address_2 ),
					'city'        => AtumHelpers::get_option( 'ship_city', $default_city ),
					'country'     => $country_state['country'] ?? '',
					'postal_code' => AtumHelpers::get_option( 'ship_zip', $default_postcode ),
				);

			}

			if ( ! empty( $country_state['state'] ) ) {

				$states = WC()->countries->get_states();

				// Get the friendly name for the state.
				if ( ! empty( $states[ $country_state['country'] ][ $country_state['state'] ] ) ) {
					$store_details['state'] = $states[ $country_state['country'] ][ $country_state['state'] ];
				}

			}

			$delivery_locations += compact( 'store_details' );

			$atum_po_vars = array(
				'addAll'                     => __( 'Add All', ATUM_PO_TEXT_DOMAIN ),
				'addAllItems'                => __( 'Add all items', ATUM_PO_TEXT_DOMAIN ),
				'addAllOutStockProducts'     => __( 'Add all out of stock products', ATUM_PO_TEXT_DOMAIN ),
				'addAllOutStockSupProducts'  => __( 'Add all the out of stock products from the same supplier at once', ATUM_PO_TEXT_DOMAIN ),
				'addDelivery'                => __( 'Add Delivery', ATUM_PO_TEXT_DOMAIN ),
				'addDeliveryModalTitle'      => __( 'Add Delivery to PO', ATUM_PO_TEXT_DOMAIN ),
				'addFee'                     => __( 'Add Fee', ATUM_PO_TEXT_DOMAIN ),
				'addFeeModalTitle'           => ! $po->is_returning() ? __( 'Add Fee to PO', ATUM_PO_TEXT_DOMAIN ) : __( 'Add Fee to Returning PO', ATUM_PO_TEXT_DOMAIN ),
				'addFeeModalSubtitle'        => __( 'Enter a fixed amount or a percentage to apply as a fee.', ATUM_PO_TEXT_DOMAIN ),
				'addFile'                    => __( 'Add File', ATUM_PO_TEXT_DOMAIN ),
				'addFileModalTitle'          => ! $po->is_returning() ? __( 'Add File to PO', ATUM_PO_TEXT_DOMAIN ) : __( 'Add File to Returning PO', ATUM_PO_TEXT_DOMAIN ),
				'addInventory'               => __( 'Add Inventory', ATUM_PO_TEXT_DOMAIN ),
				'addInventoryToPO'           => __( 'Do you want to add the new inventory to this PO?', ATUM_PO_TEXT_DOMAIN ),
				'addInvoice'                 => __( 'Add Invoice', ATUM_PO_TEXT_DOMAIN ),
				'addInvoiceModalTitle'       => __( 'Add Invoice to PO', ATUM_PO_TEXT_DOMAIN ),
				'addIt'                      => __( 'Yes, add it!', ATUM_PO_TEXT_DOMAIN ),
				'addItems'                   => ! $po->is_returning() ? __( 'Add Items to PO', ATUM_PO_TEXT_DOMAIN ) : __( 'Add Items to Returning PO', ATUM_PO_TEXT_DOMAIN ),
				'addMeta'                    => __( 'Add new meta', ATUM_PO_TEXT_DOMAIN ),
				'addEditMeta'                => __( 'Add/Edit Meta', ATUM_PO_TEXT_DOMAIN ),
				'addNoteNonce'               => wp_create_nonce( 'add-atum-order-note' ),
				'addProducts'                => __( 'Add Items', ATUM_PO_TEXT_DOMAIN ),
				'addRemainingItems'          => __( 'When clicked, it will set the remaining quantities for every item on the above list so you can add all of them at once.', ATUM_PO_TEXT_DOMAIN ),
				'addShipping'                => __( 'Add Shipping Cost', ATUM_PO_TEXT_DOMAIN ),
				'addShippingModalTitle'      => ! $po->is_returning() ? __( 'Add Shipping Cost to PO', ATUM_PO_TEXT_DOMAIN ) : __( 'Add Shipping Cost to Returning PO', ATUM_PO_TEXT_DOMAIN ),
				'addShippingModalSubtitle'   => __( 'Enter a fixed amount or a percentage to apply as a shipping cost.', ATUM_PO_TEXT_DOMAIN ),
				'addToPONonce'               => wp_create_nonce( 'atum-po-add-to-po' ),
				'addToStock'                 => __( 'Add to stock', ATUM_PO_TEXT_DOMAIN ),
				'addToStockDisabled'         => __( 'These delivery items were already added to stock', ATUM_PO_TEXT_DOMAIN ),
				'allDone'                    => __( 'All Done!', ATUM_PO_TEXT_DOMAIN ),
				'apply'                      => __( 'Apply', ATUM_PO_TEXT_DOMAIN ),
				'applyChanges'               => __( 'Apply Changes', ATUM_PO_TEXT_DOMAIN ),
				'applyConversion'            => __( 'Apply Conversion', ATUM_PO_TEXT_DOMAIN ),
				'applyRateToItems'           => __( 'Apply new rate to all items', ATUM_PO_TEXT_DOMAIN ),
				'applyRateToItemsNotice'     => __( 'Restore the previous rate (if any) and apply the new rate to all the current PO items automatically. Also save this new rate to the PO for future usage.', ATUM_PO_TEXT_DOMAIN ),
				'atumOrderItemNonce'         => wp_create_nonce( 'atum-order-item' ),
				'atumNotificationsNonce'     => wp_create_nonce( 'po-count-notifications-nonce' ),
				'atumCommentsNonce'          => wp_create_nonce( 'po-comments-nonce' ),
				'atumEmailNonce'             => wp_create_nonce( 'po-email-nonce' ),
				'atumMergeNonce'             => wp_create_nonce( 'po-merge-nonce' ),
				'atumThumbPlaceholder'       => AtumHelpers::get_atum_image_placeholder(),
				'areYouSure'                 => __( 'Are you sure?', ATUM_PO_TEXT_DOMAIN ),
				'autoSaveNonce'              => wp_create_nonce( 'po-auto-save-nonce' ),
				'cancel'                     => __( 'Cancel', ATUM_PO_TEXT_DOMAIN ),
				'cannotBeUndone'             => __( 'This action cannot be undone', ATUM_PO_TEXT_DOMAIN ),
				'checkActualCurrencyRate'    => __( 'Check the actual exchange rate here.', ATUM_PO_TEXT_DOMAIN ),
				'changeIt'                   => __( 'Yes, change it', ATUM_PO_TEXT_DOMAIN ),
				'changeSuccessful'           => __( 'All the changes were applied successfully', ATUM_PO_TEXT_DOMAIN ),
				'chooseSomething'            => __( 'You must choose something', ATUM_PO_TEXT_DOMAIN ),
				'cloneDeliveries'            => __( 'Do you also want to clone Deliveries and Invoices?', ATUM_PO_TEXT_DOMAIN ),
				'confirmChanges'             => __( 'Confirm whether you want to update your items before applying', ATUM_PO_TEXT_DOMAIN ),
				'confirmCurrencyChange'      => __( 'Do you want to change the PO currency from <strong>{oldValue}</strong> to <strong>{newValue}</strong>?', ATUM_PO_TEXT_DOMAIN ),
				'confirmDeletion'            => __( 'ATUM will archive the Purchase Order.<br><br>You can preview all archived POs in the PO List View (ATUM - Purchase Orders PRO). Select the status "Archived" from the status filter or use the search bar.', ATUM_PO_TEXT_DOMAIN ),
				'confirmDiscountChange'      => __( "Do you want to change all the PO items' discounts from <strong>{oldValue}</strong> to <strong>{newValue}</strong>?", ATUM_PO_TEXT_DOMAIN ),
				'confirmStatusChanges'       => AtumHelpers::get_option( 'po_confirm_status_changes', 'yes' ),
				'confirmTaxChange'           => __( "Do you want to change all the PO items' taxes from <strong>{oldValue}</strong> to <strong>{newValue}</strong>?", ATUM_PO_TEXT_DOMAIN ),
				'confirmUpdate'              => __( 'This PO must be updated to reflect the new currency options in all its prices', ATUM_PO_TEXT_DOMAIN ),
				'continue'                   => __( 'Continue', ATUM_PO_TEXT_DOMAIN ),
				'createNewInventory'         => __( 'Create New Inventory', ATUM_PO_TEXT_DOMAIN ),
				'currency'                   => __( 'Currency', ATUM_PO_TEXT_DOMAIN ),
				'currencyConflict'           => __( 'Currency conflict detected', ATUM_PO_TEXT_DOMAIN ),
				'currencyExchangeLink'       => 'https://www.xe.com/currencyconverter/convert/?Amount={amount}&From={oldCurrency}&To={newCurrency}',
				'currencyExchangePPNotice'   => __( "The PO's currency does not match with your shop's default currency. In order to set the right purchase price to this item, please specify the exchange rate.", ATUM_PO_TEXT_DOMAIN ),
				'currencyOptionsChanged'     => __( 'Change detected in currency options', ATUM_PO_TEXT_DOMAIN ),
				'currencySymbols'            => get_woocommerce_currency_symbols(),
				'decreasingPOItemQtyMsg'     => __( "You are decreasing the quantity for this PO item and we've found that it was previously added to deliveries and/or invoices.<br>Please, make sure you update the quantities for this item there accordingly.", ATUM_PO_TEXT_DOMAIN ),
				'decreasingPOItemQtyTitle'   => __( 'Manual change required!', ATUM_PO_TEXT_DOMAIN ),
				'defaultCurrency'            => get_woocommerce_currency(),
				'defaultDescription'         => AtumHelpers::get_option( 'po_default_description', '' ),
				'defaultTerms'               => AtumHelpers::get_option( 'po_default_delivery_terms', '' ),
				'deleteNote'                 => __( 'Are you sure you want to delete this comment?', ATUM_PO_TEXT_DOMAIN ),
				'deleteNoteNonce'            => wp_create_nonce( 'delete-atum-order-note' ),
				'deleteIt'                   => __( 'Yes, Archive it!', ATUM_PO_TEXT_DOMAIN ),
				'deliveryItemRemoved'        => __( 'Delivery item removed successfully', ATUM_PO_TEXT_DOMAIN ),
				'deliveryItemsUpdated'       => __( 'Delivery items updated successfully', ATUM_PO_TEXT_DOMAIN ),
				'deliveryLocations'          => $delivery_locations,
				'deliveryNonce'              => wp_create_nonce( 'po-delivery-nonce' ),
				'deliveryItemRemovalMsg'     => __( 'This delivery item will be removed and if it was previously added to stock, deducted', ATUM_PO_TEXT_DOMAIN ),
				'deliveryItemInvRemovalMsg'  => __( 'As this is the last inventory in the last item in this delivery, the whole item and the delivery will be also removed and if the item was previously added to stock, deducted', ATUM_PO_TEXT_DOMAIN ),
				'deliveryItemPlusRemovalMsg' => __( 'As this is the last item in this delivery, the whole delivery will be also removed and if the item was previously added to stock, deducted', ATUM_PO_TEXT_DOMAIN ),
				'deliveryRemovalMsg'         => __( 'This delivery will be removed and any inner item that was previously added to stock, deducted', ATUM_PO_TEXT_DOMAIN ),
				'discount'                   => __( 'Discount', ATUM_PO_TEXT_DOMAIN ),
				'discountChanged'            => __( 'Discount Change Detected!', ATUM_PO_TEXT_DOMAIN ),
				'doIt'                       => __( 'Yes, do it!', ATUM_PO_TEXT_DOMAIN ),
				'duplicate'                  => __( 'Duplicate', ATUM_PO_TEXT_DOMAIN ),
				'editDeliveryModalTitle'     => __( 'Edit Delivery', ATUM_PO_TEXT_DOMAIN ),
				'editInvoice'                => __( 'Edit Invoice', ATUM_PO_TEXT_DOMAIN ),
				'editItems'                  => __( 'Edit Items', ATUM_PO_TEXT_DOMAIN ),
				'emailed'                    => __( 'Emailed', ATUM_PO_TEXT_DOMAIN ),
				'emailSent'                  => __( 'Email sent', ATUM_PO_TEXT_DOMAIN ),
				'emailSuccesfullySent'       => __( 'The email was sent to your supplier successfully.', ATUM_PO_TEXT_DOMAIN ),
				'error'                      => __( 'Error!', ATUM_PO_TEXT_DOMAIN ),
				'fileAlreadyAdded'           => __( 'This file was already added to this PO', ATUM_PO_TEXT_DOMAIN ),
				'fileNonce'                  => wp_create_nonce( 'po-file-nonce' ),
				'filesExceedLimit'           => __( 'The selected files exceed the maximum limit', ATUM_PO_TEXT_DOMAIN ),
				'invoiceItemRemoved'         => __( 'Invoice item removed successfully', ATUM_PO_TEXT_DOMAIN ),
				'invoiceItemRemovalMsg'      => __( 'This invoice item will be removed permanently', ATUM_PO_TEXT_DOMAIN ),
				'invoiceItemPlusRemovalMsg'  => __( 'As this is the last item in this invoice, the whole invoice will be also removed', ATUM_PO_TEXT_DOMAIN ),
				'invoiceRemovalMsg'          => __( 'This invoice will be removed permanently', ATUM_PO_TEXT_DOMAIN ),
				'invoiceRemoved'             => __( 'Invoice removed successfully', ATUM_PO_TEXT_DOMAIN ),
				'invoiceNonce'               => wp_create_nonce( 'po-invoice-nonce' ),
				'invalidEmails'              => __( 'Invalid email(s):', ATUM_PO_TEXT_DOMAIN ),
				'isReturning'                => wc_bool_to_string( $po->is_returning() ),
				'itemsAddedToStock'          => __( 'Items added to stock successfully', ATUM_PO_TEXT_DOMAIN ),
				'keepIt'                     => __( 'No, keep it!', ATUM_PO_TEXT_DOMAIN ),
				'lastMIitemRemoved'          => __( 'This is the only inventory added to this PO product. Do you want to also remove the related product?', ATUM_PO_TEXT_DOMAIN ),
				'markAllAsRead'              => __( 'Mark all as read', ATUM_PO_TEXT_DOMAIN ),
				'markRead'                   => __( 'Mark as read', ATUM_PO_TEXT_DOMAIN ),
				'markUnread'                 => __( 'Mark as unread', ATUM_PO_TEXT_DOMAIN ),
				'maximizeMetaBox'            => __( 'Maximize meta box', ATUM_PO_TEXT_DOMAIN ),
				'mergeButton'                => __( 'Merge PO', ATUM_PO_TEXT_DOMAIN ),
				'mergePOMissing'             => __( 'The merge PO is missing', ATUM_PO_TEXT_DOMAIN ),
				'mergeTitle'                 => __( 'Merge POs', ATUM_PO_TEXT_DOMAIN ),
				'metaBoxesSizes'             => $po->meta_box_sizes,
				'metaModalTitle'             => __( 'Add/Edit item meta', ATUM_PO_TEXT_DOMAIN ),
				'metaPlaceholderName'        => esc_attr__( 'Name (required)', ATUM_PO_TEXT_DOMAIN ),
				'metaPlaceholderValue'       => esc_attr__( 'Value (required)', ATUM_PO_TEXT_DOMAIN ),
				'miItemsWithoutInventories'  => __( 'There is at least one PO item with no inventories assigned that cannot be delivered until fixed', ATUM_PO_TEXT_DOMAIN ),
				'minimizeMetaBox'            => __( 'Minimize meta box', ATUM_PO_TEXT_DOMAIN ),
				'minimumStockToAdd'          => Helpers::get_minimum_quantity_to_add(),
				'missingDeliveryItems'       => __( 'There are items in this PO that have not been delivered yet. Do you want to continue anyway?', ATUM_PO_TEXT_DOMAIN ),
				'missingPOItem'              => __( 'This item was related to a PO item that no longer exist and cannot be added to stock', ATUM_PO_TEXT_DOMAIN ),
				'missingPOItems'             => __( 'There are {missingItems} items in this delivery that were related to PO items that no longer exist, so they cannot be added to stock', ATUM_PO_TEXT_DOMAIN ),
				'missingStockedItems'        => __( 'There are delivery items in this PO that have not been added to stock yet. Do you want to continue anyway?', ATUM_PO_TEXT_DOMAIN ),
				'next'                       => __( 'Next &rarr;', ATUM_PO_TEXT_DOMAIN ),
				'no'                         => __( 'No', ATUM_PO_TEXT_DOMAIN ),
				'noDeliveryItemsAdded'       => __( 'No delivery items were added to this PO yet. Do you wan to continue anyway?', ATUM_PO_TEXT_DOMAIN ),
				'noFileAdded'                => __( 'No file added', ATUM_PO_TEXT_DOMAIN ),
				'noFileSelected'             => __( 'No file selected', ATUM_PO_TEXT_DOMAIN ),
				'noItemsAddedToPO'           => __( 'No items were added to this PO yet. Do you want to continue anyway?', ATUM_PO_TEXT_DOMAIN ),
				'noItemsAddedToStock'        => __( 'No delivery items added to stock were found in this PO. Do you want to continue anyway?', ATUM_PO_TEXT_DOMAIN ),
				'noItemsDelivered'           => __( 'There are no delivered items on this PO yet.<br>Only delivered items can be returned.', ATUM_PO_TEXT_DOMAIN ),
				'noItemsToAddToStock'        => __( 'No delivery items to add to stock were found', ATUM_PO_TEXT_DOMAIN ),
				'none'                       => __( 'None', ATUM_PO_TEXT_DOMAIN ),
				'nonSupplierItemsDetected'   => __( "We've detected some items added to this PO that were assigned to a distinct supplier. Please, note that this won't affect to items with no supplier assigned.", ATUM_PO_TEXT_DOMAIN ),
				'noNumber'                   => __( 'The PO must have a number', ATUM_PO_TEXT_DOMAIN ),
				'noProductsFound'            => __( 'No products found with the specified criteria', ATUM_PO_TEXT_DOMAIN ),
				'noRequisitioner'            => __( 'You must select a requisitioner', ATUM_PO_TEXT_DOMAIN ),
				'noSupplierProductsFound'    => __( 'No products found with the specified criteria for this supplier', ATUM_PO_TEXT_DOMAIN ),
				'notificationsTitle'         => __( 'PO Notifications', ATUM_PO_TEXT_DOMAIN ),
				'noUnreadNotifications'      => __( 'You have no more unread notifications', ATUM_PO_TEXT_DOMAIN ),
				'ok'                         => __( 'OK', ATUM_PO_TEXT_DOMAIN ),
				'postId'                     => $post->ID ?? '',
				'recipientMissing'           => __( "The recipient's email is missing", ATUM_PO_TEXT_DOMAIN ),
				'remove'                     => __( 'Remove', ATUM_PO_TEXT_DOMAIN ),
				'removeRelatedDeliveryItems' => __( 'Remove also the related items from deliveries', ATUM_PO_TEXT_DOMAIN ),
				'removeRelatedInvoiceItems'  => __( 'Remove also the related items from invoices', ATUM_PO_TEXT_DOMAIN ),
				'removeRelatedItems'         => __( 'Remove also the related items from deliveries and invoices', ATUM_PO_TEXT_DOMAIN ),
				'removeInvoice'              => __( 'Remove Invoice', ATUM_PO_TEXT_DOMAIN ),
				'removeIt'                   => __( 'Yes, Remove it!', ATUM_PO_TEXT_DOMAIN ),
				'removeItem'                 => __( 'Remove Item', ATUM_PO_TEXT_DOMAIN ),
				'removeNonSupplierItems'     => __( 'Do you want to remove PO items not belonging to this supplier?', ATUM_PO_TEXT_DOMAIN ),
				'removeParentProduct'        => __( 'Remove parent product?', ATUM_PO_TEXT_DOMAIN ),
				'requiredRequisition'        => AtumHelpers::get_option( 'po_required_requisition', 'no' ),
				'resizingNonce'              => wp_create_nonce( 'po-meta-box-resizing-nonce' ),
				'returnItems'                => __( 'If you proceed with the return, all the items added to this Returning PO that were previously added to stock on the original PO, will be discounted automatically.<br>Do you want to continue?', ATUM_PO_TEXT_DOMAIN ),
				'returningPOQuestion'        => __( 'This will create a new Returning PO with all the items that have been delivered on this PO. You can edit them later.<br>Do you want to continue?', ATUM_PO_TEXT_DOMAIN ),
				'returnPONonce'              => wp_create_nonce( 'po-return-po-nonce' ),
				'save'                       => __( 'Save', ATUM_PO_TEXT_DOMAIN ),
				'saveStatus'                 => __( 'Save PO status', ATUM_PO_TEXT_DOMAIN ),
				'saveStatusNonce'            => wp_create_nonce( 'po-save-status-nonce' ),
				'savingPO'                   => __( 'Saving PO...', ATUM_PO_TEXT_DOMAIN ),
				'screenOptionsNonce'         => wp_create_nonce( 'po-screen-options-nonce' ),
				'send'                       => __( 'Send', ATUM_PO_TEXT_DOMAIN ),
				'senderMissing'              => __( "The sender's email is missing", ATUM_PO_TEXT_DOMAIN ),
				'sendingTitle'               => __( 'Sending PO', ATUM_PO_TEXT_DOMAIN ),
				'sentEmailAlready'           => __( 'This PO has already been sent to your supplier previosuly.', ATUM_PO_TEXT_DOMAIN ),
				'setButton'                  => __( 'Set', ATUM_PO_TEXT_DOMAIN ),
				'setCustomerAddress' 		 => __( "Do you want to set the customer address as the Purchaser's info on this PO?", ATUM_PO_TEXT_DOMAIN ),
				'setExchangeRate'            => __( 'Set exchange rate', ATUM_PO_TEXT_DOMAIN ),
				'setPurchasePrice'           => __( 'Set Purchase Price', ATUM_PO_TEXT_DOMAIN ),
				'setPPTaxes'                 => AtumHelpers::get_option( 'po_set_purchase_price_taxes', 'no_taxes' ),
				'sentStatusDetails'          => Helpers::get_status_details( 'atum_ordered' ),
				'shopBaseTaxRates'           => \WC_Tax::get_base_tax_rates(),
				'statusChangeMessage'        => __( 'You are going to change the PO status from <strong>"{origin}"</strong> to <strong>"{target}"</strong>', ATUM_PO_TEXT_DOMAIN ),
				'statusChangeNotAllowed'     => __( "This status change isn't allowed", ATUM_PO_TEXT_DOMAIN ),
				'statusDue'                  => Globals::get_due_statuses(),
				'statusClosed'               => Globals::get_closed_statuses(),
				'statusFlow'                 => Globals::get_status_flow(),
				'statusFlowRestriction'      => Helpers::get_po_status_flow_restriction( $po->get_id() ),
				'stockWillChange'            => __( 'This action will increase the stock for the selected item(s)', ATUM_PO_TEXT_DOMAIN ),
				'subjectMissing'             => __( "The email's subject is missing", ATUM_PO_TEXT_DOMAIN ),
				'supplier'                   => __( 'Supplier', ATUM_PO_TEXT_DOMAIN ),
				'supplierChangesDetected'    => __( 'Supplier details changes detected!', ATUM_PO_TEXT_DOMAIN ),
				'taxesName'                  => __( 'Tax', ATUM_PO_TEXT_DOMAIN ),
				'taxRate'                    => __( 'Tax Rate', ATUM_PO_TEXT_DOMAIN ),
				'unexpectedError'            => __( 'Unexpected error while processing the changes', ATUM_PO_TEXT_DOMAIN ),
				'updateDelivery'             => __( 'Update Delivery', ATUM_PO_TEXT_DOMAIN ),
				'updateInvoice'              => __( 'Update Invoice', ATUM_PO_TEXT_DOMAIN ),
				'updateIt'                   => __( 'Yes, Update it!', ATUM_PO_TEXT_DOMAIN ),
				'usersList'                  => Helpers::get_comments_users(),
				'warning'                    => __( 'Warning!', ATUM_PO_TEXT_DOMAIN ),
				'yes'                        => __( 'Yes', ATUM_PO_TEXT_DOMAIN ),
			);

			// Add the help guide JS vars.
			$atum_po_vars = array_merge( $atum_po_vars, AtumHelpGuide::get_instance()->get_help_guide_js_vars( 'atum_po_first_access', 'atum_po_first_access' ) );

			// Extra auto help guide for PO Email.
			$closed_auto_guides = AtumHelpGuide::get_closed_auto_guides( get_current_user_id() );

			if ( ! is_array( $closed_auto_guides ) || ! in_array( 'atum_po_email_modal', $closed_auto_guides ) ) {
				$atum_po_vars['autoEmailModalHelpGuide'] = 'yes';
			}

			wp_localize_script( 'atum-purchase-orders', 'atumPOVars', apply_filters( 'atum/purchase_orders_pro/atum_po_vars', $atum_po_vars ) );

			wp_enqueue_style( 'atum-purchase-orders' );
			wp_enqueue_media();
			wp_enqueue_script( 'atum-purchase-orders' );

		}

	}

	/**
	 * Add the JS templates to the PO
	 *
	 * @since 0.8.0
	 *
	 * @param POExtended $atum_order
	 */
	public function add_js_templates( $atum_order ) {

		// Add the template for the "add item modal" and "add fee modal".
		AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/add-order-item-modal', compact( 'atum_order' ) );
		AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/add-fee-modal', compact( 'atum_order' ) );

	}

	/**
	 * Displays the Deliveries meta box at POs
	 *
	 * @since 0.9.0
	 *
	 * @param \WP_Post $post
	 */
	public function show_deliveries_meta_box( $post ) {

		$po         = $this->pos_instance->get_current_atum_order( $post instanceof \WP_Post ? $post->ID : $post, TRUE );
		$deliveries = $po ? Deliveries::get_po_orders( $po->get_id() ) : [];

		AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/deliveries/deliveries', compact( 'po', 'deliveries' ) );

	}

	/**
	 * Displays the Invoices meta box at POs
	 *
	 * @since 0.9.0
	 *
	 * @param \WP_Post|int $post
	 */
	public function show_invoices_meta_box( $post ) {

		$po       = $this->pos_instance->get_current_atum_order( $post instanceof \WP_Post ? $post->ID : $post, TRUE );
		$invoices = $po ? Invoices::get_po_orders( $po->get_id() ) : [];

		AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/invoices/invoices', compact( 'po', 'invoices' ) );

	}

	/**
	 * Displays the Files meta box at POs
	 *
	 * @since 0.9.0
	 *
	 * @param \WP_Post $post
	 */
	public function show_files_meta_box( $post ) {

		$po = $this->pos_instance->get_current_atum_order( $post instanceof \WP_Post ? $post->ID : $post, TRUE );
		AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/files/files', compact( 'po' ) );

	}

	/**
	 * Add classes to the resizable meta-boxes
	 *
	 * @since 0.9.12
	 *
	 * @param string[] $classes
	 *
	 * @return string[]
	 */
	public function add_resizable_meta_box_classes( $classes ) {

		global $post, $post_type;

		if ( ! $post || PurchaseOrders::POST_TYPE !== $post_type ) {
			return $classes;
		}

		$classes[] = 'resizable-meta-box';

		// When the Action Logs add-on is not active and the resizable meta boxes have no state saved, make them full width.
		if ( ! Addons::is_addon_active( 'action_logs' ) ) {
			$po = AtumHelpers::get_atum_order_model( $post->ID, FALSE, PurchaseOrders::POST_TYPE );

			if ( empty( $po->meta_box_sizes ) ) {
				$classes[] = 'maximized';
			}
		}

		return $classes;

	}

	/**
	 * Add screen options
	 *
	 * @since 1.0.3
	 *
	 * @param bool       $show_submit
	 * @param \WP_Screen $current_screen
	 */
	public function screen_options( $show_submit, $current_screen ) {

		if ( PurchaseOrders::POST_TYPE === $current_screen->id ) : ?>

			<?php
			// Check if there is any value previously saved for this PO.
			$value = get_post_meta( get_the_ID(), '_po_status_flow_restriction', TRUE );
			$value = $value ?: AtumHelpers::get_option( 'po_status_flow_restriction', 'yes' );
			?>

			<br>
			<fieldset class="screen-options">
				<legend><?php esc_html_e( 'PO Settings', ATUM_PO_TEXT_DOMAIN ); ?></legend>

				<span class="form-switch">
					<input type="checkbox" id="po_status_flow_restriction" name="po_status_flow_restriction"
						class="form-check-input" value="yes"<?php checked( 'yes', $value ) ?>
					>
					<label for="po_status_flow_restriction" class="form-check-label">
						<?php esc_html_e( 'PO status flow restriction', ATUM_PO_TEXT_DOMAIN ); ?>
					</label>
				</span>
			</fieldset>

		<?php endif;

	}


	/********************
	 * Instance methods
	 ********************/

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
	 * @return POMetaBoxes instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

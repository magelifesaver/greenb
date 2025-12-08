<?php
/**
 * Invoices main class
 *
 * @package         AtumPO
 * @subpackage      Invoices
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.6
 */

namespace AtumPO\Invoices;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Abstracts\AtumPOOrders\AtumPOOrderPostType;
use AtumPO\Invoices\Models\Invoice;


class Invoices extends AtumPOOrderPostType {

	/**
	 * The singleton instance holder
	 *
	 * @var Invoices
	 */
	private static $instance;

	/**
	 * The Invoice post type name
	 */
	const POST_TYPE = 'atum_po_invoice';

	/**
	 * Will hold the current purchase order object
	 *
	 * @var Invoice
	 */
	private $invoice;

	/**
	 * The capabilities used when registering the post type
	 * TODO: WE NEED MORE GRANULAR CAPABILITIES FOR THE INVOICE POSTS.
	 *
	 * @var array
	 */
	protected $capabilities = array(
		'edit_post'          => 'atum_edit_purchase_order',
		'read_post'          => 'atum_read_purchase_order',
		'read'	             => 'atum_read_purchase_orders',
		'delete_post'        => 'atum_delete_purchase_order',
		'edit_posts'         => 'atum_edit_purchase_orders',
		'edit_others_posts'  => 'atum_edit_others_purchase_orders',
		'read_private_posts' => 'atum_read_private_purchase_orders',
		'publish_posts'      => 'atum_publish_purchase_orders',
		'create_posts'       => 'atum_create_purchase_orders',
		'delete_posts'       => 'atum_delete_purchase_orders',
		'delete_other_posts' => 'atum_delete_other_purchase_orders',
	);
	
	/**
	 * Invoices singleton constructor
	 *
	 * @since 0.9.6
	 */
	private function __construct() {

		// Initialize.
		$this->init();

		// Add compatibility for the Invoice model class.
		add_filter( 'atum/order_model_class', array( $this, 'add_invoice_model_class' ), 11, 2 );

		// Save the invoices data when saving a PO.
		// NOTE: we need this hook to be added here because the parent class hook is being added using the Invoices post type instead of the PO post type.
		add_action( 'save_post_' . PurchaseOrders::POST_TYPE, array( $this, 'save_meta_boxes' ) );

	}

	/**
	 * Set Invoice post type labels
	 *
	 * @since 1.2.4
	 */
	protected function set_labels() {

		// Set post type labels.
		$this->labels = array(
			'name'                  => __( 'Invoices', ATUM_PO_TEXT_DOMAIN ),
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralContext
			'singular_name'         => _x( 'Invoice', self::POST_TYPE . ' post type singular name', ATUM_PO_TEXT_DOMAIN ),
			'add_new'               => __( 'Add New Invoice', ATUM_PO_TEXT_DOMAIN ),
			'add_new_item'          => __( 'Add New Invoice', ATUM_PO_TEXT_DOMAIN ),
			'edit'                  => __( 'Edit', ATUM_PO_TEXT_DOMAIN ),
			'edit_item'             => __( 'Edit Invoice', ATUM_PO_TEXT_DOMAIN ),
			'new_item'              => __( 'New Invoice', ATUM_PO_TEXT_DOMAIN ),
			'view'                  => __( 'View Invoice', ATUM_PO_TEXT_DOMAIN ),
			'view_item'             => __( 'View Invoice', ATUM_PO_TEXT_DOMAIN ),
			'search_items'          => __( 'Search Invoices', ATUM_PO_TEXT_DOMAIN ),
			'not_found'             => __( 'No invoices were found', ATUM_PO_TEXT_DOMAIN ),
			'not_found_in_trash'    => __( 'No invoices found in trash', ATUM_PO_TEXT_DOMAIN ),
			'parent'                => __( 'Parent invoice', ATUM_PO_TEXT_DOMAIN ),
			'menu_name'             => _x( 'PO Invoices', 'Admin menu name', ATUM_PO_TEXT_DOMAIN ),
			'filter_items_list'     => __( 'Filter invoices', ATUM_PO_TEXT_DOMAIN ),
			'items_list_navigation' => __( 'Invoices navigation', ATUM_PO_TEXT_DOMAIN ),
			'items_list'            => __( 'Invoices list', ATUM_PO_TEXT_DOMAIN ),
		);
	}

	/**
	 * Save the Invoice meta boxes
	 *
	 * @since 0.9.6
	 *
	 * @param int $po_id
	 */
	public function save_meta_boxes( $po_id ) {

		// Save the invoice data when saving a PO.
		if ( doing_action( 'save_post_' . PurchaseOrders::POST_TYPE ) && ! empty( $_POST['invoice'] ) ) {

			foreach ( $_POST['invoice'] as $invoice_id => $invoice_data ) {

				$invoice = new Invoice( $invoice_id );

				foreach ( $invoice_data as $key => $value ) {
					if ( is_callable( array( $invoice, "set_$key" ) ) ) {
						$invoice->{"set_$key"}( $value );
					}
				}

				$invoice->save( TRUE );

			}

		}

	}

	/**
	 * Get the currently instantiated Invoice object (if any) or create a new one
	 *
	 * @since 0.9.6
	 *
	 * @param int  $post_id
	 * @param bool $read_items
	 *
	 * @return Invoice|NULL
	 */
	public function get_current_atum_order( $post_id, $read_items ) {

		if ( ! $this->invoice || $this->invoice->get_id() != $post_id ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$this->invoice = AtumHelpers::get_atum_order_model( $post_id, $read_items, self::POST_TYPE );
		}

		return $this->invoice;

	}

	/**
	 * Adds compatibility with the Invoice model class
	 *
	 * @since 0.9.6
	 *
	 * @param string $model_class
	 * @param string $post_type
	 *
	 * @return string
	 */
	public function add_invoice_model_class( $model_class, $post_type ) {

		if ( self::POST_TYPE === $post_type ) {
			$model_class = '\AtumPO\Invoices\Models\Invoice';
		}

		return $model_class;
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
	 * @return Invoices instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

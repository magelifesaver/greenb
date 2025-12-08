<?php
/**
 * Deliveries main class
 *
 * @package         AtumPO
 * @subpackage      Deliveries
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.1
 */

namespace AtumPO\Deliveries;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Abstracts\AtumPOOrders\AtumPOOrderPostType;
use AtumPO\Deliveries\Models\Delivery;


class Deliveries extends AtumPOOrderPostType {

	/**
	 * The singleton instance holder
	 *
	 * @var Deliveries
	 */
	private static $instance;

	/**
	 * The Delivery post type name
	 */
	const POST_TYPE = 'atum_po_delivery';

	/**
	 * Will hold the current purchase order object
	 *
	 * @var Delivery
	 */
	private $delivery;

	/**
	 * The capabilities used when registering the post type
	 * TODO: WE NEED MORE GRANULAR CAPABILITIES FOR THE DELIVERY POSTS.
	 *
	 * @var array
	 */
	protected $capabilities = array(
		'edit_post'          => 'atum_edit_purchase_order',
		'read_post'          => 'atum_read_purchase_order',
		'read'         		 => 'atum_read_purchase_orders',
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
	 * Deliveries singleton constructor
	 *
	 * @since 0.9.1
	 */
	private function __construct() {

		// Initialize.
		$this->init();

		// Add compatibility for the Delivery model class.
		add_filter( 'atum/order_model_class', array( $this, 'add_delivery_model_class' ), 11, 2 );

		// Save the deliveries data when saving a PO.
		// NOTE: we need this hook to be added here because the parent class hook is being added using the Invoices post type instead of the PO post type.
		add_action( 'save_post_' . PurchaseOrders::POST_TYPE, array( $this, 'save_meta_boxes' ) );

	}

	/**
	 * Set Delivery post type labels
	 *
	 * @since 1.2.4
	 */
	protected function set_labels() {

		// Set post type labels.
		$this->labels = array(
			'name'                  => __( 'Deliveries', ATUM_PO_TEXT_DOMAIN ),
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralContext
			'singular_name'         => _x( 'Delivery', self::POST_TYPE . ' post type singular name', ATUM_PO_TEXT_DOMAIN ),
			'add_new'               => __( 'Add New Delivery', ATUM_PO_TEXT_DOMAIN ),
			'add_new_item'          => __( 'Add New Delivery', ATUM_PO_TEXT_DOMAIN ),
			'edit'                  => __( 'Edit', ATUM_PO_TEXT_DOMAIN ),
			'edit_item'             => __( 'Edit Delivery', ATUM_PO_TEXT_DOMAIN ),
			'new_item'              => __( 'New Delivery', ATUM_PO_TEXT_DOMAIN ),
			'view'                  => __( 'View Delivery', ATUM_PO_TEXT_DOMAIN ),
			'view_item'             => __( 'View Delivery', ATUM_PO_TEXT_DOMAIN ),
			'search_items'          => __( 'Search Deliveries', ATUM_PO_TEXT_DOMAIN ),
			'not_found'             => __( 'No deliveries were found', ATUM_PO_TEXT_DOMAIN ),
			'not_found_in_trash'    => __( 'No deliveries found in trash', ATUM_PO_TEXT_DOMAIN ),
			'parent'                => __( 'Parent delivery', ATUM_PO_TEXT_DOMAIN ),
			'menu_name'             => _x( 'PO Deliveries', 'Admin menu name', ATUM_PO_TEXT_DOMAIN ),
			'filter_items_list'     => __( 'Filter deliveries', ATUM_PO_TEXT_DOMAIN ),
			'items_list_navigation' => __( 'Deliveries navigation', ATUM_PO_TEXT_DOMAIN ),
			'items_list'            => __( 'Deliveries list', ATUM_PO_TEXT_DOMAIN ),
		);
	}

	/**
	 * Save the Delivery meta boxes
	 *
	 * @since 0.9.1
	 *
	 * @param int $po_id
	 */
	public function save_meta_boxes( $po_id ) {

		// Save the delivery data when saving a PO.
		if ( doing_action( 'save_post_' . PurchaseOrders::POST_TYPE ) && ! empty( $_POST['delivery'] ) ) {

			foreach ( $_POST['delivery'] as $delivery_id => $delivery_data ) {

				$delivery = new Delivery( $delivery_id );

				foreach ( $delivery_data as $key => $value ) {
					if ( is_callable( array( $delivery, "set_$key" ) ) ) {
						$delivery->{"set_$key"}( $value );
					}
				}

				$delivery->save( TRUE );

			}

		}

	}

	/**
	 * Get the currently instantiated Delivery object (if any) or create a new one
	 *
	 * @since 0.9.1
	 *
	 * @param int  $post_id
	 * @param bool $read_items
	 *
	 * @return Delivery|NULL
	 */
	public function get_current_atum_order( $post_id, $read_items ) {

		if ( ! $this->delivery || $this->delivery->get_id() != $post_id ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$this->delivery = AtumHelpers::get_atum_order_model( $post_id, $read_items, self::POST_TYPE );
		}

		return $this->delivery;

	}

	/**
	 * Adds compatibility with the Delivery model class
	 *
	 * @since 0.9.1
	 *
	 * @param string $model_class
	 * @param string $post_type
	 *
	 * @return string
	 */
	public function add_delivery_model_class( $model_class, $post_type ) {

		if ( self::POST_TYPE === $post_type ) {
			$model_class = '\AtumPO\Deliveries\Models\Delivery';
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
	 * @return Deliveries instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

<?php
/**
 * Handle barcodes metaboxes
 *
 * @since       0.0.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumBarcodes/Inc
 */

namespace AtumBarcodes\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumHelpGuide;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use Automattic\WooCommerce\Admin\Overrides\Order;


class Metaboxes {

	/**
	 * The singleton instance holder
	 *
	 * @var Metaboxes
	 */
	private static $instance;

	/**
	 * Metaboxes singleton constructor.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		if ( is_admin() ) {

			// Add meta boxes to some post types.
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 1 );

			// Enqueue scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		}

	}

	/**
	 * Registers the meta box to the post screen
	 *
	 * @since 0.0.1
	 *
	 * @param string $post_type
	 */
	public function add_meta_box( $post_type ) {

		global $post;

		$allowed_entities = AtumHelpers::get_option( 'bp_entities_support', NULL );

		// NULL means all the entities are enabled (default). An empty array means all the entities are disabled.
		if ( is_array( $allowed_entities ) ) {

			if (
				empty( $allowed_entities ) ||
				( isset( $allowed_entities['options'][ $post_type ] ) && 'no' === $allowed_entities['options'][ $post_type ] )
			) {
				return;
			}

		}

		if (
			in_array( $post_type, Globals::get_allowed_post_types() ) &&
			apply_filters( 'atum/barcodes_pro/show_meta_box', TRUE, $post )
		) {

			// Data meta box.
			add_meta_box(
				'atum_barcodes',
				__( 'ATUM Barcodes', ATUM_BARCODES_TEXT_DOMAIN ),
				array( $this, 'show_barcodes_meta_box' ),
				$post_type,
				'side',
				'high'
			);

		}

	}

	/**
	 * Displays the ATUM barcodes meta box in post pages
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post|Order $post
	 */
	public function show_barcodes_meta_box( $post ) {

		$barcode_type    = AtumHelpers::get_option( 'bp_default_barcode_type', 'EAN13' );
		$default_barcode = '';

		if ( $post instanceof \WP_Post ) {

			if ( ! Helpers::is_entity_supported( $post->post_type ) ) {
				return;
			}

			switch ( $post->post_type ) {
				case 'product':
					$product      = AtumHelpers::get_atum_product( $post->ID, TRUE );
					$barcode      = $product->get_barcode();
					$barcode_type = Helpers::get_product_barcode_type( $product );
					break;

				case 'shop_order':
				case 'shop_order_refund':
				case PurchaseOrders::POST_TYPE:
				case InventoryLogs::POST_TYPE:
					$barcode = get_post_meta( $post->ID, Globals::ATUM_BARCODE_META_KEY, TRUE );

					if ( ! $barcode && 'yes' === AtumHelpers::get_option( 'bp_orders_barcodes', 'yes' ) ) {
						$default_barcode = $post->ID;
					}
					break;

				default:
					$barcode = get_post_meta( $post->ID, Globals::ATUM_BARCODE_META_KEY, TRUE );
					break;
			}

		}
		// HPOS order.
		else {

			if ( ! Helpers::is_entity_supported( 'woocommerce_page_wc-orders' ) ) {
				return;
			}

			$barcode = $post->get_meta( Globals::ATUM_BARCODE_META_KEY );

			if ( ! $barcode && 'yes' === AtumHelpers::get_option( 'bp_orders_barcodes', 'yes' ) ) {
				$default_barcode = $post->get_id();
			}

		}

		$barcode_img = '';

		if ( $barcode || $default_barcode ) {
			$barcode_img = Helpers::generate_barcode( $barcode ?: $default_barcode, [ 'type' => $barcode_type ] );
		}

		AtumHelpers::load_view( ATUM_BARCODES_PATH . 'views/meta-boxes/barcodes', compact( 'default_barcode', 'barcode', 'barcode_img', 'post' ) );

	}

	/**
	 * Enqueue scripts and styles for the metaboxes
	 *
	 * @since 0.1.1
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		global $post, $taxonomy;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if (
			( in_array( $hook, ['post.php', 'post-new.php'] ) && $post && in_array( $post->post_type, Globals::get_allowed_post_types() ) ) ||
			( 'term.php' === $hook && in_array( $taxonomy, Globals::get_allowed_taxonomies() ) ) ||
			( AtumHelpers::is_using_hpos_tables() && function_exists( 'wc_get_page_screen_id' ) && wc_get_page_screen_id( 'shop-order' ) === $screen_id && ( ! empty( $_GET['id'] ) || ( ! empty( $_GET['action'] ) && 'new' === $_GET['action'] ) ) )
		) {

            AtumHelpers::register_swal_scripts();
			wp_register_style( 'atum-barcodes-metabox', ATUM_BARCODES_URL . 'assets/css/atum-barcodes-metaboxes.css', [ 'sweetalert2' ], ATUM_BARCODES_VERSION );
			wp_register_script( 'atum-barcodes-metabox', ATUM_BARCODES_URL . 'assets/js/build/atum-barcodes-metaboxes.js', [ 'jquery', 'sweetalert2' ], ATUM_BARCODES_VERSION, TRUE );

			$js_vars = array(
				'clickToExpand'      => __( 'Click to expand', ATUM_BARCODES_TEXT_DOMAIN ),
				'nonce'              => wp_create_nonce( 'barcodes-metabox-nonce' ),
				'noVariationBarcode' => __( 'This variation has no barcode yet', ATUM_BARCODES_TEXT_DOMAIN ),
				'unexpectedError'    => __( 'Unexpected error!', ATUM_BARCODES_TEXT_DOMAIN ),
			);

			// Pass the right main guide depending on the current screen and item.
			if ( in_array( $hook, ['post.php', 'post-new.php'] ) && $post && in_array( $post->post_type, Globals::get_allowed_post_types() ) ) {

				$main_guide = '';

				switch ( $post->post_type ) {
					case 'product':
						$product    = AtumHelpers::get_atum_product( $post->ID, TRUE );
						$main_guide = $product->is_type( 'variable' ) ? 'atum_bp_products_variable' : 'atum_bp_products';
				        break;

					case 'shop_order':
					case 'shop_order_refund':
						$main_guide = 'atum_bp_orders';
						break;

					case PurchaseOrders::POST_TYPE:
					case InventoryLogs::POST_TYPE:
						$main_guide = 'atum_bp_atum_orders';
						break;

					case Suppliers::POST_TYPE:
						$main_guide = 'atum_bp_suppliers';
						break;
				}

			}
			elseif ( 'term.php' === $hook && in_array( $taxonomy, Globals::get_allowed_taxonomies() ) ) {
				$main_guide = 'atum_bp_taxonomies';
			}
			elseif ( AtumHelpers::is_using_hpos_tables() && function_exists( 'wc_get_page_screen_id' ) && wc_get_page_screen_id( 'shop-order' ) === $screen_id ) {
				$main_guide = 'atum_bp_orders';
			}

			$main_guide = apply_filters( 'atum/barcodes_pro/main_guide', $main_guide, $post, $taxonomy, $hook );
			$js_vars = array_merge( $js_vars, AtumHelpGuide::get_instance()->get_help_guide_js_vars( $main_guide, $main_guide, ATUM_BARCODES_URL ) );

			wp_localize_script( 'atum-barcodes-metabox', 'atumBPVars', $js_vars );

			wp_enqueue_style( 'atum-barcodes-metabox' );
			wp_enqueue_script( 'atum-barcodes-metabox' );

		}

	}


	/*******************
	 * Instance methods
	 *******************/

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
	 * @return Metaboxes instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

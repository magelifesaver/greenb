<?php
/**
 * Handle barcodes for Products
 *
 * @since       0.0.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumBarcodes
 */

namespace AtumBarcodes\Entities;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use AtumBarcodes\Inc\Helpers;


class Products {

	/**
	 * The singleton instance holder
	 *
	 * @var Products
	 */
	private static $instance;

	/**
	 * Products singleton constructor.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		if ( is_admin() ) {

			// Add extra fields to ATUM's product data tab.
			add_action( 'atum/after_product_data_panel', array( $this, 'add_product_data_tab_fields' ), 100 );

			// Save the Barcodes meta boxes once ATUM has processed all its own meta boxes.
			add_action( 'atum/product_data/after_save_product_meta_boxes', array( $this, 'save_product_meta_boxes' ) );

			// Add the barcode within each variation.
			add_action( 'atum/barcodes/after_barcode_field', array( $this, 'add_barcode_to_variations' ), 10, 2 );

		}

	}

	/**
	 * Add fields to WC product data meta box individual Barcode settings
	 *
	 * @since 0.1.1
	 */
	public function add_product_data_tab_fields() {

		global $post, $thepostid;

		$thepostid = $post->ID;
		$product   = AtumHelpers::get_atum_product( $thepostid );

		$visibility_classes = array_map( function ( $val ) {
			return "show_if_{$val}";
		}, AtumGlobals::get_product_types_with_stock() );

		$view_args = array(
			'field_visibility'     => apply_filters( 'atum/barcodes_pro/atum_tab_fields_visibility', implode( ' ', $visibility_classes ) ),
			'product_type'         => $product->get_type(),
			'product_barcode_type' => Helpers::get_product_barcode_type( $product, TRUE ),
		);

		AtumHelpers::load_view( ATUM_BARCODES_PATH . 'views/meta-boxes/product-data/atum-tab-fields', $view_args );

	}

	/**
	 * Saves the barcodes meta boxes in products
	 *
	 * @since 0.1.1
	 *
	 * @param int $product_id The product ID. If WPML is installed, must be original translation.
	 */
	public function save_product_meta_boxes( $product_id ) {

		$product = AtumHelpers::get_atum_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		// Barcodes options at product level.
		$value = isset( $_POST['barcode_type'] ) ? wc_clean( $_POST['barcode_type'] ) : '';

		if ( $product->get_barcode_type() !== $value ) {
			$product->set_barcode_type( $value ?: NULL );
			$product->save_atum_data();
		}

	}

	/**
	 * Add the barcode within each variation
	 *
	 * @since 0.1.4
	 *
	 * @param \WP_Post $variation
	 * @param string   $barcode
	 */
	public function add_barcode_to_variations( $variation, $barcode ) {

		if ( ! empty( $variation ) && $barcode && apply_filters( 'atum/barcodes_pro/add_barcode_to_variations', TRUE, $variation ) ) {

			$variation = AtumHelpers::get_atum_product( $variation->ID, TRUE );
			$variable  = AtumHelpers::get_atum_product( $variation->get_parent_id(), TRUE );

			$barcode_img = Helpers::generate_barcode( $barcode, [
				'type'      => $variable->get_barcode_type(),
				'show_text' => FALSE,
			] );

			if ( $barcode_img && ! is_wp_error( $barcode_img ) ) : ?>
				<p class="form-field form-row form-row-last _variation_barcode"><?php echo $barcode_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif;

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
	 * @return Products instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

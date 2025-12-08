<?php
/**
 * Handle barcodes for Taxonomies
 *
 * @since       0.0.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumBarcodes
 */

namespace AtumBarcodes\Entities;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use AtumBarcodes\Inc\Helpers;

class Taxonomies {

	/**
	 * The singleton instance holder
	 *
	 * @var Taxonomies
	 */
	private static $instance;

	/**
	 * Taxonomies singleton constructor.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Add the barcode to terms.
		add_action( 'atum/barcodes/after_barcode_term_meta_input', array( $this, 'add_barcode_to_terms' ), 10, 2 );

	}

	/**
	 * Add the barcode to terms
	 *
	 * @since 0.1.2
	 *
	 * @param \WP_Term $term
	 * @param string   $barcode
	 */
	public function add_barcode_to_terms( $term, $barcode ) {

		if ( $barcode ) {
			$barcode_img = Helpers::generate_barcode( $barcode );

			AtumHelpers::load_view( ATUM_BARCODES_PATH . 'views/meta-boxes/barcodes', compact( 'barcode', 'barcode_img' ) );
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
	 * @return Taxonomies instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

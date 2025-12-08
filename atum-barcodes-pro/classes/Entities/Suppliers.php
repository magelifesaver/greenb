<?php
/**
 * Handle barcodes for Suppliers
 *
 * @since       0.2.6
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumBarcodes
 */

namespace AtumBarcodes\Entities;

defined( 'ABSPATH' ) || die;

use Atum\Suppliers\Supplier;

class Suppliers {

	/**
	 * The singleton instance holder
	 *
	 * @var Suppliers
	 */
	private static $instance;

	/**
	 * Suppliers singleton constructor.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Add the barcode field to suppliers.
		add_action( 'atum/suppliers/after_supplier_details', array( $this, 'add_barcode_field' ) );

	}

	/**
	 * Add the barcode field to suppliers
	 *
	 * @since 0.2.6
	 *
	 * @param Supplier $supplier
	 */
	public function add_barcode_field( $supplier ) {

		?>
		<div class="form-field form-field-wide">
			<label for="barcode"><?php esc_html_e( 'Barcode', ATUM_BARCODES_TEXT_DOMAIN ) ?></label>
			<input type="text" id="atum_barcode" name="supplier_details[atum_barcode]" value="<?php echo esc_attr( $supplier->atum_barcode ) ?>">
		</div>
		<?php

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
	 * @return Suppliers instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

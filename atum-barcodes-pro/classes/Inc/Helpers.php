<?php
/**
 * Helpers functions
 *
 * @package     AtumBarcodes\Inc
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.1.1
 */

namespace AtumBarcodes\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumColors;
use Atum\Inc\Helpers as AtumHelpers;
use Milon\Barcode\DNS1D;
use Milon\Barcode\DNS2D;
use Milon\Barcode\WrongCheckDigitException;


final class Helpers {

	/**
	 * Get the barcode type for the specified product
	 *
	 * @since 0.1.1
	 *
	 * @param int|\WC_Product $product      The product ID or product object.
	 * @param bool            $allow_global Optional. If FALSE, only can return one of the allowed barcode types. If TRUE, it could return 'global'.
	 *
	 * @return string
	 */
	public static function get_product_barcode_type( $product, $allow_global = FALSE ) {
		return AtumHelpers::get_product_prop( $product, 'barcode_type', 'EAN13', 'bp', $allow_global );
	}

	/**
	 * Generate a barcode from its related text
	 *
	 * @since 0.1.3
	 *
	 * @param string $barcode_text The text to convert to a barcode image.
	 * @param array  $options      {
	 *      Optional. The options for the barcode generation.
	 *
	 *      @type string $type      The barcode type to generate.
	 *      @type bool   $show_text Whether to show the barcode text under the image.
	 *      @type string $color     The barcode output color.
	 *      @type int    $width_1d  The barcode width (for 1D barcodes).
	 *      @type int    $height_1d The barcode height (for 1D barcodes).
	 *      @type int    $width_2d  The barcode width (for 2D barcodes).
	 *      @type int    $height_2d The barcode height (for 2D barcodes).
	 *      @type string $format    Optional. The format to return. It can be either 'svg' or 'png' or 'html'. By default, will return a svg.
	 * }
	 *
	 * @return string|\WP_Error
	 */
	public static function generate_barcode( $barcode_text, $options = [] ) {

		if ( ! $barcode_text ) {
			return '';
		}

		$default_options = array(
			'type'      => AtumHelpers::get_option( 'bp_default_barcode_type', 'EAN13' ),
			'show_text' => wc_string_to_bool( AtumHelpers::get_option( 'bp_show_text', 'yes' ) ),
			'color'     => AtumHelpers::get_option( 'bp_color', '#000' ),
			'width_1d'  => 2,
			'height_1d' => 50,
			'width_2d'  => 4,
			'height_2d' => 4,
			'format'    => strlen( $barcode_text ) > 8 ? 'png' : 'svg', // The default format for large barcodes is PNG.
		);

		/**
		 * Variable definition
		 *
		 * @var string $type
		 * @var bool   $show_text
		 * @var string $color
		 * @var int    $width_1d
		 * @var int    $height_1d
		 * @var int    $width_2d
		 * @var int    $height_2d
		 * @var string $format
		 */
		extract( wp_parse_args( $options, $default_options ) );

		// Validate the barcode type.
		if ( ! $type || ! array_key_exists( $type, Globals::get_allowed_barcodes() ) ) {
			$type = $default_options['type'];
		}

		$barcode_max_length = Globals::get_barcode_type_length( $type );
		if ( $barcode_max_length && strlen( $barcode_text ) > $barcode_max_length ) {
			$barcode_text = substr( $barcode_text, 0, $barcode_max_length );
		}

		try {

			if ( array_key_exists( $type, Globals::get_allowed_1d_barcodes() ) ) {

				$d = new DNS1D();
				$d->setStorPath( ATUM_BARCODES_PATH . 'cache/' );
                $barcode_text = strval( $barcode_text ); // Make sure the barcode text is a string to avoid deprecation errors in PHP 8.2+.

				switch ( $format ) {
					case 'png':
						$rgb_color = [ 0, 0, 0 ];

						if ( $color ) {
							$rgb_color = AtumColors::hex_to_rgb( $color, TRUE );
						}

						$png_base64 = $d->getBarcodePNG( $barcode_text, $type, $width_1d, $height_1d, $rgb_color, $show_text );

						return '<img src="data:image/png;base64,' . $png_base64 . '">';

					case 'html':
						return $d->getBarcodeHTML( $barcode_text, $type, $width_1d, $height_1d, $color, $show_text );

					case 'svg':
					default:
						return $d->getBarcodeSVG( $barcode_text, $type, $width_1d, $height_1d, $color, $show_text, TRUE );
				}

			}
			elseif ( array_key_exists( $type, Globals::get_allowed_2d_barcodes() ) ) {

				$d = new DNS2D();
				$d->setStorPath( ATUM_BARCODES_PATH . 'cache/' );

				// The "pdf417" type is returning a non-responsive SVG, and it works better this way.
				if ( 'PDF417' === $type ) {
					$format = 'png';
				}

				switch ( $format ) {
					case 'png':
						$rgb_color = [ 0, 0, 0 ];

						if ( $color ) {
							$rgb_color = AtumColors::hex_to_rgb( $color, TRUE );
						}

						$png_base64 = $d->getBarcodePNG( $barcode_text, $type, $width_2d, $height_2d, $rgb_color );

						return '<img src="data:image/png;base64,' . $png_base64 . '">';

					case 'html':
						return $d->getBarcodeHTML( $barcode_text, $type, $width_2d, $height_2d, $color );

					case 'svg':
					default:
						return $d->getBarcodeSVG( $barcode_text, $type, $width_2d, $height_2d, $color );
				}

			}
			else {
				throw new \Exception( __( 'Barcode type not allowed', ATUM_BARCODES_TEXT_DOMAIN ) );
			}

		} catch ( WrongCheckDigitException $de ) {

			if ( TRUE === ATUM_DEBUG ) {
				error_log( $de->getMessage() );
			}

			return new \WP_Error( 'generate_barcode', sprintf( __( 'Wrong check digit: %s', ATUM_BARCODES_TEXT_DOMAIN ), $de->getMessage() ) );

		} catch ( \Exception $e ) {

			if ( TRUE === ATUM_DEBUG ) {
				error_log( $e->getMessage() );
			}

			return new \WP_Error( 'generate_barcode', $e->getMessage() );

		}

	}

	/**
	 * Check whether an entity supports barcodes
	 *
	 * @since 0.1.8
	 *
	 * @param string $entity
	 *
	 * @return bool
	 */
	public static function is_entity_supported( $entity ) {

		if (
			! in_array( $entity, Globals::get_allowed_post_types() ) &&
			! in_array( $entity, Globals::get_allowed_taxonomies() )
		) {
			return FALSE;
		}

		// By default, all the default entities are supported.
		$allowed_entities = AtumHelpers::get_option( 'bp_entities_support', NULL );

		return ! is_array( $allowed_entities ) || ( isset( $allowed_entities['options'][ $entity ] ) && 'yes' === $allowed_entities['options'][ $entity ] );

	}

}

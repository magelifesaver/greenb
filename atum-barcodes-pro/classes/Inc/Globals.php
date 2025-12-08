<?php
/**
 * Global Options for ATUM Barcodes PRO
 *
 * @package         AtumBarcodes
 * @subpackage      Inc
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.0.2
 */

namespace AtumBarcodes\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers as AtumSuppliers;

final class Globals {

	/**
	 * The barcode meta key (for all post types except for products).
	 */
	const ATUM_BARCODE_META_KEY = '_atum_barcode';

	/**
	 * Get allowed 1D barcode types
	 *
	 * @since 0.0.2
	 *
	 * @return string[]
	 */
	public static function get_allowed_1d_barcodes() {

		return array(
			'C39'      => __( 'Code 39', ATUM_BARCODES_TEXT_DOMAIN ),
			'C39+'     => __( 'Code 39 + Checksum', ATUM_BARCODES_TEXT_DOMAIN ),
			'C39E'     => __( 'Code 39 Extended', ATUM_BARCODES_TEXT_DOMAIN ),
			'C39E+'    => __( 'Code 39 Extended + Checksum', ATUM_BARCODES_TEXT_DOMAIN ),
			'C93'      => __( 'Code 93', ATUM_BARCODES_TEXT_DOMAIN ),
			'S25'      => __( 'Standard 2 of 5', ATUM_BARCODES_TEXT_DOMAIN ),
			'S25+'     => __( 'Standard 2 of 5 + Checksum', ATUM_BARCODES_TEXT_DOMAIN ),
			'I25'      => __( 'Interleaved 2 of 5', ATUM_BARCODES_TEXT_DOMAIN ),
			'I25+'     => __( 'Interleaved 2 of 5 + Checksum', ATUM_BARCODES_TEXT_DOMAIN ),
			'C128'     => __( 'Code 128 Auto', ATUM_BARCODES_TEXT_DOMAIN ),
			'C128A'    => __( 'Code 128 A', ATUM_BARCODES_TEXT_DOMAIN ),
			'C128B'    => __( 'Code 128 B', ATUM_BARCODES_TEXT_DOMAIN ),
			'C128C'    => __( 'Code 128 C', ATUM_BARCODES_TEXT_DOMAIN ),
			'EAN8'     => __( 'EAN 8', ATUM_BARCODES_TEXT_DOMAIN ),
			'EAN13'    => __( 'EAN 13', ATUM_BARCODES_TEXT_DOMAIN ),
			'UPCA'     => __( 'UPC-A', ATUM_BARCODES_TEXT_DOMAIN ),
			'UPCE'     => __( 'UPC-E', ATUM_BARCODES_TEXT_DOMAIN ),
			'EAN5'     => __( '5-Digits UPC-Based Extention', ATUM_BARCODES_TEXT_DOMAIN ),
			'EAN2'     => __( '2-Digits UPC-Based Extention', ATUM_BARCODES_TEXT_DOMAIN ),
			'MSI'      => __( 'MSI', ATUM_BARCODES_TEXT_DOMAIN ),
			'MSI+'     => __( 'MSI + Checksum', ATUM_BARCODES_TEXT_DOMAIN ),
			'POSTNET'  => __( 'Postnet', ATUM_BARCODES_TEXT_DOMAIN ),
			'PLANET'   => __( 'Planet', ATUM_BARCODES_TEXT_DOMAIN ),
			'RMS4CC'   => __( 'RMS4CC', ATUM_BARCODES_TEXT_DOMAIN ),
			'KIX'      => __( 'KIX (Klant index - Customer index)', ATUM_BARCODES_TEXT_DOMAIN ),
			'IMB'      => __( 'IMB (Intelligent Mail Barcode - Onecode - USPS-B-3200)', ATUM_BARCODES_TEXT_DOMAIN ),
			'CODABAR'  => __( 'Codabar', ATUM_BARCODES_TEXT_DOMAIN ),
			'CODE11'   => __( 'Code 11', ATUM_BARCODES_TEXT_DOMAIN ),
			'PHARMA'   => __( 'Pharmacode', ATUM_BARCODES_TEXT_DOMAIN ),
			'PHARMA2T' => __( 'Pharmacode Two-Tracks', ATUM_BARCODES_TEXT_DOMAIN ),
		);

	}

	/**
	 * Get allowed 2D barcode types
	 *
	 * @since 0.0.2
	 *
	 * @return string[]
	 */
	public static function get_allowed_2d_barcodes() {

		return array(
			'QRCODE'     => __( 'QR Code', ATUM_BARCODES_TEXT_DOMAIN ),
			'PDF417'     => __( 'PDF417', ATUM_BARCODES_TEXT_DOMAIN ),
			'DATAMATRIX' => __( 'DATAMATRIX', ATUM_BARCODES_TEXT_DOMAIN ),
		);

	}

	/**
	 * Get all the allowed barcode types
	 *
	 * @since 0.0.2
	 *
	 * @return string[]
	 */
	public static function get_allowed_barcodes() {
		return array_merge( self::get_allowed_1d_barcodes(), self::get_allowed_2d_barcodes() );
	}

	/**
	 * Get the post types that support barcodes
	 *
	 * @since 0.1.8
	 *
	 * @return array
	 */
	public static function get_allowed_post_types() {

		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment
		$allowed_post_types = array(
			__( 'Products', ATUM_BARCODES_TEXT_DOMAIN )        => 'product',
			__( 'Purchase Orders', ATUM_BARCODES_TEXT_DOMAIN ) => PurchaseOrders::POST_TYPE,
			__( 'Inventory Logs', ATUM_BARCODES_TEXT_DOMAIN )  => InventoryLogs::POST_TYPE,
			__( 'Suppliers', ATUM_BARCODES_TEXT_DOMAIN )       => AtumSuppliers::POST_TYPE,
		);
		// phpcs:enable

		if ( AtumHelpers::is_using_hpos_tables() ) {
			$allowed_post_types[ __( 'WC Orders', ATUM_BARCODES_TEXT_DOMAIN ) ] = 'woocommerce_page_wc-orders'; // The screen ID for HPOS orders.
		}
		else {
			$allowed_post_types[ __( 'WC Orders', ATUM_BARCODES_TEXT_DOMAIN ) ]  = 'shop_order';
			$allowed_post_types[ __( 'WC Refunds', ATUM_BARCODES_TEXT_DOMAIN ) ] = 'shop_order_refund';
		}

		return apply_filters( 'atum/barcodes_pro/allowed_post_types', $allowed_post_types );

	}

	/**
	 * Get the taxonomies that support barcodes
	 *
	 * @since 0.1.8
	 *
	 * @return array
	 */
	public static function get_allowed_taxonomies() {

		return apply_filters( 'atum/barcodes_pro/allowed_taxonomies', array(
			__( 'ATUM Locations', ATUM_BARCODES_TEXT_DOMAIN )     => AtumGlobals::PRODUCT_LOCATION_TAXONOMY,
			__( 'Product Categories', ATUM_BARCODES_TEXT_DOMAIN ) => 'product_cat',
			__( 'Product Tags', ATUM_BARCODES_TEXT_DOMAIN )       => 'product_tag',
		) );

	}

	/**
	 * Get the length for the barcodes that must have a specific length
	 *
	 * @since 0.2.8
	 *
	 * @param string $type The barcode type to check
	 *
	 * @return int|false
	 */
	public static function get_barcode_type_length( $type ) {

		$barcode_lengths = array(
			'EAN2'  => 2,
			'EAN5'  => 5,
			'EAN8'  => 8,
			'EAN13' => 13,
			'UPCA'  => 12,
			'UPCE'  => 6,
		);

		return $barcode_lengths[ $type ] ?? FALSE;

	}

}

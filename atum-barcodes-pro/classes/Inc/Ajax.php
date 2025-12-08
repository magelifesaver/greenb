<?php
/**
 * Ajax callbacks
 *
 * @package         AtumBarcodes
 * @subpackage      Inc
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.1.4
 */

namespace AtumBarcodes\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use Atum\Inc\Globals as AtumGlobals;

final class Ajax {

	/**
	 * The singleton instance holder
	 *
	 * @var Ajax
	 */
	private static $instance;


	/**
	 * Ajax singleton constructor.
	 *
	 * @since 0.1.4
	 */
	private function __construct() {

		// Load all the variations barcodes from the variable product pages.
		add_action( 'wp_ajax_atum_bp_load_variations_barcodes', array( $this, 'load_variations_barcodes' ) );

		// Convert barcodes from any meta key to ATUM.
		add_action( 'wp_ajax_atum_tool_bp_convert_barcodes', array( $this, 'convert_barcodes_tool' ) );

	}

	/**
	 * Load barcodes for all the variations.
	 *
	 * @since 0.1.4
	 */
	public function load_variations_barcodes() {

		check_ajax_referer( 'barcodes-metabox-nonce', 'security' );

		if ( empty( $_POST['id'] ) ) {
			wp_send_json_error( __( 'Missing product ID', ATUM_BARCODES_TEXT_DOMAIN ) );
		}

		$product = AtumHelpers::get_atum_product( absint( $_POST['id'] ), TRUE );

		if ( ! $product instanceof \WC_Product_Variable ) {
			wp_send_json_error( __( 'This is not a variable product', ATUM_BARCODES_TEXT_DOMAIN ) );
		}

		$variation_ids = $product->get_children();
		$barcodes_data = [];

		if ( ! empty( $variation_ids ) && is_array( $variation_ids ) ) {

			// The barcode type is set at the parent level.
			$barcode_type = $product->get_barcode_type();

			foreach ( $variation_ids as $variation_id ) {

				$variation   = AtumHelpers::get_atum_product( $variation_id, TRUE );
				$barcode     = $variation->get_barcode();
				$barcode_img = '';

				if ( $barcode ) {
					$barcode_img = Helpers::generate_barcode( $barcode, [ 'type' => $barcode_type ] );

					if ( is_wp_error( $barcode_img ) ) {
						$barcode_img = '';
					}
				}

				$barcodes_data[ $variation_id ] = apply_filters( 'atum/barcodes_pro/load_variation_barcode', array(
					'name'       => $variation->get_formatted_name(),
					'barcode'    => $barcode,
					'barcodeImg' => $barcode_img,
				), $variation );

			}

		}

		wp_send_json_success( $barcodes_data );

	}

	/**
	 * Convert barcodes from any meta key to ATUM.
	 *
	 * @since 0.2.2
	 */
	public function convert_barcodes_tool() {

		check_ajax_referer( 'atum-script-runner-nonce', 'security' );

		if ( empty( $_POST['option'] ) ) {
			wp_send_json_error( __( 'Please, provide a valid meta key name.', ATUM_BARCODES_TEXT_DOMAIN ) );
		}

		$cust_barcode_meta = sanitize_text_field( $_POST['option'] );

		global $wpdb;

		// Convert barcodes for products.
		$atum_product_data_table = $wpdb->prefix . AtumGlobals::ATUM_PRODUCT_DATA_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result1 = $wpdb->query( $wpdb->prepare( "
			UPDATE $atum_product_data_table AS dest, (
				SELECT DISTINCT p.ID, pm.meta_value AS barcode 
				FROM $wpdb->posts p
				INNER JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id and pm.meta_key = %s) 
			    WHERE p.post_type IN('product', 'product_variation') AND pm.meta_value != ''
			) AS src		
			SET dest.barcode = src.barcode
			WHERE dest.product_id = src.ID AND (dest.barcode IS NULL OR dest.barcode = '')		
		", $cust_barcode_meta ) );
		// phpcs:enable

		// Convert barcodes for other post types.

		/* Inserts */

		$result2 = $wpdb->query( $wpdb->prepare( "
			INSERT INTO $wpdb->postmeta (`post_id`, `meta_key`, `meta_value`)
			SELECT `post_id`, %s, `meta_value`
			FROM $wpdb->postmeta pm
			WHERE `meta_key` = %s AND `meta_value` != ''
			AND NOT EXISTS(
			    SELECT 1
			    FROM $wpdb->postmeta AS pm2
			    WHERE pm2.post_id = pm.post_id
			    AND pm2.meta_key = %s
			)				
		", Globals::ATUM_BARCODE_META_KEY, $cust_barcode_meta, Globals::ATUM_BARCODE_META_KEY ) );

		/* Updates */

		$result3 = $wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->postmeta AS dest, (
				SELECT `post_id`, `meta_value` 
				FROM $wpdb->postmeta			 
			    WHERE `meta_key` = %s AND `meta_value` != ''
			) AS src		
			SET dest.meta_value = src.meta_value
			WHERE dest.post_id = src.post_id 
			AND dest.meta_key = %s AND dest.meta_value = ''	
		", $cust_barcode_meta, Globals::ATUM_BARCODE_META_KEY ) );

		if ( FALSE === $result1 || FALSE === $result2 || FALSE === $result3 ) {
			wp_send_json_error( __( 'An unexpected error ocurred when converting the barcodes. Please check your PHP error log.', ATUM_BARCODES_TEXT_DOMAIN ) );
		}

		/* translators: the custom meta key */
		wp_send_json_success( sprintf( __( 'All the barcodes stored in "%s" meta key have been successfully converted to ATUM barcodes', ATUM_BARCODES_TEXT_DOMAIN ), $cust_barcode_meta ) );

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
	 * @return Ajax instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

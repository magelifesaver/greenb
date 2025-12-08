<?php
/**
 * Barcodes PRO Hooks
 *
 * @since       0.1.2
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumBarcodes\Inc
 */

namespace AtumBarcodes\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListTable;
use Atum\Inc\Helpers as AtumHelpers;

class Hooks {

	/**
	 * The singleton instance holder
	 *
	 * @var Hooks
	 */
	private static $instance;

	/**
	 * Hooks singleton constructor
	 */
	private function __construct() {

		if ( is_admin() || AtumHelpers::is_rest_request() ) {
			$this->register_admin_hooks();
		}

		$this->register_global_hooks();

	}

	/**
	 * Register global hooks
	 *
	 * @since 0.1.2
	 */
	private function register_global_hooks() {

		// Add the barcodes PRO columns to be saved with ATUM Data Store columns.
		add_filter( 'atum/data_store/columns', array( $this, 'add_data_store_barcodes_columns' ) );
		add_filter( 'atum/data_store/allow_null_columns', array( $this, 'add_data_store_allow_null_barcodes_columns' ) );

	}

	/**
	 * Register admin hooks
	 *
	 * @since 0.1.2
	 */
	private function register_admin_hooks() {

		// Check whether to remove barcodes support from products.
		add_filter( 'atum/barcodes/barcode_support/product', array( $this, 'maybe_add_product_entity_support' ), 100 );

		// Check whether to remove barcodes support from some taxonomies.
		add_filter( 'atum/barcodes/allowed_taxonomies', array( $this, 'maybe_add_taxonomy_entities_support' ), 100 );

		// Show the barcode image on ATUM list tables.
		if ( 'yes' === AtumHelpers::get_option( 'bp_list_table_barcodes', 'yes' ) ) {
			add_filter( 'atum/barcodes/list_table/editable_args', array( $this, 'barcode_imgs_in_list_tables' ), 10, 2 );
		}

		// Register the BP help guides.
		add_filter( 'atum/help_guides/guides_paths', array( $this, 'register_help_guides' ) );

	}

	/**
	 * Add the barcodes PRO columns to the ATUM data store
	 *
	 * @since 0.1.2
	 *
	 * @param array $atum_columns
	 *
	 * @return array
	 */
	public function add_data_store_barcodes_columns( $atum_columns ) {
		return array_merge( $atum_columns, [ 'barcode_type' ] );
	}

	/**
	 * Add the barcodes PRO columns that allow NULL to the ATUM data store
	 *
	 * @since 0.1.2
	 *
	 * @param array $atum_columns
	 *
	 * @return array
	 */
	public function add_data_store_allow_null_barcodes_columns( $atum_columns ) {
		return array_merge( $atum_columns, [ 'barcode_type' ] );
	}

	/**
	 * Check whether to remove barcodes support from products
	 *
	 * @since 0.1.9
	 *
	 * @return bool
	 */
	public function maybe_add_product_entity_support() {
		return Helpers::is_entity_supported( 'product' );
	}

	/**
	 * Check whether to remove barcodes support from some taxonomies
	 *
	 * @since 0.1.9
	 *
	 * @param string[] $taxonomies
	 *
	 * @return string[]
	 */
	public function maybe_add_taxonomy_entities_support( $taxonomies ) {

		foreach ( $taxonomies as $index => $taxonomy ) {
			if ( ! Helpers::is_entity_supported( $taxonomy ) ) {
				unset( $taxonomies[ $index ] );
			}
		}

		return $taxonomies;

	}

	/**
	 * Show the barcode images in list tables instead of text
	 *
	 * @since 0.2.1
	 *
	 * @param array       $args
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public function barcode_imgs_in_list_tables( $args, $product ) {

		// Prevent trying to generate barcodes for empty columns.
		if ( AtumListTable::EMPTY_COL === $args['value'] ) {
			return $args;
		}

		// If it's a variation, we must get the variable instead.
		if ( $product instanceof \WC_Product && $product->is_type( 'variation' ) ) {
			$product = $product->get_parent_id();
		}

		$barcode_type = Helpers::get_product_barcode_type( $product );

		$barcode_img = Helpers::generate_barcode( $args['value'], [
			'type'      => $barcode_type,
			'color'     => '#00B8DB', // The editable cell color.
			'show_text' => TRUE,
			'width_1d'  => 1,
			'height_id' => 25,
			'width_2d'  => 2,
			'height_2d' => 2,
		] );

		if ( $barcode_img && ! is_wp_error( $barcode_img ) ) {
			$args['value'] = $barcode_img . '<span style="display:none">' . $args['value'] . '</span>';
		}

		return $args;

	}

	/**
	 * Register the BP help guides
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $guides_paths
	 *
	 * @return string[]
	 */
	public function register_help_guides( $guides_paths ) {

		return array_merge( $guides_paths, array(
			'atum_bp_orders'            => ATUM_BARCODES_PATH . 'help-guides/orders',
			'atum_bp_suppliers'         => ATUM_BARCODES_PATH . 'help-guides/suppliers',
			'atum_bp_atum_orders'       => ATUM_BARCODES_PATH . 'help-guides/atum-orders',
			'atum_bp_products'          => ATUM_BARCODES_PATH . 'help-guides/products',
			'atum_bp_products_variable' => ATUM_BARCODES_PATH . 'help-guides/products-variable',
			'atum_bp_taxonomies'        => ATUM_BARCODES_PATH . 'help-guides/taxonomies',
		) );

	}


	/****************************
	 * Instance methods
	 ****************************/

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
	 * @return Hooks instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

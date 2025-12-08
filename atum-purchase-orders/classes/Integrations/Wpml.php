<?php
/**
 * WPML multilingual integration class for Purchase Orders Pro
 *
 * @package        AtumPO\Integrations
 * @subpackage     Integrations
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          1.1.3
 */

namespace AtumPO\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Integrations\Wpml as AtumWpml;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumMultiInventory\Models\Inventory;
use AtumPO\Models\POExtended;
use Atum\Inc\Helpers as AtumHelpers;


class Wpml {

	/**
	 * The singleton instance holder
	 *
	 * @since 1.1.3
	 *
	 * @var Wpml
	 */
	private static $instance;

	/**
	 * The ATUM's WPML class
	 *
	 * @since 1.1.3
	 *
	 * @var AtumWpml
	 */
	private $atum_wpml;

	/**
	 * Wpml constructor
	 *
	 * @since 1.1.3
	 */
	private function __construct() {

		$this->atum_wpml = AtumWpml::get_instance();

		$this->register_hooks();

	}
	
	/**
	 * Register the WPML Atum Purchase Orders hooks
	 *
	 * @since 1.1.3
	 */
	public function register_hooks() {
		
		if ( is_admin() ) {

			add_action( 'atum/purchase_orders/after_po_supplier_data', array( $this, 'add_lang_dropdown' ), 10, 2 );

			// WPML compatibility with Ajax Product Search.
			add_filter( 'wp_ajax_atum_po_json_search_products', array( $this->atum_wpml, 'add_po_filter_search' ), 9 );
			add_filter( 'atum/purchase_orders_pro/multi_inventory_search_data', array( $this, 'update_json_search_inventory_data' ), 10, 4 );

			// WPML compatibility with Supplier items search.
			add_filter( 'atum/purchase_orders_pro/ajax/add_supplier_items/low_stock_join', array( $this, 'add_supplier_translations_join_clause' ) );
			add_filter( 'atum/purchase_orders_pro/ajax/add_supplier_items/low_stock_where', array( $this, 'add_supplier_translations_where_clause' ) );
			add_filter( 'atum/purchase_orders_pro/ajax/add_supplier_items/restock_join', array( $this, 'add_supplier_translations_join_clause' ) );
			add_filter( 'atum/purchase_orders_pro/ajax/add_supplier_items/restock_where', array( $this, 'add_supplier_translations_where_clause' ) );
			add_filter( 'atum/purchase_orders_pro/ajax/add_supplier_items/out_stock_join', array( $this, 'add_supplier_translations_join_clause' ) );
			add_filter( 'atum/purchase_orders_pro/ajax/add_supplier_items/out_stock_where', array( $this, 'add_supplier_translations_where_clause' ) );
			add_filter( 'atum/purchase_orders_pro/bulk_add_to_po', array( $this, 'save_po_wpml_lang' ), 10, 3 );

			// Add is active WPML to localized vars.
			add_filter( 'atum/purchase_orders_pro/atum_po_vars', array( $this, 'add_wpml_po_js_vars' ) );

			// Multi-Inventory integration.
			if ( Addons::is_addon_active( 'multi_inventory' ) ) {
				// Filter inventories and products info depending on the language.
				add_filter( 'atum/load_view_args/' . ATUM_PO_PATH . 'views/meta-boxes/po-items/item', array( $this, 'localize_item_args' ) );

			}

		}

	}

	/**
	 * Add the language dropdown to PO.
	 *
	 * @since 1.1.3
	 *
	 * @param \WP_Post $atum_order_post
	 * @param array    $labels
	 */
	public function add_lang_dropdown( $atum_order_post, $labels ) {

		if ( PurchaseOrders::POST_TYPE !== get_post_type( $atum_order_post->ID ) ) {
			return;
		}

		$active_languages = apply_filters( 'wpml_active_languages', NULL, NULL );
		$po_lang             = get_post_meta( $atum_order_post->ID, '_wpml_lang', TRUE );

		if ( $po_lang ) {
			$this->atum_wpml::$sitepress->switch_lang( $po_lang );
		}

		$po = AtumHelpers::get_atum_order_model( $atum_order_post->ID, FALSE, PurchaseOrders::POST_TYPE ); ?>

		<div class="form-field">
			<label for="supplier_language"><?php esc_html_e( 'Language', ATUM_PO_TEXT_DOMAIN ) ?></label>

			<div class="wpml-lang-dropdown">
				<?php if ( $po->is_editable() ) :

					$this->atum_wpml->echo_lang_flag_dropdown( $po_lang );

				else :

					$active_langs = wpml_get_active_languages(); ?>

					<select class="visible" disabled>
						<option value="<?php echo esc_attr( $this->atum_wpml->current_language ) ?>">
							<?php echo esc_html( $active_langs[ $this->atum_wpml->current_language ]['english_name'] ?? __( 'Default Language', ATUM_PO_TEXT_DOMAIN ) ) ?>
						</option>
					</select>

				<?php endif; ?>
			</div>

		</div>

		<?php
		if ( $po_lang ) {
			$this->atum_wpml::$sitepress->switch_lang( $this->atum_wpml->current_language );
		}

	}

	/**
	 * Update the search inventory data results for translations
	 *
	 * @since 1.1.3
	 *
	 * @param array       $inventory_data
	 * @param Inventory   $inventory
	 * @param \WC_Product $product
	 * @param POExtended  $po
	 *
	 * @return array
	 */
	public function update_json_search_inventory_data( $inventory_data, $inventory, $product, $po ) {

		if ( ! $this->atum_wpml->wpml->products->is_original_product( $product->get_id() ) ) {
			$inventory_data['product_id'] = $product->get_id();
		}

		return $inventory_data;

	}

	/**
	 * Filter item view attributes to adapt the element shown to the PO's lang if possible.
	 *
	 * @since 1.1.3
	 *
	 * @param array $item_args  {.
	 *
	 * @var POExtended           $atum_order
	 * @var AtumOrderItemProduct $item
	 *                                       }
	 *
	 * @return array
	 */
	public function localize_item_args( $item_args ) {

		$po_lang = get_post_meta( $item_args['atum_order']->get_id(), '_wpml_lang', TRUE );

		if ( ! $po_lang ) {
			$po_lang = $this->atum_wpml::$sitepress->get_default_language();
		}

		if ( $item_args['item']->get_variation_id() ) {
			$product_id   = $item_args['item']->get_variation_id();
			$element_type = 'product_variation';
		}
		else {
			$product_id   = $item_args['item']->get_product_id();
			$element_type = 'product';
		}

		$product_lang = $this->atum_wpml::$sitepress->get_language_for_element( $product_id, "post_$element_type" );
		$product      = AtumHelpers::get_atum_product( $product_id );

		// Ensure the product still exists and has a language.
		if ( $product instanceof \WC_Product && $product_lang && $product_lang !== $po_lang ) {

			$po_lang_product_id = wpml_object_id_filter( $product_id, $element_type, TRUE, $po_lang );

			if ( $po_lang_product_id ) {

				$po_lang_product = AtumHelpers::get_atum_product( $po_lang_product_id );

				$item_args['item']->set_name( $po_lang_product->get_name() );
			}

		}

		return $item_args;
	}

	/**
	 * Add join clause to the WPML translations table
	 *
	 * @since 1.1.3
	 *
	 * @param array $join_clauses
	 *
	 * @return array
	 */
	public function add_supplier_translations_join_clause( $join_clauses ) {

		global $wpdb;

		$join_clauses[] = "{$wpdb->prefix}icl_translations tra ON (p.ID = tra.element_id AND tra.element_type IN('post_product','post_product_variation') )";

		return $join_clauses;

	}

	/**
	 * Add where clause to the WPML translations table
	 *
	 * @since 1.1.3
	 *
	 * @param array $where_clauses
	 *
	 * @return array
	 */
	public function add_supplier_translations_where_clause( $where_clauses ) {

		global $wpdb;

		$where_clauses[] = "tra.language_code = '{$this->atum_wpml->current_language}'";

		return $where_clauses;

	}

	/**
	 * Set the PO lang when saving from the add to po modal.
	 *
	 * @since 1.1.3
	 *
	 * @param POExtended $po
	 * @param array      $items
	 * @param array      $qtys
	 *
	 * @return POExtended
	 */
	public function save_po_wpml_lang( $po, $items, $qtys ) {

		$po->set_wpml_lang( $this->atum_wpml->current_language );
		$po->save_meta();

		return $po;
	}

	/**
	 * Add the WPML js needed vars to PO vars
	 *
	 * @since 1.1.5
	 *
	 * @param array $js_vars
	 *
	 * @return array
	 */
	public function add_wpml_po_js_vars( $js_vars ) {

		$js_vars                = $this->atum_wpml->add_wpml_active_var( $js_vars );
		$js_vars['defaultLang'] = $this->atum_wpml::$sitepress->get_default_language();

		return $js_vars;
	}


	/******************
	 * Instace methods
	 ******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LEVELS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LEVELS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Wpml instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

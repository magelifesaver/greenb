<?php
/**
 * Barcodes PRO's bootstrap class
 *
 * @package     AtumBarcodes
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.0.1
 */

namespace AtumBarcodes;

defined( 'ABSPATH' ) || die;

use Atum\Addons\AddonBootstrap;
use Atum\Addons\Addons;
use Atum\Components\AtumBarcodes;
use Atum\PurchaseOrders\PurchaseOrders as AtumPurchaseOrders;
use Atum\Suppliers\Suppliers as AtumSuppliers;
use AtumBarcodes\Entities\Orders;
use AtumBarcodes\Entities\Products;
use AtumBarcodes\Entities\Suppliers;
use AtumBarcodes\Entities\Taxonomies;
use AtumBarcodes\Inc\Ajax;
use AtumBarcodes\Inc\Documents;
use AtumBarcodes\Inc\Helpers;
use AtumBarcodes\Inc\Hooks;
use AtumBarcodes\Inc\Metaboxes;
use AtumBarcodes\Inc\Settings;
use AtumBarcodes\Inc\Upgrade;
use AtumBarcodes\Integrations\MultiInventory;
use AtumBarcodes\Integrations\PickPack;
use AtumBarcodes\Integrations\PurchaseOrders;
use AtumBarcodes\Integrations\StockTakes;


class Bootstrap extends AddonBootstrap {

	/**
	 * Bootstrap constructor
	 *
	 * @since 0.0.1
	 *
	 * @param string $addon_key
	 */
	public function __construct( $addon_key ) {

		parent::__construct( $addon_key );

		if ( ! self::$bootstrapped ) {
			return;
		}

		// Make the Barcodes cache group, non-persistent.
		wp_cache_add_non_persistent_groups( ATUM_BARCODES_TEXT_DOMAIN );

	}

	/**
	 * Load Barcodes PRO's stuff once ATUM is fully loaded.
	 *
	 * @since 0.0.1
	 */
	public function init() {

		// Load language files.
		load_plugin_textdomain( ATUM_BARCODES_TEXT_DOMAIN, FALSE, plugin_basename( ATUM_BARCODES_PATH ) . '/languages' ); // phpcs:ignore: WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found


		// Check the add-on version and run the updater if required.
		$db_version = get_option( 'atum_barcodes_pro_version' );
		if ( version_compare( ATUM_BARCODES_VERSION, $db_version, '!=' ) ) {
			new Upgrade( $db_version ?: '0.0.1' );
		}

		// Make sure all the add-ons have been bootstrapped before adding any integrations.
		if ( Addons::is_addon_active( 'multi_inventory' ) && Helpers::is_entity_supported( 'product' ) ) {
			MultiInventory::get_instance();
		}

		if ( Addons::is_addon_active( 'purchase_orders' ) && Helpers::is_entity_supported( AtumPurchaseOrders::POST_TYPE ) ) {
			PurchaseOrders::get_instance();
		}

		if ( Addons::is_addon_active( 'pick_pack' ) ) {
			PickPack::get_instance();
		}
		
	}

	/**
	 * Load the add-on dependencies
	 *
	 * @since 0.0.1
	 */
	protected function load_dependencies() {

		Hooks::get_instance(); // The hooks class mut be loaded before the AtumBarcodes class.
		AtumBarcodes::get_instance(); // This must be loaded because it's not being loaded by ATUM anymore since we removed the Barcodes module.
		Metaboxes::get_instance();
		Settings::get_instance();
		Documents::get_instance();

		if ( Helpers::is_entity_supported( 'product' ) ) {
			Products::get_instance();
		}

		Taxonomies::get_instance();

		if (
			Helpers::is_entity_supported( 'shop_order' ) ||
			Helpers::is_entity_supported( 'shop_order_refund' ) ||
			Helpers::is_entity_supported( 'woocommerce_page_wc-orders' )
		) {
			Orders::get_instance();
		}

		if ( Helpers::is_entity_supported( AtumSuppliers::POST_TYPE ) ) {
			Suppliers::get_instance();
	    }

		Ajax::get_instance();

	}

}

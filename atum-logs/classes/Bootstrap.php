<?php
/**
 * Adds the Atum Action Logs to the WooCommerce UI
 *
 * @package         AtumLogs
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace AtumLogs;

defined( 'ABSPATH' ) || die;

use Atum\Addons\AddonBootstrap;
use Atum\Addons\Addons;
use Atum\Modules\ModuleManager;
use AtumLogs\Api\AtumLogsApi;
use AtumLogs\Inc\Ajax;
use AtumLogs\Inc\Hooks;
use AtumLogs\Inc\Settings;
use AtumLogs\Inc\Upgrade;
use AtumLogs\Integrations\CostPrice;
use AtumLogs\Integrations\MultiInventory;
use AtumLogs\Integrations\PickPack;
use AtumLogs\Integrations\ProductLevels;
use AtumLogs\Integrations\ExportPro;
use AtumLogs\Integrations\PurchaseOrders;
use AtumLogs\Integrations\StockTakes;
use AtumLogs\LogRegistry\LogRegistry;

class Bootstrap extends AddonBootstrap {

	/**
	 * AtumLogs constructor
	 *
	 * @since 0.0.1
	 *
	 * @param string $addon_key
	 */
	public function __construct( $addon_key ) {

		parent::__construct( $addon_key );

		if ( ! self::$bootstrapped ) {
			FALSE;
		}

		// Make the Atum Action Logs cache group, non-persistent.
		wp_cache_add_non_persistent_groups( ATUM_LOGS_TEXT_DOMAIN );

	}

	/**
	 * Load Atum Action Logs stuff once ATUM is fully loaded.
	 *
	 * @since 0.0.1
	 */
	public function init() {

		// Load language files.
		load_plugin_textdomain( ATUM_LOGS_TEXT_DOMAIN, FALSE, plugin_basename( ATUM_LOGS_PATH ) . '/languages' ); // phpcs:ignore: WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found

		// Check the add-on version and run the updater if required.
		$db_version = get_option( 'atum_action_logs_version' );
		if ( version_compare( ATUM_LOGS_VERSION, $db_version, '!=' ) ) {
			new Upgrade( $db_version ?: '0.0.1' );
		}

		/**
		 * Make sure all the add-ons have been bootstrapped before adding any integrations.
		 */

		// Load Multi-Inventory integration for AL if active.
		if ( Addons::is_addon_active( 'multi_inventory' ) ) {
			MultiInventory::get_instance();
		}

		// Load Product Levels integration for AL if active.
		if ( Addons::is_addon_active( 'product_levels' ) ) {
			ProductLevels::get_instance();
		}

		// Load Export Pro integration for AL if active.
		if ( Addons::is_addon_active( 'export_pro' ) ) {
			ExportPro::get_instance();
		}

		// Load Purchase Orders integration for AL if active.
		if ( Addons::is_addon_active( 'purchase_orders' ) ) {
			PurchaseOrders::get_instance();
		}

		// Load Stock Takes integration for AL if active.
		if ( Addons::is_addon_active( 'stock_takes' ) ) {
			StockTakes::get_instance();
		}

		// Load Pick & Pack integration for AL if active.
		if ( Addons::is_addon_active( 'pick_pack' ) ) {
			PickPack::get_instance();
		}

		// Load Costs Price integration for AL if active.
		if ( Addons::is_addon_active( 'cost_price' ) ) {
			CostPrice::get_instance();
		}

	}

	/**
	 * Load the add-on dependencies
	 *
	 * @since 0.0.1
	 */
	protected function load_dependencies() {

		Hooks::get_instance();
		Settings::get_instance();
		Ajax::get_instance();
		LogRegistry::get_instance();

		if ( ModuleManager::is_module_active( 'api' ) ) {
			AtumLogsApi::get_instance();
		}

	}

}

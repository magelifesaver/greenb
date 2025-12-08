<?php
/**
 * Purchase Orders' bootstrap class
 *
 * @package     AtumPO
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.0.1
 */

namespace AtumPO;

defined( 'ABSPATH' ) || die;

use Atum\Addons\AddonBootstrap;
use Atum\Addons\Addons;
use Atum\Components\AtumAdminNotices;
use Atum\Components\AtumCapabilities;
use Atum\Modules\ModuleManager;
use AtumPO\Api\PurchaseOrdersApi;
use AtumPO\Deliveries\Deliveries;
use AtumPO\Inc\AddToPO;
use AtumPO\Inc\Ajax;
use AtumPO\Deliveries\DeliveryLocations;
use AtumPO\Inc\DuplicatePO;
use AtumPO\Inc\Hooks;
use AtumPO\Inc\ListTables;
use AtumPO\Inc\ReturningPOs;
use AtumPO\Inc\Settings;
use AtumPO\Inc\Upgrade;
use AtumPO\Integrations\MultiInventory;
use AtumPO\Integrations\ProductLevels;
use AtumPO\Integrations\Wpml;
use AtumPO\Invoices\Invoices;
use AtumPO\ListTables\POListPage;
use AtumPO\MetaBoxes\POMetaBoxes;

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

		// Make the Purchase Orders cache group, non-persistent.
		wp_cache_add_non_persistent_groups( ATUM_PO_TEXT_DOMAIN );

	}

	/**
	 * Load POs stuff once ATUM is fully loaded.
	 *
	 * @since 0.0.1
	 */
	public function init() {

		// Load language files.
		load_plugin_textdomain( ATUM_PO_TEXT_DOMAIN, FALSE, plugin_basename( ATUM_PO_PATH ) . '/languages' ); // phpcs:ignore: WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found

		// Check the add-on version and run the updater if required.
		$db_version = get_option( 'atum_purchase_orders_pro_version' );
		if ( version_compare( ATUM_PO_VERSION, $db_version, '!=' ) ) {
			new Upgrade( $db_version ?: '0.0.1' );
		}

		/**
		 * Make sure all the add-ons have been bootstrapped before adding any integrations.
		 */

		// Load WPML integration for PO if active.
		if ( class_exists( '\SitePress' ) && class_exists( '\woocommerce_wpml' ) ) {
			Wpml::get_instance();
		}

		// Multi-Inventory integration.
		if ( Addons::is_addon_active( 'multi_inventory' ) ) {
			MultiInventory::get_instance();
		}

		// Product Levels integration.
		if ( Addons::is_addon_active( 'product_levels' ) ) {
			ProductLevels::get_instance();
		}
		
	}

	/**
	 * Load the add-on dependencies
	 *
	 * @since 0.0.1
	 */
	protected function load_dependencies() {

		// The POs module must be enabled.
		if ( ModuleManager::is_module_active( 'purchase_orders' ) ) {

			if ( ModuleManager::is_module_active( 'api' ) ) {
				PurchaseOrdersApi::get_instance();
			}

			// The current user should have the capability.
			if ( AtumCapabilities::current_user_can( 'read_purchase_orders' ) ) {
				Deliveries::get_instance();
				Invoices::get_instance();
				Hooks::get_instance();
				Ajax::get_instance();
				POMetaBoxes::get_instance();
				DuplicatePO::get_instance();
				Settings::get_instance();
				AddToPO::get_instance();
				POListPage::get_instance();
				DeliveryLocations::get_instance();
				ReturningPOs::get_instance();
			}

		}
		// Show the admin notice and stop loading the add-on.
		else {

			/* translators: The ATUM settings page URL */
			$message = sprintf( __( "The ATUM Purchase Orders PRO add-on requires the Purchase Orders module to be enabled from <a href='%s'>ATUM Settings</a>. Please enable it.", ATUM_PO_TEXT_DOMAIN ), add_query_arg( [
				'page' => 'atum-settings',
				'tab'  => 'module_manager',
			] ), admin_url( 'admin.php' ) );
			AtumAdminNotices::add_notice( $message, 'purchase_orders_module_disabled', 'error' );

		}

	}

}

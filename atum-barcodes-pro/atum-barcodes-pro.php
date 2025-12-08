<?php
/**
 * ATUM Barcodes PRO
 *
 * @link              https://stockmanagementlabs.com/
 * @since             0.0.1
 * @package           AtumBarcodes
 *
 * @wordpress-plugin
 * Plugin Name:          ATUM Barcodes PRO
 * Requires Plugins:     woocommerce, atum-stock-manager-for-woocommerce
 * Plugin URI:           https://stockmanagementlabs.com/addons/atum-barcodes-pro
 * Description:          Transform your inventory management with our Barcodes add-on. Easily create barcodes for products, orders, suppliers, and more. Customize barcodes and take advantage of Multi-Inventory support. Improve operations with barcode integration in list tables, purchase order emails, PDFs, and more. Enhance efficiency and accuracy in inventory tracking today.
 * Version:              1.0.7
 * Author:               Stock Management Labs™
 * Author URI:           https://stockmanagementlabs.com/
 * Contributors:         BE REBEL - https://berebel.studio
 * Requires at least:    5.9
 * Tested up to:         6.8.2
 * Requires PHP:         7.4
 * WC requires at least: 5.0
 * WC tested up to:      10.2.2
 * Text Domain:          atum-barcodes-pro
 * Domain Path:          /languages
 * License:              ©2025 Stock Management Labs™
 */

defined( 'ABSPATH' ) || die;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ATUM_BARCODES_VERSION' ) ) {
	define( 'ATUM_BARCODES_VERSION', '1.0.7' );
}

if ( ! defined( 'ATUM_BARCODES_URL' ) ) {
	define( 'ATUM_BARCODES_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ATUM_BARCODES_PATH' ) ) {
	define( 'ATUM_BARCODES_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ATUM_BARCODES_TEXT_DOMAIN' ) ) {
	define( 'ATUM_BARCODES_TEXT_DOMAIN', 'atum-barcodes-pro' );
}

if ( ! defined( 'ATUM_BARCODES_BASENAME' ) ) {
	define( 'ATUM_BARCODES_BASENAME', plugin_basename( __FILE__ ) );
}

class AtumBarcodesAddon {

	/**
	 * The required minimum version of ATUM
	 */
	const MINIMUM_ATUM_VERSION = '1.9.51';

	/**
	 * The required minimum of Multi-Inventory
	 */
	const MINIMUM_ATUM_MI_VERSION = '1.8.4';

	/**
	 * The required minimum of Purchase Orders PRO
	 */
	const MINIMUM_ATUM_POP_VERSION = '1.2.8';

	/**
	 * The required minimum of Pick & Pack
	 */
	const MINIMUM_ATUM_PICK_VERSION = '1.1.1';

	/**
	 * The required minimum version of PHP
	 */
	const MINIMUM_PHP_VERSION = '7.4';

	/**
	 * The required minimum version of Woocommerce
	 */
	const MINIMUM_WC_VERSION = '5.0';

	/**
	 * The required minimum version of WordPress
	 */
	const MINIMUM_WP_VERSION = '5.9';

	/**
	 * The add-on slug
	 */
	const ADDON_KEY = 'barcodes_pro';

	/**
	 * The add-on name
	 */
	const ADDON_NAME = 'Barcodes PRO';

	/**
	 * AtumBarcodesAddon constructor
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		// Deactivation tasks.
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		// Uninstallation tasks.
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

		// Check the PHP and ATUM minimum versions required.
		add_action( 'plugins_loaded', array( $this, 'check_dependencies_minimum_versions' ) );

		// Register compatibility with HPOS.
		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_compatibilities' ) );

		// Registrate the add-on to ATUM.
		add_filter( 'atum/addons/setup', array( $this, 'register' ) );

		// Get rid of the barcodes module when this add-on is enabled.
		add_filter( 'atum/module_manager/modules', array( $this, 'unset_barcodes_module' ) );
		add_filter( 'atum/module_manager/modules_settings', array( $this, 'unset_barcodes_module_settings' ) );
		add_filter( 'atum/module_manager/is_module_active_barcodes', '__return_true' );

	}

	/**
	 * Register the add-on to ATUM
	 *
	 * @since 0.0.1
	 *
	 * @param array $installed The array of installed add-ons.
	 *
	 * @return array
	 */
	public function register( $installed ) {

		$installed[ self::ADDON_KEY ] = array(
			'name'        => self::ADDON_NAME,
			// NOTE: We cannot use the translations at this point to avoid the "_load_textdomain_just_in_time" error.
			'description' => 'Transform your inventory management with our Barcodes add-on. Easily create barcodes for products, orders, suppliers, and more. Customize barcodes and take advantage of Multi-Inventory support. Improve operations with barcode integration in list tables, purchase order emails, PDFs, and more. Enhance efficiency and accuracy in inventory tracking today.',
			'addon_url'   => 'https://stockmanagementlabs.com/addons/atum-barcodes-pro/',
			'version'     => ATUM_BARCODES_VERSION,
			'basename'    => ATUM_BARCODES_BASENAME,
			'bootstrap'   => array( $this, 'bootstrap' ),
		);

		return $installed;

	}

	/**
	 * Bootstrap the add-on
	 *
	 * @since 0.0.1
	 */
	public function bootstrap() {

		$bootstrapped = FALSE;

		// Check minimum versions required before bootstrapping the addon.
		if ( $this->check_minimum_versions() ) {

			$bootstrapped = TRUE;

			/* @noinspection PhpIncludeInspection */
			require_once ATUM_BARCODES_PATH . 'vendor/autoload.php';
			new \AtumBarcodes\Bootstrap( self::ADDON_KEY );

		}

		return $bootstrapped;
	}

	/**
	 * Deactivation tasks
	 *
	 * @since 0.1.1
	 */
	public static function deactivate() {
		if ( class_exists( '\Atum\Components\AtumAdminNotices' ) ) {
			\Atum\Components\AtumAdminNotices::clear_permament_notices();
		}
	}

	/**
	 * Uninstallation checks (this will run only once at plugin uninstallation)
	 *
	 * @since 0.0.1
	 */
	public static function uninstall() {

		global $wpdb;

		// Delete the addons transients.
		delete_transient( 'atum_addons_list' );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_atum_addon_status_%'" );

		$settings = get_option( 'atum_settings' );

		if (
			! empty( $settings ) && 'yes' === $settings['delete_data'] &&
			! apply_filters( 'atum/addons/prevent_uninstall_data_removal', FALSE )
		) {

			$atum_product_data_table = $wpdb->prefix . 'atum_product_data';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$atum_product_data_table';" ) ) {
				$wpdb->query( "UPDATE $atum_product_data_table SET barcode = NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}

		}

	}

	/**
	 * Check minimum versions for bootstrapping the addon.
	 *
	 * @since 0.0.1
	 *
	 * @return bool
	 */
	public function check_minimum_versions() {

		global $wp_version;

		$minimum_version = TRUE;
		$message         = '';

		// Check ATUM minimum version.
		if ( version_compare( ATUM_VERSION, self::MINIMUM_ATUM_VERSION, '<' ) ) {

			/* translators: the addon name and the minimum ATUM version */
			$message         = sprintf( __( 'The %1$s add-on requires ATUM version %2$s or greater. Please update it.', ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_VERSION );
			$minimum_version = FALSE;

		}
		// Check Multi-Inventory minimum version.
		elseif ( defined( 'ATUM_MULTINV_VERSION' ) && version_compare( ATUM_MULTINV_VERSION, self::MINIMUM_ATUM_MI_VERSION, '<' ) ) {

			/* translators: the addon name and the minimum MI version */
			$message         = sprintf( __( 'The %1$s add-on requires ATUM Multi-Inventory version %2$s or greater. Please update or disable it.', ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_MI_VERSION );
			$minimum_version = FALSE;

		}
		// Check Purchase Orders PRO minimum version.
		elseif ( defined( 'ATUM_PO_VERSION' ) && version_compare( ATUM_PO_VERSION, self::MINIMUM_ATUM_POP_VERSION, '<' ) ) {

			/* translators: the addon name and the minimum PO version */
			$message         = sprintf( __( 'The %1$s add-on requires ATUM Purchase Orders PRO version %2$s or greater. Please update or disable it.', ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_POP_VERSION );
			$minimum_version = FALSE;

		}
		// Check Pick & Pack minimum version.
		elseif ( defined( 'ATUM_PICK_VERSION' ) && version_compare( ATUM_PICK_VERSION, self::MINIMUM_ATUM_PICK_VERSION, '<' ) ) {

			/* translators: the addon name and the minimum PO version */
			$message         = sprintf( __( 'The %1$s add-on requires ATUM Pick & Pack version %2$s or greater. Please update or disable it.', ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_PICK_VERSION );
			$minimum_version = FALSE;

		}
		// Check the WordPress minimum version required.
		elseif ( version_compare( $wp_version, self::MINIMUM_WP_VERSION, '<' ) ) {

			/* translators: First one is the addon name, second is the minimum WP version and third is the WP updates page */
			$message         = sprintf( __( "The %1\$s add-on requires the WordPress %2\$s version or greater. Please <a href='%3\$s'>update it</a>.", ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_WP_VERSION, esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) );
			$minimum_version = FALSE;

		}
		// Check that WooCommerce is activated.
		elseif ( ! function_exists( 'wc' ) ) {

			/* translators: the addon name */
			$message         = sprintf( __( 'The ATUM %s add-on requires WooCommerce to be activated.', ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME );
			$minimum_version = FALSE;

		}
		// Check the WooCommerce minimum version required.
		elseif ( version_compare( wc()->version, self::MINIMUM_WC_VERSION, '<' ) ) {

			/* translators: First one is the addon name, second is the minimum WooCommerce version and third is the WP updates page */
			$message         = sprintf( __( "The %1\$s add-on requires the WooCommerce %2\$s version or greater. Please <a href='%3\$s'>update it</a>.", ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_WC_VERSION, esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) );
			$minimum_version = FALSE;

		}

		if ( ! $minimum_version ) {
			\Atum\Components\AtumAdminNotices::add_notice( $message, 'barcodes_pro_minimum_version', 'error' );
		}

		return $minimum_version;

	}

	/**
	 * Check PHP minimum version and if ATUM is installed before installing ATUM Barcodes PRO.
	 *
	 * @since 0.0.1
	 */
	public function check_dependencies_minimum_versions() {

		$minimum_version = TRUE;
		$message         = '';

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = get_plugins();
		$atum_file = 'atum-stock-manager-for-woocommerce/atum-stock-manager-for-woocommerce.php';

		// Check PHP minimum version.
		if ( version_compare( phpversion(), self::MINIMUM_PHP_VERSION, '<' ) ) {

			/* translators: The addons name and the minimum PHP version required */
			$message         = sprintf( __( 'The ATUM %1$s add-on requires PHP version %2$s or greater. Please, update it or contact your hosting provider.', ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_PHP_VERSION );
			$minimum_version = FALSE;

		}
		// Check if ATUM is installed.
		elseif ( ! isset( $installed[ $atum_file ] ) ) {

			/* translators: The first one is the addon name and second is the plugins installation page URL */
			$message         = sprintf( __( "The ATUM %1\$s add-on requires the ATUM Inventory Management for WooCommerce plugin. Please <a href='%2\$s'>install it</a>.", ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME, admin_url( 'plugin-install.php?s=atum&tab=search&type=term' ) );
			$minimum_version = FALSE;

		}
		// Check if ATUM is active.
		elseif ( ! is_plugin_active( $atum_file ) ) {

			/* translators: The first one is the addon name and second is the plugins page URL */
			$message         = sprintf( __( "The ATUM %1\$s add-on requires the ATUM Inventory Management for WooCommerce plugin. Please enable it from <a href='%2\$s'>plugins page</a>.", ATUM_BARCODES_TEXT_DOMAIN ), self::ADDON_NAME, admin_url( 'plugins.php' ) );
			$minimum_version = FALSE;

		}

		if ( ! $minimum_version ) {

			// We cannot use the AtumAdminNotices here because ATUM could be not enabled.
			add_action( 'admin_notices', function() use ( $message ) {
				?>
				<div class="atum-notice notice notice-error">
					<p>
						<strong>
							<?php echo wp_kses_post( $message ); ?>
						</strong>
					</p>
				</div>
				<?php
			} );

		}

	}

	/**
	 * Register compatibility with new WC features.
	 *
	 * @since 0.0.1
	 */
	public function declare_wc_compatibilities() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', ATUM_BARCODES_BASENAME ); // HPOS compatibility.
		}
	}

	/**
	 * Get rid of the barcodes module when this add-on is enabled.
	 *
	 * @since 0.1.1
	 *
	 * @param string[] $modules
	 *
	 * @return string[]
	 */
	public function unset_barcodes_module( $modules ) {

		unset( $modules[ array_search( 'barcodes', $modules ) ] );

		return $modules;

	}

	/**
	 * Get rid of the barcodes module when this add-on is enabled.
	 *
	 * @since 0.1.1
	 *
	 * @param string[] $defaults
	 *
	 * @return string[]
	 */
	public function unset_barcodes_module_settings( $defaults ) {

		unset( $defaults[ 'barcodes' . \Atum\Modules\ModuleManager::OPTION_SUFFIX ] );

		return $defaults;

	}

}

// Instantiate the add-on.
new AtumBarcodesAddon();

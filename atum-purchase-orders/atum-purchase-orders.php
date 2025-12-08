<?php
/**
 * ATUM Purchase Orders PRO
 *
 * @link              https://stockmanagementlabs.com/
 * @since             0.0.1
 * @package           AtumLevels
 *
 * @wordpress-plugin
 * Plugin Name:          ATUM Purchase Orders PRO
 * Requires Plugins:     woocommerce, atum-stock-manager-for-woocommerce
 * Plugin URI:           https://stockmanagementlabs.com/addons/atum-purchase-orders
 * Description:          Expands and improves the advantages that Purchase Orders had in ATUM free. It includes very useful and long requested features that allow the shop owners to manage their incoming stock in an effective way.
 * Version:              1.2.8
 * Author:               Stock Management Labs™
 * Author URI:           https://stockmanagementlabs.com/
 * Contributors:         BE REBEL - https://berebel.studio
 * Requires at least:    5.9
 * Tested up to:         6.8.2
 * Requires PHP:         7.4
 * WC requires at least: 5.0
 * WC tested up to:      10.2.2
 * Text Domain:          atum-purchase-orders
 * Domain Path:          /languages
 * License:              ©2025 Stock Management Labs™
 */

defined( 'ABSPATH' ) || die;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ATUM_PO_VERSION' ) ) {
	define( 'ATUM_PO_VERSION', '1.2.8' );
}

if ( ! defined( 'ATUM_PO_URL' ) ) {
	define( 'ATUM_PO_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ATUM_PO_PATH' ) ) {
	define( 'ATUM_PO_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ATUM_PO_TEXT_DOMAIN' ) ) {
	define( 'ATUM_PO_TEXT_DOMAIN', 'atum-purchase-orders' );
}

if ( ! defined( 'ATUM_PO_BASENAME' ) ) {
	define( 'ATUM_PO_BASENAME', plugin_basename( __FILE__ ) );
}

class AtumPurchaseOrdersAddon {

	/**
	 * The required minimum version of ATUM
	 */
	const MINIMUM_ATUM_VERSION = '1.9.51';

	/**
	 * The required minimum of Product Levels
	 */
	const MINIMUM_ATUM_PL_VERSION = '1.9.3';

	/**
	 * The required minimum of Multi-Inventory
	 */
	const MINIMUM_ATUM_MI_VERSION = '1.8.7';

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
	 * The add-on name
	 */
	const ADDON_NAME = 'Purchase Orders PRO';

	/**
	 * The add-on key
	 */
	const ADDON_KEY = 'purchase_orders';

	/**
	 * AtumPurchaseOrdersAddon constructor
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		// Activation tasks.
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );

		// Deactivation tasks.
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		// Uninstallation tasks.
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

		// Check the PHP and ATUM minimum versions required for ATUM Purchase Orders.
		add_action( 'plugins_loaded', array( $this, 'check_dependencies_minimum_versions' ) );

		// Register compatibility with new WC features.
		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_compatibilities' ) );

		// Registrate the add-on to ATUM.
		add_filter( 'atum/addons/setup', array( $this, 'register' ) );

	}

	/**
	 * Register the add-on to ATUM
	 *
	 * @since 0.0.1
	 *
	 * @param array $installed  The array of installed add-ons.
	 *
	 * @return array
	 */
	public function register( $installed ) {

		$installed[ self::ADDON_KEY ] = array(
			'name'        => self::ADDON_NAME,
			// NOTE: We cannot use the translations at this point to avoid the "_load_textdomain_just_in_time" error.
			'description' => 'Expands and improves the advantages that Purchase Orders had in ATUM free. It includes very useful and long requested features that allow the shop owners to manage their incoming stock in an effective way.',
			'addon_url'   => 'https://stockmanagementlabs.com/addons/atum-purchase-orders-pro/',
			'version'     => ATUM_PO_VERSION,
			'basename'    => ATUM_PO_BASENAME,
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

		// Check minimum versions for install ATUM Product Levels.
		if ( $this->check_minimum_versions() ) {

			$bootstrapped = TRUE;

			/* @noinspection PhpIncludeInspection */
			require_once ATUM_PO_PATH . 'vendor/autoload.php';
			new \AtumPO\Bootstrap( self::ADDON_KEY );

		}

		return $bootstrapped;
	}

	/**
	 * Just trigger a hook that other add-ons can use to do some actions when PO is enabled
	 *
	 * @since 1.0.9
	 */
	public static function activate() {
		do_action( 'atum/purchase_orders_pro/activated', ATUM_PO_VERSION );
	}

	/**
	 * Deactivation tasks
	 *
	 * @since 1.0.9
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

			// Delete all the posts of ATUM PO Pro's custom post types and their meta.
			$atum_post_types = array(
				'atum_po_delivery',
				'atum_po_invoice',
			);

			foreach ( $atum_post_types as $atum_post_type ) {

				$args       = array(
					'post_type'      => $atum_post_type,
					'posts_per_page' => - 1,
					'fields'         => 'ids',
					'post_status'    => 'any',
				);
				$atum_posts = get_posts( $args );

				$order_items_table      = $wpdb->prefix . 'atum_order_items';
				$order_items_meta_table = $wpdb->prefix . 'atum_order_itemmeta';

				if ( ! empty( $atum_posts ) ) {
					$wpdb->query( "DELETE FROM $order_items_meta_table WHERE order_item_id IN (
    					SELECT order_item_id FROM $order_items_table WHERE order_id IN (" . implode( ',', $atum_posts ) . ")
					)" );
					$wpdb->query( "DELETE FROM $order_items_table WHERE order_id IN (" . implode( ',', $atum_posts ) . ")" );
					$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN (" . implode( ',', $atum_posts ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->delete( $wpdb->posts, array( 'post_type' => $atum_post_type ) );
				}

			}

			// Delete the ATUM PO Pro options.
			delete_option(  'atum_purchase_orders_pro_version' );

		}

	}

	/**
	 * Check minimum versions before activating ATUM Purchase Orders.
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
		if ( ! defined( 'ATUM_VERSION' ) || version_compare( ATUM_VERSION, self::MINIMUM_ATUM_VERSION, '<' ) ) {

			/* translators: the addon name and the minimum ATUM version */
			$message         = sprintf( __( 'The %1$s add-on requires ATUM version %2$s or greater. Please update it.', ATUM_PO_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_VERSION );
			$minimum_version = FALSE;

		}
		// Check Product Levels minimum version.
		elseif ( defined( 'ATUM_LEVELS_VERSION' ) && version_compare( ATUM_LEVELS_VERSION, self::MINIMUM_ATUM_PL_VERSION, '<' ) ) {

			/* translators: the addon name and the minimum ATUM version */
			$message         = sprintf( __( 'The %1$s add-on requires ATUM Product Levels version %2$s or greater. Please update or disable it.', ATUM_PO_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_PL_VERSION );
			$minimum_version = FALSE;

		}
		// Check Multi-Inventory minimum version.
		elseif ( defined( 'ATUM_MULTINV_VERSION' ) && version_compare( ATUM_MULTINV_VERSION, self::MINIMUM_ATUM_MI_VERSION, '<' ) ) {

			/* translators: the addon name and the minimum ATUM version */
			$message         = sprintf( __( 'The %1$s add-on requires ATUM Multi-Inventory version %2$s or greater. Please update or disable it.', ATUM_PO_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_MI_VERSION );
			$minimum_version = FALSE;

		}
		// Check the WordPress minimum version required for ATUM Purchase Orders.
		elseif ( version_compare( $wp_version, self::MINIMUM_WP_VERSION, '<' ) ) {

			/* translators: First one is the addon name, second is the minimum WP version and third is the WP updates page */
			$message         = sprintf( __( "The %1\$s add-on requires the WordPress %2\$s version or greater. Please <a href='%3\$s'>update it</a>.", ATUM_PO_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_WP_VERSION, esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) );
			$minimum_version = FALSE;

		}
		// Check that WooCommerce is activated.
		elseif ( ! function_exists( 'wc' ) ) {

			/* translators: the addon name */
			$message         = sprintf( __( 'The ATUM %s add-on requires WooCommerce to be activated.', ATUM_PO_TEXT_DOMAIN ), self::ADDON_NAME );
			$minimum_version = FALSE;

		}
		// Check the WooCommerce minimum version required for ATUM Purchase Orders.
		elseif ( version_compare( wc()->version, self::MINIMUM_WC_VERSION, '<' ) ) {

			/* translators: First one is the addon name, second is the minimum WooCommerce version and third is the WP updates page */
			$message         = sprintf( __( "The %1\$s add-on requires the WooCommerce %2\$s version or greater. Please <a href='%3\$s'>update it</a>.", ATUM_PO_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_WC_VERSION, esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) );
			$minimum_version = FALSE;

		}

		if ( ! $minimum_version ) {
			\Atum\Components\AtumAdminNotices::add_notice( $message, 'purchase_orders_pro_minimum_version', 'error' );
		}

		return $minimum_version;

	}

	/**
	 * Check PHP minimum version and if ATUM is installed before activating ATUM Purchase Orders.
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
			$message         = sprintf( __( 'The ATUM %1$s add-on requires PHP version %2$s or greater. Please, update it or contact your hosting provider.', ATUM_PO_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_PHP_VERSION );
			$minimum_version = FALSE;

		}
		// Check if ATUM is installed.
		elseif ( ! isset( $installed[ $atum_file ] ) ) {

			/* translators: The first one is the addon name and second is the plugins installation page URL */
			$message         = sprintf( __( "The ATUM %1\$s add-on requires the ATUM Inventory Management for WooCommerce plugin. Please <a href='%2\$s'>install it</a>.", ATUM_PO_TEXT_DOMAIN ), self::ADDON_NAME, admin_url( 'plugin-install.php?s=atum&tab=search&type=term' ) );
			$minimum_version = FALSE;

		}
		// Check if ATUM is active.
		elseif ( ! is_plugin_active( $atum_file ) ) {

			/* translators: The first one is the addon name and second is the plugins page URL */
			$message         = sprintf( __( "The ATUM %1\$s add-on requires the ATUM Inventory Management for WooCommerce plugin. Please enable it from <a href='%2\$s'>plugins page</a>.", ATUM_PO_TEXT_DOMAIN ), self::ADDON_NAME, admin_url( 'plugins.php' ) );
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
	 * Register PO PRO's compatibility with new WC features.
	 *
	 * @since 1.0.7
	 */
	public function declare_wc_compatibilities() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', ATUM_PO_BASENAME ); // HPOS compatibility.
		}
	}

}

// Instantiate the add-on.
new AtumPurchaseOrdersAddon();

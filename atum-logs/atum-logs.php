<?php
/**
 * ATUM Action Logs
 *
 * @link              https://stockmanagementlabs.com/
 * @since             0.0.1
 * @package           AtumLogs
 *
 * @wordpress-plugin
 * Plugin Name:          ATUM Action Logs
 * Requires Plugins:     woocommerce, atum-stock-manager-for-woocommerce
 * Plugin URI:           https://stockmanagementlabs.com/addons/atum-action-logs
 * Description:          Keeping track of any changes happening in your shop has never been easier. The Action Logs add-on supports all ATUM premium add-ons and all WooCommerce actions.
 * Version:              1.4.8.1
 * Author:               Stock Management Labs™
 * Author URI:           https://stockmanagementlabs.com/
 * Contributors:         BE REBEL - https://berebel.studio
 * Requires at least:    5.9
 * Tested up to:         6.8.2
 * Requires PHP:         7.4
 * WC requires at least: 5.0
 * WC tested up to:      10.1.1
 * Text Domain:          atum-logs
 * Domain Path:          /languages
 * License:              ©2025 Stock Management Labs™
 */

defined( 'ABSPATH' ) || die;

use Automattic\WooCommerce\Utilities\FeaturesUtil;


if ( ! defined( 'ATUM_LOGS_VERSION' ) ) {
	define( 'ATUM_LOGS_VERSION', '1.4.8.1' );
}

if ( ! defined( 'ATUM_LOGS_URL' ) ) {
	define( 'ATUM_LOGS_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ATUM_LOGS_PATH' ) ) {
	define( 'ATUM_LOGS_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ATUM_LOGS_TEXT_DOMAIN' ) ) {
	define( 'ATUM_LOGS_TEXT_DOMAIN', 'atum-logs' );
}

if ( ! defined( 'ATUM_LOGS_BASENAME' ) ) {
	define( 'ATUM_LOGS_BASENAME', plugin_basename( __FILE__ ) );
}

class AtumLogsAddon {

	/**
	 * The required minimum version of ATUM
	 */
	const MINIMUM_ATUM_VERSION = '1.9.50';

	/**
	 * The required minimum of Product Levels
	 */
	const MINIMUM_ATUM_PL_VERSION = '1.9.3';

	/**
	 * The required minimum of Multi-Inventory
	 */
	const MINIMUM_ATUM_MI_VERSION = '1.8.4';

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
	const ADDON_NAME = 'Action Logs';

	/**
	 * The addon key
	 */
	const ADDON_KEY = 'action_logs';

	/**
	 * AtumLogsAddon constructor
	 */
	public function __construct() {

		global $wp_version;

		// Installation tasks.
		register_activation_hook( __FILE__, array( __CLASS__, 'install' ) );

		// Deactivation tasks.
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		// Uninstallation tasks.
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

		if ( version_compare( $wp_version, '5.1.0', '<' ) ) {
			add_action( 'wpmu_new_blog', array( $this, 'new_blog_created' ), 10, 6 );
		}
		else {
			add_action( 'wp_insert_site', array( $this, 'new_site_created' ) );
		}

		// Check the PHP AND ATUM minimum version required for ATUM Action Logs.
		add_action( 'plugins_loaded', array( $this, 'check_dependencies_minimum_versions' ) );

		// Register compatibility with new WC features.
		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_compatibilities' ) );

		// Register the add-on to ATUM.
		add_filter( 'atum/addons/setup', array( $this, 'register' ), 100 ); // Register this add-on the last one.

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
			'description' => 'Keeping track of any changes happening in your shop has never been easier. The Action Logs add-on supports all ATUM premium add-ons and all WooCommerce actions.',
			'addon_url'   => 'https://stockmanagementlabs.com/addons/atum-action-logs/',
			'version'     => ATUM_LOGS_VERSION,
			'basename'    => ATUM_LOGS_BASENAME,
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

		// Check minimum versions for install ATUM Action Logs.
		if ( $this->check_minimum_versions() ) {

			$bootstrapped = TRUE;

			/* @noinspection PhpIncludeInspection */
			require_once ATUM_LOGS_PATH . 'vendor/autoload.php';
			new \AtumLogs\Bootstrap( self::ADDON_KEY );

		}

		return $bootstrapped;

	}

	/**
	 * Installation checks (this will run only once at plugin activation)
	 *
	 * @since 0.0.1
	 *
	 * @param bool $network_wide Whether is the plugin being activated in all the network.
	 */
	public static function install( $network_wide ) {

		global $wpdb;

		if ( is_multisite() && $network_wide ) {

			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( $blog_id );

				self::create_atum_logs_table();

				restore_current_blog();
			}

		}
		else {
			self::create_atum_logs_table();
		}

		do_action( 'atum/action_logs/activated', ATUM_LOGS_VERSION );

	}

	/**
	 * Deactivation tasks
	 *
	 * @since 1.3.4
	 */
	public static function deactivate() {
		if ( class_exists( '\Atum\Components\AtumAdminNotices' ) ) {
			\Atum\Components\AtumAdminNotices::clear_permament_notices();
		}
	}

	/**
	 * Uninstallation checks (this will run only once at plugin uninstallation)
	 *
	 * @since 1.2.1
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

			$logs_table      = $wpdb->prefix . 'atum_logs';
			$table_cache     = $wpdb->prefix . 'atum_logs_cache';
			$table_cache_var = $wpdb->prefix . 'atum_logs_cache_var';

			// Delete the ATUM Action Logs tables in db.
			$wpdb->query( "DROP TABLE IF EXISTS $logs_table" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->query( "DROP TABLE IF EXISTS $table_cache" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->query( "DROP TABLE IF EXISTS $table_cache_var" ); // phpcs:ignore WordPress.DB.PreparedSQL

			// Delete the ATUM Action Logs options.
			delete_option( 'atum_action_logs_version' );

		}

	}

	/**
	 * Create the stock logs table when new blog created (before WP 5.1)
	 *
	 * @since 0.0.1
	 *
	 * @param int    $blog_id Blog ID.
	 * @param int    $user_id User ID.
	 * @param string $domain  Site domain.
	 * @param string $path    Site path.
	 * @param int    $site_id Site ID. Only relevant on multi-network installs.
	 * @param array  $meta    Meta data. Used to set initial site options.
	 */
	public function new_blog_created( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

		if ( is_plugin_active_for_network( 'atum-logs/atum-logs.php' ) ) {
			switch_to_blog( $blog_id );

			self::create_atum_logs_table();

			restore_current_blog();
		}

	}

	/**
	 * Create the stock logs table when new site created (since WP 5.1)
	 *
	 * @since 0.0.1
	 *
	 * @param WP_Site $wp_site
	 */
	public function new_site_created( $wp_site ) {

		if ( is_plugin_active_for_network( 'atum-logs/atum-logs.php' ) ) {
			switch_to_blog( $wp_site->id );

			self::create_atum_logs_table();

			restore_current_blog();
		}

	}

	/**
	 * Create the Atum Action Logs table
	 *
	 * @since 0.0.1
	 */
	private static function create_atum_logs_table() {

		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		// Create the DB tables to log the stock activity.
		// Note: ATUM_PREFIX may not be available here.
		$table_logs      = $wpdb->prefix . 'atum_logs';
		$table_cache     = $wpdb->prefix . 'atum_logs_cache';
		$table_cache_var = $wpdb->prefix . 'atum_logs_cache_var';

		// phpcs:ignore WordPress.DB.PreparedSQL
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$table_logs';" ) ) {

			$sql = "CREATE TABLE $table_logs (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  	`user_id` BIGINT DEFAULT NULL,
				`module` VARCHAR(256) DEFAULT NULL,
				`source` VARCHAR(256) DEFAULT NULL,
				`time` BIGINT DEFAULT NULL,
				`entry` VARCHAR(256) NOT NULL DEFAULT '',
				`read` TINYINT(1) DEFAULT '0',
				`featured` TINYINT(1) DEFAULT '0',
				`deleted` TINYINT(1) DEFAULT '0',
				`data` LONGTEXT,
			  	PRIMARY KEY (id),
			  	UNIQUE KEY id (id),
			  	INDEX `module` (`module`),
			  	INDEX `source` (`source`),
				INDEX `read` (`read`),
				INDEX `featured` (`featured`),
				INDEX `deleted` (`deleted`)
			) $collate;"; // phpcs:ignore WordPress.DB.NotPreparedSQL

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

		}

		// phpcs:ignore WordPress.DB.PreparedSQL
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$table_cache';" ) ) {

			$sql = "CREATE TABLE $table_cache (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  	`slug` VARCHAR(100) DEFAULT NULL,
				`locale` VARCHAR(5) NOT NULL DEFAULT 'en_GB',
				`entry` LONGTEXT,
			  	PRIMARY KEY (id),
			  	UNIQUE KEY id (id),
			  	UNIQUE KEY `slug_locale` (`slug`, `locale`),
			  	INDEX `slug` (`slug`)
			) $collate;"; // phpcs:ignore WordPress.DB.NotPreparedSQL

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

		}

		// phpcs:ignore WordPress.DB.PreparedSQL
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$table_cache_var';" ) ) {

			$sql = "CREATE TABLE $table_cache_var (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  	`id_log` BIGINT DEFAULT 0,
				`data` VARCHAR(128) NOT NULL DEFAULT '',
			  	PRIMARY KEY (id),
			  	UNIQUE KEY id (id),
			  	INDEX `id_log` (`id_log`)
			) $collate;"; // phpcs:ignore WordPress.DB.NotPreparedSQL

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

		}
	}

	/**
	 * Check minimum versions before activating ATUM Action Logs.
	 *
	 * @since 1.3.0
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
			$message         = sprintf( __( 'The %1$s add-on requires ATUM version %2$s or greater. Please update it.', ATUM_LOGS_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_VERSION );
			$minimum_version = FALSE;

		}
		// Check Product Levels minimum version.
		elseif ( defined( 'ATUM_LEVELS_VERSION' ) && version_compare( ATUM_LEVELS_VERSION, self::MINIMUM_ATUM_PL_VERSION, '<' ) ) {
			
			/* translators: the addon name and the minimum ATUM version */
			$message         = sprintf( __( 'The %1$s add-on requires ATUM Product Levels version %2$s or greater. Please update or disable it.', ATUM_LOGS_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_PL_VERSION );
			$minimum_version = FALSE;

		}
		// Check Multi-Inventory minimum version.
		elseif ( defined( 'ATUM_MULTINV_VERSION' ) && version_compare( ATUM_MULTINV_VERSION, self::MINIMUM_ATUM_MI_VERSION, '<' ) ) {
			
			/* translators: the addon name and the minimum ATUM version */
			$message         = sprintf( __( 'The %1$s add-on requires ATUM Multi-Inventory version %2$s or greater. Please update or disable it.', ATUM_LOGS_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_ATUM_MI_VERSION );
			$minimum_version = FALSE;

		}
		// Check the WordPress minimum version required for ATUM Action Logs.
		elseif ( version_compare( $wp_version, self::MINIMUM_WP_VERSION, '<' ) ) {
			
			/* translators: First one is the addon name, second is the minimum WP version and third is the WP updates page */
			$message         = sprintf( __( "The %1\$s add-on requires the WordPress %2\$s version or greater. Please <a href='%3\$s'>update it</a>.", ATUM_LOGS_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_WP_VERSION, esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) );
			$minimum_version = FALSE;

		}
		// Check that WooCommerce is activated.
		elseif ( ! function_exists( 'wc' ) ) {
			
			/* translators: the addon name */
			$message         = sprintf( __( 'The ATUM %s add-on requires WooCommerce to be activated.', ATUM_LOGS_TEXT_DOMAIN ), self::ADDON_NAME );
			$minimum_version = FALSE;

		}
		// Check the WooCommerce minimum version required for ATUM Action Logs.
		elseif ( version_compare( wc()->version, self::MINIMUM_WC_VERSION, '<' ) ) {
			
			/* translators: First one is the addon name, second is the minimum WooCommerce version and third is the WP updates page */
			$message         = sprintf( __( "The %1\$s add-on requires the WooCommerce %2\$s version or greater. Please <a href='%3\$s'>update it</a>.", ATUM_LOGS_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_WC_VERSION, esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) );
			$minimum_version = FALSE;

		}

		if ( ! $minimum_version ) {
			\Atum\Components\AtumAdminNotices::add_notice( $message, 'action_logs_minimum_version', 'error' );
		}

		return $minimum_version;

	}

	/**
	 * Check PHP minimum version and if ATUM is installed, before activating ATUM Action Logs.
	 *
	 * @since 1.3.0
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
			$message         = sprintf( __( 'The ATUM %1$s add-on requires PHP version %2$s or greater. Please, update it or contact your hosting provider.', ATUM_LOGS_TEXT_DOMAIN ), self::ADDON_NAME, self::MINIMUM_PHP_VERSION );
			$minimum_version = FALSE;

		}
		// Check if ATUM is installed.
		elseif ( ! isset( $installed[ $atum_file ] ) ) {
			
			/* translators: The first one is the addon name and second is the plugins installation page URL */
			$message         = sprintf( __( "The ATUM %1\$s add-on requires the ATUM Inventory Management for WooCommerce plugin. Please <a href='%2\$s'>install it</a>.", ATUM_LOGS_TEXT_DOMAIN ), self::ADDON_NAME, admin_url( 'plugin-install.php?s=atum&tab=search&type=term' ) );
			$minimum_version = FALSE;

		}
		// Check if ATUM is active.
		elseif ( ! is_plugin_active( $atum_file ) ) {
			
			/* translators: The first one is the addon name and second is the plugins page URL */
			$message         = sprintf( __( "The ATUM %1\$s add-on requires the ATUM Inventory Management for WooCommerce plugin. Please enable it from <a href='%2\$s'>plugins page</a>.", ATUM_LOGS_TEXT_DOMAIN ), self::ADDON_NAME, admin_url( 'plugins.php' ) );
			$minimum_version = FALSE;

		}

		if ( ! $minimum_version ) {

			// We cannot use the AtumAdminNotices here because ATUM could be not enabled.
			add_action( 'admin_notices', function () use ( $message ) {
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
	 * Register AL's compatibility with new WC features.
	 *
	 * @since 1.3.2
	 */
	public function declare_wc_compatibilities() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', ATUM_LOGS_BASENAME ); // HPOS compatibility.
		}
	}

}

// Instantiate the add-on.
new AtumLogsAddon();

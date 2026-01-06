<?php
/**
 * Delivery Module Loader (DEV) — aaa-oc-adbdel-loader.php
 *
 * Responsibilities
 * - Wire up Delivery DB installer and indexers
 * - In 100% dev mode: drop and recreate Delivery tables on activation
 * - Provide a WP-CLI command and an admin tool to manually reset tables
 * - Load the Delivery sub-module aggregator and the assets loader
 *
 * Usage
 * - Include this file from your main plugin bootstrap.
 * - Ensure the Delivery classes referenced here exist in your includes/ path.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AAA_OC_ADBDEL_VERSION' ) ) {
	define( 'AAA_OC_ADBDEL_VERSION', '0.1.0-dev' );
}

if ( ! class_exists( 'AAA_OC_AdbDel_Loader' ) ) :

final class AAA_OC_AdbDel_Loader {

	/** @var string Absolute plugin dir (ends with slash) */
	private $base_dir;

	public function __construct( $base_dir = null ) {
		$this->base_dir = $base_dir ? rtrim( $base_dir, '/\\' ) . '/' : plugin_dir_path( __FILE__ );
	}

	public function init() {
		// --- 1) Require Delivery core pieces (tables + indexers) ---
		$this->require_file( 'includes/delivery/index/class-aaa-oc-delivery-table-installer.php' );
		$this->require_file( 'includes/delivery/index/class-aaa-oc-delivery-indexer.php' );
		$this->require_file( 'includes/delivery/index/class-aaa-oc-delivery-index.php' );

		// --- 2) Register activation to rebuild tables in dev ---
		register_activation_hook( $this->guess_plugin_file(), array( $this, 'dev_rebuild_tables' ) );

		// --- 3) Admin page + actions for manual reset ---
		add_action( 'admin_menu', array( $this, 'register_admin_tools' ) );
		add_action( 'admin_post_aaa_oc_adbdel_reset_tables', array( $this, 'handle_admin_reset' ) );

		// --- 4) WP-CLI command for resets ---
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'adbdel reset-tables', array( $this, 'cli_reset_tables' ) );
		}

		// --- 5) Load the Delivery modules aggregator and assets loader ---
		$this->require_file( 'includes/delivery/aaa-wf-delivery-modules-loader.php' );
		$this->require_file( 'includes/delivery/aaa-oc-adbdel-assets-loader.php' );
	}

	/**
	 * Drop & recreate the Delivery tables (DEV).
	 * Safe to call repeatedly; it uses DROP TABLE IF EXISTS.
	 */
	public function dev_rebuild_tables() {
		global $wpdb;

		if ( ! class_exists( 'AAA_OC_Delivery_Table_Installer' ) ) {
			return;
		}

		$tables = array(
			$wpdb->prefix . 'aaa_oc_delivery_route_order',
			$wpdb->prefix . 'aaa_oc_delivery_route',
		);

		// Drop tables (order matters: drop child/map first)
		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Recreate via installer
		$installer = new \AAA_OC_Delivery_Table_Installer();
		$installer->activate();
	}

	/**
	 * Admin menu node under WooCommerce -> Status (Tools).
	 */
	public function register_admin_tools() {
		$cap = 'manage_woocommerce';
		add_submenu_page(
			'woocommerce',
			'Delivery Dev Tools',
			'Delivery Dev Tools',
			$cap,
			'aaa-oc-adbdel-tools',
			array( $this, 'render_admin_tools' )
		);
	}

	public function render_admin_tools() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'aaa-order-workflow' ) );
		}
		$nonce = wp_create_nonce( 'aaa_oc_adbdel_reset' );
		?>
		<div class="wrap">
			<h1>Delivery Dev Tools</h1>
			<p>You are in <strong>100% DEV mode</strong>. Use the button below to drop and recreate the Delivery tables.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="aaa_oc_adbdel_reset_tables" />
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
				<?php submit_button( 'Drop & Recreate Delivery Tables', 'delete' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_admin_reset() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'aaa-order-workflow' ) );
		}
		check_admin_referer( 'aaa_oc_adbdel_reset' );

		$this->dev_rebuild_tables();

		wp_safe_redirect( add_query_arg( array( 'page' => 'aaa-oc-adbdel-tools', 'reset' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * WP-CLI: wp adbdel reset-tables
	 */
	public function cli_reset_tables() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}
		\WP_CLI::log( 'Dropping and recreating Delivery tables…' );
		$this->dev_rebuild_tables();
		\WP_CLI::success( 'Delivery tables rebuilt.' );
	}

	/**
	 * Utility — require a file relative to plugin base.
	 */
	private function require_file( $relative ) {
		$path = $this->base_dir . ltrim( $relative, '/\\' );
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}

	/**
	 * Best-effort guess at the plugin main file for activation hook.
	 * Adjust if your structure differs.
	 */
	private function guess_plugin_file() {
		// If this file lives inside includes/delivery/, go two levels up.
		$maybe = dirname( dirname( $this->base_dir ) );
		// Fallback: this file itself.
		return defined( 'AAA_OC_PLUGIN_FILE' ) ? AAA_OC_PLUGIN_FILE : __FILE__;
	}
}

// Auto-boot when included.
add_action( 'plugins_loaded', static function () {
	$loader = new \AAA_OC_AdbDel_Loader();
	$loader->init();
} );

endif; // class_exists

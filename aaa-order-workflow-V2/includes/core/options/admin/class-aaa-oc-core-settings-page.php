<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/admin/class-aaa-oc-core-settings-page.php
 * Purpose: Central “Workflow Settings” page (tabbed). Core discovers only its own tabs;
 *          modules must register their tabs via the 'aaa_oc_core_settings_tabs' filter.
 *          This ensures module tabs respect WFCP enable/disable toggles.
 * Version: 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Per-file debug toggle (guarded) */
if ( ! defined( 'AAA_OC_CORE_SETTINGS_DEBUG' ) ) {
	define( 'AAA_OC_CORE_SETTINGS_DEBUG', false );
}

class AAA_OC_Core_Settings_Page {

	public static function init() : void {
		add_action( 'admin_menu', [ __CLASS__, 'add_menu' ], 15 );
	}

	public static function add_menu() : void {
		$cap = defined('AAA_OC_REQUIRED_CAP') ? AAA_OC_REQUIRED_CAP : 'manage_woocommerce';

		add_submenu_page(
			'aaa-oc-workflow-board',
			__( 'Workflow Settings', 'aaa-oc' ),
			__( 'Workflow Settings', 'aaa-oc' ),
			$cap,
			'aaa-oc-core-settings',
			[ __CLASS__, 'render' ]
		);
	}

	public static function render() : void {
		// Start with core-discovered tabs and let modules add/modify via filter.
		$tabs = apply_filters( 'aaa_oc_core_settings_tabs', self::discover_core_tabs() );

		// Resolve active tab safely
		$active = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : '';
		if ( empty( $active ) || empty( $tabs[ $active ] ) ) {
			$active = array_key_first( $tabs ) ?: '';
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Workflow Settings', 'aaa-oc' ) . '</h1>';

		// Tabs nav
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $id => $tab ) {
			$url = add_query_arg(
				[ 'page' => 'aaa-oc-core-settings', 'tab' => $id ],
				admin_url( 'admin.php' )
			);
			$active_class = ( $id === $active ) ? ' nav-tab-active' : '';
			$label = isset( $tab['label'] ) ? $tab['label'] : $id;
			echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . $active_class . '">'
			     . esc_html( $label ) . '</a>';
		}
		echo '</h2>';

		// Tab content
		if ( $active && isset( $tabs[ $active ]['file'] ) && file_exists( $tabs[ $active ]['file'] ) ) {
			include $tabs[ $active ]['file'];
		} else {
			echo '<p>' . esc_html__( 'No settings available.', 'aaa-oc' ) . '</p>';
		}

		echo '</div>';

		if ( AAA_OC_CORE_SETTINGS_DEBUG ) {
			error_log( '[AAA_OC_Core_Settings_Page] Rendered with active tab: ' . $active );
		}
	}

	/**
	 * Discover only core’s own tabs under /includes/core/options/admin/tabs/.
	 * Modules must use the 'aaa_oc_core_settings_tabs' filter to add/remove tabs.
	 */
	private static function discover_core_tabs() : array {
		$base  = plugin_dir_path( __DIR__ ); // /includes/core/options/
		$path  = $base . 'admin/tabs/';
		$tabs  = [];

		foreach ( glob( $path . 'aaa-oc-*.php' ) as $file ) {
			$id    = basename( $file, '.php' );
			$label = ucwords( str_replace( [ 'aaa-oc-', '-', '_' ], [ '', ' ', ' ' ], $id ) );
			$tabs[ $id ] = [ 'label' => $label, 'file' => $file ];
		}

		return $tabs;
	}
}

AAA_OC_Core_Settings_Page::init();

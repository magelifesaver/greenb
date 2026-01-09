<?php
/**
 * File: /includes/core/options/admin/class-aaa-oc-core-settings-page.php
 * Purpose: Central “Workflow Settings” page. Loads all module tabs automatically.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Core_Settings_Page {

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_menu' ], 15 );
	}

	public static function add_menu() {
		add_submenu_page(
			'aaa-oc-workflow-board',
			'Workflow Settings',
			'Workflow Settings',
			'manage_woocommerce',
			'aaa-oc-core-settings',
			[ __CLASS__, 'render' ]
		);
	}

	public static function render() {

		// Discover all tab files across modules.
		$tabs = apply_filters( 'aaa_oc_core_settings_tabs', self::discover_tabs() );

		$active = isset( $_GET['tab'] )
			? sanitize_key( $_GET['tab'] )
			: ( array_key_first( $tabs ) ?: '' );

		echo '<div class="wrap"><h1>Workflow Settings</h1>';

		// --- Tabs navigation ---
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $id => $tab ) {
			$url = add_query_arg(
				[ 'page' => 'aaa-oc-core-settings', 'tab' => $id ],
				admin_url( 'admin.php' )
			);
			$active_class = $id === $active ? ' nav-tab-active' : '';
			echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . $active_class . '">' . esc_html( $tab['label'] ) . '</a>';
		}
		echo '</h2>';

		// --- Tab content ---
		if ( isset( $tabs[ $active ]['file'] ) && file_exists( $tabs[ $active ]['file'] ) ) {
			include $tabs[ $active ]['file'];
		} else {
			echo '<p>No settings available.</p>';
		}

		echo '</div>';
	}

	/**
	 * Auto-discover all /admin/tabs/ files from registered modules.
	 */
	private static function discover_tabs() {
		$base = plugin_dir_path( __DIR__ ); // /includes/core/options/
		$paths = [
			$base . 'admin/tabs/',
			dirname( $base, 2 ) . '/announcements/admin/tabs/',
			dirname( $base, 2 ) . '/core/modules/board-order-counter/admin/tabs/',
		];

		$tabs = [];

		foreach ( $paths as $path ) {
			foreach ( glob( $path . 'aaa-oc-*.php' ) as $file ) {
				$id    = basename( $file, '.php' );
				$label = ucwords( str_replace( [ 'aaa-oc-', '-', '_' ], [ '', ' ', ' ' ], $id ) );
				$tabs[ $id ] = [ 'label' => $label, 'file' => $file ];
			}
		}
		return $tabs;
	}
}

AAA_OC_Core_Settings_Page::init();

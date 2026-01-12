<?php
/**
 * Plugin Name:       AAA Stock Forecast Workflow
 * Description:       Predicts out-of-stock risks and prepares purchase orders based on sales velocity, stock, and lead time.
 * Version:           1.5.0
 * Author:            Webmaster Workflow
 * Text Domain:       aaa-wf-sfwf
 * Domain Path:       /languages
 *
 * Filepath: sfwf/aaa-wf-sfwf-stock-loader.php
 * ---------------------------------------------------------------------------
 * Main loader for AAA Stock Forecast Workflow plugin.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// === Constants ===
define( 'SFWF_ROOT', plugin_dir_path( __FILE__ ) );
define( 'SFWF_URL',  plugin_dir_url( __FILE__ ) );
define( 'SFWF_VER',  '1.0.0' );
define( 'SFWF_QUEUE_DB_VER', '1.0.0' );

// === Activation Hook: Create custom tables ===
register_activation_hook( __FILE__, function() {
	require_once SFWF_ROOT . 'index/table-options.php';
	require_once SFWF_ROOT . 'index/table-queue.php';
	sfwf_create_options_table();
	sfwf_create_queue_table();
	update_option( 'sfwf_queue_db_ver', SFWF_QUEUE_DB_VER, false );
});

// === Ensure queue table exists (no re-activation required) ===
add_action( 'plugins_loaded', function() {
	require_once SFWF_ROOT . 'index/table-queue.php';
	sfwf_maybe_upgrade_queue_table();
});

// === Load Core Files ===
require_once SFWF_ROOT . 'settings/class-wf-sfwf-settings.php';
require_once SFWF_ROOT . 'includes/forecast/class-forecast-meta-registry.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-runner.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-timeline.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-sales-metrics.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-stock.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-projections.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-status.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-overrides.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-meta-updater.php';
require_once SFWF_ROOT . 'settings/sfwf-settings-page.php';
require_once SFWF_ROOT . 'includes/queue/class-wf-sfwf-forecast-queue.php';
require_once SFWF_ROOT . 'helpers/class-wf-sfwf-product-fields.php';
require_once SFWF_ROOT . 'helpers/forecast-column-definitions.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-product-fields.php';

// === Admin Menu: Settings + Grid + Queue ===
add_action( 'admin_menu', 'sfwf_register_settings_page' );
function sfwf_register_settings_page() {
	add_submenu_page(
		'woocommerce',
		'Stock Forecast Settings',
		'Stock Forecast Settings',
		'manage_woocommerce',
		'sfwf-settings',
		'sfwf_render_settings_page'
	);
}

add_action( 'admin_menu', function() {
	add_submenu_page(
		'woocommerce',
		'Forecast Grid',
		'Forecast Grid',
		'manage_woocommerce',
		'sfwf-forecast-grid',
		function() { require_once SFWF_ROOT . 'views/forecast-dashboard.php'; }
	);

	add_submenu_page(
		'woocommerce',
		'Forecast Queue',
		'Forecast Queue',
		'manage_woocommerce',
		'sfwf-forecast-queue',
		function() { require_once SFWF_ROOT . 'views/forecast-queue.php'; }
	);
});

// === Settings link in Plugins list ===
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function( $links ) {
	$url = admin_url( 'admin.php?page=sfwf-settings' );
	array_unshift( $links, '<a href="' . esc_url($url) . '">Settings</a>' );
	return $links;
});

// === Queue actions (safe URL triggers) ===
add_action( 'admin_init', function() {
	if ( ! is_admin() || ! current_user_can('manage_woocommerce') ) return;
	if ( ! isset($_GET['page']) || $_GET['page'] !== 'sfwf-forecast-queue' ) return;

	$action = isset($_GET['sfwf_queue_action']) ? sanitize_key($_GET['sfwf_queue_action']) : '';
	if ( $action === '' ) return;

	$nonce = isset($_GET['sfwf_nonce']) ? sanitize_text_field( wp_unslash($_GET['sfwf_nonce']) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'sfwf_queue_' . $action ) ) {
		wp_die( 'Invalid request.' );
	}

	$msg = '';
	if ( $action === 'process' ) {
		$res = WF_SFWF_Forecast_Queue::process( 25 );
		$msg = 'Queue processed. OK: ' . intval($res['processed']) . ', Failed: ' . intval($res['failed']) . ', Skipped: ' . intval($res['skipped']);
	} elseif ( $action === 'sync' ) {
		$added = WF_SFWF_Forecast_Queue::sync_from_enabled_products();
		$msg = 'Queue synced from enabled products. Added/Reset: ' . intval($added);
	} elseif ( $action === 'clear_failed' ) {
		$cleared = WF_SFWF_Forecast_Queue::clear_failed();
		$msg = 'Failed queue rows cleared: ' . intval($cleared);
	}

	$redirect = admin_url( 'admin.php?page=sfwf-forecast-queue' );
	$redirect = add_query_arg( 'sfwf_msg', rawurlencode($msg), $redirect );
	wp_safe_redirect( $redirect );
	exit;
});

// === Manual Trigger for Forecast Update (DEV/ADMIN USE ONLY) ===
add_action( 'admin_init', function() {
	if ( isset($_GET['run_forecast']) && current_user_can('manage_woocommerce') ) {
		WF_SFWF_Forecast_Runner::update_all_products();
		add_action('admin_notices', function() {
			echo '<div class="notice notice-success"><p><strong>[SFWF]</strong> Forecast updated for all products.</p></div>';
		});
	}
});

// === Optional: Add admin footer version tag ===
add_filter( 'admin_footer_text', function( $footer ) {
	if ( isset($_GET['page']) && $_GET['page'] === 'sfwf-settings' ) {
		$footer .= ' | <span style="opacity: 0.6;">SFWF v' . esc_html(SFWF_VER) . '</span>';
	}
	return $footer;
});

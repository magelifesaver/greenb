<?php
/**
 * Plugin Name: A Order Workflow Board LIVE (XHV98-WF)
 * Description: Displays a workflow board using existing WooCommerce statuses, partial DOM updates for new orders. Includes a countdown bar toggle in settings, dynamic columns, and a popup-based order expansion.
 * Version:     1.5.0
 * Author:      Webmaster Delivery
 * Text Domain: aaa-order-workflow
 * Domain Path: /languages
 *
 * File: /wp-content/plugins/aaa-order-workflow/aaa-order-workflow.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* -------------------------------------------------------------
 * WooCommerce Dependency Check
 * ------------------------------------------------------------- */
add_action( 'admin_init', 'aaa_oc_check_woocommerce_active' );
function aaa_oc_check_woocommerce_active() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) && ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', 'aaa_oc_show_wc_required_notice' );
	}
}
function aaa_oc_show_wc_required_notice() {
	echo '<div class="error"><p>' . esc_html__( 'AAA Order Workflow requires WooCommerce to be installed and active.', 'aaa-order-workflow' ) . '</p></div>';
}

/* -------------------------------------------------------------
 * Core Constants
 * ------------------------------------------------------------- */
define( 'AAA_OC_VERSION',    '1.5.0' );
define( 'AAA_OC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_OC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/* -------------------------------------------------------------
 * Translations
 * ------------------------------------------------------------- */
add_action( 'plugins_loaded', 'aaa_oc_load_textdomain' );
function aaa_oc_load_textdomain() {
	load_plugin_textdomain( 'aaa-order-workflow', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/* -------------------------------------------------------------
 * Helpers (Logging + Retention)
 * ------------------------------------------------------------- */

/**
 * Writes to a per-day log file to prevent unbounded growth.
 * Default retention is controlled by option: aaa_oc_log_retention_days (default 14).
 *
 * Files are written to:
 * /wp-content/plugins/aaa-order-workflow/logs/aaa_oc-YYYY-MM-DD.log
 */
function aaa_oc_log( $message ) {
	$timestamp = current_time( 'timestamp' ); // WP timezone.
	$date      = date( 'Y-m-d', $timestamp );
	$time      = date( 'Y-m-d H:i:s', $timestamp );

	$logs_dir = trailingslashit( AAA_OC_PLUGIN_DIR ) . 'logs/';
	if ( ! is_dir( $logs_dir ) ) {
		@mkdir( $logs_dir, 0755, true );
	}

	// Fallback: if logs dir can't be created/written, write in plugin root (existing behavior).
	$target_dir = ( is_dir( $logs_dir ) && is_writable( $logs_dir ) ) ? $logs_dir : trailingslashit( AAA_OC_PLUGIN_DIR );

	$file = $target_dir . 'aaa_oc-' . $date . '.log';
	$line = '[' . $time . '] ' . (string) $message . PHP_EOL;

	@file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
}

/**
 * Deletes daily log files older than N days (default 14).
 * Hooked to aaa_oc_scheduled_cleanup (already scheduled daily on activation).
 */
add_action( 'aaa_oc_scheduled_cleanup', 'aaa_oc_cleanup_logs' );
function aaa_oc_cleanup_logs() {
	$retention_days = (int) get_option( 'aaa_oc_log_retention_days', 14 );
	if ( $retention_days < 1 ) {
		$retention_days = 1;
	}

	$logs_dir = trailingslashit( AAA_OC_PLUGIN_DIR ) . 'logs/';
	if ( ! is_dir( $logs_dir ) ) {
		return;
	}

	$cutoff_ts = current_time( 'timestamp' ) - ( $retention_days * DAY_IN_SECONDS );
	$files     = glob( $logs_dir . 'aaa_oc-*.log' );

	if ( empty( $files ) || ! is_array( $files ) ) {
		return;
	}

	foreach ( $files as $path ) {
		$base = basename( $path );

		// Expect: aaa_oc-YYYY-MM-DD.log
		if ( ! preg_match( '/^aaa_oc-(\d{4}-\d{2}-\d{2})\.log$/', $base, $m ) ) {
			continue;
		}

		$file_ts = strtotime( $m[1] . ' 00:00:00' );
		if ( ! $file_ts ) {
			continue;
		}

		if ( $file_ts < $cutoff_ts ) {
			@unlink( $path );
		}
	}
}

/* -------------------------------------------------------------
 * Module bootstrap (requires only; init happens inside each loader)
 * ------------------------------------------------------------- */
require_once AAA_OC_PLUGIN_DIR . 'includes/core/aaa-oc-core-assets-loader.php';



 /* PayConfirm (Auto Payment Confirmation */
require_once AAA_OC_PLUGIN_DIR . 'includes/payconfirm/aaa-oc-payconfirm-assets-loader.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payconfirm/aaa-oc-payconfirm-loader.php';


 /*  productsearch  */
require_once AAA_OC_PLUGIN_DIR . 'includes/productsearch/aaa-oc-productsearch-assets-loader.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/productsearch/aaa-oc-productsearch-loader.php';

/* Board modules / options / helpers */
require_once AAA_OC_PLUGIN_DIR . 'includes/core/modules/board-order-counter/board-order-counter-loader.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/core/options/class-aaa-oc-options-loader.php';
//require_once AAA_OC_PLUGIN_DIR . 'includes/core/options/helpers/class-aaa-oc-loader-util.php';

require_once AAA_OC_PLUGIN_DIR . 'includes/payment/helpers/class-aaa-oc-map-payment-method.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payment/helpers/class-aaa-oc-payment-status-label.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payment/helpers/class-aaa-oc-disable-auto-paid.php';

require_once AAA_OC_PLUGIN_DIR . 'includes/fulfillment/helpers/class-aaa-oc-fulfillment-analytics.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/fulfillment/helpers/class-aaa-oc-map-fulfillment-status.php';

require_once AAA_OC_PLUGIN_DIR . 'includes/helpers/class-aaa-oc-map-order-source.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/helpers/class-aaa-oc-update-order-notes.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/delivery/helpers/class-aaa-oc-save-driver.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/delivery/helpers/class-aaa-oc-save-delivery.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/delivery/helpers/class-aaa-oc-delivery-key-bridge.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/delivery/helpers/class-aaa-oc-delivery-metabox.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/fulfillment/helpers/class-build-product-table.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/helpers/class-render-next-prev-icons.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/helpers/class-time-diff-helper.php';

require_once AAA_OC_PLUGIN_DIR . 'includes/indexers/class-aaa-oc-customer-index.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/indexers/class-aaa-oc-delivery-index.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/indexers/class-aaa-oc-indexer.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/indexers/class-aaa-oc-table-installer.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/indexers/class-aaa-oc-indexer-settings-page.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/fulfillment/index/class-aaa-oc-product-fulfillment-index.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/fulfillment/index/class-aaa-oc-fulfillment-table-installer.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/fulfillment/index/class-aaa-oc-fulfillment-indexer.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/indexers/class-aaa-oc-reindex-functions.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payment/index/class-aaa-oc-payment-indexer.php';

require_once AAA_OC_PLUGIN_DIR . 'includes/announcements/class-aaa-oc-annc-table-installer.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/announcements/class-aaa-oc-annc-loader.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payment/class-aaa-oc-payment-modal.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payment/class-aaa-oc-ajax-payment-update.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payment/class-aaa-oc-payment-fields.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payment/class-aaa-oc-payment-meta.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payment/class-aaa-oc-payment-setup.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/payment/ajax/class-aaa-oc-payment-feed.php';


require_once AAA_OC_PLUGIN_DIR . 'includes/fulfillment/admin/class-aaa-oc-admin-fulfillment-column.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/fulfillment/admin/class-aaa-oc-admin-bulk-fulfill.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/ajax/class-aaa-oc-board-prefs.php';

require_once AAA_OC_PLUGIN_DIR . 'includes/class-aaa-oc-ajax-cards.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/class-aaa-oc-ajax-core.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/class-aaa-oc-board.php';
require_once AAA_OC_PLUGIN_DIR . 'includes/class-aaa-oc-printing.php';

require_once AAA_OC_PLUGIN_DIR . 'includes/patches/class-aaa-oc-tip-index-sync.php';

/* -------------------------------------------------------------
 * Init after all classes are present
 * ------------------------------------------------------------- */
add_action( 'plugins_loaded', 'aaa_oc_init_plugin' );
function aaa_oc_init_plugin() {
	if ( ! class_exists( 'WooCommerce' ) ) return;

	new AAA_OC_Board();
	new AAA_OC_Ajax_Core();
	new AAA_OC_Indexing();
	new AAA_OC_Indexer_Settings_Page();
	new AAA_OC_Announcements_Loader();

	AAA_OC_Payment_Meta::init();
	AAA_OC_Ajax_Payment_Update::init();
	AAA_OC_Fulfillment_Analytics::init();
	AAA_OC_Update_Order_Notes::init();

	// Hooks for indexing lifecycle
	add_action( 'woocommerce_new_order', 'aaa_oc_insert_default_payment_row', 10, 1 );
	add_action( 'woocommerce_after_order_object_save', 'aaa_oc_index_after_save', 20 );
	add_action( 'woocommerce_update_order', 'aaa_oc_index_after_save', 10, 1 );
	add_action( 'before_delete_post', 'aaa_oc_fallback_delete_order_index', 10, 1 );
}

function aaa_oc_insert_default_payment_row( $order_id ) {
	if ( ! class_exists( 'AAA_OC_Payment_Fields' ) ) return;
	AAA_OC_Payment_Fields::ensure_payment_row_exists( $order_id );
}

function aaa_oc_index_after_save( $order ) {
	if ( ! is_a( $order, 'WC_Order' ) ) return;
	if ( class_exists( 'AAA_OC_Indexing' ) ) {
		$i = new AAA_OC_Indexing();
		$i->index_order( $order->get_id() );
	}
}

function aaa_oc_fallback_delete_order_index( $post_id ) {
	if ( get_post_type( $post_id ) === 'shop_order' ) {
		error_log( "[OC] fallback delete triggered for order #$post_id" );
		aaa_oc_delete_index_row( $post_id );
	}
}

function aaa_oc_delete_index_row( $order_id ) {
	error_log( "[TEST] Delete hook fired for order #$order_id" );
	global $wpdb;
	$table = $wpdb->prefix . 'aaa_oc_order_index';
	$wpdb->delete( $table, [ 'order_id' => $order_id ], [ '%d' ] );
	aaa_oc_log( "[Indexing] Deleted order #$order_id from index." );
}

add_action( 'admin_enqueue_scripts', 'aaa_oc_enqueue_admin_assets' );
function aaa_oc_enqueue_admin_assets() {
    $screen = get_current_screen();
    if ( isset( $screen->id ) && strpos( $screen->id, 'aaa-oc' ) !== false ) {

	if ( isset( $_GET['page'] ) && $_GET['page'] === 'aaa-oc-workflow-board' ) {
	    wp_enqueue_style( 'aaa-oc-board-css', AAA_OC_PLUGIN_URL . 'assets/css/board.css', [], AAA_OC_VERSION );
	}

// Scripts
wp_enqueue_script( 'aaa-oc-board-js',            AAA_OC_PLUGIN_URL . 'assets/js/board.js',              [ 'jquery' ], AAA_OC_VERSION, true );
wp_enqueue_script( 'aaa-oc-board-listener-js',   AAA_OC_PLUGIN_URL . 'assets/js/board-listener.js',     [ 'jquery' ], AAA_OC_VERSION, true );
wp_enqueue_script( 'aaa-oc-board-data-indexer',  AAA_OC_PLUGIN_URL . 'assets/js/board-data-indexer.js', [ 'jquery' ], '1.0', true );
wp_enqueue_script( 'aaa-oc-board-print-js',      AAA_OC_PLUGIN_URL . 'assets/js/board-print.js',        [ 'jquery' ], AAA_OC_VERSION, true );
wp_enqueue_script( 'board-payment-calc',         AAA_OC_PLUGIN_URL . 'assets/js/board-payment-calc.js', [ 'jquery' ], '1.0.0', true );
wp_enqueue_script( 'board-payment-save',         AAA_OC_PLUGIN_URL . 'assets/js/board-payment-save.js', [ 'jquery' ], '1.0.0', true );
wp_enqueue_script( 'board-payment-modal',        AAA_OC_PLUGIN_URL . 'assets/js/board-payment-modal.js',[ 'jquery' ], '1.0.0', true );
wp_enqueue_script( 'aaa-oc-admin-notes',         AAA_OC_PLUGIN_URL . 'assets/js/board-admin-notes.js',  [ 'jquery' ], '1.0.0', true );
wp_enqueue_script( 'aaa-oc-save-driver',         AAA_OC_PLUGIN_URL . 'assets/js/board-save-driver.js',  [ 'jquery' ], '1.0.0', true );

		// Header shell + panels (provides aaaOcPanels, sidebar feed, top sheets)
//		wp_enqueue_script( 'aaa-oc-board-toolbar-extras', AAA_OC_PLUGIN_URL . 'assets/js/board-toolbar-extras.js', [ 'jquery' ], '1.0.0', true );

	// Header alignment fix (must load last so it can move elements out of <h1>)
	wp_enqueue_script( 'aaa-oc-board-header-fix', AAA_OC_PLUGIN_URL . 'assets/js/board-header-fix.js', [ 'jquery', 'aaa-oc-board-toolbar', 'aaa-oc-board-filters', 'aaa-oc-board-actions' ], '1.0.0', true );

	// Board-specific UI options
	$showCountdown = (int) get_option( 'aaa_oc_show_countdown_bar', 0 );

        wp_localize_script( 'aaa-oc-board-js', 'AAA_OC_Vars', [
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'aaa_oc_ajax_nonce' ),
	    'showCountdown' => $showCountdown,
            'pollInterval'  => 60,
        ]);

        wp_localize_script( 'aaa-oc-board-listener-js', 'AAA_OC_Listener_Vars', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'aaa_oc_listener_nonce' ),
        ]);

        wp_localize_script( 'aaa-oc-board-data-indexer', 'AAA_OC_Vars', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'aaa_oc_ajax_nonce' ),
        ]);
	wp_localize_script('board-payment-save', 'AAA_OC_Payment', [
	    'ajaxUrl' => admin_url('admin-ajax.php'),
	    'nonce'   => wp_create_nonce('aaa_oc_ajax_nonce'),
	]);
    }
}
/**
 * Manual board refresh (Version 1)
 */
add_action( 'wp_ajax_aaa_oc_refresh_board', function () {
	check_ajax_referer( 'aaa_oc_ajax_nonce', 'nonce' );

	global $wpdb;
	$table = $wpdb->prefix . 'aaa_oc_order_index';

	$statuses = [ 'pending', 'processing', 'completed' ];
	$columns = [];

	foreach ( $statuses as $slug ) {
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table WHERE status = %s ORDER BY time_published DESC", $slug )
		);

		ob_start();
		foreach ( $rows as $r ) {
			$order_id = (int) $r->order_id;
			$row      = (array) $r;
			include AAA_OC_PLUGIN_DIR . 'includes/partials/card-collapsed.php';
		}
		$columns[ $slug ] = ob_get_clean();
	}

	wp_send_json_success( [ 'columns' => $columns ] );
} );

/* -------------------------------------------------------------
 * Activation / Deactivation
 * ------------------------------------------------------------- */
register_activation_hook( __FILE__, 'aaa_oc_activate' );
function aaa_oc_activate() {
	if ( class_exists( 'AAA_OC_Payment_Setup' ) )        { AAA_OC_Payment_Setup::install(); }
	if ( class_exists( 'AAA_OC_Table_Installer' ) )      { AAA_OC_Table_Installer::create_index_table(); }
	if ( class_exists( 'AAA_OC_Fulfillment_Analytics' ) ){ AAA_OC_Fulfillment_Analytics::create_table(); }

	// Default log retention (days) if not set.
	if ( get_option( 'aaa_oc_log_retention_days', null ) === null ) {
		add_option( 'aaa_oc_log_retention_days', 14, '', false );
	}

	if ( ! wp_next_scheduled( 'aaa_oc_scheduled_cleanup' ) ) {
		wp_schedule_event( time(), 'daily', 'aaa_oc_scheduled_cleanup' );
	}
}

register_deactivation_hook( __FILE__, 'aaa_oc_deactivate' );
function aaa_oc_deactivate() {
	wp_clear_scheduled_hook( 'aaa_oc_scheduled_cleanup' );
}

/* -------------------------------------------------------------
 * Admin Bar shortcut → Board (V1)
 * ------------------------------------------------------------- */
add_action( 'admin_bar_menu', function( $wp_admin_bar ) {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
	$wp_admin_bar->add_node( [
		'id'    => 'aaa-workflow-board-v1',
		'title' => 'Workflow Board',
		'href'  => admin_url( 'admin.php?page=aaa-oc-workflow-board' ),
		'meta'  => [
			'class'  => 'aaa-workflow-board-v1-link',
			'title'  => 'Go to Workflow Board (Version 1)',
			'target' => '_blank',
		],
	] );
}, 100 );

/* -------------------------------------------------------------
 * Plugin list → “Settings” link to Workflow Settings
 * ------------------------------------------------------------- */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	$settings_url = admin_url( 'admin.php?page=aaa-oc-core-settings' );
	array_unshift( $links, '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'aaa-order-workflow' ) . '</a>' );
	return $links;
} );

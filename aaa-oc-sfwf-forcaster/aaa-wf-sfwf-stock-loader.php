<?php
/**
 * Plugin Name:       AAA Stock Forecast Workflow V1
 * Description:       Predicts out-of-stock risks and prepares purchase orders based on sales velocity, stock, and lead time.
 * Version:           1.4.0
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

// === Activation Hook: Create custom options table ===
register_activation_hook( __FILE__, function() {
	require_once SFWF_ROOT . 'index/table-options.php';
	sfwf_create_options_table();
	// Create forecast index table for partial rebuilds
	if ( class_exists( 'WF_SFWF_Forecast_Index' ) ) {
		WF_SFWF_Forecast_Index::create_table();
	}
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
require_once SFWF_ROOT . 'helpers/class-wf-sfwf-product-fields.php';
require_once SFWF_ROOT . 'helpers/forecast-column-definitions.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-product-fields.php';
//require_once SFWF_ROOT . 'api/class-sfwf-rest-forecast.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-index.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-scheduler.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-flag-handler.php';

// === Custom: Load additional classes for bulk actions and selected forecasts ===
// These files register themselves via hooks and do not modify existing
// functionality. They provide the ability to run the forecast on selected
// products from both the products list and the forecast grid.
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-bulk-actions.php';
require_once SFWF_ROOT . 'index/class-wf-sfwf-forecast-selected-handler.php';
// Load the purchase order handler.  This adds the ability to add selected
// products to a purchase order from the forecast grid.  The handler itself
// provides a stub implementation that simply flags products as added to a PO.
require_once SFWF_ROOT . 'index/class-wf-sfwf-purchase-order-handler.php';

// === Admin Menu: Settings Page ===
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
        function() {
            require_once SFWF_ROOT . 'views/forecast-dashboard.php';
        }
    );
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

/**
 * Prevent third‑party update check scripts from loading on the forecast pages.
 *
 * The Admin Columns Pro plugin and some other extensions enqueue scripts that
 * perform remote update checks via AJAX. On some hosting configurations these
 * scripts attempt to call a different domain and cause CORS errors that can
 * break other JavaScript on the page (including DataTables sorting). To
 * safeguard the forecast UI, we dequeue and deregister those scripts when
 * rendering our plugin pages. See the support threads for update‑plugins‑check.js
 * errors for more details.
 *
 * @param string $hook_suffix The current admin page hook.
 */
add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
        // Identify our plugin pages where the forecast UI is rendered. When
        // visiting these pages, disable the problematic scripts to avoid JS
        // errors that interfere with DataTable sorting and filtering.
        $pages_to_clean = [
                'woocommerce_page_sfwf-forecast-grid',
                'woocommerce_page_sfwf-settings',
        ];
        if ( in_array( $hook_suffix, $pages_to_clean, true ) ) {
                // Deregister the Admin Columns Pro plugin update check script if present.
                // Both handles are checked because the exact handle name can vary
                // between versions (acp-plugins-update-check or update-plugins-check).
                foreach ( [ 'acp-plugins-update-check', 'update-plugins-check' ] as $handle ) {
                        if ( wp_script_is( $handle, 'enqueued' ) || wp_script_is( $handle, 'registered' ) ) {
                                wp_dequeue_script( $handle );
                                wp_deregister_script( $handle );
                        }
                }
                // Deregister the WooCommerce Global Cart (woogc) trigger if present.
                if ( wp_script_is( 'woogc', 'enqueued' ) || wp_script_is( 'woogc', 'registered' ) ) {
                        wp_dequeue_script( 'woogc' );
                        wp_deregister_script( 'woogc' );
                }
        }
}, 100 );

/*
 * Enqueue DataTables assets for the forecast grid.
 *
 * The forecast grid relies on the DataTables library for sorting, filtering
 * and scrolling. To ensure a single version of DataTables is loaded and
 * avoid conflicts with other plugins, we enqueue the CSS and JS from
 * WordPress rather than embedding CDN links in the view file. These assets
 * are loaded only on the forecast grid page (woocommerce_page_sfwf-forecast-grid).
 */
add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
        // Only enqueue on our forecast grid page
        if ( $hook_suffix !== 'woocommerce_page_sfwf-forecast-grid' ) {
                return;
        }
        // Enqueue core DataTables styles and scripts.  Versions are pinned to
        // ensure compatibility across the plugin.  Should you need to update
        // versions in the future, change these version strings consistently.
        wp_enqueue_style( 'sfwf-dt-core', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', [], '1.13.6' );
        wp_enqueue_style( 'sfwf-dt-fixedheader', 'https://cdn.datatables.net/fixedheader/3.2.4/css/fixedHeader.dataTables.min.css', [ 'sfwf-dt-core' ], '3.2.4' );
        // Optionally add DataTables Buttons or other extensions here if needed.

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'sfwf-dt-core', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', [ 'jquery' ], '1.13.6', true );
        wp_enqueue_script( 'sfwf-dt-fixedheader', 'https://cdn.datatables.net/fixedheader/3.2.4/js/dataTables.fixedHeader.min.js', [ 'sfwf-dt-core' ], '3.2.4', true );
        // The view file includes inline DataTables initialization; therefore
        // no separate script is enqueued here.  Additional DataTables
        // extensions could be added as dependencies if necessary.
}, 20 );
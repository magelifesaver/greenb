<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/admin/tabs/aaa-oc-productforecast.php
 * Main settings dispatcher for the Product Forecast module.
 * Version: 1.1.0
 * This file proxies to the legacy SFWF settings page that contains global
 * options for forecasting (lead time, cost percent, window sizes, thresholds).
 * The use of this proxy allows us to expose the settings under the new module
 * slug without altering the original page contents.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Delegate rendering to the legacy settings page.  The file contains the
// function `sfwf_render_settings_page()` that outputs the form and handles
// POST submissions.
require_once __DIR__ . '/aaa-oc-productforecast-settings.php';

// Call the rendering function if it exists.
if ( function_exists( 'sfwf_render_settings_page' ) ) {
    sfwf_render_settings_page();
} else {
    echo '<div class="notice notice-error"><p>Unable to load forecast settings page.</p></div>';
}

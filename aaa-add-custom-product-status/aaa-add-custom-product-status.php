<?php
/**
 * File: /plugins/aaa-add-custom-product-status/aaa-add-custom-product-status.php
 * Plugin Name: A A Add Custom Product Status 2.0 (visibility-only)
 * Description: Keeps post_status stable; archives out-of-stock via meta and hides them from admin lists. Compatible with sync/global cart.
 * Version: 2.0.0
 * Author: WebMaster
 * Text Domain: aaa-custom-status
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AAA_CPS_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_CPS_VER', '2.0.0' );

require_once AAA_CPS_DIR . 'inc/core.php';
require_once AAA_CPS_DIR . 'inc/admin.php';
require_once AAA_CPS_DIR . 'inc/cron.php';

register_activation_hook( __FILE__, function() {
    if ( function_exists( 'aaa_reconcile_stock_statuses' ) ) {
        aaa_reconcile_stock_statuses();
    }
    if ( ! wp_next_scheduled( 'aaa_reconcile_stock_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'aaa_reconcile_stock_event' );
    }
} );

register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'aaa_reconcile_stock_event' );
} );

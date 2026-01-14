<?php
/**
 * Forecast module loader.
 *
 * Responsible for defining constants, loading classes and
 * registering cron jobs and admin links. Breaking this logic out
 * into its own file keeps the main plugin file lightweight and
 * follows the "wide & thin" architecture guidelines.
 * Version: 0.1.4
 * @package AAA_Order_Workflow
 */

// Do not allow direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Forecast_Loader
 *
 * This static class initialises the forecast module. Constants are defined
 * once, required files are included, init hooks are registered and a
 * Settings link is added to the plugin list. Keeping all of this in
 * one place makes the load order obvious and avoids fragmented
 * initialisation code spread across multiple files.
 */
final class AAA_OC_Forecast_Loader {

    /**
     * Initialise the module. This method is called immediately when
     * this file is included by the main plugin. It defines constants,
     * loads classes and wires up hooks.
     */
    public static function init(): void {
        global $wpdb;

        // Define module version if not already defined. Bump this when
        // database schema changes or major features are added.
        if ( ! defined( 'AAA_OC_FORECAST_VERSION' ) ) {
            define( 'AAA_OC_FORECAST_VERSION', '0.2.0' );
        }

        // Define queue table names using the current database prefix. This
        // allows multisite installations to have isolated tables per site.
        if ( ! defined( 'AAA_OC_FORECAST_QUEUE_TABLE' ) ) {
            define( 'AAA_OC_FORECAST_QUEUE_TABLE', $wpdb->prefix . 'aaa_oc_forecast_queue' );
        }
        if ( ! defined( 'AAA_OC_FORECAST_PO_QUEUE_TABLE' ) ) {
            define( 'AAA_OC_FORECAST_PO_QUEUE_TABLE', $wpdb->prefix . 'aaa_oc_forecast_po_queue' );
        }

        /*
         * Always load our classes. Splitting responsibilities across
         * multiple files keeps each one under 150 lines and makes the
         * codebase easier to reason about. Classes now reside in
         * subdirectories beneath this file.
         */
        require_once __DIR__ . '/queue/class-aaa-oc-forecast-queue.php';
        require_once __DIR__ . '/queue/class-aaa-oc-forecast-po-queue.php';
        require_once __DIR__ . '/po/class-aaa-oc-po-manager.php';
        require_once __DIR__ . '/install/class-aaa-oc-forecast-queue-installer.php';
        require_once __DIR__ . '/admin/class-aaa-oc-forecast-admin-actions.php';
        // Include the assets loader if it exists. This file enqueues any
        // JavaScript or CSS used by the forecast module.
        $assets_loader = __DIR__ . '/aaa-oc-forecast-assets-loader.php';
        if ( file_exists( $assets_loader ) ) {
            require_once $assets_loader;
        }

        // Initialise the queue classes and admin actions. Each class
        // registers its own hooks when init() is called.
        AAA_OC_Forecast_Queue::init();
        AAA_OC_Forecast_PO_Queue::init();
        AAA_OC_Forecast_Admin_Actions::init();

        // Register the installer on plugin activation. Using the full
        // plugin path here ensures WordPress calls our installer when the
        // module is first activated.
        $plugin_file = dirname( __DIR__, 2 ) . '/aaa-order-workflow.php';
        if ( function_exists( 'register_activation_hook' ) ) {
            register_activation_hook( $plugin_file, [ 'AAA_OC_Forecast_Queue_Installer', 'install' ] );
        }

        // Add a Settings link to the plugin row in the admin plugins page.
        $plugin_basename = plugin_basename( $plugin_file );
        add_filter( 'plugin_action_links_' . $plugin_basename, [ __CLASS__, 'settings_link' ] );
    }

    /**
     * Append our settings link to the plugin action links.
     *
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public static function settings_link( array $links ): array {
        $url = admin_url( 'admin.php?page=aaa-oc-core-settings&tab=aaa-oc-forecast-queue' );
        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'aaa-oc' ) . '</a>';
        return $links;
    }
}

// Kick off initialisation as soon as this file is included.
AAA_OC_Forecast_Loader::init();
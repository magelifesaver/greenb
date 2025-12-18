<?php
/**
 * Plugin Name: Vector Sync
 * Description: Synchronize WordPress content with external vector databases.  This plugin allows administrators to connect their site to either a Pinecone index or an OpenAI vector store and schedule synchronisation of posts, products or orders into a selected vector space.
 * Version: 1.3.0
 * Author: AI Assistant
 * License: GPL2
 *
 * This plugin is designed to remain small and modular.  Each class lives in its
 * own file under the `includes/` directory and is loaded from here.  See
 * individual files for implementation details.  Activation and deactivation
 * hooks are used to schedule and unschedule cron events.  Further logic is
 * contained within the classes.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'VECTOR_SYNC_VERSION', '1.3.0' );
define( 'VECTOR_SYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Require core classes.  Keeping includes at top-level makes the architecture
// predictable and avoids conditional loading.  Each class file is kept under
// 150 lines per the project’s “wide & thin” preference.
require_once VECTOR_SYNC_PLUGIN_DIR . 'includes/class-vector-sync-scheduler.php';
require_once VECTOR_SYNC_PLUGIN_DIR . 'includes/class-vector-sync-api-client.php';
require_once VECTOR_SYNC_PLUGIN_DIR . 'includes/class-vector-sync-data-manager.php';
require_once VECTOR_SYNC_PLUGIN_DIR . 'includes/class-vector-sync-admin-page.php';
require_once VECTOR_SYNC_PLUGIN_DIR . 'includes/class-vector-sync-db.php';

// When settings are updated, reschedule cron jobs based on user preferences.
// We no longer rely on the default WordPress option for saving plugin settings.  Instead, the
// admin page calls Vector_Sync_DB::update_settings() directly.  However, in order to
// maintain backward compatibility with the original scheduling mechanism we also
// listen to changes on the option and reschedule when necessary.
add_action( 'update_option_vector_sync_settings', array( 'Vector_Sync_Scheduler', 'settings_updated' ), 10, 2 );

/**
 * Initialise the plugin by instantiating the admin page handler.  Hooked on
 * `init` so that WordPress has loaded its environment but before output.
 */
function vector_sync_init() {
    // Instantiate the admin page to register menus and settings.  All
    // functionality is encapsulated in the class; instantiating it here
    // triggers its constructor which hooks into WordPress.
    new Vector_Sync_Admin_Page();
}
add_action( 'init', 'vector_sync_init' );

/**
 * Activation hook schedules any initial events.  When the plugin is
 * activated, we set up default cron schedules for initial import and recurring
 * synchronisation if the user has configured schedules.
 */
function vector_sync_activate() {
    // Create our custom settings table on activation.  Storing plugin data
    // outside of wp_options keeps it separate from other plugins and allows
    // migrating the settings structure without collisions.
    Vector_Sync_DB::create_table();
    Vector_Sync_Scheduler::activate();
}
register_activation_hook( __FILE__, 'vector_sync_activate' );

/**
 * Deactivation hook cleans up scheduled events.  WordPress’s cron system
 * continues to run scheduled tasks even after the plugin is removed unless
 * explicitly unscheduled【357505950125911†L166-L170】.
 */
function vector_sync_deactivate() {
    Vector_Sync_Scheduler::deactivate();
}
register_deactivation_hook( __FILE__, 'vector_sync_deactivate' );

/**
 * Use the custom settings table when retrieving our plugin options.  The
 * `pre_option_{$option}` filter fires before WordPress loads an option from
 * the options table.  We intercept requests for `vector_sync_settings` and
 * return the value stored in the plugin’s own table.  Returning a non‑null
 * value short‑circuits the default get_option() call.
 *
 * @param mixed  $value   The default value passed to get_option().
 * @param string $option  Option name (always vector_sync_settings here).
 * @param mixed  $default The default value requested by get_option().
 * @return mixed Settings array from the custom table or null to fall back.
 */
function vector_sync_pre_option_filter( $value, $option, $default ) {
    if ( 'vector_sync_settings' === $option ) {
        return Vector_Sync_DB::get_settings();
    }
    return $value;
}
add_filter( 'pre_option_vector_sync_settings', 'vector_sync_pre_option_filter', 10, 3 );
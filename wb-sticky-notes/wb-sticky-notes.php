<?php
/**
 * The plugin bootstrap file
 *
 *
 * @link              https://wordpress.org/plugins/wb-sticky-notes
 * @since             1.0.0
 * @package           Wb_Sticky_Notes
 *
 * @wordpress-plugin
 * Plugin Name:       Sticky Notes for WP Dashboard
 * Description:       Easily add, manage, and organize sticky notes directly on your WordPress dashboard. Perfect for reminders, to-dos, and team collaboration.
 * Version:           1.2.7
 * Author:            Web Builder 143
 * Author URI:        https://profiles.wordpress.org/webbuilder143/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wb-sticky-notes
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define('WB_STICKY_NOTES_VERSION','1.2.7');

define('WB_STN_SETTINGS','WB_STN_SETTINGS');

define('WB_STN_POST_TYPE','wb-sticky-notes');

define ('WB_STN_PLUGIN_FILENAME',__FILE__);
define ('WB_STICKY_PLUGIN_NAME','wb-sticky-notes');
define ('WB_STN_PLUGIN_PATH',plugin_dir_path(WB_STN_PLUGIN_FILENAME));
define ('WB_STN_PLUGIN_URL',plugin_dir_url(WB_STN_PLUGIN_FILENAME));


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wb-sticky-notes-activator.php
 */
function activate_wb_sticky_notes() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wb-sticky-notes-activator.php';
	Wb_Sticky_Notes_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wb-sticky-notes-deactivator.php
 */
function deactivate_wb_sticky_notes() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wb-sticky-notes-deactivator.php';
	Wb_Sticky_Notes_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wb_sticky_notes' );
register_deactivation_hook( __FILE__, 'deactivate_wb_sticky_notes' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wb-sticky-notes.php';

/**
 * Begins execution of the plugin.
 * @since    1.0.0
 */
function run_wb_sticky_notes() {

	$plugin = new Wb_Sticky_Notes();
	$plugin->run();

}
add_action('init', 'run_wb_sticky_notes');
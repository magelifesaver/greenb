<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://powerfulwp.com/
 * @since             1.0.0
 * @package           Aafw
 *
 * @wordpress-plugin
 * Plugin Name: Autocomplete Address and Location Picker for WooCommerce Premium
 * Plugin URI:        https://powerfulwp.com/autocomplete-address-and-location-picker-for-woocommerce
 * Description:       Autocomplete Address and Location Picker for WooCommerce Checkout.
 * Version:           1.1.9
 * Update URI: https://api.freemius.com
 * Author:            powerfulwp
 * Author URI:        https://powerfulwp.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aafw
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}
// Declare extension compatible with HPOS.
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if ( !function_exists( 'aafw_fs' ) ) {
    // Create a helper function for easy SDK access.
    function aafw_fs() {
        global $aafw_fs;
        if ( !isset( $aafw_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_8803_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_8803_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $aafw_fs = fs_dynamic_init( array(
                'id'             => '8803',
                'slug'           => 'autocomplete-address-and-location-picker-for-woocommerce',
                'type'           => 'plugin',
                'public_key'     => 'pk_abf76737e4756573c372cd520c94c',
                'is_premium'     => true,
                'premium_suffix' => 'Premium',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                    'slug'    => 'aafw-settings',
                    'support' => false,
                    'network' => true,
                ),
                'is_live'        => true,
            ) );
        }
        return $aafw_fs;
    }

    // Init Freemius.
    aafw_fs();
    // Signal that SDK was initiated.
    do_action( 'aafw_fs_loaded' );
}
if ( !function_exists( 'activate_aafw' ) ) {
    define( 'AAFW_VERSION', '1.1.9' );
    /**
     * Define supported plugins.
     */
    $aafw_plugins = array();
    if ( is_plugin_active( 'pickup-and-delivery-from-customer-locations-for-woocommerce-pro/pickup-and-delivery-from-customer-locations-for-woocommerce.php' ) ) {
        // Pickup from customers locations premium.
        $aafw_plugins[] = 'pickup-and-delivery-from-customer-locations-for-woocommerce-pro';
    }
    define( 'AAFW_PLUGINS', $aafw_plugins );
    /**
     * The code that runs during plugin activation.
     * This action is documented in includes/class-aafw-activator.php
     */
    function activate_aafw() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-aafw-activator.php';
        Aafw_Activator::activate();
    }

    /**
     * The code that runs during plugin deactivation.
     * This action is documented in includes/class-aafw-deactivator.php
     */
    function deactivate_aafw() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-aafw-deactivator.php';
        Aafw_Deactivator::deactivate();
    }

    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since    1.0.0
     */
    function run_aafw() {
        $plugin = new Aafw();
        $plugin->run();
    }

    /**
     * Admin notices function.
     *
     * @since 1.0.0
     */
    function aafw_admin_notices() {
        if ( !class_exists( 'WooCommerce' ) ) {
            echo '<div class="notice notice-error is-dismissible">
				<p>' . esc_html( __( 'Local delivery drivers for WooCommerce is a WooCommerce add-on, you must activate a WooCommerce on your site.', 'aafw' ) ) . '</p>
				</div>';
        }
    }

    /**
     * Initializes the plugin.
     * This function checks if WooCommerce is active before running the plugin.
     * If WooCommerce is not active, it displays an admin notice.
     */
    function initialize_run_aafw() {
        // Check if WooCommerce is active.
        if ( !class_exists( 'WooCommerce' ) ) {
            // Adding action to admin_notices to display a notice if WooCommerce is not active.
            add_action( 'admin_notices', 'aafw_admin_notices' );
            return;
            // Stop the initialization as WooCommerce is not active.
        }
        // WooCommerce is active, so initialize the plugin.
        run_aafw();
    }

}
register_activation_hook( __FILE__, 'activate_aafw' );
register_deactivation_hook( __FILE__, 'deactivate_aafw' );
// Include the internationalization class to handle text domain loading.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aafw-i18n.php';
/**
 * Initializes internationalization (i18n) support for the plugin.
 */
if ( !function_exists( 'aafw_initialize_i18n' ) ) {
    function aafw_initialize_i18n() {
        // Create an instance of the Aafw_I18n class.
        $plugin_i18n = new Aafw_I18n();
        // Hook the 'load_plugin_textdomain' method of the Aafw_I18n class to the 'plugins_loaded' action.
        // This ensures that the plugin's text domain is loaded as soon as all plugins are loaded by WordPress,
        // making translations available.
        add_action( 'plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain') );
    }

}
// Call the function to initialize internationalization support.
aafw_initialize_i18n();
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-aafw.php';
// Hook into 'plugins_loaded' with a priority of 20 to initialize the plugin after all plugins have loaded.
// This is particularly useful for ensuring the plugin loads after WooCommerce, if WooCommerce is a dependency.
add_action( 'plugins_loaded', 'initialize_run_aafw', 20 );
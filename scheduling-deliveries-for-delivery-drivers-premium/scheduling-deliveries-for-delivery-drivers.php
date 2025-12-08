<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://https://powerfulwp.com
 * @since             1.0.0
 * @package           Scheduling_Deliveries_For_Delivery_Drivers
 *
 * @wordpress-plugin
 * Plugin Name: Scheduling Deliveries for Delivery Drivers Premium
 * Plugin URI:        https://https://powerfulwp.com/Scheduling-Deliveries-for-Delivery-Drivers
 * Description:       Scheduling Deliveries for Delivery Drivers
 * Version:           1.0.2
 * Update URI: https://api.freemius.com
 * Author:            powerfulwp
 * Author URI:        https://https://powerfulwp.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       scheduling-deliveries-for-delivery-drivers
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}
/**
 * Declare compatibility with HPOS (High-Performance Order Storage).
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
/**
 * Freemius integration function.
 */
if ( !function_exists( 'sdfdd_fs' ) ) {
    // Create a helper function for easy SDK access.
    function sdfdd_fs() {
        global $sdfdd_fs;
        if ( !isset( $sdfdd_fs ) ) {
            // Include Freemius SDK.
            if ( file_exists( dirname( dirname( __FILE__ ) ) . '/local-delivery-drivers-for-woocommerce/freemius/start.php' ) ) {
                // Try to load SDK from parent plugin folder.
                require_once dirname( dirname( __FILE__ ) ) . '/local-delivery-drivers-for-woocommerce/freemius/start.php';
            } else {
                if ( file_exists( dirname( dirname( __FILE__ ) ) . '/local-delivery-drivers-for-woocommerce-premium/freemius/start.php' ) ) {
                    // Try to load SDK from premium parent plugin folder.
                    require_once dirname( dirname( __FILE__ ) ) . '/local-delivery-drivers-for-woocommerce-premium/freemius/start.php';
                } else {
                    require_once dirname( __FILE__ ) . '/freemius/start.php';
                }
            }
            $sdfdd_fs = fs_dynamic_init( array(
                'id'               => '16535',
                'slug'             => 'scheduling-deliveries-for-delivery-drivers',
                'type'             => 'plugin',
                'public_key'       => 'pk_2982f5732717909536b9c48d935d1',
                'is_premium'       => true,
                'premium_suffix'   => 'Premium',
                'has_paid_plans'   => true,
                'is_org_compliant' => false,
                'trial'            => array(
                    'days'               => 14,
                    'is_require_payment' => true,
                ),
                'parent'           => array(
                    'id'         => '6995',
                    'slug'       => 'local-delivery-drivers-for-woocommerce',
                    'public_key' => 'pk_5ae065da4addc985fe67f63c46a51',
                    'name'       => 'Local Delivery Drivers for WooCommerce',
                ),
                'menu'             => array(
                    'support' => false,
                ),
                'is_live'          => true,
            ) );
        }
        return $sdfdd_fs;
    }

    /**
     * Define the plugin version.
     */
    define( 'SCHEDULING_DELIVERIES_FOR_DELIVERY_DRIVERS_VERSION', '1.0.2' );
    /**
     * Activation hook function.
     */
    function activate_scheduling_deliveries_for_delivery_drivers() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-scheduling-deliveries-for-delivery-drivers-activator.php';
        Scheduling_Deliveries_For_Delivery_Drivers_Activator::activate();
    }

    /**
     * Deactivation hook function.
     */
    function deactivate_scheduling_deliveries_for_delivery_drivers() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-scheduling-deliveries-for-delivery-drivers-deactivator.php';
        Scheduling_Deliveries_For_Delivery_Drivers_Deactivator::deactivate();
    }

    register_activation_hook( __FILE__, 'activate_scheduling_deliveries_for_delivery_drivers' );
    register_deactivation_hook( __FILE__, 'deactivate_scheduling_deliveries_for_delivery_drivers' );
    /**
     * Core plugin class that defines admin and public-facing hooks.
     */
    require plugin_dir_path( __FILE__ ) . 'includes/class-scheduling-deliveries-for-delivery-drivers.php';
    /**
     * Begins execution of the plugin.
     */
    function run_scheduling_deliveries_for_delivery_drivers() {
        if ( sdfdd_fs()->can_use_premium_code__premium_only() ) {
            if ( sdfdd_fs()->is_plan( 'premium' ) ) {
                $plugin = new Scheduling_Deliveries_For_Delivery_Drivers();
                $plugin->run();
            }
        }
    }

    /**
     * Checks if parent plugin (Local Delivery Drivers) is loaded.
     *
     * @return bool True if the parent plugin is active.
     */
    function sdfdd_fs_is_parent_active_and_loaded() {
        // Check if the parent's init SDK method exists.
        return function_exists( 'lddfw_fs' );
    }

    /**
     * Checks if parent plugin (Local Delivery Drivers) is active.
     *
     * @return bool True if the parent plugin is active.
     */
    function sdfdd_fs_is_parent_active() {
        $active_plugins = get_option( 'active_plugins', array() );
        if ( is_multisite() ) {
            $network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
            $active_plugins = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
        }
        foreach ( $active_plugins as $basename ) {
            if ( 0 === strpos( $basename, 'local-delivery-drivers-for-woocommerce-premium/' ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Initialize plugin functionality based on the parent plugin's status.
     */
    function sdfdd_fs_init() {
        if ( sdfdd_fs_is_parent_active_and_loaded() ) {
            // Init Freemius.
            sdfdd_fs();
            // Signal that the add-on's SDK was initiated.
            do_action( 'sdfdd_fs_loaded' );
            // Parent is active, add your init code here.
            run_scheduling_deliveries_for_delivery_drivers();
        } else {
            // Parent is inactive, add your error handling here.
        }
    }

    /**
     * Admin notices function.
     *
     * @since 1.0.0
     */
    function sdfdd_admin_notices() {
        if ( !class_exists( 'WooCommerce' ) ) {
            echo '<div class="notice notice-error is-dismissible">
				<p>' . esc_html( __( 'Scheduling Deliveries for Delivery Drivers is a WooCommerce add-on, you must activate a WooCommerce on your site.', 'scheduling-deliveries-for-delivery-drivers' ) ) . '</p>
				</div>';
        }
    }

    /**
     * Show an admin notice if Local Delivery Drivers version is incorrect.
     */
    function sdfdd_version_error_notice() {
        echo '<div class="notice notice-error is-dismissible">
        <p>' . esc_html__( 'Scheduling Deliveries for Delivery Drivers requires version 1.9.5 or higher of Local Delivery Drivers for WooCommerce Premium plugin. Please update the Local Delivery Drivers plugin to continue using this plugin.', 'scheduling-deliveries-for-delivery-drivers' ) . '</p>
    </div>';
    }

    /**
     * Initialize plugin on `plugins_loaded`.
     */
    function initialize_sdfdd_run() {
        if ( !class_exists( 'WooCommerce' ) ) {
            // Adding action to admin_notices to display a notice if WooCommerce is not active.
            add_action( 'admin_notices', 'sdfdd_admin_notices' );
            return;
            // Stop the initialization as WooCommerce is not active.
        }
        if ( defined( 'LDDFW_VERSION' ) ) {
            $required_version = '1.9.5';
            $current_version = LDDFW_VERSION;
            // If the version is lower than the required version, deactivate the plugin and show a notice.
            if ( version_compare( $current_version, $required_version, '<' ) ) {
                // Display an admin notice.
                add_action( 'admin_notices', 'sdfdd_version_error_notice' );
                return;
            }
        }
        if ( sdfdd_fs_is_parent_active_and_loaded() ) {
            // If parent already included, init add-on.
            sdfdd_fs_init();
        } elseif ( sdfdd_fs_is_parent_active() ) {
            // Init add-on only after the parent is loaded.
            add_action( 'lddfw_fs_loaded', 'sdfdd_fs_init' );
        } else {
            // Even though the parent is not activated, execute add-on for activation / uninstall hooks.
            sdfdd_fs_init();
        }
    }

    // Include the internationalization class to handle text domain loading.
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-scheduling-deliveries-for-delivery-drivers-i18n.php';
    /**
     * Initializes internationalization (i18n) support for the plugin.
     */
    if ( !function_exists( 'sdfdd_initialize_i18n' ) ) {
        function sdfdd_initialize_i18n() {
            // Create an instance of the Payments_For_Delivery_Drivers_i18n class.
            $plugin_i18n = new Scheduling_Deliveries_For_Delivery_Drivers_i18n();
            add_action( 'plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain') );
        }

    }
    // Call the function to initialize internationalization support.
    sdfdd_initialize_i18n();
    add_action( 'plugins_loaded', 'initialize_sdfdd_run', 21 );
}
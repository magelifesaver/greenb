<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link    http://www.powerfulwp.com
 * @since   1.0.0
 * @package LDDFW
 *
 * @wordpress-plugin
 * Plugin Name: Local Delivery Drivers for WooCommerce (Premium)
 * Plugin URI:        https://powerfulwp.com/local-delivery-drivers-for-woocommerce-premium/
 * Description:       Improve the way you deliver, manage drivers, assign drivers to orders, and with premium version much more, send SMS and email notifications, routes planning, navigation & more!
 * Version:           1.9.9
 * Update URI: https://api.freemius.com
 * Author:            powerfulwp
 * Author URI:        http://www.powerfulwp.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lddfw
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: 4.8
 *
 * @fs_premium_only /public/js/signature_pad.min.js, /includes/class-lddfw-media.php, /includes/class-lddfw-tracking.php, /includes/class-lddfw-assigned-order-email-driver.php, /includes/class-lddfw-delivered-email-admin.php, /includes/class-lddfw-failed-delivery-email.php, /includes/class-lddfw-out-for-delivery-email.php, /includes/class-lddfw-start-delivery-email.php, /woocommerce, /includes/class-lddfw-whatsapp.php, /includes/class-lddfw-sms.php, /includes/class-lddfw-route.php , includes/class-lddfw-application.php, /includes/class-lddfw-assigned-order-email-admin.php, /includes/class-lddfw-assigned-order-email-vendor.php, tracking.php
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
if ( !function_exists( 'lddfw_fs' ) ) {
    // Create a helper function for easy SDK access.
    function lddfw_fs() {
        global $lddfw_fs;
        if ( !isset( $lddfw_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $lddfw_fs = fs_dynamic_init( array(
                'id'              => '6995',
                'slug'            => 'local-delivery-drivers-for-woocommerce',
                'type'            => 'plugin',
                'public_key'      => 'pk_5ae065da4addc985fe67f63c46a51',
                'is_premium'      => true,
                'premium_suffix'  => 'Premium',
                'has_addons'      => true,
                'has_paid_plans'  => true,
                'trial'           => array(
                    'days'               => 14,
                    'is_require_payment' => true,
                ),
                'has_affiliation' => 'selected',
                'menu'            => array(
                    'slug'    => 'lddfw-dashboard',
                    'support' => false,
                ),
                'is_live'         => true,
            ) );
        }
        return $lddfw_fs;
    }

    // Init Freemius.
    lddfw_fs();
    // Signal that SDK was initiated.
    do_action( 'lddfw_fs_loaded' );
}
$lddfw_plugin_basename = plugin_basename( __FILE__ );
$lddfw_plugin_basename_array = explode( '/', $lddfw_plugin_basename );
$lddfw_plugin_folder = $lddfw_plugin_basename_array[0];
$lddfw_delivery_drivers_page = get_option( 'lddfw_delivery_drivers_page', '' );
if ( !function_exists( 'lddfw_activate' ) ) {
    /**
     * Currently plugin version.
     * Start at version 1.0.0 and use SemVer - https://semver.org
     */
    define( 'LDDFW_VERSION', '1.9.9' );
    /**
     * Define delivery driver page id.
     */
    define( 'LDDFW_PAGE_ID', $lddfw_delivery_drivers_page );
    /**
     * Define plugin folder name.
     */
    define( 'LDDFW_FOLDER', $lddfw_plugin_folder );
    /**
     * Define plugin dir.
     */
    define( 'LDDFW_DIR', __DIR__ );
    /**
     * Define supported plugins.
     */
    $lddfw_plugins = array();
    $lddfw_multivendor = '';
    if ( is_plugin_active( 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php' ) ) {
        // Brazil checkout fields.
        $lddfw_plugins[] = 'woocommerce-extra-checkout-fields-for-brazil';
    }
    if ( is_plugin_active( 'comunas-de-chile-para-woocommerce/woocoomerce-comunas.php' ) ) {
        // Chile states.
        $lddfw_plugins[] = 'comunas-de-chile-para-woocommerce';
    }
    if ( lddfw_fs()->is__premium_only() ) {
        if ( lddfw_fs()->can_use_premium_code() ) {
            if ( is_plugin_active( 'wc-frontend-manager/wc_frontend_manager.php' ) ) {
                // WCFM.
                $lddfw_plugins[] = 'wcfm';
                $lddfw_multivendor = 'wcfm';
            }
            if ( is_plugin_active( 'dc-woocommerce-multi-vendor/dc_product_vendor.php' ) ) {
                // WC Marketplace.
                $lddfw_plugins[] = 'wcmp';
                $lddfw_multivendor = 'wcmp';
            }
            if ( is_plugin_active( 'dokan-lite/dokan.php' ) ) {
                // Dokan.
                $lddfw_plugins[] = 'dokan';
                $lddfw_multivendor = 'dokan';
            }
        }
    }
    define( 'LDDFW_PLUGINS', $lddfw_plugins );
    /**
     * Define multivendor plugin.
     */
    define( 'LDDFW_MULTIVENDOR', ( in_array( $lddfw_multivendor, LDDFW_PLUGINS, true ) ? $lddfw_multivendor : '' ) );
    /**
     * The code that runs during plugin activation.
     * This action is documented in includes/class-lddfw-activator.php
     *
     * @param array $network_wide network wide.
     */
    function lddfw_activate(  $network_wide  ) {
        include_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
        include_once plugin_dir_path( __FILE__ ) . 'includes/class-lddfw-activator.php';
        $activator = new LDDFW_Activator();
        $activator->activate( $network_wide );
    }

    /**
     * Check for free version
     *
     * @since 1.1.2
     * @return boolean
     */
    function lddfw_is_free() {
        if ( lddfw_fs()->is__premium_only() && lddfw_fs()->can_use_premium_code() ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Premium feature notice content.
     *
     * @since 1.3.0
     * @param string $html html.
     * @return html
     */
    function lddfw_premium_feature_notice_content(  $html  ) {
        return '
		<div class="lddfw_premium-feature-content"><div class="lddfw_title premium_feature_title">
		 <h2>' . esc_html( __( 'Premium Feature', 'lddfw' ) ) . '</h2>
		 <p>' . esc_html( __( 'You Discovered a Premium Feature!', 'lddfw' ) ) . '</p>
		</div>
		 <p class="lddfw_content-subtitle">' . esc_html( __( 'With premium version you will be able to:', 'lddfw' ) ) . '</p>
		' . $html . '</div>';
    }

    /**
     * Premium feature notice.
     *
     * @since 1.1.2
     * @param string $button text.
     * @param string $html html.
     * @param string $class class.
     * @return html
     */
    function lddfw_premium_feature_notice(  $button, $html, $class  ) {
        return '<div class="lddfw_premium-feature ' . $class . '">
			<button class="btn btn-secondary btn-sm">' . lddfw_premium_feature( '' ) . ' ' . $button . '</button>
			<div class="lddfw_lightbox" style="display:none">
				<div class="lddfw_lightbox_wrap">
					<div class="container">
						<a href="#" class="lddfw_lightbox_close">×</a>' . lddfw_premium_feature_notice_content( $html ) . '
					</div>
				</div>
			</div>
		</div>';
    }

    /**
     * Premium feature.
     *
     * @since 1.1.2
     * @param string $value text.
     * @return html
     */
    function lddfw_premium_feature(  $value  ) {
        $result = $value;
        if ( lddfw_is_free() ) {
            $result = '<svg style="color:#ffc106" width=20 aria-hidden="true" focusable="false" data-prefix="fas" data-icon="star" class=" lddfw_premium_iconsvg-inline--fa fa-star fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"> <title>' . esc_attr__( 'Premium Feature', 'lddfw' ) . '</title><path fill="currentColor" d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"></path></svg>';
        }
        return $result;
    }

    /**
     * Currency symbol
     *
     * @since 1.6.3
     * @return html
     */
    function lddfw_currency_symbol() {
        $result = '';
        if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
            $result = get_woocommerce_currency_symbol();
        }
        return $result;
    }

    /**
     * Prices
     *
     * @param boolean $permission driver permission.
     * @param string  $price price.
     * @since 1.6.7
     * @return html
     */
    function lddfw_price(  $permission, $price  ) {
        if ( true === $permission ) {
            return $price;
        } else {
            return '';
        }
    }

    /**
     * The code that runs during plugin deactivation.
     * This action is documented in includes/class-lddfw-deactivator.php
     */
    function lddfw_deactivate() {
        include_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
        include_once plugin_dir_path( __FILE__ ) . 'includes/class-lddfw-deactivator.php';
        LDDFW_Deactivator::deactivate();
    }

    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since 1.0.0
     */
    function lddfw_run() {
        $plugin = new LDDFW();
        $plugin->run();
    }

    /**
     * Get delivery driver page url.
     *
     * @param string $params params.
     * @since 1.0.0
     */
    function lddfw_drivers_page_url(  $params  ) {
        $link = get_page_link( LDDFW_PAGE_ID );
        if ( '' !== $params ) {
            if ( strpos( $link, '?' ) !== false ) {
                $link = esc_url( $link ) . '&' . $params;
            } else {
                $link = esc_url( $link ) . '?' . $params;
            }
            $link .= '&rnd=' . wp_rand( 10000, 999999 );
        }
        return $link;
    }

    /**
     * Register_query_vars for delivery driver page.
     *
     * @since 1.0.0
     * @param array $vars query_vars array.
     * @return array
     */
    function lddfw_register_query_vars(  $vars  ) {
        $vars[] = 'lddfw_screen';
        $vars[] = 'lddfw_orderid';
        $vars[] = 'lddfw_page';
        $vars[] = 'lddfw_dates';
        $vars[] = 'lddfw_reset_login';
        $vars[] = 'lddfw_reset_key';
        $vars[] = 'k';
        return $vars;
    }

    /**
     * Function that format the date for the plugin.
     *
     * @since 1.0.0
     * @param string $type part of the date.
     * @return string
     */
    function lddfw_date_format(  $type  ) {
        $date_format = get_option( 'date_format', '' );
        $time_format = get_option( 'time_format', '' );
        if ( 'date' === $type ) {
            if ( 'F j, Y' !== $date_format && 'Y-m-d' !== $date_format && 'm/d/Y' !== $date_format && 'd/m/Y' !== $date_format ) {
                return 'F j, Y';
            } else {
                return $date_format;
            }
        }
        if ( 'time' === $type ) {
            if ( 'g:i a' !== $time_format && 'g:i A' !== $time_format && 'H:i' !== $time_format ) {
                return 'g:i a';
            } else {
                return $time_format;
            }
        }
    }

    /**
     * Format address.
     *
     * @since 1.0.0
     * @param string $format address format.
     * @param array  $array address array.
     * @return string
     */
    function lddfw_format_address(  $format, $array  ) {
        $address_1 = $array['street_1'];
        $address_2 = $array['street_2'];
        $city = $array['city'];
        $postcode = $array['zip'];
        $country = $array['country'];
        $state = $array['state'];
        if ( 'array' === $format ) {
            return $array;
        }
        if ( 'map_address' === $format ) {
            // Show state only for USA.
            if ( 'US' !== $array['country'] && 'United States (US)' !== $array['country'] ) {
                $state = '';
            }
            $address = $address_1 . ', ';
            $address .= $city;
            if ( !empty( $state ) || !empty( $postcode ) ) {
                $address .= ', ';
            }
            if ( !empty( $state ) ) {
                $address .= $state . ' ';
            }
            if ( !empty( $postcode ) ) {
                $address .= $postcode . ' ';
            }
            if ( !empty( $country ) ) {
                $address .= ' ' . $country;
            }
            $address = str_replace( '+', '%2B', $address );
            $address = str_replace( '  ', ' ', trim( $address ) );
            $address = str_replace( ' ', '+', $address );
            return $address;
        }
        if ( 'address_line' === $format ) {
            // Show state only for USA.
            if ( 'US' !== $array['country'] && 'United States (US)' !== $array['country'] ) {
                $state = '';
            }
            $address = $address_1 . ', ';
            if ( !empty( $address_2 ) ) {
                $address .= $address_2 . ', ';
            }
            $address .= $city;
            if ( !empty( $state ) || !empty( $postcode ) ) {
                $address .= ', ';
            }
            if ( !empty( $state ) ) {
                $address .= $state . ' ';
            }
            if ( !empty( $postcode ) ) {
                $address .= $postcode . ' ';
            }
            if ( !empty( $country ) ) {
                $address .= ' ' . $country;
            }
            $address = str_replace( '  ', ' ', trim( $address ) );
            return $address;
        }
        if ( 'address' === $format ) {
            // Format address.
            // Show state only for USA.
            if ( 'US' !== $array['country'] && 'United States (US)' !== $array['country'] ) {
                $state = '';
            }
            $address = '';
            if ( !empty( $array['first_name'] ) ) {
                $first_name = $array['first_name'];
                $last_name = $array['last_name'];
                $address = $first_name . ' ' . $last_name . '<br>';
            }
            if ( !empty( $array['company'] ) ) {
                $address .= $array['company'] . '<br>';
            }
            $address .= $address_1;
            if ( !empty( $address_2 ) ) {
                $address .= ', ' . $address_2 . ' ';
            }
            $address .= '<br>' . $city;
            if ( !empty( $state ) || !empty( $postcode ) ) {
                $address .= ', ';
            }
            if ( !empty( $state ) ) {
                $address .= $state . ' ';
            }
            if ( !empty( $postcode ) ) {
                $address .= $postcode . ' ';
            }
            if ( !empty( $country ) ) {
                $address .= '<br>' . $country;
            }
            return $address;
        }
    }

    /**
     * Function that clear_cache.
     *
     * @param string $type type.
     * @param int    $driver_id driver id.
     * @since 1.7.3
     * @return void
     */
    function lddfw_delete_cache(  $type, $driver_id  ) {
        // Delete driver cache.
        if ( 'driver' === $type ) {
            if ( '' !== $driver_id && '-1' !== $driver_id && false !== $driver_id ) {
                $transient_key = 'lddfw-driver-' . $driver_id . '-orders-count-' . date_i18n( 'Y-m-d' );
                delete_transient( $transient_key );
            }
        }
        // Delete orders cache.
        if ( 'orders' === $type ) {
            wp_cache_delete( 'lddfw_claim_orders', 'lddfw_cache_group' );
        }
    }

    /**
     * Function that uninstall the plugin.
     *
     * @since 1.0.0
     * @return void
     */
    function lddfw_fs_uninstall_cleanup() {
        if ( lddfw_fs()->is__premium_only() ) {
            if ( lddfw_fs()->can_use_premium_code() ) {
                // Delete all custom_fields.
                $allposts = get_posts( array(
                    'post_type'   => 'lddfw_custom_fields',
                    'numberposts' => -1,
                ) );
                foreach ( $allposts as $eachpost ) {
                    wp_delete_post( $eachpost->ID, true );
                }
                // Delete all custom_fields terms.
                $terms = get_terms( 'lddfw_custom_fields_sections', array(
                    'fields'     => 'ids',
                    'hide_empty' => false,
                ) );
                foreach ( $terms as $value ) {
                    wp_delete_term( $value, 'lddfw_custom_fields_sections' );
                }
                // Unregister custom_fields.
                unregister_taxonomy( 'lddfw_custom_fields_sections' );
                unregister_post_type( 'lddfw_custom_fields' );
            }
        }
    }

    if ( lddfw_fs()->is__premium_only() ) {
        if ( lddfw_fs()->can_use_premium_code() ) {
            /**
             * The code that runs during plugin activation.
             * Deactive free plugin version.
             */
            function lddfw_deactivate_lite_version__premium_only() {
                deactivate_plugins( 'local-delivery-drivers-for-woocommerce/local-delivery-drivers-for-woocommerce.php' );
            }

        }
    }
    /**
     * Admin notices function.
     *
     * @since 1.0.0
     */
    function lddfw_admin_notices() {
        if ( !class_exists( 'WooCommerce' ) ) {
            echo '<div class="notice notice-error is-dismissible">
				<p>' . esc_html( __( 'Local delivery drivers for WooCommerce is a WooCommerce add-on, you must activate a WooCommerce on your site.', 'lddfw' ) ) . '</p>
				</div>';
        }
    }

    /**
     * Initializes the plugin.
     * This function checks if WooCommerce is active before running the plugin.
     * If WooCommerce is not active, it displays an admin notice.
     */
    function initialize_lddfw_run() {
        // Check if WooCommerce is active.
        if ( !class_exists( 'WooCommerce' ) ) {
            // Adding action to admin_notices to display a notice if WooCommerce is not active.
            add_action( 'admin_notices', 'lddfw_admin_notices' );
            return;
            // Stop the initialization as WooCommerce is not active.
        }
        // WooCommerce is active, so initialize the plugin.
        lddfw_run();
    }

}
// Include the internationalization class to handle text domain loading.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lddfw-i18n.php';
/**
 * Initializes internationalization (i18n) support for the plugin.
 */
if ( !function_exists( 'lddfw_initialize_i18n' ) ) {
    function lddfw_initialize_i18n() {
        // Create an instance of the LDDFW_I18n class.
        $plugin_i18n = new LDDFW_I18n();
        // Hook the 'load_plugin_textdomain' method of the LDDFW_I18n class to the 'plugins_loaded' action.
        // This ensures that the plugin's text domain is loaded as soon as all plugins are loaded by WordPress,
        // making translations available.
        add_action( 'plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain') );
    }

}
// Call the function to initialize internationalization support.
lddfw_initialize_i18n();
// Include the main plugin class file.
require plugin_dir_path( __FILE__ ) . 'includes/class-lddfw.php';
// Register activation and deactivation hooks.
// These hooks are called when the plugin is activated or deactivated, respectively.
register_activation_hook( __FILE__, 'lddfw_activate' );
register_deactivation_hook( __FILE__, 'lddfw_deactivate' );
/**
 * Adds custom query vars to the list of recognized WordPress query variables.
 *
 * @param array $vars The array of existing query variables.
 * @return array The modified array including the plugin's custom query variables.
 */
add_filter( 'query_vars', 'lddfw_register_query_vars' );
// Hook into 'plugins_loaded' with a priority of 20 to initialize the plugin after all plugins have loaded.
// This is particularly useful for ensuring the plugin loads after WooCommerce, if WooCommerce is a dependency.
add_action( 'plugins_loaded', 'initialize_lddfw_run', 20 );
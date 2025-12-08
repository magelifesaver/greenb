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
 * @package PWDDM
 *
 * @wordpress-plugin
 * Plugin Name: Delivery Drivers Manager Premium
 * Plugin URI:        https://powerfulwp.com/delivery-drivers-manager/
 * Description:       Let your stuff manage delivery drivers<span>, assign drivers to orders, routes, reports, commission, and more!
 * Version:           1.2.4
 * Update URI: https://api.freemius.com
 * Author:            powerfulwp
 * Author URI:        http://www.powerfulwp.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pwddm
 * Domain Path:       /languages
 *
 * @fs_premium_only   /includes/class-pwddm-route.php
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
if ( !function_exists( 'pwddm_fs' ) ) {
    // Create a helper function for easy SDK access.
    function pwddm_fs() {
        global $pwddm_fs;
        if ( !isset( $pwddm_fs ) ) {
            // Include Freemius SDK.
            if ( file_exists( dirname( dirname( __FILE__ ) ) . '/local-delivery-drivers-for-woocommerce/freemius/start.php' ) ) {
                // Try to load SDK from parent plugin folder.
                require_once dirname( dirname( __FILE__ ) ) . '/local-delivery-drivers-for-woocommerce/freemius/start.php';
            } elseif ( file_exists( dirname( dirname( __FILE__ ) ) . '/local-delivery-drivers-for-woocommerce-premium/freemius/start.php' ) ) {
                // Try to load SDK from premium parent plugin folder.
                require_once dirname( dirname( __FILE__ ) ) . '/local-delivery-drivers-for-woocommerce-premium/freemius/start.php';
            } else {
                require_once dirname( __FILE__ ) . '/freemius/start.php';
            }
            $pwddm_fs = fs_dynamic_init( array(
                'id'               => '8281',
                'slug'             => 'delivery-drivers-manager',
                'type'             => 'plugin',
                'public_key'       => 'pk_a23f29749fd50960f01cdb867ba39',
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
                    'first-path' => 'admin.php?page=lddfw-settings&tab=pwddm',
                    'support'    => false,
                ),
                'is_live'          => true,
            ) );
        }
        return $pwddm_fs;
    }

}
$pwddm_plugin_basename = plugin_basename( __FILE__ );
$pwddm_plugin_basename_array = explode( '/', $pwddm_plugin_basename );
$pwddm_plugin_folder = $pwddm_plugin_basename_array[0];
$pwddm_manager_page = get_option( 'pwddm_manager_page', '' );
if ( !function_exists( 'pwddm_activate' ) ) {
    /**
     * Currently plugin version.
     * Start at version 1.0.0 and use SemVer - https://semver.org
     */
    define( 'PWDDM_VERSION', '1.2.4' );
    /**
     * Define delivery driver page id.
     */
    define( 'PWDDM_PAGE_ID', $pwddm_manager_page );
    /**
     * Define plugin folder name.
     */
    define( 'PWDDM_FOLDER', $pwddm_plugin_folder );
    /**
     * The code that runs during plugin activation.
     * This action is documented in includes/class-pwddm-activator.php
     */
    function pwddm_activate(  $network_wide  ) {
        include_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
        include_once plugin_dir_path( __FILE__ ) . 'includes/class-pwddm-activator.php';
        $activator = new PWDDM_Activator();
        $activator->activate( $network_wide );
    }

    /**
     * Get order custom fields.
     *
     * @since 1.1.2
     * @param int   $orderid order id.
     * @param array $posts custom fields array.
     * @return html
     */
    function pwddm_order_custom_fields__premium_only(  $orderid, $posts  ) {
        $html = '';
        $counter = 0;
        $order = wc_get_order( $orderid );
        foreach ( $posts as $post ) {
            $meta = get_post_meta( $post->ID, '', true );
            $meta = array_map( function ( $n ) {
                return $n[0];
            }, $meta );
            if ( 0 < $counter && '' !== $html ) {
                $html .= '<br>';
            }
            $field_value = '';
            foreach ( $meta as $key => $value ) {
                $value = preg_replace( '/\\s+/', ' ', $value );
                if ( '_' !== substr( $key, 0, 1 ) ) {
                    $post_meta = $order->get_meta( $key );
                    if ( '' !== $post_meta ) {
                        if ( '' !== $value ) {
                            $field_value .= $value . $post_meta . ' ';
                        } else {
                            $field_value .= $post_meta . ' ';
                        }
                    }
                }
            }
            if ( '' !== $field_value ) {
                $html .= $post->post_title . ': ' . $field_value;
            }
            $counter++;
        }
        return $html;
    }

    /**
     * The code that runs during plugin deactivation.
     * This action is documented in includes/class-pwddm-deactivator.php
     */
    function pwddm_deactivate() {
        include_once plugin_dir_path( __FILE__ ) . 'includes/class-pwddm-deactivator.php';
        PWDDM_Deactivator::deactivate();
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
    function pwddm_run() {
        $plugin = new PWDDM();
        $plugin->run();
    }

    /**
     * Check for free version
     *
     * @since 1.0.0
     * @return boolean
     */
    function pwddm_is_free() {
        if ( pwddm_fs()->is__premium_only() && pwddm_fs()->can_use_premium_code() ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Premium feature notice.
     *
     * @since 1.0.0
     * @param string $button text.
     * @param string $html html.
     * @param string $class class.
     * @return html
     */
    function pwddm_premium_feature_notice(  $button, $html, $class  ) {
        return '<div class="lddfw_premium-feature ' . $class . '">
			<button class="btn btn-secondary btn-sm">' . pwddm_premium_feature( '' ) . ' ' . $button . '</button>
			<div class="lddfw_lightbox" style="display:none">
				<div class="lddfw_lightbox_wrap">
					<div class="container">
						<a href="#" class="lddfw_lightbox_close">×</a>' . pwddm_premium_feature_notice_content( $html ) . '
					</div>
				</div>
			</div>
		</div>';
    }

    /**
     * Premium feature.
     *
     * @since 1.0.0
     * @param string $value text.
     * @return html
     */
    function pwddm_premium_feature(  $value  ) {
        $result = $value;
        if ( pwddm_is_free() ) {
            $result = '<svg style="color:#ffc106" width=20 aria-hidden="true" focusable="false" data-prefix="fas" data-icon="star" class=" pwddm_premium_icon svg-inline--fa fa-star fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"> <title>' . esc_attr( __( 'Premium Feature', 'pwddm' ) ) . '</title><path fill="currentColor" d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"></path></svg>';
        }
        return $result;
    }

    /**
     * Premium feature notice content.
     *
     * @since 1.0.0
     * @param string $html html.
     * @return html
     */
    function pwddm_premium_feature_notice_content(  $html  ) {
        return '
		<div class="lddfw_premium-feature-content"><div class="lddfw_title premium_feature_title">
		 <h2>' . esc_html( __( 'Premium Feature', 'pwddm' ) ) . '</h2>
		 <p>' . esc_html( __( 'You Discovered a Premium Feature!', 'pwddm' ) ) . '</p>
		</div>
		 <p class="lddfw_content-subtitle">' . esc_html( __( 'With premium version you will be able to:', 'pwddm' ) ) . '</p>
		' . $html . '</div>';
    }

    /**
     * Get delivery driver page url.
     *
     * @since 1.0.0
     */
    function pwddm_manager_page_url(  $params  ) {
        $link = get_page_link( PWDDM_PAGE_ID );
        if ( '' !== $params ) {
            if ( strpos( $link, '?' ) !== false ) {
                $link = esc_url( $link ) . '&' . $params;
            } else {
                $link = esc_url( $link ) . '?' . $params;
            }
            $link .= '&rnd=' . rand();
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
    function pwddm_register_query_vars(  $vars  ) {
        $vars[] = 'pwddm_screen';
        $vars[] = 'pwddm_orderid';
        $vars[] = 'pwddm_page';
        $vars[] = 'pwddm_dates';
        $vars[] = 'pwddm_reset_login';
        $vars[] = 'pwddm_reset_key';
        $vars[] = 'pwddm_driverid';
        $vars[] = 'pwddm_status';
        return $vars;
    }

    /**
     * Function that return manager role.
     *
     * @since 1.0.0
     * @return void
     */
    function pwddm_manager_role() {
        return 'Delivery_manager';
    }

    /**
     * Add image to media.
     *
     * @since 1.3.0
     * @return obj
     */
    function pwddm_add_image_to_media(  $image, $type  ) {
        $pos = strpos( $image, ';' );
        $mime = explode( ':', substr( $image, 0, $pos ) )[1];
        if ( 'image/png' === $mime ) {
            $image = str_replace( 'data:image/png;base64,', '', $image );
            $filename = $type . '.png';
        }
        if ( 'image/jpeg' === $mime ) {
            $image = str_replace( 'data:image/jpeg;base64,', '', $image );
            $filename = $type . '.jpg';
        }
        if ( 'image/gif' === $mime ) {
            $image = str_replace( 'data:image/gif;base64,', '', $image );
            $filename = $type . '.gif';
        }
        $upload_dir = wp_upload_dir();
        $upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;
        $image = str_replace( ' ', '+', $image );
        $decoded = base64_decode( $image );
        $hashed_filename = md5( $filename . microtime() ) . '_' . $filename;
        $file_path = $upload_path . $hashed_filename;
        $wp_filetype = wp_check_filetype( $filename, null );
        $image_upload = file_put_contents( $upload_path . $hashed_filename, $decoded );
        // Handle uploaded file
        if ( !function_exists( 'wp_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $file = array();
        $file['error'] = '';
        $file['tmp_name'] = $upload_path . $hashed_filename;
        $file['name'] = $hashed_filename;
        $file['type'] = 'image/png';
        $file['size'] = filesize( $upload_path . $hashed_filename );
        // upload file to server
        $file_return = wp_handle_sideload( $file, array(
            'test_form' => false,
        ) );
        $filename = $file_return['file'];
        $attachment = array(
            'post_mime_type' => $file_return['type'],
            'post_title'     => preg_replace( '/\\.[^.]+$/', '', basename( $filename ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'guid'           => $upload_dir['url'] . '/' . basename( $filename ),
        );
        foreach ( get_intermediate_image_sizes() as $s ) {
            $sizes[$s] = array(
                'width'  => '',
                'height' => '',
            );
            $sizes[$s]['width'] = get_option( "{$s}_size_w" );
            $sizes[$s]['height'] = get_option( "{$s}_size_h" );
        }
        $sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );
        foreach ( $sizes as $size => $size_data ) {
            $resized = image_make_intermediate_size( $filename, $size_data['width'], $size_data['height'] );
            if ( $resized ) {
                $metadata['sizes'][$size] = $resized;
            }
        }
        $attach_id = wp_insert_attachment( $attachment, $filename );
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        return $attach_id;
    }

    /**
     * Function that uninstall the plugin.
     *
     * @since 1.0.0
     * @return void
     */
    function pwddm_fs_uninstall_cleanup() {
    }

    /**
     * Premium feature.
     *
     * @param string $value text.
     * @return html
     */
    function pwddm_admin_premium_feature(  $value  ) {
        $result = $value;
        if ( pwddm_is_free() ) {
            $result = '<div class="pwddm_premium_feature">
							<a class="pwddm_star_button" href="#"><svg style="color:#ffc106" width=20 aria-hidden="true" focusable="false" data-prefix="fas" data-icon="star" class=" pwddm_premium_icon svg-inline--fa fa-star fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"> <title>' . esc_attr__( 'Premium Feature', 'pwddm' ) . '</title><path fill="currentColor" d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"></path></svg></a>
							  <div class="pwddm_premium_feature_note" style="display:none">
							  <a href="#" class="pwddm_premium_close">
							  <svg aria-hidden="true"  width=10 focusable="false" data-prefix="fas" data-icon="times" class="svg-inline--fa fa-times fa-w-11" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg></a>
							  <h2>' . esc_html( __( 'Premium Feature', 'pwddm' ) ) . '</h2>
							  <p>' . esc_html( __( 'You Discovered a Premium Feature!', 'pwddm' ) ) . '</p>
							  <p>' . esc_html( __( 'Upgrading to Premium will unlock it.', 'pwddm' ) ) . '</p>
							  <a target="_blank" href="https://powerfulwp.com/delivery-drivers-manager#pricing" class="pwddm_premium_buynow">' . esc_html( __( 'UNLOCK PREMIUM', 'pwddm' ) ) . '</a>
							  </div>
						  </div>';
        }
        return $result;
    }

    function pwddm_fs_is_parent_active_and_loaded() {
        // Check if the parent's init SDK method exists.
        return function_exists( 'lddfw_fs' );
    }

    function pwddm_fs_is_parent_active() {
        $active_plugins = get_option( 'active_plugins', array() );
        if ( is_multisite() ) {
            $network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
            $active_plugins = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
        }
        foreach ( $active_plugins as $basename ) {
            if ( 0 === strpos( $basename, 'local-delivery-drivers-for-woocommerce/' ) || 0 === strpos( $basename, 'local-delivery-drivers-for-woocommerce-premium/' ) ) {
                return true;
            }
        }
        return false;
    }

    function pwddm_fs_init() {
        if ( pwddm_fs_is_parent_active_and_loaded() ) {
            // Init Freemius.
            pwddm_fs();
            // Signal that the add-on's SDK was initiated.
            do_action( 'pwddm_fs_loaded' );
            pwddm_run();
        } else {
            // Parent is inactive, add your error handling here.
        }
    }

}
// Include the internationalization class to handle text domain loading.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-pwddm-i18n.php';
/**
 * Initializes internationalization (i18n) support for the plugin.
 */
if ( !function_exists( 'pwddm_initialize_i18n' ) ) {
    function pwddm_initialize_i18n() {
        // Create an instance of the PWDDM_I18n class.
        $plugin_i18n = new PWDDM_I18n();
        add_action( 'plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain') );
    }

}
// Call the function to initialize internationalization support.
pwddm_initialize_i18n();
register_activation_hook( __FILE__, 'pwddm_activate' );
register_deactivation_hook( __FILE__, 'pwddm_deactivate' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pwddm.php';
add_filter( 'query_vars', 'pwddm_register_query_vars' );
if ( !function_exists( 'pwddm_admin_notices' ) ) {
    /**
     * Admin notices function.
     *
     * @since 1.0.0
     */
    function pwddm_admin_notices() {
        if ( !class_exists( 'WooCommerce' ) ) {
            echo '<div class="notice notice-error is-dismissible">
				<p>' . esc_html( __( 'Delivery Drivers Manager is a WooCommerce add-on, you must activate a WooCommerce on your site.', 'pwddm' ) ) . '</p>
				</div>';
        }
    }

}
if ( !function_exists( 'initialize_pwddm_run' ) ) {
    /**
     * Inital function.
     *
     * @since 1.0.0
     */
    function initialize_pwddm_run() {
        if ( !class_exists( 'WooCommerce' ) ) {
            // Adding action to admin_notices to display a notice if WooCommerce is not active.
            add_action( 'admin_notices', 'pwddm_admin_notices' );
            return;
            // Stop the initialization as WooCommerce is not active.
        }
        if ( pwddm_fs_is_parent_active_and_loaded() ) {
            // If parent already included, init add-on.
            pwddm_fs_init();
        } elseif ( pwddm_fs_is_parent_active() ) {
            // Init add-on only after the parent is loaded.
            add_action( 'lddfw_fs_loaded', 'pwddm_fs_init' );
        } else {
            // Even though the parent is not activated, execute add-on for activation / uninstall hooks.
            pwddm_fs_init();
        }
    }

}
add_action( 'plugins_loaded', 'initialize_pwddm_run', 21 );
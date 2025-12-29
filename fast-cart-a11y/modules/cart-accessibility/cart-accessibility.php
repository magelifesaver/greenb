<?php
/**
 * Accessibility enhancements for Fast Cart.
 *
 * This module enqueues a lightweight script and stylesheet on the
 * front end whenever the Fast Cart plugin is active. The script
 * observes the cart drawer and toggles the inert state of the rest
 * of the page, traps focus within the drawer and restores focus when
 * closed. The stylesheet ensures transitions respect the user’s
 * motion preferences.
 *
 * @package FastCartAccessibility\CartAccessibility
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class FCA11Y_Cart_Accessibility
 */
class FCA11Y_Cart_Accessibility {

    /**
     * Initialise hooks.
     */
    public static function init() {
        // Only load on the front end.
        if ( is_admin() ) {
            return;
        }
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    /**
     * Enqueue scripts and styles.
     *
     * Checks that the Fast Cart plugin main class exists before
     * enqueueing. This prevents unnecessary scripts from loading when
     * Fast Cart is inactive.
     */
    public static function enqueue_assets() {
        // Ensure Fast Cart is available before enqueuing assets.
        if ( ! class_exists( 'Fast_Cart' ) ) {
            return;
        }

        $plugin_url = plugin_dir_url( FCA11Y_PLUGIN_FILE );

        // Enqueue JavaScript.
        $script_handle = 'fca11y-cart-accessibility';
        $script_path   = $plugin_url . 'modules/cart-accessibility/assets/js/cart-accessibility.js';
        wp_enqueue_script( $script_handle, $script_path, [], '1.0.0', true );

        // Enqueue CSS.
        $style_handle = 'fca11y-cart-accessibility';
        $style_path   = $plugin_url . 'modules/cart-accessibility/assets/css/cart-accessibility.css';
        wp_enqueue_style( $style_handle, $style_path, [], '1.0.0' );
    }
}

// Initialise the module.
FCA11Y_Cart_Accessibility::init();
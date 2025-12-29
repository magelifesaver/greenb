<?php
/**
 * Plugin Name: Fast Cart Accessibility Addon
 * Description: Enhances the Fast Cart for WooCommerce plugin with improved accessibility features such as focus management and inert toggling.
 * Version: 1.0.0
 * Author: AI Generated
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * This plugin hooks into the Fast Cart side cart drawer and adds
 * accessibility helpers without modifying the original plugin. When
 * the cart tray opens the rest of the page becomes inert (non‑interactive)
 * and focus is trapped within the drawer. Closing the drawer restores
 * interaction to the rest of the document and returns focus to the
 * previously active element. Animations respect the user’s motion
 * preferences via CSS. The code is intentionally kept short and
 * modular to align with the wide‑and‑thin architecture guidelines.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'FCA11Y_PLUGIN_FILE' ) ) {
    define( 'FCA11Y_PLUGIN_FILE', __FILE__ );
}

// Bootstrapping loader file.
require_once plugin_dir_path( __FILE__ ) . 'includes/loader.php';
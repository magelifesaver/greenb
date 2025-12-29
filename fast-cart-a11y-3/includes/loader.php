<?php
/**
 * Fast Cart Accessibility Addon loader.
 *
 * Responsible for initialising individual modules. Keeping this
 * file lean makes it easy to scan and maintain. Each module is
 * responsible for registering its own hooks. New modules can be
 * added by requiring them here.
 *
 * @package FastCartAccessibility
 */

defined( 'ABSPATH' ) || exit;

// Load the cart accessibility module if Fast Cart is active.
// The check is deferred to the module itself to avoid fatal errors
// when Fast Cart is missing.
require_once plugin_dir_path( __FILE__ ) . '../modules/cart-accessibility/cart-accessibility.php';
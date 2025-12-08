<?php
/**
 * File: adbsa-loader.php
 * Purpose: Load all core classes for AAA Delivery Blocks Scheduler Advanced (adbsa).
 * Version: 1.5.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Read options early to decide which branch to load
/**
 * ================================
 * Core Delivery Logic
 * ================================
 */

require_once plugin_dir_path( __FILE__ ) . 'ajax/class-adbsa-ajax-get-slots.php';

// Same-Day slot builder (dynamic slot generation).
// Version: 1.3.1
require_once plugin_dir_path( __FILE__ ) . 'inc/class-adbsa-delivery-sameday.php';

// Universal field renderer (decides which slot logic to use at checkout).
// Version: 1.4.0
require_once plugin_dir_path( __FILE__ ) . 'inc/class-adbsa-delivery-fields.php';

// Frontend + email hooks (Thank You page, My Account, Emails).
// Version: 1.0.0
require_once plugin_dir_path( __FILE__ ) . 'inc/class-adbsa-delivery-hooks.php';

// Helper: unified delivery summary renderer.
// Version: 1.2.0
require_once plugin_dir_path( __FILE__ ) . 'helpers/class-adbsa-summary-helper.php';
require_once plugin_dir_path( __FILE__ ) . 'helpers/class-adbsa-delivery-field-renderer.php';
require_once plugin_dir_path( __FILE__ ) . 'helpers/class-adbsa-delivery-normalizer.php';
/**
 * ================================
 * Admin UI
 * ================================
 */
require_once plugin_dir_path( __FILE__ ) . 'admin/class-adbsa-settings-page.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-adbsa-delivery-metabox.php';

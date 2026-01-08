<?php
/**
 * Plugin Name: AAA OC AIP Indexer Bridge
 * Description: Bridges the AAA Order Workflow and AIP AI Integration Assistant plugins.  This loader exposes WooCommerce orders to the AIP indexer, synchronises order metadata for indexing, limits indexing to recent and relevant orders, caches AIP update checks, and provides a debug interface.  Designed for backend use only.
 * Version: 2.1.0
 * Author: AI Assistant
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer.php
 * Purpose: Entry point for the AIP/Order Workflow bridge.  This file
 * includes the core bridge class, order meta synchronisation module,
 * and debug module, then initialises the bridge.  Keeping the loader
 * lean supports the wide‑and‑thin architecture.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_AIP_INDEXER_BRIDGE_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_BRIDGE_LOADED', true );

// Local debug toggle.  Set to false to silence log statements in this file.
if ( ! defined( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE' ) ) {
    define( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE', true );
}

// Include the core bridge functionality.
require_once dirname( __FILE__ ) . '/aaa-oc-aip-indexer-core.php';

// Include the order meta synchronisation module.
require_once dirname( __FILE__ ) . '/aaa-oc-aip-indexer-order-meta.php';

// Include the order items summary module.  This lists products and
// quantities for each order as a plain‑text meta field.
require_once dirname( __FILE__ ) . '/aaa-oc-aip-indexer-order-items.php';

// Include the customer summary module.  Generates a plain‑text
// summary of the customer’s purchase history and stores it on both
// the user and the order.
require_once dirname( __FILE__ ) . '/aaa-oc-aip-indexer-customer-summary.php';

// Include the payment confirmation summary module.  This runs in both
// admin and front contexts because payment confirmations may be
// processed via cron or front‑end actions.  The class itself checks
// post types before acting.
require_once dirname( __FILE__ ) . '/aaa-oc-aip-indexer-payconfirm-summary.php';

// Include the debug module only in the admin area.
if ( is_admin() ) {
    require_once dirname( __FILE__ ) . '/aaa-oc-aip-indexer-debug.php';
}

// Initialise the bridge.  Hooks are registered in the core class.
AAA_OC_AIP_Indexer_Bridge::init();

// Add plugin action link (debug) to this plugin's entry on the plugins page.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ 'AAA_OC_AIP_Indexer_Bridge', 'plugin_action_links' ] );
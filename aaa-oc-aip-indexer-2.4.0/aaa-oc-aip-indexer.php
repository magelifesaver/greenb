<?php
/**
 * Plugin Name: AAA OC AIP Indexer Bridge
 * Description: Bridges AAA Order Workflow and AIP. Exposes orders to AIP and writes indexable summaries (completed orders only).
 * Version: 2.4.0
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-2.1.0/aaa-oc-aip-indexer.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_AIP_INDEXER_BRIDGE_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_BRIDGE_LOADED', true );

/**
 * Global logging toggle (STOP BLEEDING DEFAULT = OFF).
 * Set to true in wp-config.php if you explicitly want logs:
 * define('AAA_OC_AIP_INDEXER_LOGGING_ENABLED', true);
 */
if ( ! defined( 'AAA_OC_AIP_INDEXER_LOGGING_ENABLED' ) ) {
    define( 'AAA_OC_AIP_INDEXER_LOGGING_ENABLED', false );
}

require_once __DIR__ . '/aaa-oc-aip-indexer-core.php';
require_once __DIR__ . '/aaa-oc-aip-indexer-order-meta.php';
require_once __DIR__ . '/aaa-oc-aip-indexer-order-items.php';
require_once __DIR__ . '/aaa-oc-aip-indexer-customer-summary.php';
require_once __DIR__ . '/aaa-oc-aip-indexer-payconfirm-summary.php';

if ( is_admin() ) {
    require_once __DIR__ . '/aaa-oc-aip-indexer-debug.php';
}

AAA_OC_AIP_Indexer_Bridge::init();

add_filter(
    'plugin_action_links_' . plugin_basename( __FILE__ ),
    [ 'AAA_OC_AIP_Indexer_Bridge', 'plugin_action_links' ]
);

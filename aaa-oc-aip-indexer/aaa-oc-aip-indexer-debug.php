<?php
/**
 * Debug module for the AAA OC AIP Indexer Bridge.
 *
 * Provides an admin page to inspect the query used to index WooCommerce orders
 * for the AIP integration.  Displays the query arguments, the number of
 * orders found, and a sample of order IDs.  Logs details when debug is
 * enabled.  This module is only loaded in the admin area by the main
 * plugin loader.
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-debug.php
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_AIP_INDEXER_DEBUG_MODULE_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_DEBUG_MODULE_LOADED', true );

// Local debug toggle for this file.
if ( ! defined( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE' ) ) {
    define( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE', true );
}

/**
 * Debug class for displaying order query information.
 */
class AAA_OC_AIP_Indexer_Debug {

    /**
     * Bootstraps the admin menu for the debug page.
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
    }

    /**
     * Registers the submenu under the AIP plugin menu.
     */
    public static function add_menu() {
        // The slug 'wp-ai-content-generator' is used by the AIP plugin for its top‑level page.
        add_submenu_page(
            'wp-ai-content-generator',
            __( 'AIP Order Debug', 'aaa-oc-aip-indexer' ),
            __( 'AIP Order Debug', 'aaa-oc-aip-indexer' ),
            'manage_options',
            'aaa-oc-aip-order-debug',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Renders the debug page.
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'AIP Order Query Debug', 'aaa-oc-aip-indexer' ) . '</h1>';
        // Build the same query arguments used by the main plugin for orders.
        $args = [
            'post_type'   => 'shop_order',
            // Include completed orders as well; this list should match the bridge.
            'post_status' => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-failed', 'wc-completed' ],
            'date_query'  => [ [ 'after' => gmdate( 'Y-m-d', strtotime( '-90 days' ) ), 'inclusive' => true ] ],
            'fields'      => 'ids',
        ];
        $query = new WP_Query( $args );
        echo '<p><strong>' . esc_html__( 'Query Arguments', 'aaa-oc-aip-indexer' ) . ':</strong></p>';
        echo '<pre>' . esc_html( print_r( $args, true ) ) . '</pre>';
        echo '<p><strong>' . esc_html__( 'Orders Found', 'aaa-oc-aip-indexer' ) . ':</strong> ' . esc_html( $query->found_posts ) . '</p>';
        if ( $query->posts ) {
            $sample = array_slice( $query->posts, 0, 10 );
            $sample_ids = array_map( 'intval', $sample );
            echo '<p><strong>' . esc_html__( 'Sample Order IDs', 'aaa-oc-aip-indexer' ) . ':</strong> ' . esc_html( implode( ', ', $sample_ids ) ) . '</p>';
        }
        echo '</div>';
        // Log details if debug is enabled.
        if ( AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE ) {
            error_log( '[AIP Debug] Query args: ' . json_encode( $args ) . '; found: ' . $query->found_posts );
        }
    }
}

// Kick off the debug class.
AAA_OC_AIP_Indexer_Debug::init();
<?php
/**
 * Plugin Name: AAA Workflow AI Reports
 * Description: Admin plugin integrating Lokey Delivery reporting endpoints with OpenAI for smart WooCommerce insights.
 * Version: 1.3.2
 * Author: AAA Workflow DevOps
 *
 * This enhanced version expands the reporting capabilities by pulling in
 * additional sales data (top products) and exposing more configuration
 * options to administrators. The plugin now allows custom prompts,
 * adjustable temperature and token limits for the OpenAI client, and
 * displays both the AI generated summary and the raw data returned from
 * LokeyReports.  See the settings screens for details.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Define constants early so sub‑files can reference them.
define( 'AAA_WF_AI_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_WF_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'AAA_WF_AI_VERSION', '1.3.2' );

// Load the main loader and the assets loader.
require_once AAA_WF_AI_DIR . 'aaa-workflow-ai-reports-loader.php';
require_once AAA_WF_AI_DIR . 'aaa-workflow-ai-reports-assets-loader.php';

/**
 * --------------------------------------------------------------------------
 * 🧭 Settings link
 * --------------------------------------------------------------------------
 * Add a direct link to the settings page from the Plugins list table.
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
    $url = admin_url( 'admin.php?page=aaa-workflow-ai-reports&tab=openai' );
    array_unshift( $links, '<a href="' . esc_url( $url ) . '">Settings</a>' );
    return $links;
});
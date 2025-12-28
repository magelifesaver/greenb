<?php
/**
 * Plugin Name: AAA Workflow AI Reports
 * Description: Admin plugin integrating Lokey Delivery reporting endpoints with OpenAI for smart WooCommerce insights.
 * Version: 1.0.0
 * Author: AAA Workflow DevOps
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AAA_WF_AI_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_WF_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'AAA_WF_AI_VERSION', '1.0.0' );

// --- Load the main loader ---
require_once AAA_WF_AI_DIR . 'aaa-workflow-ai-reports-loader.php';
require_once AAA_WF_AI_DIR . 'aaa-workflow-ai-reports-assets-loader.php';
/**
 * --------------------------------------------------------------------------
 * 🧭 Settings link
 * --------------------------------------------------------------------------
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links){
	$url = admin_url('admin.php?page=aaa-workflow-ai-reports&tab=openai');
	array_unshift($links, '<a href="' . esc_url($url) . '">Settings</a>');
	return $links;
});

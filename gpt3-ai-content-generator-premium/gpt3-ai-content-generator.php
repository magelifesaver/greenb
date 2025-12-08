<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/gpt3-ai-content-generator.php
/**
 * The plugin bootstrap file
 *
 * @link              https://aipower.org
 * @since             1.0.0
 * @package           Wp_Ai_Content_Generator
 *
 * @wordpress-plugin
 * Plugin Name: AIP: Complete AI Toolkit for WordPress (formerly AI Power) Pro
 * Description:       AI, Chatbot, ChatGPT, Content Writer, Auto Content Writer, Product Writer, Image Generator, AutoGPT, ChatPDF, AI Training, Embeddings and more.
 * Version:           2.3.43
 * Update URI: https://api.freemius.com
 * Author:            Senol Sahin
 * Author URI:        https://aipower.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gpt3-ai-content-generator
 * Domain Path:       /languages
 * @fs_premium_only   /lib/
 */
if ( !defined( 'WPINC' ) ) {
    die;
}
define( 'WPAICG_VERSION', '2.3.43' );
define( 'WPAICG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPAICG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAICG_LIB_DIR', WPAICG_PLUGIN_DIR . 'lib/' );
// Freemius SDK Integration
if ( function_exists( 'wpaicg_gacg_fs' ) ) {
    wpaicg_gacg_fs()->set_basename( true, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF — it ensures `function_exists()` is reliable
    if ( !function_exists( 'wpaicg_gacg_fs' ) ) {
        // Create a helper function for easy SDK access.
        function wpaicg_gacg_fs() {
            global $wpaicg_gacg_fs;
            if ( !isset( $wpaicg_gacg_fs ) ) {
                // Activate multisite network integration.
                if ( !defined( 'WP_FS__PRODUCT_11606_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_11606_MULTISITE', true );
                }
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                $wpaicg_gacg_fs = fs_dynamic_init( array(
                    'id'             => '11606',
                    'slug'           => 'gpt3-ai-content-generator',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_374fe2f12f24f09286bc6f89cd0c6',
                    'is_premium'     => true,
                    'premium_suffix' => 'Pro',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                        'slug'       => 'wpaicg',
                        'first-path' => 'admin.php?page=wpaicg',
                        'support'    => false,
                    ),
                    'is_live'        => true,
                ) );
            }
            return $wpaicg_gacg_fs;
        }

        // Init Freemius.
        wpaicg_gacg_fs();
        // Signal that SDK was initiated.
        do_action( 'wpaicg_gacg_fs_loaded' );
    }
}
// --- Load Core Dashboard Class (needed by Pro loader) ---
$aipkit_dashboard_class_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php';
if ( file_exists( $aipkit_dashboard_class_path ) ) {
    require_once $aipkit_dashboard_class_path;
}
// --- End Load Core Dashboard Class ---
// --- Load Pro Features ---
// We always load the Pro library loader. The logic within that file handles the Freemius checks.
$pro_loader_path = WPAICG_LIB_DIR . 'wpaicg__premium_only.php';
if ( file_exists( $pro_loader_path ) ) {
    require_once $pro_loader_path;
}
// --- Core Plugin Includes ---
require_once WPAICG_PLUGIN_DIR . 'includes/class-wp-ai-content-generator.php';
require_once WPAICG_PLUGIN_DIR . 'includes/class-wp-ai-content-generator-activator.php';
require_once WPAICG_PLUGIN_DIR . 'includes/class-wp-ai-content-generator-deactivator.php';
// --- Activation / Deactivation Hooks ---
register_activation_hook( __FILE__, ['WPAICG\\WP_AI_Content_Generator_Activator', 'activate'] );
register_deactivation_hook( __FILE__, ['WPAICG\\WP_AI_Content_Generator_Deactivator', 'deactivate'] );
// --- Multisite Setup ---
if ( function_exists( 'wp_initialize_site' ) ) {
    add_action(
        'wp_initialize_site',
        ['WPAICG\\WP_AI_Content_Generator_Activator', 'setup_new_blog'],
        10,
        2
    );
} else {
    add_action(
        'wpmu_new_blog',
        ['WPAICG\\WP_AI_Content_Generator_Activator', 'setup_new_blog'],
        10,
        2
    );
}
// --- Run Plugin ---
\WPAICG\WP_AI_Content_Generator::get_instance()->run();
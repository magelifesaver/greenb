<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/dependency-loaders/class-woocommerce-writer-loader.php
// Status: MODIFIED

namespace WPAICG\Includes\DependencyLoaders;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Woocommerce_Writer_Loader
{
    /**
     * Registers an action to initialize integrations after all plugins are loaded.
     */
    public static function load()
    {
        add_action('plugins_loaded', [__CLASS__, 'init_integrations']);
    }

    /**
     * Initializes WooCommerce-dependent features after ensuring WooCommerce is active.
     */
    public static function init_integrations()
    {
        // Only load any of this if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        $woo_base_path = WPAICG_PLUGIN_DIR . 'classes/woocommerce/';

        // Load Product Writer
        $woo_writer_path = $woo_base_path . 'class-aipkit-woocommerce-writer.php';
        if (file_exists($woo_writer_path)) {
            require_once $woo_writer_path;
        }

        // Load new Token Package Integration files
        $woo_integration_path = $woo_base_path . 'class-aipkit-woocommerce-integration.php';
        if (file_exists($woo_integration_path)) {
            require_once $woo_integration_path;

            // Get the singleton instance to register hooks
            \WPAICG\WooCommerce\AIPKit_WooCommerce_Integration::get_instance();
        }
    }
}
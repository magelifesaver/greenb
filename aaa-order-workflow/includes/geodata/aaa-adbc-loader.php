<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/delivery/adbc-loader.php
 * Purpose: General loader for AAA Delivery Blocks Coords.
 * Handles includes for all non-asset functionality.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ADBC_Loader {

    public static function init() {
        // Core includes
        require_once ADBC_PLUGIN_DIR . 'inc/class-adbc-logger.php';
        require_once ADBC_PLUGIN_DIR . 'inc/class-adbc-fields.php';
        require_once ADBC_PLUGIN_DIR . 'inc/class-adbc-admin.php';
	require_once ADBC_PLUGIN_DIR . 'inc/class-adbc-account-save.php';
        require_once ADBC_PLUGIN_DIR . 'inc/class-adbc-ajax.php';
        require_once ADBC_PLUGIN_DIR . 'helpers/class-adbc-text-overrides.php';

        // Initialize
        ADBC_Account_Save::init();
	ADBC_Fields::register();
        new ADBC_Admin();
        ADBC_Ajax::init();

        // Blocks integration
        $has_interface = interface_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' );
        $has_registry  = class_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' );
        if ( $has_interface && $has_registry ) {
            require_once ADBC_PLUGIN_DIR . 'inc/class-adbc-integration.php';
            add_action( 'woocommerce_blocks_checkout_block_registration', function( $registry ) {
                $registry->register( new ADBC_Integration() );
            } );
        }

        ADBC_Logger::log( 'Loader ready (integration ' . ( $has_interface && $has_registry ? 'enabled' : 'fallback' ) . ').' );
    }
}

ADBC_Loader::init();

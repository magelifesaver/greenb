<?php
/**
 * File Path: assets/aaa-cfc-assets-loader.php
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Only target our main plugin pages
    if ( $hook !== 'toplevel_page_ddd-cfc-settings' 
      && $hook !== 'combine-file-tree-v2-5_page_cfc-live-search-index' ) {
        return;
    }
        wp_enqueue_style(
            'ddd-cfc-styles',
        plugin_dir_url( __FILE__ ) . '/css/cfc-styles.css',
            [],
            '2.0'
        );
        wp_enqueue_script(
            'ddd-cfc-script',
        plugin_dir_url( __FILE__ ) . '/js/cfc-script.js',
            [ 'jquery' ],
            '2.0',
            true
        );
        wp_localize_script(
            'ddd-cfc-script',
            'cfcAjax',
            [
                'ajaxurl'       => admin_url( 'admin-ajax.php' ),
                'selectedItems' => [],            // prevent “No selectedItems” error
            ]
        );

        // Highlight.js (UMD/browser build) + live-search preview enhancements
        wp_enqueue_script(
            'highlightjs',
            'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
            [],
            '11.9.0',
            true
        );
        wp_enqueue_script(
            'highlightjs-line-numbers',
            'https://cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.9.0/highlightjs-line-numbers.min.js',
            [ 'highlightjs' ],
            '2.9.0',
            true
        );
        wp_enqueue_style(
            'cfc-live-search-css',
        plugin_dir_url( __FILE__ ) . '/css/cfc-live-search.css',
            [],
            '3.0'
        );
        wp_enqueue_script(
            'cfc-live-search-js',
        plugin_dir_url( __FILE__ ) . '/js/cfc-live-search.js',
            [ 'jquery', 'highlightjs-line-numbers' ],
            '3.0',
            true
        );
        wp_localize_script(
            'cfc-live-search-js',
            'CFC_LS_Settings',
            [
                'api_base'  => esc_url_raw( get_rest_url( null, 'ls/v1/' ) ),
            'api_nonce' => wp_create_nonce( 'wp_rest' )
            ]
        );

    // Index-management scripts (only on Live Search Index submenu)
    if ( $hook === 'combine-file-tree-v2-5_page_cfc-live-search-index' ) {
        wp_enqueue_script(
            'cfc-index-manage-js',
            plugin_dir_url( __FILE__ ) . '/js/cfc-index-manage.js',
            [ 'jquery' ],
            '3.0',
            true
        );
        wp_localize_script(
            'cfc-index-manage-js',
            'CFC_LS_Settings',
            [
                'api_base'  => esc_url_raw( get_rest_url( null, 'ls/v1/' ) ),
                'api_nonce' => wp_create_nonce( 'wp_rest' ),
            ]
        );
    }
} );

<?php
/**
 * File Path: assets/class-aaa-cfc-assets-loader.php
 * Purpose: Enqueue CSS/JS for Combine File Contents admin pages (V3).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Detect which plugin page we’re on.
    $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

    $is_main  = ( 'ddd-cfc-settings' === $page );        // Combine File Contents
    $is_index = ( 'cfc-live-search-index' === $page );   // Live Search Index

    if ( ! $is_main && ! $is_index ) {
        return;
    }

    // Base URL points to /assets/
    $base_url = plugin_dir_url( __FILE__ );

    // === Shared styles (tree + layout) ===
    wp_enqueue_style(
        'ddd-cfc-styles',
        $base_url . 'css/cfc-styles.css',
        array(),
        '3.0'
    );

    // Live search / preview styles (used on both pages, harmless on index).
    wp_enqueue_style(
        'cfc-live-search-css',
        $base_url . 'css/cfc-live-search.css',
        array( 'ddd-cfc-styles' ),
        '3.0'
    );

    // === Tree UI script (both pages use the tree markup) ===
    wp_enqueue_script(
        'ddd-cfc-script',
        $base_url . 'js/cfc-script.js',
        array( 'jquery' ),
        '3.0',
        true
    );

    wp_localize_script(
        'ddd-cfc-script',
        'cfcAjax',
        array(
            'ajaxurl'       => admin_url( 'admin-ajax.php' ),
            'selectedItems' => array(),
        )
    );

    // === Combine page: highlight.js + live preview/search ===
    if ( $is_main ) {
        // Highlight.js core + line numbers
        wp_enqueue_script(
            'highlightjs',
            'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
            array(),
            '11.9.0',
            true
        );

        wp_enqueue_script(
            'highlightjs-line-numbers',
            'https://cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.9.0/highlightjs-line-numbers.min.js',
            array( 'highlightjs' ),
            '2.9.0',
            true
        );

        // Live tree search + enhanced preview (line numbers, match count, current file)
        wp_enqueue_script(
            'cfc-live-search-js',
            $base_url . 'js/cfc-live-search.js',
            array( 'jquery', 'highlightjs-line-numbers' ),
            '3.1',
            true
        );

        wp_localize_script(
            'cfc-live-search-js',
            'CFC_LS_Settings',
            array(
                'api_base'  => esc_url_raw( get_rest_url( null, 'ls/v1/' ) ),
                'api_nonce' => wp_create_nonce( 'wp_rest' ),
            )
        );
    }

    // === Index page: index management buttons ===
    if ( $is_index ) {
        wp_enqueue_script(
            'cfc-index-manage-js',
            $base_url . 'js/cfc-index-manage.js',
            array( 'jquery' ),
            '3.0',
            true
        );

        wp_localize_script(
            'cfc-index-manage-js',
            'CFC_LS_Settings',
            array(
                'api_base'  => esc_url_raw( get_rest_url( null, 'ls/v1/' ) ),
                'api_nonce' => wp_create_nonce( 'wp_rest' ),
            )
        );
    }
} );

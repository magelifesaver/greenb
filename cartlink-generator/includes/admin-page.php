<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'gvnclg_cartlink_generator_menu' );

function gvnclg_cartlink_generator_menu() {
    if ( defined( 'GUAVEN_CARTLINK_GENERATOR_SHOW_IN_SUBMENU' ) && GUAVEN_CARTLINK_GENERATOR_SHOW_IN_SUBMENU ) {
        add_submenu_page(
            'woocommerce', 
            __( 'CartLink Generator', 'cartlink-generator' ),
            __( 'CartLink Generator', 'cartlink-generator' ),
            'manage_woocommerce',
            'cartlink-generator',
            'gvnclg_cartlink_generator_page'
        );
    } else {
        add_menu_page(
            __( 'CartLink Generator', 'cartlink-generator' ),
            __( 'CartLink Generator', 'cartlink-generator' ),
            'manage_woocommerce',
            'cartlink-generator',
            'gvnclg_cartlink_generator_page',
            'dashicons-cart',
            60
        );
    }
}

function gvnclg_cartlink_generator_page() {
    $products = get_transient( 'cartlink_products_v2' );
    if ( ! $products ) {
        $products = gvnclg_cartlink_generator_fetch_products();
        set_transient( 'cartlink_products_v2', $products, 3600 );
    }

    include GUAVEN_CARTLINK_GENERATOR_PATH . 'templates/admin-page.php';
}

function gvnclg_cartlink_generator_fetch_products() {
    $products = [];
    $args = [
        'post_type'      => [ 'product', 'product_variation' ],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];
    $product_ids = get_posts( $args );

    foreach ( $product_ids as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $products[] = [
                'id'    => $product_id,
                'name'  => $product->get_name() . ' - ' . $product->get_sku(),
                'price' => $product->get_price(),
            ];
        }
    }

    return $products;
}


add_action( 'admin_enqueue_scripts', 'gvnclg_enqueue_admin_assets' );

function gvnclg_enqueue_admin_assets( $hook_suffix ) {
    // Check if we are on the correct admin page
    if ( 'toplevel_page_cartlink-generator' === $hook_suffix || 'woocommerce_page_cartlink-generator' === $hook_suffix ) {
        // Enqueue the admin CSS file
        wp_enqueue_style(
            'cartlink-generator-admin-css',
            plugins_url( 'assets/admin-styles.css', __DIR__ ),
            [],
            GUAVEN_CARTLINK_GENERATOR_VERSION // Version
        );

        // Enqueue the admin JS file
        wp_enqueue_script(
            'cartlink-generator-admin-js',
            plugins_url( 'assets/admin-scripts.js', __DIR__ ),
            [ ], // Dependencies
            GUAVEN_CARTLINK_GENERATOR_VERSION, // Version
            true // Load in footer
        );

        wp_localize_script(
            'cartlink-generator-admin-js',
            'clg_vars',
            [
                'ajaxurl'    => esc_url( admin_url( 'admin-ajax.php' ) ),
                'data_nonce' => esc_html( wp_create_nonce( 'gvnclg_fetch_nonce' ) ),
            ]
        );
    }
}

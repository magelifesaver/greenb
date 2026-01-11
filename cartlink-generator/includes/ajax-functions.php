<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function gvnclg_sanitize_textfields_array($data){
    if ( is_array( $data ) ) {
        return array_map( 'gvnclg_sanitize_textfields_array', $data );
    }
    return sanitize_text_field( $data );
}

function gvnclg_generate_cartlink() {
    // Verify nonce for security
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'gvnclg_fetch_nonce' ) ) {
        wp_send_json_error( __( 'Invalid request. Please refresh the page and try again.', 'cartlink-generator' ) );
    }

    // Check if products data is set
    if ( ! isset( $_POST['products'] ) || empty( $_POST['products'] ) ) {
        wp_send_json_error( __( 'No products provided.', 'cartlink-generator' ) );
    }

    // Unslash and sanitize input
    $raw_products = gvnclg_sanitize_textfields_array(wp_unslash( $_POST['products'] ));
    $products = [];

    foreach ( $raw_products as $raw_product ) {
        $products[] = [
            'id'       => isset( $raw_product['id'] ) ? intval( $raw_product['id'] ) : 0,
            'quantity' => isset( $raw_product['quantity'] ) ? intval( $raw_product['quantity'] ) : 1,
            'price'    => isset( $raw_product['price'] ) ? floatval( $raw_product['price'] ) : 0.0,
        ];
    }

    $fixed_subtotal = isset( $_POST['fixed_subtotal'] ) ? floatval( wp_unslash( $_POST['fixed_subtotal'] ) ) : null;
    $expire_days    = isset( $_POST['expire_days'] ) ? intval( wp_unslash( $_POST['expire_days'] ) ) : 1;

    // Ensure expire_days is within a reasonable range
    if ( $expire_days < 1 || $expire_days > 365 ) {
        $expire_days = 1;
    }

    // Generate unique identifier
    $unique_id = uniqid();

    // Store the data
    gvnclg_set_pseudo_transient( 'cartlink_' . $unique_id, [
        'products'      => $products,
        'fixed_subtotal' => $fixed_subtotal,
    ] , $expire_days * DAY_IN_SECONDS);

    // Generate the link
    $link = site_url( '/cartlink_autogenerate/?uid=' . $unique_id );
   

    // Send JSON response
    wp_send_json_success( [
        'message' => __( 'Cart link generated successfully!', 'cartlink-generator' ),
        'link'    => esc_url( $link ),
    ] );
}
add_action( 'wp_ajax_generate_cartlink', 'gvnclg_generate_cartlink' );






add_action('wp_ajax_fetch_product_suggestions', 'gvnclg_fetch_product_suggestions');
function gvnclg_fetch_product_suggestions() {
    // Verify nonce for security
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'gvnclg_fetch_nonce' ) ) {
        wp_send_json_error( __( 'Invalid request. Please refresh the page and try again.', 'cartlink-generator' ) );
    }

    // Check if search term is provided
    if ( ! isset( $_POST['search'] ) || empty( $_POST['search'] ) ) {
        wp_send_json_error( __( 'Search term is required.', 'cartlink-generator' ) );
    }

    // Sanitize the search term
    $search_term = sanitize_text_field( wp_unslash( $_POST['search'] ) );

    // Query products
    $args = [
        'post_type' => ['product', 'product_variation'],
        'posts_per_page' => 10,
        's' => $search_term,
        'fields' => 'ids',
    ];
    $product_ids = get_posts( $args );

    // Build product data
    $products = [];
    foreach ( $product_ids as $product_id ) {
        $product = wc_get_product( $product_id );
        $sku=$product->get_sku();
        if(!empty($sku))$sku=' - '.$sku;
        if ( $product ) {
            $products[] = [
                'id' => $product_id,
                'name' => $product->get_name() . $sku,
                'price' => $product->get_price(),
            ];
        }
    }

    // Send JSON response
    wp_send_json_success( $products );
}
add_action( 'wp_ajax_fetch_product_suggestions', 'gvnclg_fetch_product_suggestions' );

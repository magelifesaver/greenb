<?php
/**
 * File: /plugins/aaa-add-custom-product-status/inc/admin.php
 * Purpose: Adds product list filters (In Stock, Out of Stock, All Products, Store Credit, Promo Banner)
 *          with background highlights for active and hover states.
 * Version: 2.9.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add custom CSS for hover/active link styling.
 */
add_action( 'admin_head', function() {
    ?>
    <style>
        /* General hover styles */
        .aaa-view-link:hover {
            background-color: rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            text-decoration: none !important;
            padding: 2px 5px;
        }
        /* Specific hover tints */
        .aaa-view-in:hover   { background-color: rgba(0,128,0,0.15) !important; }
        .aaa-view-out:hover  { background-color: rgba(208,0,0,0.15) !important; }
        .aaa-view-all:hover  { background-color: rgba(30,115,190,0.15) !important; }
        .aaa-view-credit:hover { background-color: rgba(123,63,242,0.15) !important; }
        .aaa-view-promo:hover  { background-color: rgba(224,176,0,0.15) !important; }

        /* Active view highlighting */
        .aaa-view-link.active {
            background-color: rgba(0,0,0,0.12);
            border-radius: 4px;
            padding: 2px 6px;
        }
        .aaa-view-in.active    { background-color: rgba(0,128,0,0.25) !important; color: #fff !important; }
        .aaa-view-out.active   { background-color: rgba(208,0,0,0.25) !important; color: #fff !important; }
        .aaa-view-all.active   { background-color: rgba(30,115,190,0.25) !important; color: #fff !important; }
        .aaa-view-credit.active{ background-color: rgba(123,63,242,0.25) !important; color: #fff !important; }
        .aaa-view-promo.active { background-color: rgba(224,176,0,0.25) !important; color: #000 !important; }
    </style>
    <?php
});

/**
 * Build top filter view links with colors and dynamic active class.
 */
add_filter( 'views_edit-product', function( $views ) {
    $active = isset( $_GET['aaa_view'] ) ? sanitize_text_field( wp_unslash( $_GET['aaa_view'] ) ) : '';

    $base = add_query_arg( array( 'post_type' => 'product' ), admin_url( 'edit.php' ) );

    $count_query = function( $meta_query = array(), $tax_query = array() ) {
        $args = array(
            'post_type'      => 'product',
            'post_status'    => array( 'publish', 'private' ),
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'meta_query'     => $meta_query,
            'tax_query'      => $tax_query,
        );
        $q = new WP_Query( $args );
        return (int) $q->found_posts;
    };

    $in_count = $count_query(
        array(
            'relation' => 'OR',
            array( 'key' => 'aaa_oos_archived', 'compare' => 'NOT EXISTS' ),
            array( 'key' => 'aaa_oos_archived', 'value' => 'yes', 'compare' => '!=' ),
        ),
        array( array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'simple' ) ) )
    );

    $out_count = $count_query(
        array( array( 'key' => 'aaa_oos_archived', 'value' => 'yes', 'compare' => '=' ) ),
        array( array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'simple' ) ) )
    );

    $all_count = $count_query( array(), array(
        array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'simple' ) )
    ) );

    $credit_count = $count_query( array(), array(
        array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'store_credit' ) )
    ) );

    $promo_count = $count_query( array(), array(
        array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'promo' ) )
    ) );

    $views['aaa_in_stock'] = sprintf(
        '<a class="aaa-view-link aaa-view-in %s" style="font-weight:bold;color:#008000;" href="%s">%s <span class="count" style="color:#008000;">(%d)</span></a>',
        $active === 'in' ? 'active' : '',
        esc_url( add_query_arg( 'aaa_view', 'in', $base ) ),
        esc_html__( 'In Stock', 'aaa-custom-status' ),
        $in_count
    );

    $views['aaa_out_stock'] = sprintf(
        '<a class="aaa-view-link aaa-view-out %s" style="font-weight:bold;color:#d00000;" href="%s">%s <span class="count" style="color:#d00000;">(%d)</span></a>',
        $active === 'out' ? 'active' : '',
        esc_url( add_query_arg( 'aaa_view', 'out', $base ) ),
        esc_html__( 'Out of Stock', 'aaa-custom-status' ),
        $out_count
    );

    $views['aaa_all_products'] = sprintf(
        '<a class="aaa-view-link aaa-view-all %s" style="font-weight:bold;color:#1e73be;" href="%s">%s <span class="count" style="color:#1e73be;">(%d)</span></a>',
        $active === 'all' ? 'active' : '',
        esc_url( add_query_arg( 'aaa_view', 'all', $base ) ),
        esc_html__( 'All Products', 'aaa-custom-status' ),
        $all_count
    );

    $views['aaa_store_credit'] = sprintf(
        '<a class="aaa-view-link aaa-view-credit %s" style="font-weight:bold;color:#7b3ff2;" href="%s">%s <span class="count" style="color:#7b3ff2;">(%d)</span></a>',
        $active === 'credit' ? 'active' : '',
        esc_url( add_query_arg( 'aaa_view', 'credit', $base ) ),
        esc_html__( 'Store Credit', 'aaa-custom-status' ),
        $credit_count
    );

    $views['aaa_promo_banner'] = sprintf(
        '<a class="aaa-view-link aaa-view-promo %s" style="font-weight:bold;color:#e0b000;" href="%s">%s <span class="count" style="color:#e0b000;">(%d)</span></a>',
        $active === 'promo' ? 'active' : '',
        esc_url( add_query_arg( 'aaa_view', 'promo', $base ) ),
        esc_html__( 'Promo Banner', 'aaa-custom-status' ),
        $promo_count
    );

    return $views;
} );

/**
 * Keep WooCommerce default statuses intact; only filter when our view is active.
 */
add_action( 'pre_get_posts', function( $q ) {
    if ( ! is_admin() || ! $q->is_main_query() ) return;
    if ( 'product' !== $q->get( 'post_type' ) ) return;

    $mode = isset( $_GET['aaa_view'] ) ? sanitize_text_field( wp_unslash( $_GET['aaa_view'] ) ) : '';
    if ( ! $mode ) return;

    $mq = (array) $q->get( 'meta_query', array() );
    $tq = (array) $q->get( 'tax_query', array() );

    switch ( $mode ) {
        case 'out':
            $mq[] = array( 'key' => 'aaa_oos_archived', 'value' => 'yes', 'compare' => '=' );
            $tq[] = array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'simple' ) );
            break;
        case 'in':
            $mq[] = array(
                'relation' => 'OR',
                array( 'key' => 'aaa_oos_archived', 'compare' => 'NOT EXISTS' ),
                array( 'key' => 'aaa_oos_archived', 'value' => 'yes', 'compare' => '!=' ),
            );
            $tq[] = array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'simple' ) );
            break;
        case 'all':
            $tq[] = array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'simple' ) );
            break;
        case 'credit':
            $tq[] = array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'store_credit' ) );
            break;
        case 'promo':
            $tq[] = array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'promo' ) );
            break;
    }

    $q->set( 'meta_query', $mq );
    $q->set( 'tax_query', $tq );
    $q->set( 'post_status', array( 'publish', 'private' ) );
}, 9 );

/**
 * Preserve filter param during search/pagination
 */
add_filter( 'request', function( $vars ) {
    if ( ! is_admin() || ! isset( $_GET['post_type'] ) || 'product' !== $_GET['post_type'] ) return $vars;
    if ( isset( $_GET['aaa_view'] ) && ! isset( $vars['aaa_view'] ) ) {
        $vars['aaa_view'] = sanitize_text_field( wp_unslash( $_GET['aaa_view'] ) );
    }
    return $vars;
} );

/**
 * Add "Out of Stock" column for visibility.
 */
add_filter( 'manage_edit-product_columns', function( $cols ) {
    $cols['aaa_oos_archived'] = __( 'Out of Stock', 'aaa-custom-status' );
    return $cols;
} );

add_action( 'manage_product_posts_custom_column', function( $col, $post_id ) {
    if ( 'aaa_oos_archived' !== $col ) return;
    $is_out = get_post_meta( $post_id, 'aaa_oos_archived', true ) === 'yes';
    echo $is_out
        ? '<span style="color:#d00000;font-weight:bold;">&#10004;</span>'
        : '<span style="color:#008000;">&#8212;</span>';
}, 10, 2 );

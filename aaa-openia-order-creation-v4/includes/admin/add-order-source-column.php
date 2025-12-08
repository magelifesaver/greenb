<?php
// File: /wp-content/plugins/aaa-openia-order-creation-v4/admin/add-order-source-column.php
// Purpose: Show created_via “Source” as a colored pill in Orders list
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Add column
add_filter( 'manage_edit-shop_order_columns', function( $columns ) {
    $columns['created_via'] = 'Source';
    return $columns;
}, 20 );

// Render value
add_action( 'manage_shop_order_posts_custom_column', function( $column, $post_id ) {
    if ( 'created_via' === $column ) {
        $order  = wc_get_order( $post_id );
        $source = $order ? $order->get_created_via() : '';

        if ( ! $source ) { $source = 'unknown'; }

        // Humanize: 'weedmaps_ftp' -> 'Weedmaps FTP'
        $label = ucwords( str_replace( '_', ' ', $source ) );

        echo '<span class="aaa-order-pill source-pill source-' . esc_attr( $source ) . '">' . esc_html( $label ) . '</span>';
    }
}, 20, 2 );

// Styles
add_action( 'admin_head', function() {
    echo '<style>
    .source-pill {
        display:inline-block; padding:3px 8px; border-radius:12px;
        font-size:11px; font-weight:600; color:#fff; line-height:1.5;
    }
    .source-weedmaps   { background-color:#28a745; }
    .source-phone      { background-color:#0073aa; }
    .source-checkout   { background-color:#ff7f50; }
    .source-unknown    { background-color:#777; }

    /* NEW source types */
    .source-weedmaps_ftp { background-color:#1e7e34; } /* darker green */
    .source-phone_ftp    { background-color:#005f99; } /* darker blue */
    .source-web_ftp      { background-color:#6f42c1; } /* purple */
    </style>';
});

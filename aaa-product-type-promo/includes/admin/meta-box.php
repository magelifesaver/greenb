<?php
/**
 * Registers the banner image meta box for Promo products only.  We use the
 * Meta Box plugin (rwmb_meta_boxes filter) to expose a single image upload
 * field.  The meta box is conditionally added based on the current product
 * type so other product types remain unaffected.
 */

defined( 'ABSPATH' ) || exit;

// Only run if the Meta Box plugin is present.
if ( class_exists( 'RW_Meta_Box' ) ) {
    add_filter( 'rwmb_meta_boxes', function ( array $meta_boxes ) {
        // Determine if we are on a product edit screen and editing a promo.
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'product' !== $screen->post_type ) {
            return $meta_boxes;
        }

        // Determine the product type from query or post meta.
        $product_type = '';
        if ( isset( $_GET['product_type'] ) && ! empty( $_GET['product_type'] ) ) {
            $product_type = wc_clean( wp_unslash( $_GET['product_type'] ) );
        } elseif ( isset( $_GET['post'] ) ) {
            $pid          = absint( $_GET['post'] );
            $product_type = get_post_meta( $pid, '_product_type', true );
        }

        // Only register the meta box when editing or creating a promo product.
        if ( 'promo' !== $product_type ) {
            return $meta_boxes;
        }

        $meta_boxes[] = [
            'title'      => __( 'Promo Banner Image', 'aaa' ),
            'id'         => 'promo_banner_box',
            'post_types' => [ 'product' ],
            'context'    => 'side',
            'priority'   => 'low',
            'fields'     => [
                [
                    'name'             => __( 'Banner Image', 'aaa' ),
                    'id'               => '_promo_banner_image',
                    'type'             => 'image_advanced',
                    'max_file_uploads' => 1,
                ],
            ],
        ];
        return $meta_boxes;
    } );
}

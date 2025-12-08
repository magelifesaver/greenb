<?php
/**
 * Plugin Name: AAA WC Attribute Visibility CLI
 * Description: Adds a WP-CLI command to bulk-set taxonomy-based WooCommerce product attributes as visible on the frontend by directly updating _product_attributes meta.
 * Author: Lokey Delivery
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

    /**
     * Bulk-fix WooCommerce product attribute visibility (taxonomy attributes only).
     *
     * ## EXAMPLES
     *
     *   # Dry run on all products (no saves, just counts)
     *   wp wc-attr-visibility fix --dry-run
     *
     *   # Fix all products in batches of 200
     *   wp wc-attr-visibility fix --batch=200
     *
     *   # Fix only products in category ID 44
     *   wp wc-attr-visibility fix --category=44
     */
    class AAA_WC_Attr_Visibility_Command {

        /**
         * Fix attribute visibility.
         *
         * ## OPTIONS
         *
         * [--batch=<number>]
         * : Number of products to process per page. Default: 100.
         *
         * [--category=<id>]
         * : Optional product_cat term ID to limit products by category.
         *
         * [--dry-run]
         * : Show what would be changed without saving products.
         */
        public function fix( $args, $assoc_args ) {

            // WooCommerce presence check still useful for context, but we work on raw meta.
            if ( ! class_exists( 'WooCommerce' ) ) {
                WP_CLI::warning( 'WooCommerce class not found. Proceeding to operate on _product_attributes meta directly.' );
            }

            $batch    = isset( $assoc_args['batch'] ) ? max( 1, (int) $assoc_args['batch'] ) : 100;
            $category = isset( $assoc_args['category'] ) ? (int) $assoc_args['category'] : 0;
            $dry_run  = isset( $assoc_args['dry-run'] );

            WP_CLI::log( sprintf(
                'Starting taxonomy attribute visibility fix (meta-level). Batch: %d, Category: %s, Dry-run: %s',
                $batch,
                $category ? $category : 'ALL',
                $dry_run ? 'yes' : 'no'
            ) );

            $paged          = 1;
            $total_products = 0;
            $total_changed  = 0;

            do {
                $query_args = [
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    'posts_per_page' => $batch,
                    'paged'          => $paged,
                    'fields'         => 'ids',
                ];

                if ( $category ) {
                    $query_args['tax_query'] = [
                        [
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => $category,
                        ],
                    ];
                }

                $q = new WP_Query( $query_args );

                if ( ! $q->have_posts() ) {
                    break;
                }

                WP_CLI::log( sprintf( 'Processing page %d (%d products)...', $paged, count( $q->posts ) ) );

                foreach ( $q->posts as $product_id ) {
                    $meta = get_post_meta( $product_id, '_product_attributes', true );

                    if ( empty( $meta ) || ! is_array( $meta ) ) {
                        continue;
                    }

                    $changed = 0;

                    foreach ( $meta as $key => $attr ) {
                        // Make sure it's in the expected array format.
                        if ( ! is_array( $attr ) ) {
                            continue;
                        }

                        $is_taxonomy = isset( $attr['is_taxonomy'] ) ? (int) $attr['is_taxonomy'] : 0;
                        $is_visible  = isset( $attr['is_visible'] ) ? (int) $attr['is_visible'] : 0;

                        // Only touch taxonomy-based attributes that are currently not visible.
                        if ( $is_taxonomy && ! $is_visible ) {
                            $meta[ $key ]['is_visible'] = 1;
                            $changed++;
                        }
                    }

                    if ( $changed ) {
                        $total_changed++;
                        $total_products++;

                        if ( $dry_run ) {
                            WP_CLI::log( sprintf(
                                'Would update product #%d – taxonomy attribute rows flipped to visible: %d',
                                $product_id,
                                $changed
                            ) );
                        } else {
                            update_post_meta( $product_id, '_product_attributes', $meta );
                            WP_CLI::log( sprintf(
                                'Updated product #%d – taxonomy attribute rows flipped to visible: %d',
                                $product_id,
                                $changed
                            ) );
                        }
                    }
                }

                $paged++;
                wp_reset_postdata();

            } while ( true );

            if ( $dry_run ) {
                WP_CLI::success( sprintf(
                    'Dry-run complete. Products that would change: %d, total products with taxonomy attributes flipped: %d',
                    $total_products,
                    $total_changed
                ) );
            } else {
                WP_CLI::success( sprintf(
                    'Done. Products updated: %d, total products with taxonomy attributes flipped: %d',
                    $total_products,
                    $total_changed
                ) );
            }
        }
    }

    WP_CLI::add_command( 'wc-attr-visibility', 'AAA_WC_Attr_Visibility_Command' );
}

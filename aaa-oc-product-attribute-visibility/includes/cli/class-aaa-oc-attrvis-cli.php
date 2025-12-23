<?php
/**
 * File: /aaa-oc-product-attribute-visibility/includes/cli/class-aaa-oc-attrvis-cli.php
 * Purpose: Provide a WP‑CLI interface for backward compatibility.
 *
 * The command name remains `wc-attr-visibility` to avoid breaking existing
 * scripts. Usage:
 *   wp wc-attr-visibility fix [--batch=100] [--category=ID] [--dry-run]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_AttrVis_CLI {
    /**
     * Register the CLI command if WP_CLI is available.
     */
    public static function init() {
        if ( ! class_exists( 'WP_CLI' ) ) {
            return;
        }
        /**
         * Backward compatible command wrapper.
         */
        class AAA_OC_AttrVis_CLI_Command {
            /**
             * Fix attribute visibility across products.
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
             *
             * ## EXAMPLES
             *
             *   # Dry run on all products
             *   wp wc-attr-visibility fix --dry-run
             *
             *   # Fix all products in batches of 200
             *   wp wc-attr-visibility fix --batch=200
             *
             *   # Fix only products in category ID 44
             *   wp wc-attr-visibility fix --category=44
             *
             * @param array $args       Positional arguments.
             * @param array $assoc_args Associative arguments.
             */
            public function fix( $args, $assoc_args ) {
                $batch    = isset( $assoc_args['batch'] ) ? max( 1, (int) $assoc_args['batch'] ) : 100;
                $category = isset( $assoc_args['category'] ) ? (int) $assoc_args['category'] : 0;
                $dry_run  = isset( $assoc_args['dry-run'] );
                WP_CLI::log( 'Starting fix. batch=' . $batch . ' category=' . ( $category ? $category : 'ALL' ) . ' dry=' . ( $dry_run ? 'yes' : 'no' ) );
                $paged                  = 1;
                $total_checked          = 0;
                $total_products_updated = 0;
                $total_rows             = 0;
                do {
                    $res = AAA_OC_AttrVis_Fixer::run_batch( $batch, $paged, $category, $dry_run );
                    $total_checked          += (int) $res['checked'];
                    $total_products_updated += (int) $res['products_updated'];
                    $total_rows             += (int) $res['rows_changed'];
                    WP_CLI::log( 'Page ' . $paged . ': checked=' . $res['checked'] . ' updated=' . $res['products_updated'] . ' rows=' . $res['rows_changed'] );
                    $paged++;
                } while ( ! empty( $res['has_more'] ) );
                WP_CLI::success( 'Done. checked=' . $total_checked . ' updated=' . $total_products_updated . ' rows=' . $total_rows );
            }
        }
        WP_CLI::add_command( 'wc-attr-visibility', 'AAA_OC_AttrVis_CLI_Command' );
    }
}
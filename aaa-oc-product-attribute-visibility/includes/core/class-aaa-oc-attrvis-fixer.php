<?php
/**
 * File: /aaa-oc-product-attribute-visibility/includes/core/class-aaa-oc-attrvis-fixer.php
 * Purpose: Encapsulates the core logic to audit and fix product attribute
 * visibility.  
 *
 * This class provides methods to:
 *  - Inspect product attribute metadata and return a report of attribute
 *    visibility for a batch of products.
 *  - Flip taxonomy attributes (`is_taxonomy` = 1) from not visible to visible
 *    in `_product_attributes` meta (with optional dry‑run).
 *  - Iterate through products in batches to perform fixes or reports.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'AAA_OC_ATTRVIS_DEBUG_FIXER' ) ) {
    define( 'AAA_OC_ATTRVIS_DEBUG_FIXER', true );
}

class AAA_OC_AttrVis_Fixer {

    /**
     * Flip taxonomy attribute visibility for a single product.
     *
     * @param int  $product_id Product post ID.
     * @param bool $dry_run    Whether to simulate updates without saving.
     * @return int Number of attribute rows changed (0 means no change).
     */
    public static function fix_product( $product_id, $dry_run = false ) {
        $meta = get_post_meta( $product_id, '_product_attributes', true );
        if ( empty( $meta ) || ! is_array( $meta ) ) {
            return 0;
        }
        $changed_rows = 0;
        foreach ( $meta as $key => $attr ) {
            if ( ! is_array( $attr ) ) {
                continue;
            }
            $is_taxonomy = isset( $attr['is_taxonomy'] ) ? (int) $attr['is_taxonomy'] : 0;
            $is_visible  = isset( $attr['is_visible'] ) ? (int) $attr['is_visible'] : 0;
            if ( $is_taxonomy && ! $is_visible ) {
                $meta[ $key ]['is_visible'] = 1;
                $changed_rows++;
            }
        }
        if ( $changed_rows && ! $dry_run ) {
            update_post_meta( $product_id, '_product_attributes', $meta );
        }
        return $changed_rows;
    }

    /**
     * Run a batch fix or report over products.
     *
     * @param int  $batch    Number of products per page.
     * @param int  $paged    The current page of products (1‑based).
     * @param int  $category Optional product category term ID filter.
     * @param bool $dry_run  If true, do not save changes.
     * @return array Results with keys: checked, products_updated, rows_changed,
     *               has_more, next_paged.
     */
    public static function run_batch( $batch = 100, $paged = 1, $category = 0, $dry_run = false ) {
        $batch = max( 1, (int) $batch );
        $paged = max( 1, (int) $paged );
        $args  = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $batch,
            'paged'          => $paged,
            'fields'         => 'ids',
        );
        if ( $category ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => (int) $category,
                ),
            );
        }
        $q = new WP_Query( $args );
        $checked          = 0;
        $products_updated = 0;
        $rows_changed     = 0;
        foreach ( (array) $q->posts as $product_id ) {
            $checked++;
            $rows = self::fix_product( (int) $product_id, $dry_run );
            if ( $rows ) {
                $products_updated++;
                $rows_changed += $rows;
                if ( AAA_OC_ATTRVIS_DEBUG_FIXER ) {
                    error_log( '[AAA_OC_ATTRVIS][fixer] product #' . $product_id . ' rows_changed=' . $rows . ' dry=' . ( $dry_run ? '1' : '0' ) );
                }
            }
        }
        wp_reset_postdata();
        return array(
            'checked'          => $checked,
            'products_updated' => $products_updated,
            'rows_changed'     => $rows_changed,
            'has_more'         => ( $checked === $batch ),
            'next_paged'       => $paged + 1,
        );
    }

    /**
     * Fix visibility for an explicit list of product IDs.
     *
     * @param array $post_ids Array of product IDs.
     * @param bool  $dry_run  If true, simulate updates without saving.
     * @return array Summary counts: checked, products_updated, rows_changed.
     */
    public static function fix_by_ids( $post_ids, $dry_run = false ) {
        $checked          = 0;
        $products_updated = 0;
        $rows_changed     = 0;
        foreach ( (array) $post_ids as $product_id ) {
            $checked++;
            $rows = self::fix_product( (int) $product_id, $dry_run );
            if ( $rows ) {
                $products_updated++;
                $rows_changed += $rows;
            }
        }
        return array(
            'checked'          => $checked,
            'products_updated' => $products_updated,
            'rows_changed'     => $rows_changed,
        );
    }

    /**
     * Produce a report of attribute visibility for a batch of products.
     *
     * This helper inspects each product's `_product_attributes` meta and returns
     * the attribute names along with their current `is_visible` status. Only
     * taxonomy attributes (where `is_taxonomy=1`) are considered.  Non‑taxonomy
     * attributes are ignored because WooCommerce always displays custom
     * attributes. The report does **not** modify any data.
     *
     * @param int  $batch    Number of products per page.
     * @param int  $paged    Current page number (1‑based).
     * @param int  $category Optional product category term ID filter.
     * @return array Associative array with keys:
     *               - items: list of arrays with product_id, product_title,
     *                 attributes (each attribute array has name and visible).
     *               - has_more: whether additional pages exist.
     *               - next_paged: next page index.
     *               - checked: number of products processed.
     */
    public static function get_visibility_report( $batch = 50, $paged = 1, $category = 0 ) {
        $batch = max( 1, (int) $batch );
        $paged = max( 1, (int) $paged );
        $args  = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $batch,
            'paged'          => $paged,
            'fields'         => 'ids',
        );
        if ( $category ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => (int) $category,
                ),
            );
        }
        $q = new WP_Query( $args );
        $items   = array();
        $checked = 0;
        foreach ( (array) $q->posts as $product_id ) {
            $checked++;
            $title  = get_the_title( $product_id );
            $meta   = get_post_meta( $product_id, '_product_attributes', true );
            $attrs  = array();
            if ( is_array( $meta ) ) {
                foreach ( $meta as $key => $attr ) {
                    if ( ! is_array( $attr ) ) {
                        continue;
                    }
                    $is_taxonomy = isset( $attr['is_taxonomy'] ) ? (int) $attr['is_taxonomy'] : 0;
                    if ( ! $is_taxonomy ) {
                        continue; // ignore custom attributes.
                    }
                    $taxonomy  = isset( $attr['name'] ) ? $attr['name'] : $key;
                    $is_visible = isset( $attr['is_visible'] ) ? (int) $attr['is_visible'] : 0;
                    $attrs[] = array(
                        'name'    => $taxonomy,
                        'visible' => $is_visible,
                    );
                }
            }
            $items[] = array(
                'product_id'    => $product_id,
                'product_title' => $title,
                'attributes'    => $attrs,
            );
        }
        wp_reset_postdata();
        return array(
            'items'     => $items,
            'has_more'  => ( $checked === $batch ),
            'next_paged' => $paged + 1,
            'checked'   => $checked,
        );
    }
}
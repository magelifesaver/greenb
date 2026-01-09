<?php
// Product summary sync for the AIP indexer.
// Creates two meta keys on save or stock change:
// `api_product_attribute_summary` (taxonomy attributes + price/stock) and
// `api_product_admin_summary` (name, SKU, categories, price, stock, sales, revenue, last sale date).

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Product_Summary {
    // Track currently processing product IDs to avoid recursion.
    protected static $running = [];

    // Register hooks to capture product saves and stock updates.
    public static function init() {
        $callback = [ __CLASS__, 'sync' ];
        // CRUD insert/update hooks.
        add_action( 'woocommerce_new_product', $callback, 10 );
        add_action( 'woocommerce_update_product', $callback, 10 );
        // Post save hook to catch edits via editor.
        add_action( 'save_post_product', function ( $post_id, $post, $update ) {
            if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
                return;
            }
            self::sync( $post_id );
        }, 10, 3 );
        // Stock quantity and status updates.
        add_action( 'woocommerce_product_set_stock', function ( $product ) {
            $pid = is_object( $product ) && method_exists( $product, 'get_id' ) ? $product->get_id() : 0;
            self::sync( $pid );
        }, 10 );
        add_action( 'woocommerce_product_set_stock_status', function ( $product_id ) {
            self::sync( $product_id );
        }, 10 );
    }

    // Public entry point to build summaries. Accepts a product ID or object.
    public static function sync( $product ) {
        $product_id = 0;
        if ( is_numeric( $product ) ) {
            $product_id = absint( $product );
        } elseif ( $product instanceof WC_Product ) {
            $product_id = $product->get_id();
        }
        if ( ! $product_id || isset( self::$running[ $product_id ] ) ) {
            return;
        }
        self::$running[ $product_id ] = true;
        $p = wc_get_product( $product_id );
        if ( ! $p ) {
            unset( self::$running[ $product_id ] );
            return;
        }
        // Normalise variations to the parent product.
        if ( $p->is_type( 'variation' ) ) {
            $parent_id = $p->get_parent_id();
            $p = $parent_id ? wc_get_product( $parent_id ) : $p;
        }
        self::build( $p );
        unset( self::$running[ $product_id ] );
    }

    // Build attribute and admin summaries for a product and write to meta.
    protected static function build( WC_Product $product ) {
        $id    = $product->get_id();
        $price = $product->get_price();
        $stock = $product->get_stock_quantity();
        // Build attribute summary as a series of label:value pairs.
        $parts = [];
        foreach ( $product->get_attributes() as $attr ) {
            if ( ! $attr ) {
                continue;
            }
            $name  = $attr->get_name();
            $label = wc_attribute_label( $name, $product );
            $vals  = [];
            if ( $attr->is_taxonomy() ) {
                $terms = wc_get_product_terms( $id, $name, [ 'fields' => 'names' ] );
                $vals  = is_array( $terms ) ? array_map( 'sanitize_text_field', $terms ) : [];
            } else {
                $opts = $attr->get_options();
                $vals = is_array( $opts ) ? array_map( 'sanitize_text_field', $opts ) : [];
            }
            if ( $vals ) {
                $parts[] = sanitize_text_field( $label ) . ': ' . implode( ', ', $vals );
            }
        }
        if ( $price !== '' ) {
            $parts[] = 'Price: ' . wc_price( $price );
        }
        $parts[] = 'Stock: ' . ( $stock === null ? 'N/A' : $stock );
        update_post_meta( $id, 'api_product_attribute_summary', wp_strip_all_tags( implode( ' | ', $parts ) ) );
        // Build admin summary.
        $admin    = [];
        $admin[]  = 'Name: ' . sanitize_text_field( $product->get_name() );
        $sku      = $product->get_sku();
        if ( $sku ) {
            $admin[] = 'SKU: ' . sanitize_text_field( $sku );
        }
        $cats = wc_get_product_terms( $id, 'product_cat', [ 'fields' => 'names' ] );
        if ( $cats ) {
            $admin[] = 'Categories: ' . implode( ', ', array_map( 'sanitize_text_field', $cats ) );
        }
        if ( $price !== '' ) {
            $admin[] = 'Price: ' . wc_price( $price );
        }
        $admin[] = 'Stock: ' . ( $stock === null ? 'N/A' : $stock );
        $sales    = (int) $product->get_total_sales();
        if ( $sales > 0 ) {
            $admin[] = 'Total Sales: ' . $sales;
            if ( $price !== '' ) {
                $admin[] = 'Revenue: ' . wc_price( $sales * (float) $price );
            }
            // Last sale date: fetch the most recent order containing this product.
            $orders = wc_get_orders( [
                'limit'      => 1,
                'status'     => [ 'completed', 'processing', 'on-hold' ],
                'orderby'    => 'date_completed',
                'order'      => 'DESC',
                'product_id' => $id,
                'return'     => 'ids',
            ] );
            if ( $orders ) {
                $o_id  = $orders[0];
                $order = wc_get_order( $o_id );
                if ( $order ) {
                    $date = $order->get_date_completed() ?: $order->get_date_created();
                    if ( $date ) {
                        $admin[] = 'Last Sale: ' . $date->date( 'Y-m-d H:i:s' );
                    }
                }
            }
        }
        $created = $product->get_date_created();
        if ( $created ) {
            $admin[] = 'Created: ' . $created->date( 'Y-m-d H:i:s' );
        }
        $modified = $product->get_date_modified();
        if ( $modified ) {
            $admin[] = 'Modified: ' . $modified->date( 'Y-m-d H:i:s' );
        }
        update_post_meta( $id, 'api_product_admin_summary', wp_strip_all_tags( implode( ' | ', $admin ) ) );
    }
}
// Kick things off.
AAA_OC_Product_Summary::init();
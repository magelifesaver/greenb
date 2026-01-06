<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/hooks/class-aaa-oc-productforecast-hooks-products.php
 * Purpose: Hooks to keep ProductForecast index table in sync when products or forecast meta changes.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_ProductForecast_Hooks_Products {

    public static function boot() : void {
        // Only run when WooCommerce is active.
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Ensure installer available.
        if ( ! class_exists( 'AAA_OC_ProductForecast_Table_Indexer' ) ) {
            return;
        }

        // Product saved.
        add_action( 'save_post_product', [ __CLASS__, 'on_product_save' ], 20, 2 );

        // Stock status changes.
        add_action( 'woocommerce_product_set_stock_status', [ __CLASS__, 'on_stock_status' ], 20, 3 );

        // Forecast meta changes (this is the root sync).
        add_action( 'updated_post_meta', [ __CLASS__, 'on_updated_post_meta' ], 20, 4 );
        add_action( 'added_post_meta',   [ __CLASS__, 'on_added_post_meta' ], 20, 4 );
        add_action( 'deleted_post_meta', [ __CLASS__, 'on_deleted_post_meta' ], 20, 4 );

        // Term changes can affect brand/category string columns.
        add_action( 'set_object_terms',  [ __CLASS__, 'on_terms_set' ], 20, 6 );
    }

    public static function on_product_save( $post_id, $post ) : void {
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        if ( ! $post || ( isset( $post->post_type ) && $post->post_type !== 'product' ) ) {
            return;
        }
        AAA_OC_ProductForecast_Table_Indexer::upsert_now( (int) $post_id, 'save_post_product' );
    }

    public static function on_stock_status( $product_id, $status, $product ) : void {
        AAA_OC_ProductForecast_Table_Indexer::upsert_now( (int) $product_id, 'stock_status' );
    }

    protected static function is_forecast_meta_key( string $key ) : bool {
        if ( strpos( $key, 'forecast_' ) === 0 ) return true;
        if ( strpos( $key, 'aip_' ) === 0 ) return true;
        // Cost sources can affect margin/frozen_capital.
        if ( $key === '_purchase_price' ) return true;
        if ( $key === '_cogs_total_value' ) return true;
        return false;
    }

    public static function on_updated_post_meta( $meta_id, $post_id, $meta_key, $meta_value ) : void {
        if ( get_post_type( $post_id ) !== 'product' ) return;
        if ( ! self::is_forecast_meta_key( (string) $meta_key ) ) return;
        AAA_OC_ProductForecast_Table_Indexer::upsert_now( (int) $post_id, 'updated_post_meta:' . $meta_key );
    }

    public static function on_added_post_meta( $meta_id, $post_id, $meta_key, $meta_value ) : void {
        if ( get_post_type( $post_id ) !== 'product' ) return;
        if ( ! self::is_forecast_meta_key( (string) $meta_key ) ) return;
        AAA_OC_ProductForecast_Table_Indexer::upsert_now( (int) $post_id, 'added_post_meta:' . $meta_key );
    }

    public static function on_deleted_post_meta( $meta_ids, $post_id, $meta_key, $meta_value ) : void {
        if ( get_post_type( $post_id ) !== 'product' ) return;
        if ( ! self::is_forecast_meta_key( (string) $meta_key ) ) return;
        AAA_OC_ProductForecast_Table_Indexer::upsert_now( (int) $post_id, 'deleted_post_meta:' . $meta_key );
    }

    public static function on_terms_set( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) : void {
        if ( get_post_type( $object_id ) !== 'product' ) return;
        if ( $taxonomy !== 'product_cat' && $taxonomy !== 'berocket_brand' ) return;
        AAA_OC_ProductForecast_Table_Indexer::upsert_now( (int) $object_id, 'set_object_terms:' . $taxonomy );
    }
}

<?php
/**
 * Plugin Name: AAA – WooGC Attribute & Brand Sync Fix (XHV98-ADMIN)
 * Description: Ensures attribute/brand terms map by slug during WP Global Cart product sync so values attach correctly on child sites.
 * Version: 1.1.0
 * Author: Webmaster Workflow
 * File Path: /wp-content/plugins/aaa-woogc-attr-sync/aaa-woogc-attr-sync.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_WOOGC_Attr_Sync' ) ) {
    class AAA_WOOGC_Attr_Sync {
        const DEBUG_THIS_FILE = true; // dev default

        public static function init() {
            // Runs just before WP Global Cart saves the child product
            add_filter( 'woogc/ps/synchronize_product/child_product', [ __CLASS__, 'fix_terms_and_attributes' ], 99, 3 );
        }

        protected static function log( $msg ) {
            if ( self::DEBUG_THIS_FILE ) { error_log( '[AAA_WOOGC_Attr_Sync] ' . $msg ); }
        }

        /**
         * Map attribute options by slug and sync brand/attribute taxonomies.
         *
         * @param WC_Product $child_product
         * @param array      $main_product_data e.g. [ 'id' => origin_product_id, ... ]
         * @param int        $origin_blog_id
         * @return WC_Product
         */
        public static function fix_terms_and_attributes( $child_product, $main_product_data, $origin_blog_id ) {
            if ( ! $child_product || empty( $main_product_data['id'] ) ) {
                return $child_product;
            }

            $child_id          = $child_product->get_id();
            $origin_product_id = (int) $main_product_data['id'];

            // 1) Determine which taxonomies to sync: brand + any pa_* attributes used on origin
            $taxonomies_to_sync = [ 'berocket_brand' ];
            switch_to_blog( $origin_blog_id );
            $origin_product = wc_get_product( $origin_product_id );

            if ( $origin_product ) {
                $origin_attrs = $origin_product->get_attributes();
                foreach ( $origin_attrs as $attr ) {
                    if ( $attr->is_taxonomy() ) {
                        $tax = $attr->get_name();
                        if ( strpos( $tax, 'pa_' ) === 0 ) {
                            $taxonomies_to_sync[] = $tax;
                        }
                    }
                }
            }
            restore_current_blog();
            $taxonomies_to_sync = array_values( array_unique( $taxonomies_to_sync ) );

            // 2) Sync taxonomies by slug
            foreach ( $taxonomies_to_sync as $tax ) {
                switch_to_blog( $origin_blog_id );
                $origin_term_ids = wp_get_object_terms( $origin_product_id, $tax, [ 'fields' => 'ids' ] );
                restore_current_blog();

                if ( ! is_wp_error( $origin_term_ids ) && $origin_term_ids ) {
                    foreach ( $origin_term_ids as $origin_tid ) {
                        switch_to_blog( $origin_blog_id );
                        $origin_term = get_term( $origin_tid, $tax );
                        restore_current_blog();

                        if ( $origin_term && ! is_wp_error( $origin_term ) ) {
                            $slug       = $origin_term->slug;
                            $child_term = get_term_by( 'slug', $slug, $tax );

                            if ( ! $child_term ) {
                                $new = wp_insert_term( $origin_term->name, $tax, [ 'slug' => $slug ] );
                                if ( ! is_wp_error( $new ) ) {
                                    $child_term_id = $new['term_id'];
                                    self::log( "Created new {$tax} term '{$origin_term->name}' [slug={$slug}] on child site" );
                                }
                            } else {
                                $child_term_id = $child_term->term_id;
                            }

                            if ( ! empty( $child_term_id ) ) {
                                wp_set_object_terms( $child_id, $child_term_id, $tax, true );
                                self::log( "Synced taxonomy {$tax} by slug '{$slug}' to child {$child_id}" );
                            }
                        }
                    }

                    // Debug: log final terms attached
                    $after_terms = wp_get_object_terms( $child_id, $tax, [ 'fields' => 'slugs' ] );
                    self::log( "Child product {$child_id} now has {$tax}: " . implode( ',', $after_terms ) );
                }
            }

            // 3) Remap attribute options from origin IDs -> child term IDs using slugs
            $attributes = $child_product->get_attributes();
            $modified   = false;

            foreach ( $attributes as $key => $attr ) {
                if ( ! $attr instanceof WC_Product_Attribute ) { continue; }
                if ( ! $attr->is_taxonomy() ) { continue; }

                $tax     = $attr->get_name();
                $options = (array) $attr->get_options();
                if ( ! $options ) { continue; }

                $child_term_ids = [];

                foreach ( $options as $opt ) {
                    $slug = null;

                    if ( is_numeric( $opt ) ) {
                        switch_to_blog( $origin_blog_id );
                        $t = get_term( (int) $opt, $tax );
                        $slug = ( $t && ! is_wp_error( $t ) ) ? $t->slug : null;
                        restore_current_blog();
                    } else {
                        $slug = sanitize_title( $opt );
                    }

                    if ( $slug ) {
                        $child_term = get_term_by( 'slug', $slug, $tax );
                        if ( $child_term && ! is_wp_error( $child_term ) ) {
                            $child_term_ids[] = (int) $child_term->term_id;
                        }
                    }
                }

                if ( $child_term_ids ) {
                    $attr->set_options( array_values( array_unique( $child_term_ids ) ) );
                    $attributes[ $key ] = $attr;
                    $modified = true;
                }
            }

            if ( $modified ) {
                $child_product->set_attributes( $attributes );
                $child_product->save(); // 🔹 ensure persistence
                self::log( "Adjusted attribute options by slug for child product {$child_id}" );
            }

            return $child_product;
        }
    }

    add_action( 'plugins_loaded', [ 'AAA_WOOGC_Attr_Sync', 'init' ] );
}

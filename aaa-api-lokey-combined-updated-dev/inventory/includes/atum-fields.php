<?php
/**
 * ATUM helpers for Lokey Inventory API.
 *
 * Provides utilities to update ATUM-controlled product data (stored in
 * wp_atum_product_data) without writing raw post meta.
 *
 * This file intentionally contains no REST route registrations.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lokey_inv_normalize_atum_locations' ) ) {
    /**
     * Normalize multiple possible location inputs into ATUM's REST shape:
     *   [ { "id": 123 }, { "id": 456 } ]
     *
     * Accepts:
     * - int|string: 123
     * - array of ints: [123,456]
     * - array of objects: [ {"id":123}, {"id":456} ]
     * - empty array: [] (explicitly clears locations)
     *
     * @param mixed $raw Raw input.
     * @return array|null Returns array (possibly empty) or null when nothing provided.
     */
    function lokey_inv_normalize_atum_locations( $raw ) {
        if ( is_null( $raw ) ) {
            return null;
        }

        // Allow explicit clearing.
        if ( is_array( $raw ) && $raw === [] ) {
            return [];
        }

        $ids = [];

        if ( is_numeric( $raw ) ) {
            $ids[] = absint( $raw );
        } elseif ( is_array( $raw ) ) {
            foreach ( $raw as $item ) {
                if ( is_array( $item ) && isset( $item['id'] ) ) {
                    $ids[] = absint( $item['id'] );
                } elseif ( is_numeric( $item ) ) {
                    $ids[] = absint( $item );
                }
            }
        }

        $ids = array_values( array_filter( array_unique( $ids ) ) );

        $out = [];
        foreach ( $ids as $id ) {
            $out[] = [ 'id' => $id ];
        }
        return $out;
    }
}

if ( ! function_exists( 'lokey_inv_update_atum_product_data' ) ) {
    /**
     * Update ATUM product data fields for a WooCommerce product.
     *
     * Supported keys (when present in $fields):
     * - purchase_price (float)
     * - supplier_id (int)
     * - atum_locations (array of {id})
     * - stock_quantity (int) (Woo stock update, not stored in ATUM table)
     *
     * @param int   $product_id WooCommerce product ID.
     * @param array $fields     Fields to update.
     * @return array|\WP_Error  Updated snapshot (best-effort).
     */
    function lokey_inv_update_atum_product_data( $product_id, array $fields ) {

        $product_id = absint( $product_id );
        if ( $product_id <= 0 ) {
            return new \WP_Error( 'lokey_inv_invalid_id', 'Invalid product ID.', [ 'status' => 400 ] );
        }

        $updated = [
            'id' => $product_id,
        ];

        // Update Woo stock if requested.
        if ( array_key_exists( 'stock_quantity', $fields ) ) {
            $qty = is_null( $fields['stock_quantity'] ) ? null : intval( $fields['stock_quantity'] );
            if ( is_null( $qty ) ) {
                return new \WP_Error( 'lokey_inv_invalid_stock', 'stock_quantity cannot be null.', [ 'status' => 400 ] );
            }

            $wc_product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
            if ( ! $wc_product ) {
                return new \WP_Error( 'lokey_inv_product_missing', 'WooCommerce product not found.', [ 'status' => 404 ] );
            }

            // Ensure stock is managed if we're setting a quantity.
            if ( method_exists( $wc_product, 'set_manage_stock' ) ) {
                $wc_product->set_manage_stock( true );
            }
            if ( method_exists( $wc_product, 'set_stock_quantity' ) ) {
                $wc_product->set_stock_quantity( $qty );
            }
            if ( method_exists( $wc_product, 'save' ) ) {
                $wc_product->save();
            }

            $updated['stock_quantity'] = $qty;
        }

        // ATUM product data updates require ATUM plugin.
        $has_atum = class_exists( '\\Atum\\Inc\\Helpers' );
        $has_any_atum_field = array_key_exists( 'purchase_price', $fields )
            || array_key_exists( 'supplier_id', $fields )
            || array_key_exists( 'atum_locations', $fields );

        if ( $has_any_atum_field && ! $has_atum ) {
            return new \WP_Error( 'lokey_inv_atum_missing', 'ATUM plugin not available.', [ 'status' => 500 ] );
        }

        if ( $has_any_atum_field ) {

            $atum_product = \Atum\Inc\Helpers::get_atum_product( $product_id );
            if ( ! $atum_product || ! is_object( $atum_product ) ) {
                return new \WP_Error( 'lokey_inv_atum_product_missing', 'ATUM product wrapper not available for this product.', [ 'status' => 500 ] );
            }

            if ( array_key_exists( 'purchase_price', $fields ) && method_exists( $atum_product, 'set_purchase_price' ) ) {
                $val = $fields['purchase_price'];
                if ( $val !== '' && ! is_null( $val ) ) {
                    $atum_product->set_purchase_price( floatval( $val ) );
                    $updated['purchase_price'] = floatval( $val );
                }
            }

            if ( array_key_exists( 'supplier_id', $fields ) && method_exists( $atum_product, 'set_supplier_id' ) ) {
                $val = $fields['supplier_id'];
                if ( $val !== '' && ! is_null( $val ) ) {
                    $atum_product->set_supplier_id( absint( $val ) );
                    $updated['supplier_id'] = absint( $val );
                }
            }

            // Save ATUM table fields.
            if ( method_exists( $atum_product, 'save_atum_data' ) ) {
                if ( class_exists( '\\Atum\\Components\\AtumCache' ) ) {
                    \Atum\Components\AtumCache::disable_cache();
                }
                $atum_product->save_atum_data();
                if ( class_exists( '\\Atum\\Components\\AtumCache' ) ) {
                    \Atum\Components\AtumCache::enable_cache();
                }
            }

            // Update ATUM locations taxonomy (stored as terms).
            if ( array_key_exists( 'atum_locations', $fields ) ) {
                $raw = $fields['atum_locations'];
                $norm = lokey_inv_normalize_atum_locations( $raw );
                if ( is_array( $norm ) ) {
                    $term_ids = [];
                    foreach ( $norm as $loc ) {
                        if ( is_array( $loc ) && isset( $loc['id'] ) ) {
                            $term_ids[] = absint( $loc['id'] );
                        }
                    }
                    $term_ids = array_values( array_filter( array_unique( $term_ids ) ) );

                    $taxonomy = 'atum_location';
                    if ( class_exists( '\\Atum\\Inc\\Globals' ) ) {
                        $taxonomy = \Atum\Inc\Globals::PRODUCT_LOCATION_TAXONOMY;
                    }

                    wp_set_object_terms( $product_id, $term_ids, $taxonomy, false );
                    $updated['atum_location_ids'] = $term_ids;
                }
            }

        }

        return $updated;
    }
}

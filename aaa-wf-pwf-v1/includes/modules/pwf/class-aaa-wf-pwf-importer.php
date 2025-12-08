<?php
/**
 * class-aaa-wf-pwf-importer.php
 *
 * Contains logic to parse the Weedmaps CSV, match/create products, update meta, and call the indexer.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_WF_PWF_Importer {

    /**
     * Reads a CSV file, processes each row, and:
     * - Matches existing products by SKU or lkd_wm_new_sku
     * - Creates new products when no match
     * - Updates all 23 fields as post meta
     * - Updates WooCommerce price/stock meta
     * - Calls the indexer to upsert the record
     *
     * @param string $file_path Absolute path to the uploaded CSV file.
     * @return array|WP_Error   Returns an array with keys 'created', 'updated', 'skipped', or WP_Error on failure.
     */
    public static function import_from_csv( $file_path ) {
        if ( ! file_exists( $file_path ) ) {
            return new WP_Error( 'file_missing', __( 'CSV file not found.', 'aaa-wf-pwf' ) );
        }

        global $wpdb;
        $created_count = 0;
        $updated_count = 0;
        $skipped_count = 0;

        if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
            $header = fgetcsv( $handle );
            if ( ! is_array( $header ) || count( $header ) === 0 ) {
                fclose( $handle );
                return new WP_Error( 'invalid_csv', __( 'CSV header row is missing or invalid.', 'aaa-wf-pwf' ) );
            }

            while ( ( $row = fgetcsv( $handle ) ) !== false ) {
                $data = array_combine( $header, $row );
                if ( ! is_array( $data ) || empty( $data['id'] ) ) {
                    $skipped_count++;
                    continue;
                }

                $wm_id = sanitize_text_field( $data['id'] );
                if ( '' === $wm_id ) {
                    $skipped_count++;
                    continue;
                }

                // 1) Attempt to find product by SKU (_sku)
                $product_id = wc_get_product_id_by_sku( $wm_id );
                $found_by   = '';

                if ( $product_id ) {
                    $found_by = 'sku';
                } else {
                    // 2) Fallback: find by meta 'lkd_wm_new_sku'
                    $meta_key = 'lkd_wm_new_sku';
                    $found_id = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                            $meta_key,
                            $wm_id
                        )
                    );
                    if ( $found_id ) {
                        $product_id = intval( $found_id );
                        $found_by   = 'lkd';
                    }
                }

                $was_created = false;
                if ( ! $product_id ) {
                    // 3) No existing product → create new WooCommerce product
                    $post_data = array(
                        'post_title'   => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : 'Unnamed Product',
                        'post_name'    => isset( $data['slug'] ) && $data['slug'] !== ''
                            ? sanitize_title( $data['slug'] )
                            : sanitize_title( $data['name'] ),
                        'post_content' => '',
                        'post_excerpt' => isset( $data['body'] ) ? sanitize_textarea_field( $data['body'] ) : '',
                        'post_status'  => 'private',
                        'post_type'    => 'product',
                    );
                    $new_id = wp_insert_post( $post_data );
                    if ( is_wp_error( $new_id ) ) {
                        $skipped_count++;
                        continue;
                    }
                    $product_id   = $new_id;
                    $was_created  = true;
                    $created_count++;

                    // Set SKU and lkd_wm_new_sku to the Weedmaps ID
                    update_post_meta( $product_id, '_sku',             $wm_id );
                    update_post_meta( $product_id, 'lkd_wm_new_sku',    $wm_id );

                    // Default stock management: 0 qty, out of stock
                    update_post_meta( $product_id, '_manage_stock', 'yes' );
                    update_post_meta( $product_id, '_stock',        0 );
                    update_post_meta( $product_id, '_stock_status', 'outofstock' );
                } else {
                    // Existing product found
                    if ( 'sku' === $found_by ) {
                        // If matched by SKU, ensure lkd_wm_new_sku is set
                        $existing_meta = get_post_meta( $product_id, 'lkd_wm_new_sku', true );
                        if ( '' === $existing_meta ) {
                            update_post_meta( $product_id, 'lkd_wm_new_sku', $wm_id );
                        }
                    }
                    // If matched by lkd_wm_new_sku, we do NOT overwrite the SKU
                    $updated_count++;
                }

                // Update all 23 fields (mapping from CSV header to meta key)
                $bool_val = function( $val ) {
                    return ( isset( $val ) && strtoupper( $val ) === 'TRUE' ) ? 1 : 0;
                };
                $meta_updates = array(
                    '_wm_id'            => $wm_id,
                    '_wm_external_id'   => isset( $data['external_id'] )  ? sanitize_text_field( $data['external_id'] ) : '',
                    '_wm_product_id'    => isset( $data['product_id'] )   ? sanitize_text_field( $data['product_id'] ) : '',
                    '_brand_id'         => isset( $data['brand_id'] )     ? sanitize_text_field( $data['brand_id'] ) : '',
                    'wm_brand_name'     => isset( $data['brand_name'] )   ? sanitize_text_field( $data['brand_name'] ) : '',
                    '_strain_id'        => isset( $data['strain_id'] )    ? sanitize_text_field( $data['strain_id'] ) : '',
                    'wm_strain_name'    => isset( $data['strain_name'] )  ? sanitize_text_field( $data['strain_name'] ) : '',
                    'wm_genetics'       => isset( $data['genetics'] )     ? sanitize_text_field( $data['genetics'] ) : '',
                    'wm_thc_percentage' => isset( $data['thc_percentage'] ) ? sanitize_text_field( $data['thc_percentage'] ) : '',
                    'wm_cbd_percentage' => isset( $data['cbd_percentage'] ) ? sanitize_text_field( $data['cbd_percentage'] ) : '',
                    'wm_license_type'   => isset( $data['license_type'] ) ? sanitize_text_field( $data['license_type'] ) : '',
                    'wm_price_currency' => isset( $data['price_currency'] ) ? sanitize_text_field( $data['price_currency'] ) : '',
                    'wm_unit_price'     => isset( $data['unit_price'] )   ? floatval( $data['unit_price'] ) : 0.00,
                    'wm_discount_type'  => isset( $data['price_rule_adjustment_type'] ) 
                                               ? sanitize_text_field( $data['price_rule_adjustment_type'] )
                                               : '',
                    'wm_discount_value' => isset( $data['price_rule_adjustment_value'] ) 
                                               ? sanitize_text_field( $data['price_rule_adjustment_value'] )
                                               : '',
                    'wm_online_orderable' => $bool_val( $data['online_orderable'] ),
                    'wm_published'        => $bool_val( $data['published'] ),
                    'wm_created_at'       => isset( $data['created_at'] ) && ! empty( $data['created_at'] )
                                              ? sanitize_text_field( $data['created_at'] )
                                              : current_time( 'mysql' ),
                    'wm_updated_at'       => isset( $data['updated_at'] ) && ! empty( $data['updated_at'] )
                                              ? sanitize_text_field( $data['updated_at'] )
                                              : current_time( 'mysql' ),
                    'wm_category_raw'     => isset( $data['categories'] ) ? sanitize_text_field( $data['categories'] ) : '',
                    'wm_tags_raw'         => isset( $data['tags'] ) ? sanitize_text_field( $data['tags'] ) : '',
                    'wm_og_name'          => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
                    'wm_og_slug'          => isset( $data['slug'] ) ? sanitize_text_field( $data['slug'] ) : '',
                    'wm_og_body'          => isset( $data['body'] ) ? sanitize_textarea_field( $data['body'] ) : '',
                );

                foreach ( $meta_updates as $meta_key => $value ) {
                    update_post_meta( $product_id, $meta_key, $value );
                }

                // Pricing logic (regular + sale)
                $unit_price = floatval( $meta_updates['wm_unit_price'] );
                update_post_meta( $product_id, '_regular_price', $unit_price );
                update_post_meta( $product_id, '_price',         $unit_price );

                if ( 'percentage' === $meta_updates['wm_discount_type'] && $unit_price > 0 ) {
                    $discount_perc = floatval( $meta_updates['wm_discount_value'] );
                    $sale_price   = $unit_price - ( $unit_price * $discount_perc / 100 );
                    update_post_meta( $product_id, '_sale_price', $sale_price );
                    update_post_meta( $product_id, '_price',      $sale_price );
                } else {
                    delete_post_meta( $product_id, '_sale_price' );
                }

                // Update the index table
                AAA_WF_PWF_Index::upsert_index( $product_id, $data, $was_created );
            }

            fclose( $handle );
        } else {
            return new WP_Error( 'cannot_open', __( 'Unable to open the CSV file.', 'aaa-wf-pwf' ) );
        }

        return array(
            'created' => $created_count,
            'updated' => $updated_count,
            'skipped' => $skipped_count,
        );
    }
}

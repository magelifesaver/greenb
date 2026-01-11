<?php
/**
 * Extended Products CRUD endpoints for GPT automation.
 *
 * Adds safe creation and update routes that merge attributes, handle categories,
 * brands, descriptions, images, and ATUM fields via wp_atum_product_data.
 *
 * Routes:
 *   - POST /lokey-inventory/v1/products/extended
 *   - PUT  /lokey-inventory/v1/products/extended/{id}
 *
 * Non-destructive policy:
 * - Only fields explicitly provided are updated.
 * - ATUM fields are updated via lokey_inv_update_atum_product_data() (no raw meta writes).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    /**
     * POST /products/extended — Create new product safely
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/extended', [
        'methods'  => 'POST',
        'callback' => function ( WP_REST_Request $req ) {

            $body = $req->get_json_params() ?: [];

            // Extract fields we handle locally (avoid Woo REST doing its own imports/side-effects).
            $images = [];
            if ( isset( $body['images'] ) && is_array( $body['images'] ) ) {
                $images = $body['images'];
                unset( $body['images'] );
            }

            $brands = [];
            if ( isset( $body['brands'] ) && is_array( $body['brands'] ) ) {
                $brands = $body['brands'];
                unset( $body['brands'] );
            }

            // ATUM fields are applied AFTER product creation (guarantees wp_atum_product_data write).
            $atum_update = [];

            if ( array_key_exists( 'purchase_price', $body ) ) {
                $atum_update['purchase_price'] = $body['purchase_price'];
                unset( $body['purchase_price'] );
            }
            if ( array_key_exists( 'supplier_id', $body ) ) {
                $atum_update['supplier_id'] = $body['supplier_id'];
                unset( $body['supplier_id'] );
            }

            // Locations can come in as atum_locations or friendlier aliases.
            $loc_raw = null;
            if ( array_key_exists( 'atum_locations', $body ) ) {
                $loc_raw = $body['atum_locations'];
                unset( $body['atum_locations'] );
            } elseif ( array_key_exists( 'location_ids', $body ) ) {
                $loc_raw = $body['location_ids'];
                unset( $body['location_ids'] );
            } elseif ( array_key_exists( 'atum_location_ids', $body ) ) {
                $loc_raw = $body['atum_location_ids'];
                unset( $body['atum_location_ids'] );
            } elseif ( array_key_exists( 'location_id', $body ) ) {
                $loc_raw = $body['location_id'];
                unset( $body['location_id'] );
            } elseif ( array_key_exists( 'atum_location_id', $body ) ) {
                $loc_raw = $body['atum_location_id'];
                unset( $body['atum_location_id'] );
            }

						if ( is_null( $loc_raw ) ) {
						    $loc_raw = 1194;
						}

						$loc_norm = lokey_inv_normalize_atum_locations( $loc_raw );
						if ( ! is_null( $loc_norm ) ) {
						    $atum_update['atum_locations'] = $loc_norm;
						}

            // Defaults & Safety
            $body['type']           = $body['type'] ?? 'simple';
            $body['manage_stock']   = true;
            $body['stock_quantity'] = $body['stock_quantity'] ?? 0;
            $body['status']         = $body['status'] ?? 'publish';

            // Sale Price Calculation
            if ( ! empty( $body['discount_percent'] ) && ! empty( $body['regular_price'] ) ) {
                $regular  = floatval( $body['regular_price'] );
                $discount = floatval( $body['discount_percent'] );
                $sale     = round( $regular * ( 1 - ( $discount / 100 ) ), 2 );

                // Woo requires string prices.
                $body['regular_price'] = number_format( $regular, 2, '.', '' );
                $body['sale_price']    = number_format( $sale, 2, '.', '' );
            } else {
                unset( $body['sale_price'] );
            }
            unset( $body['discount_percent'] );

            // Attribute Handling
            if ( ! empty( $body['attributes'] ) ) {
                foreach ( $body['attributes'] as &$attr ) {
                    $attr['visible']   = true;
                    $attr['variation'] = false;
                }
                unset( $attr );
            }

            // Create Product via WooCommerce API
            $res  = lokey_inv_request( 'products', 'POST', $body );
            $code = $res['code'] ?? 500;
            $data = $res['body'] ?? [];

            if ( $code >= 400 || empty( $data['id'] ) ) {
                return new WP_REST_Response( [
                    'status'  => 'error',
                    'code'    => $code,
                    'message' => 'Product creation failed',
                    'data'    => $data,
                ], $code );
            }

            $id = absint( $data['id'] );

            // Image Handling (custom sideload with name normalization + de-dup)
            if ( ! empty( $images ) && is_array( $images ) ) {

                if ( ! function_exists( 'download_url' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }
                if ( ! function_exists( 'media_handle_sideload' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                }

                $primary = $images[0]['src'] ?? null;

                if ( ! empty( $primary ) ) {

                    $image_url = esc_url_raw( $primary );
										if ( strpos( $image_url, '?' ) !== false ) {
										    $image_url = strtok( $image_url, '?' );
										}


                    // Normalize product name for SEO-safe filenames
                    $raw_name     = $data['name'] ?? ( $body['name'] ?? 'product-image' );
                    $product_name = sanitize_file_name( sanitize_title_with_dashes( $raw_name ) );

                    // Default to JPG if no extension found
                    $file_ext = strtolower( pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'jpg' );

                    // Check for existing imported image (prevents duplicates)
                    $existing = get_posts( [
                        'post_type'      => 'attachment',
                        'meta_key'       => '_source_image_url',
                        'meta_value'     => $image_url,
                        'post_parent'    => $id,
                        'posts_per_page' => 1,
                        'fields'         => 'ids',
                    ] );

                    if ( ! empty( $existing ) ) {
                        set_post_thumbnail( $id, $existing[0] );
                    } else {

                        $tmp_file = download_url( $image_url );

                        if ( ! is_wp_error( $tmp_file ) ) {

                            $file_array = [
                                'name'     => "{$product_name}.{$file_ext}",
                                'tmp_name' => $tmp_file,
                            ];

                            $image_id = media_handle_sideload( $file_array, $id );

                            if ( ! is_wp_error( $image_id ) ) {

                                set_post_thumbnail( $id, $image_id );

                                wp_update_post( [
                                    'ID'         => $image_id,
                                    'post_title' => ( $data['name'] ?? $raw_name ) . ' Image',
                                    'post_name'  => $product_name,
                                ] );

                                update_post_meta( $image_id, '_source_image_url', $image_url );
																$orig_basename = wp_basename( parse_url( $image_url, PHP_URL_PATH ) );

																if ( ! empty( $orig_basename ) ) {

																    $dupes = get_posts( [
																        'post_type'      => 'attachment',
																        'post_parent'    => $id,
																        'posts_per_page' => 20,
																        'fields'         => 'ids',
																        'meta_query'     => [
																            [
																                'key'     => '_wp_attached_file',
																                'value'   => $orig_basename,
																                'compare' => 'LIKE',
																            ],
																        ],
																    ] );

																    if ( ! empty( $dupes ) ) {
																        foreach ( $dupes as $dup_id ) {
																            $dup_id = absint( $dup_id );
																            if ( $dup_id && $dup_id !== absint( $image_id ) ) {
																                wp_delete_attachment( $dup_id, true );
																            }
																        }
																    }
																}


                                // Optional gallery images beyond the first
                                if ( count( $images ) > 1 ) {
                                    $gallery_ids = [];
                                    $i = 0;

                                    foreach ( array_slice( $images, 1 ) as $img ) {
                                        $gallery_url = esc_url_raw( $img['src'] ?? '' );
																				if ( strpos( $gallery_url, '?' ) !== false ) {
																				    $gallery_url = strtok( $gallery_url, '?' );
																				}

                                        if ( empty( $gallery_url ) ) {
                                            continue;
                                        }

                                        $tmp_gallery = download_url( $gallery_url );
                                        if ( is_wp_error( $tmp_gallery ) ) {
                                            continue;
                                        }

                                        $i++;
                                        $gallery_file = [
                                            'name'     => "{$product_name}-gallery-{$i}.{$file_ext}",
                                            'tmp_name' => $tmp_gallery,
                                        ];

                                        $gallery_id = media_handle_sideload( $gallery_file, $id );
                                        if ( ! is_wp_error( $gallery_id ) ) {
                                            $gallery_ids[] = $gallery_id;
                                            update_post_meta( $gallery_id, '_source_image_url', $gallery_url );
                                        } else {
                                            @unlink( $gallery_file['tmp_name'] );
                                        }
                                    }

                                    if ( ! empty( $gallery_ids ) ) {
                                        update_post_meta( $id, '_product_image_gallery', implode( ',', $gallery_ids ) );
                                    }
                                }

                            } else {
                                @unlink( $file_array['tmp_name'] );
                            }
                        }
                    }
                }
            }

            // Assign BeRocket Brand (if provided)
            if ( ! empty( $brands ) && is_array( $brands ) ) {
                foreach ( $brands as $brand ) {
                    if ( ! empty( $brand['id'] ) ) {
                        wp_set_object_terms( $id, (int) $brand['id'], 'berocket_brand', false );
                    }
                }
            }

            // Apply ATUM fields (purchase_price, supplier_id, atum_locations) without raw meta writes
            $atum_applied = null;
            if ( ! empty( $atum_update ) || array_key_exists( 'atum_locations', $atum_update ) ) {
                $atum_applied = lokey_inv_update_atum_product_data( $id, $atum_update );
            }

            return new WP_REST_Response( [
                'version'      => LOKEY_INV_API_VERSION,
                'status'       => 'success',
                'action'       => 'created',
                'id'           => $id,
                'data'         => $data,
                'atum_applied' => is_wp_error( $atum_applied ) ? [
                    'status'  => 'error',
                    'error'   => $atum_applied->get_error_code(),
                    'message' => $atum_applied->get_error_message(),
                ] : $atum_applied,
                'timestamp'    => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
    ] );

    /**
     * PUT /products/extended/{id} — Update product (merge attributes + descriptions)
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/extended/(?P<id>\d+)', [
        'methods'  => 'PUT',
        'callback' => function ( WP_REST_Request $req ) {

            $id   = absint( $req['id'] );
            $body = $req->get_json_params() ?: [];

            if ( $id <= 0 ) {
                return new WP_REST_Response( [
                    'status'  => 'error',
                    'message' => 'Invalid product ID.',
                ], 400 );
            }

            // Extract fields we handle locally.
            $images = [];
            if ( array_key_exists( 'images', $body ) && is_array( $body['images'] ) ) {
                $images = $body['images'];
                unset( $body['images'] );
            }

            $brands = [];
            if ( array_key_exists( 'brands', $body ) && is_array( $body['brands'] ) ) {
                $brands = $body['brands'];
                unset( $body['brands'] );
            }

            // ATUM fields applied after the Woo update (writes to wp_atum_product_data).
            $atum_update = [];

            if ( array_key_exists( 'purchase_price', $body ) ) {
                $atum_update['purchase_price'] = $body['purchase_price'];
                unset( $body['purchase_price'] );
            }
            if ( array_key_exists( 'supplier_id', $body ) ) {
                $atum_update['supplier_id'] = $body['supplier_id'];
                unset( $body['supplier_id'] );
            }

            $loc_raw = null;
            if ( array_key_exists( 'atum_locations', $body ) ) {
                $loc_raw = $body['atum_locations'];
                unset( $body['atum_locations'] );
            } elseif ( array_key_exists( 'location_ids', $body ) ) {
                $loc_raw = $body['location_ids'];
                unset( $body['location_ids'] );
            } elseif ( array_key_exists( 'atum_location_ids', $body ) ) {
                $loc_raw = $body['atum_location_ids'];
                unset( $body['atum_location_ids'] );
            } elseif ( array_key_exists( 'location_id', $body ) ) {
                $loc_raw = $body['location_id'];
                unset( $body['location_id'] );
            } elseif ( array_key_exists( 'atum_location_id', $body ) ) {
                $loc_raw = $body['atum_location_id'];
                unset( $body['atum_location_id'] );
            }

            $loc_norm = lokey_inv_normalize_atum_locations( $loc_raw );
            if ( ! is_null( $loc_norm ) ) {
                $atum_update['atum_locations'] = $loc_norm;
            }

            // Sale Price Calculation
            if ( ! empty( $body['discount_percent'] ) && ! empty( $body['regular_price'] ) ) {
                $regular  = floatval( $body['regular_price'] );
                $discount = floatval( $body['discount_percent'] );
                $sale     = round( $regular * ( 1 - ( $discount / 100 ) ), 2 );

                $body['regular_price'] = number_format( $regular, 2, '.', '' );
                $body['sale_price']    = number_format( $sale, 2, '.', '' );
            } else {
                unset( $body['sale_price'] );
            }
            unset( $body['discount_percent'] );

            // Allow description updates
            if ( isset( $body['description'] ) ) {
                $body['description'] = wp_kses_post( $body['description'] );
            }
            if ( isset( $body['short_description'] ) ) {
                $body['short_description'] = wp_kses_post( $body['short_description'] );
            }

            // Attribute Safety
            if ( ! empty( $body['attributes'] ) ) {
                foreach ( $body['attributes'] as &$attr ) {
                    $attr['visible']   = true;
                    $attr['variation'] = false;
                }
                unset( $attr );
            }

            // Forward PUT to WooCommerce
            $res  = lokey_inv_request( "products/{$id}", 'PUT', $body );
            $code = $res['code'] ?? 500;
            $data = $res['body'] ?? [];

            if ( $code >= 400 ) {
                return new WP_REST_Response( [
                    'status'  => 'error',
                    'code'    => $code,
                    'message' => 'Product update failed',
                    'data'    => $data,
                ], $code );
            }

            // Image handling
            if ( ! empty( $images ) && is_array( $images ) ) {

                if ( ! function_exists( 'download_url' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }
                if ( ! function_exists( 'media_handle_sideload' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                }

                $primary = $images[0]['src'] ?? null;
                if ( ! empty( $primary ) ) {

                    $image_url = esc_url_raw( $primary );
										if ( strpos( $image_url, '?' ) !== false ) {
										    $image_url = strtok( $image_url, '?' );
										}

                    $raw_name  = $data['name'] ?? ( $body['name'] ?? 'product-image' );
                    $product_name = sanitize_file_name( sanitize_title_with_dashes( $raw_name ) );
                    $file_ext = strtolower( pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'jpg' );

                    $existing = get_posts( [
                        'post_type'      => 'attachment',
                        'meta_key'       => '_source_image_url',
                        'meta_value'     => $image_url,
                        'post_parent'    => $id,
                        'posts_per_page' => 1,
                        'fields'         => 'ids',
                    ] );

                    if ( ! empty( $existing ) ) {
                        set_post_thumbnail( $id, $existing[0] );
                    } else {

                        $tmp_file = download_url( $image_url );
                        if ( ! is_wp_error( $tmp_file ) ) {

                            $file_array = [
                                'name'     => "{$product_name}.{$file_ext}",
                                'tmp_name' => $tmp_file,
                            ];

                            $image_id = media_handle_sideload( $file_array, $id );
                            if ( ! is_wp_error( $image_id ) ) {

                                set_post_thumbnail( $id, $image_id );

                                wp_update_post( [
                                    'ID'         => $image_id,
                                    'post_title' => ( $data['name'] ?? $raw_name ) . ' Image',
                                    'post_name'  => $product_name,
                                ] );

                                update_post_meta( $image_id, '_source_image_url', $image_url );
																$orig_basename = wp_basename( parse_url( $image_url, PHP_URL_PATH ) );

																if ( ! empty( $orig_basename ) ) {

																    $dupes = get_posts( [
																        'post_type'      => 'attachment',
																        'post_parent'    => $id,
																        'posts_per_page' => 20,
																        'fields'         => 'ids',
																        'meta_query'     => [
																            [
																                'key'     => '_wp_attached_file',
																                'value'   => $orig_basename,
																                'compare' => 'LIKE',
																            ],
																        ],
																    ] );

																    if ( ! empty( $dupes ) ) {
																        foreach ( $dupes as $dup_id ) {
																            $dup_id = absint( $dup_id );
																            if ( $dup_id && $dup_id !== absint( $image_id ) ) {
																                wp_delete_attachment( $dup_id, true );
																            }
																        }
																    }
																}


                            } else {
                                @unlink( $file_array['tmp_name'] );
                            }
                        }
                    }
                }
            }

            // Assign/update BeRocket Brand
            if ( ! empty( $brands ) && is_array( $brands ) ) {
                foreach ( $brands as $brand ) {
                    if ( ! empty( $brand['id'] ) ) {
                        wp_set_object_terms( $id, (int) $brand['id'], 'berocket_brand', false );
                    }
                }
            }

            // Apply ATUM fields (purchase_price, supplier_id, atum_locations) without raw meta writes
            $atum_applied = null;
            if ( ! empty( $atum_update ) || array_key_exists( 'atum_locations', $atum_update ) ) {
                $atum_applied = lokey_inv_update_atum_product_data( $id, $atum_update );
            }

            return new WP_REST_Response( [
                'version'      => LOKEY_INV_API_VERSION,
                'status'       => 'success',
                'action'       => 'updated',
                'id'           => $id,
                'data'         => $data,
                'atum_applied' => is_wp_error( $atum_applied ) ? [
                    'status'  => 'error',
                    'error'   => $atum_applied->get_error_code(),
                    'message' => $atum_applied->get_error_message(),
                ] : $atum_applied,
                'timestamp'    => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
    ] );

} );

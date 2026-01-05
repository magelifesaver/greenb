<?php
/**
 * Extended Products CRUD endpoints for GPT automation.
 *
 * Adds safe creation and update routes that merge attributes, handle categories,
 * brands, suppliers, descriptions, and sale pricing automatically.
 *
 * Routes:
 *   - POST /lokey-inventory/v1/products/extended
 *   - PUT  /lokey-inventory/v1/products/extended/{id}
 *
 * These endpoints use lokey_inv_request() to proxy WooCommerce/ATUM REST calls.
 * They are non-destructive: only fields explicitly provided are updated.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function() {

    /**
     * 🔹 POST /products/extended — Create new product safely
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/extended', [
        'methods'  => 'POST',
        'callback' => function( WP_REST_Request $req ) {

            $body = $req->get_json_params() ?: [];

            // --- Defaults & Safety ---
            $body['type']           = $body['type'] ?? 'simple';
            $body['manage_stock']   = true;
            $body['stock_quantity'] = $body['stock_quantity'] ?? 0;
            $body['status']         = $body['status'] ?? 'publish';

						// --- Sale Price Calculation ---
						if ( ! empty( $body['discount_percent'] ) && ! empty( $body['regular_price'] ) ) {
						    $regular  = floatval( $body['regular_price'] );
						    $discount = floatval( $body['discount_percent'] );

						    $sale = round( $regular * ( 1 - ( $discount / 100 ) ), 2 );

						    // Woo requires string prices, not floats.
						    $body['regular_price'] = number_format( $regular, 2, '.', '' );
						    $body['sale_price']    = number_format( $sale, 2, '.', '' );
						} else {
						    // If discount not provided, ensure sale_price is cleared.
						    unset( $body['sale_price'] );
						}

            // --- Attribute Handling ---
            if ( ! empty( $body['attributes'] ) ) {
                foreach ( $body['attributes'] as &$attr ) {
                    $attr['visible']   = true;
                    $attr['variation'] = false;
                }
                unset( $attr );
            }

            // --- Create Product via WooCommerce API ---
            $res = lokey_inv_request( 'products', 'POST', $body );
            $code = $res['code'] ?? 500;
            $data = $res['body'] ?? [];

            if ( $code >= 400 || empty( $data['id'] ) ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'code'    => $code,
                    'message' => 'Product creation failed',
                    'data'    => $data,
                ], $code );
            }

            $id = absint( $data['id'] );
						
						// --- Image Handling (Woo Standard) ---
						if ( ! empty( $body['images'] ) && is_array( $body['images'] ) ) {

						    $images = $body['images'];
						    $primary = $images[0]['src'] ?? null;

						    if ( ! empty( $primary ) ) {

						        $image_url = esc_url_raw( $primary );

						        // Normalize product name for SEO-safe filenames
						        $raw_name     = $body['name'] ?? 'product-image';
						        $product_name = sanitize_file_name( sanitize_title_with_dashes( $raw_name ) );

						        // Default to JPG if no extension found
						        $file_ext = strtolower( pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'jpg' );

						        // Check for existing imported image (prevents duplicates)
						        $existing = get_posts([
						            'post_type'      => 'attachment',
						            'meta_key'       => '_source_image_url',
						            'meta_value'     => $image_url,
						            'post_parent'    => $id,
						            'posts_per_page' => 1,
						            'fields'         => 'ids',
						        ]);

						        if ( ! empty( $existing ) ) {
						            // Image already exists — set as featured
						            set_post_thumbnail( $id, $existing[0] );
						        } else {
						            // Fetch and sideload the new image
						            $tmp_file = download_url( $image_url );

						            if ( ! is_wp_error( $tmp_file ) ) {

						                $file_array = [
						                    'name'     => "{$product_name}.{$file_ext}",
						                    'tmp_name' => $tmp_file,
						                ];

						                $image_id = media_handle_sideload( $file_array, $id );

						                if ( ! is_wp_error( $image_id ) ) {
						                    // Set as featured image
						                    set_post_thumbnail( $id, $image_id );

						                    // Update metadata for SEO clarity
						                    wp_update_post([
						                        'ID'         => $image_id,
						                        'post_title' => $body['name'] . ' Image',
						                        'post_name'  => $product_name,
						                    ]);

						                    // Save original URL to prevent future duplicates
						                    update_post_meta( $image_id, '_source_image_url', $image_url );

						                    // Optional: Handle gallery images beyond the first
						                    if ( count( $images ) > 1 ) {
						                        $gallery_ids = [];
						                        foreach ( array_slice( $images, 1 ) as $img ) {
						                            $gallery_url = esc_url_raw( $img['src'] ?? '' );
						                            if ( ! empty( $gallery_url ) ) {
						                                $tmp_gallery = download_url( $gallery_url );
						                                if ( ! is_wp_error( $tmp_gallery ) ) {
						                                    $gallery_file = [
						                                        'name'     => "{$product_name}-gallery.{$file_ext}",
						                                        'tmp_name' => $tmp_gallery,
						                                    ];
						                                    $gallery_id = media_handle_sideload( $gallery_file, $id );
						                                    if ( ! is_wp_error( $gallery_id ) ) {
						                                        $gallery_ids[] = $gallery_id;
						                                        update_post_meta( $gallery_id, '_source_image_url', $gallery_url );
						                                    }
						                                }
						                            }
						                        }

						                        if ( ! empty( $gallery_ids ) ) {
						                            update_post_meta( $id, '_product_image_gallery', implode(',', $gallery_ids) );
						                        }
						                    }
						                } else {
						                    @unlink( $file_array['tmp_name'] );
						                }
						            }
						        }
						    }
						}


						// --- 🔹 ADD THIS HERE ---
						if ( ! empty( $body['brands'] ) && is_array( $body['brands'] ) ) {
						    foreach ( $body['brands'] as $brand ) {
						        if ( ! empty( $brand['id'] ) ) {
						            wp_set_object_terms( $id, (int) $brand['id'], 'berocket_brand', false );
						        }
						    }
						}

            // --- Optional: Supplier Link ---
            if ( ! empty( $body['supplier_id'] ) ) {
                update_post_meta( $id, '_supplier_id', absint( $body['supplier_id'] ) );
            }

            return new WP_REST_Response([
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'action'    => 'created',
                'id'        => $id,
                'data'      => $data,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
    ] );

    /**
     * 🔹 PUT /products/extended/{id} — Update product (merge attributes + descriptions)
     */
    register_rest_route( LOKEY_INV_API_NS, '/products/extended/(?P<id>\d+)', [
        'methods'  => 'PUT',
        'callback' => function( WP_REST_Request $req ) {

            $id   = absint( $req['id'] );
            $body = $req->get_json_params() ?: [];

						// --- Sale Price Calculation ---
						if ( ! empty( $body['discount_percent'] ) && ! empty( $body['regular_price'] ) ) {
						    $regular  = floatval( $body['regular_price'] );
						    $discount = floatval( $body['discount_percent'] );

						    $sale = round( $regular * ( 1 - ( $discount / 100 ) ), 2 );

						    // Woo requires string prices, not floats.
						    $body['regular_price'] = number_format( $regular, 2, '.', '' );
						    $body['sale_price']    = number_format( $sale, 2, '.', '' );
						} else {
						    // If discount not provided, ensure sale_price is cleared.
						    unset( $body['sale_price'] );
						}

            if ( $id <= 0 ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'message' => 'Invalid product ID.',
                ], 400 );
            }

            // --- Allow description updates ---
            if ( isset( $body['description'] ) ) {
                $body['description'] = wp_kses_post( $body['description'] );
            }
            if ( isset( $body['short_description'] ) ) {
                $body['short_description'] = wp_kses_post( $body['short_description'] );
            }

            // --- Attribute Safety ---
            if ( ! empty( $body['attributes'] ) ) {
                foreach ( $body['attributes'] as &$attr ) {
                    $attr['visible']   = true;
                    $attr['variation'] = false;
                }
                unset( $attr );
            }

            // --- Forward PUT to WooCommerce ---
            $res  = lokey_inv_request( "products/{$id}", 'PUT', $body );
            $code = $res['code'] ?? 500;
            $data = $res['body'] ?? [];

            if ( $code >= 400 ) {
                return new WP_REST_Response([
                    'status'  => 'error',
                    'code'    => $code,
                    'message' => 'Product update failed',
                    'data'    => $data,
                ], $code );
            }

            // --- Supplier Linking (if provided) ---
            if ( ! empty( $body['supplier_id'] ) ) {
                update_post_meta( $id, '_supplier_id', absint( $body['supplier_id'] ) );
            }
						
						// --- Image Handling (Woo Standard) ---
						if ( ! empty( $body['images'] ) && is_array( $body['images'] ) ) {

						    $images = $body['images'];
						    $primary = $images[0]['src'] ?? null;

						    if ( ! empty( $primary ) ) {

						        $image_url = esc_url_raw( $primary );

						        // Normalize product name for SEO-safe filenames
						        $raw_name     = $body['name'] ?? 'product-image';
						        $product_name = sanitize_file_name( sanitize_title_with_dashes( $raw_name ) );

						        // Default to JPG if no extension found
						        $file_ext = strtolower( pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'jpg' );

						        // Check for existing imported image (prevents duplicates)
						        $existing = get_posts([
						            'post_type'      => 'attachment',
						            'meta_key'       => '_source_image_url',
						            'meta_value'     => $image_url,
						            'post_parent'    => $id,
						            'posts_per_page' => 1,
						            'fields'         => 'ids',
						        ]);

						        if ( ! empty( $existing ) ) {
						            // Image already exists — set as featured
						            set_post_thumbnail( $id, $existing[0] );
						        } else {
						            // Fetch and sideload the new image
						            $tmp_file = download_url( $image_url );

						            if ( ! is_wp_error( $tmp_file ) ) {

						                $file_array = [
						                    'name'     => "{$product_name}.{$file_ext}",
						                    'tmp_name' => $tmp_file,
						                ];

						                $image_id = media_handle_sideload( $file_array, $id );

						                if ( ! is_wp_error( $image_id ) ) {
						                    // Set as featured image
						                    set_post_thumbnail( $id, $image_id );

						                    // Update metadata for SEO clarity
						                    wp_update_post([
						                        'ID'         => $image_id,
						                        'post_title' => $body['name'] . ' Image',
						                        'post_name'  => $product_name,
						                    ]);

						                    // Save original URL to prevent future duplicates
						                    update_post_meta( $image_id, '_source_image_url', $image_url );

						                    // Optional: Handle gallery images beyond the first
						                    if ( count( $images ) > 1 ) {
						                        $gallery_ids = [];
						                        foreach ( array_slice( $images, 1 ) as $img ) {
						                            $gallery_url = esc_url_raw( $img['src'] ?? '' );
						                            if ( ! empty( $gallery_url ) ) {
						                                $tmp_gallery = download_url( $gallery_url );
						                                if ( ! is_wp_error( $tmp_gallery ) ) {
						                                    $gallery_file = [
						                                        'name'     => "{$product_name}-gallery.{$file_ext}",
						                                        'tmp_name' => $tmp_gallery,
						                                    ];
						                                    $gallery_id = media_handle_sideload( $gallery_file, $id );
						                                    if ( ! is_wp_error( $gallery_id ) ) {
						                                        $gallery_ids[] = $gallery_id;
						                                        update_post_meta( $gallery_id, '_source_image_url', $gallery_url );
						                                    }
						                                }
						                            }
						                        }

						                        if ( ! empty( $gallery_ids ) ) {
						                            update_post_meta( $id, '_product_image_gallery', implode(',', $gallery_ids) );
						                        }
						                    }
						                } else {
						                    @unlink( $file_array['tmp_name'] );
						                }
						            }
						        }
						    }
						}

						
						// --- Assign or update BeRocket Brand on update ---
						if ( ! empty( $body['brands'] ) && is_array( $body['brands'] ) ) {
						    foreach ( $body['brands'] as $brand ) {
						        if ( ! empty( $brand['id'] ) ) {
						            wp_set_object_terms( $id, (int) $brand['id'], 'berocket_brand', false );
						        }
						    }
						}

            return new WP_REST_Response([
                'version'   => LOKEY_INV_API_VERSION,
                'status'    => 'success',
                'action'    => 'updated',
                'id'        => $id,
                'data'      => $data,
                'timestamp' => current_time( 'mysql' ),
            ], 200 );
        },
        'permission_callback' => '__return_true',
    ] );

});

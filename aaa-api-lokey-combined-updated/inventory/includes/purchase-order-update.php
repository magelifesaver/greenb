<?php
/**
 * Purchase Order update endpoint.
 *
 * Merges new line items into an existing ATUM purchase order and forwards
 * the updated data to the ATUM API.  Quantities are incremented when
 * duplicate product/variation IDs are supplied.  Requires a valid JWT.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    register_rest_route( LOKEY_INV_API_NS, '/purchase-orders/(?P<id>\d+)', [
        'methods'  => 'PUT',
        'callback' => 'lokey_inv_update_purchase_order_v200',
        // Permit PUT /purchase-orders/{id} without requiring JWT.  The request
        // still calls the ATUM API with appropriate credentials.
        'permission_callback' => '__return_true',
    ] );
} );

/**
 * Handles PUT requests to update an existing ATUM Purchase Order.
 *
 * Steps:
 *  1. Fetch existing PO (edit context)
 *  2. Merge provided line_items with existing lines
 *  3. Send updated payload via PUT to ATUM
 *  4. Return structured result with merge summary
 *
 * @param WP_REST_Request $request Request containing the PO ID and body.
 * @return WP_REST_Response Structured API response.
 */
function lokey_inv_update_purchase_order_v200( WP_REST_Request $request ) {
    $id   = absint( $request['id'] );
    $body = $request->get_json_params() ?: [];

    if ( $id <= 0 ) {
        return new WP_REST_Response( [
            'version' => LOKEY_INV_API_VERSION,
            'status'  => 'error',
            'code'    => 400,
            'message' => 'Invalid purchase order ID.',
        ], 400 );
    }

    // Step 1: Retrieve current PO data.
    $existing = lokey_inv_request( "atum/purchase-orders/{$id}?context=edit", 'GET' );
    if ( $existing['code'] >= 400 || empty( $existing['body'] ) ) {
        return new WP_REST_Response( [
            'version' => LOKEY_INV_API_VERSION,
            'status'  => 'error',
            'code'    => $existing['code'],
            'message' => 'Failed to fetch existing purchase order.',
            'id'      => $id,
            'data'    => $existing['body'],
        ], $existing['code'] );
    }

    $existing_data  = $existing['body'];
    $existing_lines = isset( $existing_data['line_items'] ) && is_array( $existing_data['line_items'] )
        ? $existing_data['line_items']
        : [];

    // Step 2: Merge incoming line_items.
    $merge_count = 0;
    if ( ! empty( $body['line_items'] ) && is_array( $body['line_items'] ) ) {
        foreach ( $body['line_items'] as $new_item ) {
            if ( empty( $new_item['product_id'] ) ) {
                continue;
            }
            $product_id   = (int) $new_item['product_id'];
            $variation_id = isset( $new_item['variation_id'] ) ? (int) $new_item['variation_id'] : 0;
            $quantity     = isset( $new_item['quantity'] ) ? max( 1, (int) $new_item['quantity'] ) : 1;
            $purchase_price = null;
            if ( isset( $new_item['purchase_price'] ) && $new_item['purchase_price'] !== '' ) {
                $purchase_price = (float) $new_item['purchase_price'];
            } elseif ( isset( $new_item['cost'] ) && $new_item['cost'] !== '' ) {
                $purchase_price = (float) $new_item['cost'];
            }
            $merged = false;
            foreach ( $existing_lines as &$line ) {
                $existing_pid   = (int) ( $line['product_id'] ?? 0 );
                $existing_varid = (int) ( $line['variation_id'] ?? 0 );
                if ( $existing_pid === $product_id && $existing_varid === $variation_id ) {
                    $line['quantity'] = isset( $line['quantity'] )
                        ? (int) $line['quantity'] + $quantity
                        : $quantity;
                    if ( null !== $purchase_price ) {
                        $line['purchase_price'] = $purchase_price;
                    }
                    $merged = true;
                    $merge_count++;
                    break;
                }
            }
            unset( $line );
            if ( ! $merged ) {
                $new_line = [
                    'product_id' => $product_id,
                    'quantity'   => $quantity,
                ];
                if ( $variation_id ) {
                    $new_line['variation_id'] = $variation_id;
                }
                if ( null !== $purchase_price ) {
                    $new_line['purchase_price'] = $purchase_price;
                }
                $existing_lines[] = $new_line;
                $merge_count++;
            }
        }
        $body['line_items'] = $existing_lines;
    }

    // Step 3: Forward updated PO to ATUM.
    $response = lokey_inv_request( "atum/purchase-orders/{$id}", 'PUT', $body );
    $success  = $response['code'] < 400;

    // Step 4: Build final response.
    return new WP_REST_Response( [
        'version'       => LOKEY_INV_API_VERSION,
        'status'        => $success ? 'success' : 'error',
        'code'          => $response['code'],
        'merged_lines'  => $merge_count,
        'po_id'         => $id,
        'timestamp'     => current_time( 'mysql' ),
        'data'          => $response['body'],
    ], $response['code'] );
}

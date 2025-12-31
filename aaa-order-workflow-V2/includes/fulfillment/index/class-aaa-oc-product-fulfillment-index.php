<?php
/**
 * 
 * FilePath: plugins/aaa-order-workflow/includes/indexers/class-aaa-oc-product-fulfillment-index.php
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Product_Fulfillment_Index {

    public static function update_product_fulfillment( $order_or_id ) {
        global $wpdb;
        $order = is_a( $order_or_id, 'WC_Order' ) ? $order_or_id : wc_get_order( $order_or_id );
        if ( ! $order ) {
            return;
        }
        $order_id = $order->get_id();

        // Gather item & brand data
        $brands    = [];
        $item_list = [];
        foreach ( $order->get_items() as $item ) {
            if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
                $pid = $item->get_product_id();
                $bterms = wp_get_post_terms( $pid, 'berocket_brand' );
                $bname  = '';
                if ( ! empty($bterms) && ! is_wp_error($bterms) ) {
                    $bname = $bterms[0]->name;
                    $brands[$bterms[0]->term_id] = $bname;
                }
                $product_obj = $item->get_product();
                $sku         = $product_obj ? $product_obj->get_sku() : '';
                $item_list[] = [
                    'name'     => $item->get_name(),
                    'brand'    => $bname,
                    'sku'      => $sku,
                    'quantity' => (int) $item->get_quantity(),
                    'subtotal' => (float) $item->get_subtotal(),
                    'total'    => (float) $item->get_total(),
                ];
            }
        }
        $brand_list = implode( ', ', array_unique( $brands ) );
        $items_json = wp_json_encode( $item_list );

        // Coupons
        $coupons_arr  = $order->get_coupon_codes();
        $coupons_json = wp_json_encode( $coupons_arr );

        // Fulfillment data
        $picked_items_meta = get_post_meta( $order_id, '_aaa_picked_items', true );
        $picked_items = ( is_array($picked_items_meta) ) ? wp_json_encode($picked_items_meta) : null;

        // Decide fulfillment_status if you want
        $status = $order->get_status();
        $fulfillment_status = 'not_picked';
        if ( $status === 'processing' && ! empty($picked_items_meta) ) {
            $all_picked = true;
            foreach ( $order->get_items() as $fulfill_item ) {
                $prod_obj = $fulfill_item->get_product();
                $sku      = $prod_obj ? $prod_obj->get_sku() : null;
                $qty_ordered = (int) $fulfill_item->get_quantity();
                $qty_picked  = isset($picked_items_meta[$sku]) ? (int)$picked_items_meta[$sku] : 0;
                if ( $qty_picked < $qty_ordered ) {
                    $all_picked = false;
                    break;
                }
            }
            if ( $all_picked ) {
                $fulfillment_status = 'fully_picked';
            }
        } else {
            // If not in "processing," assume fully_picked if there's data?
            $fulfillment_status = ! empty($picked_items_meta) ? 'fully_picked' : 'not_picked';
        }

        // Possibly also store "usbs_order_fulfillment_data" from meta
        $usbs_data = get_post_meta( $order_id, 'usbs_order_fulfillment_data', true );
        $usbs_fulfillment_data = is_array($usbs_data) ? wp_json_encode($usbs_data) : '';

        // Build an array of just the columns related to product & fulfillment
        $data = [
            'brand_list'          => $brand_list,
            'items'               => $items_json,
            'coupons'             => $coupons_json,
            'picked_items'        => $picked_items,
            'fulfillment_status'  => $fulfillment_status,
            'usbs_order_fulfillment_data' => $usbs_fulfillment_data,
        ];

        // Update in the same single table
        $table_name = $wpdb->prefix . 'aaa_oc_order_index';
        $wpdb->update(
            $table_name,
            $data,
            [ 'order_id' => $order_id ],
            null,
            [ '%d' ]
        );
    }
}

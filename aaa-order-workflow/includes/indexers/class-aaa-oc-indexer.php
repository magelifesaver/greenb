<?php
/**
 * Class: AAA_OC_Indexer
 * File Path: /aaa-order-workflow/includes/indexers/class-aaa-oc-indexer.php
 * Purpose: Indexes individual WooCommerce orders into `aaa_oc_order_index`,
 * including customer meta, product brand, payment snapshots, and user flags.
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Indexing {

    private static string $table_name;

    public function __construct() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'aaa_oc_order_index';
    }

    /**
     * Index a single WooCommerce order by ID.
     * @param int $order_id
     * @return bool
     */
    public function index_order( int $order_id ): bool {
        global $wpdb;
        $order = wc_get_order($order_id);
        if ( ! $order ) {
            aaa_oc_log("[Indexing] Order #$order_id not found.");
            return false;
        }

        $time_pub  = $order->get_date_created() ? $order->get_date_created()->date_i18n('Y-m-d H:i:s') : current_time('mysql');
        $time_stat = $order->get_date_modified() ? $order->get_date_modified()->date_i18n('Y-m-d H:i:s') : current_time('mysql');
        $status    = $order->get_status();
	$normalized_status = (strpos($status, 'wc-') === 0) ? $status : 'wc-' . $status;

	// V1 option key
	$allowed_statuses_raw = aaa_oc_get_option( 'aaa_oc_enabled_statuses', 'workflow', array() );
	aaa_oc_log("[Indexing][Debug] Raw option value (aaa_oc_enabled_statuses): " . print_r($allowed_statuses_raw, true));

	$allowed_statuses = is_array($allowed_statuses_raw) ? $allowed_statuses_raw : (array) $allowed_statuses_raw;
	$allowed_statuses = array_map( 'sanitize_key', $allowed_statuses );
	aaa_oc_log("[Indexing][Debug] Normalized allowed statuses: " . json_encode($allowed_statuses));

	if ( ! in_array( $normalized_status, $allowed_statuses, true ) ) {
	aaa_oc_log("[Indexing] Order #$order_id SKIPPED (status={$normalized_status} not in allowed list)");
	return false;
	}

	aaa_oc_log("[Indexing] Order #$order_id PASSED status filter (status={$normalized_status})");
        $order_num = method_exists($order, 'get_order_number') ? $order->get_order_number() : $order->get_id();
        $total_amt = (float) $order->get_total();

        $subtotal = 0;
        foreach ( $order->get_items() as $itm ) {
            $subtotal += (float) $itm->get_subtotal();
        }
        $shipping_total = (float) $order->get_shipping_total();
        $tax_total      = (float) $order->get_total_tax();
        $discount_total = (float) $order->get_discount_total();

        $tip_meta = get_post_meta( $order_id, '_wpslash_tip', true );
        $tip_amt  = ($tip_meta !== '' && is_numeric( $tip_meta )) ? (float) $tip_meta : 0.0;
        $currency = $order->get_currency();

        // Payment Index
        $pay_table = $wpdb->prefix . 'aaa_oc_payment_index';
        $payrec = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $pay_table WHERE order_id = %d LIMIT 1",
                $order_id
            ),
            ARRAY_A
        );
        if ( ! $payrec ) {
            aaa_oc_log("[Indexing] Missing payment record for order #$order_id. Skipping payment fields.");
            $payrec = [];
        }
        if ( empty( $payrec['aaa_oc_tip_total'] ) ) {
            $payrec['aaa_oc_tip_total'] = $tip_amt;
        }
        if ( empty( $payrec['total_order_tip'] ) ) {
            $payrec['total_order_tip'] = $tip_amt;
        }

        // Envelope Outstanding (new field, mirrors from payment index)
        $envelope_outstanding = isset($payrec['envelope_outstanding'])
            ? (int) $payrec['envelope_outstanding']
            : 0;

        // ==== Fulfillment handling (unchanged) ====
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT fulfillment_status, picked_items
                 FROM " . self::$table_name . " WHERE order_id = %d LIMIT 1",
                $order_id
            )
        );

        $picked_items_meta = get_post_meta( $order_id, '_aaa_picked_items', true );
        $picked_rows = [];
        $all_picked  = true;
        if ( is_array($picked_items_meta) && ! empty($picked_items_meta) ) {
            foreach ( $order->get_items() as $fulfill_item ) {
                if ( ! is_a( $fulfill_item, 'WC_Order_Item_Product' ) ) { continue; }
                $product = $fulfill_item->get_product();
                $sku     = $product ? (string) $product->get_sku() : '';
                $qty     = (int) $fulfill_item->get_quantity();

                $new_sku = $product ? get_post_meta( $product->get_id(), 'lkd_wm_new_sku', true ) : '';
                $picked  = 0;
                if ( $sku && isset($picked_items_meta[$sku]) ) {
                    $picked = (int) $picked_items_meta[$sku];
                } elseif ( $new_sku && isset($picked_items_meta[$new_sku]) ) {
                    $picked = (int) $picked_items_meta[$new_sku];
                }

                $picked_rows[] = [ 'sku' => $sku ?: ($new_sku ?: ''), 'picked' => $picked, 'max' => $qty ];
                if ( $qty > 0 && $picked < $qty ) { $all_picked = false; }
            }
            $picked_items_json   = wp_json_encode( $picked_rows );
            $fulfillment_status  = $all_picked ? 'fully_picked' : 'not_picked';
        } else {
            $picked_items_json  = $existing ? $existing->picked_items : null;
            if ( ! empty( $picked_items_json ) ) {
                $try = json_decode( $picked_items_json, true );
                if ( is_array($try) && isset($try[0]['sku']) ) {
                    $all_picked = true;
                    foreach ( $try as $r ) {
                        $mx = (int) ($r['max'] ?? 0);
                        $pk = (int) ($r['picked'] ?? 0);
                        if ( $mx > 0 && $pk < $mx ) { $all_picked = false; break; }
                    }
                    $fulfillment_status = $all_picked ? 'fully_picked' : 'not_picked';
                } else {
                    $fulfillment_status = ($existing && !empty($existing->fulfillment_status))
                        ? $existing->fulfillment_status
                        : 'fully_picked';
                }
            } else {
                $fulfillment_status = 'not_picked';
            }
        }

        // User meta
        $user_id = $order->get_user_id();
        $lkd_upload_med    = '';
        $lkd_upload_selfie = '';
        $lkd_upload_id     = '';
        $lkd_birthday      = null;
        $lkd_dl_exp        = null;
        $lkd_dln           = '';
        if ( $user_id ) {
            $lkd_upload_med    = get_user_meta( $user_id, 'afreg_additional_4630', true );
            $lkd_upload_selfie = get_user_meta( $user_id, 'afreg_additional_4627', true );
            $lkd_upload_id     = get_user_meta( $user_id, 'afreg_additional_4626', true );
            $lkd_birthday      = get_user_meta( $user_id, 'afreg_additional_4625', true );
            $lkd_dl_exp        = get_user_meta( $user_id, 'afreg_additional_4623', true );
            $lkd_dln           = get_user_meta( $user_id, 'afreg_additional_4532', true );
            $lkd_upload_med    = self::maybe_prepend_upload_url($lkd_upload_med);
            $lkd_upload_selfie = self::maybe_prepend_upload_url($lkd_upload_selfie);
            $lkd_upload_id     = self::maybe_prepend_upload_url($lkd_upload_id);
        }

        $daily_order_number = (int) get_post_meta( $order_id, '_daily_order_number', true );
        $driver_meta        = get_post_meta( $order_id, 'lddfw_driverid', true );
        $shipping_method = implode( ', ', array_map( function( $sm ) {
            return $sm->get_method_title();
        }, $order->get_shipping_methods() ) );

        $brands    = [];
        $item_list = [];

        foreach ( $order->get_items() as $item ) {
            if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
                $pid    = $item->get_product_id();
                $bterms = wp_get_post_terms( $pid, 'berocket_brand' );
                $bname  = '';
                if ( ! empty( $bterms ) && ! is_wp_error( $bterms ) ) {
                    $bname  = $bterms[0]->name;
                    $brands[$bterms[0]->term_id] = $bname;
                }

                $product_obj = $item->get_product();
                $sku = $product_obj ? $product_obj->get_sku() : '';
                $new_sku = $product_obj ? get_post_meta($product_obj->get_id(), 'lkd_wm_new_sku', true) : '';

                $item_list[] = [
                    'name'     => $item->get_name(),
                    'brand'    => $bname,
                    'sku'      => $sku,
                    'new_sku'  => $new_sku,
                    'product_id' => $item->get_product_id(),
                    'quantity' => (int) $item->get_quantity(),
                    'subtotal' => (float) $item->get_subtotal(),
                    'total'    => (float) $item->get_total(),
                ];
            }
        }
        $brand_list = implode( ', ', array_unique( $brands ) );
        $items_json = wp_json_encode( $item_list );
        $coupons_arr  = $order->get_coupon_codes();
        $coupons_json = wp_json_encode( $coupons_arr );

        $billing_data = [
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
            'company'    => $order->get_billing_company(),
            'address_1'  => $order->get_billing_address_1(),
            'address_2'  => $order->get_billing_address_2(),
            'city'       => $order->get_billing_city(),
            'state'      => $order->get_billing_state(),
            'postcode'   => $order->get_billing_postcode(),
            'country'    => $order->get_billing_country(),
            'email'      => $order->get_billing_email(),
            'phone'      => $order->get_billing_phone(),
        ];
        $billing_json = wp_json_encode( $billing_data );

        // NEW: Shipping fields
        $shipping_address_1 = $order->get_shipping_address_1();
        $shipping_address_2 = $order->get_shipping_address_2();
        $shipping_city      = $order->get_shipping_city();
        $shipping_state     = $order->get_shipping_state();
        $shipping_postcode  = $order->get_shipping_postcode();
        $shipping_country   = $order->get_shipping_country();

        // Verification flags from meta
        $shipping_verified = (int) get_post_meta( $order_id, '_adb_shipping_verified', true );
        $billing_verified  = (int) get_post_meta( $order_id, '_adb_billing_verified', true );

        $fees_array = [];
        $fee_items  = $order->get_items( 'fee' );
        if ( ! empty( $fee_items ) ) {
            foreach ( $fee_items as $fi ) {
                $fees_array[] = [
                    'name'   => $fi->get_name(),
                    'amount' => (float) $fi->get_total()
                ];
            }
        }
        $fees_json = wp_json_encode( $fees_array );

        $delivery_time       = get_post_meta( $order_id, 'delivery_time', true );
        $delivery_time_range = get_post_meta( $order_id, 'delivery_time_range', true );
        if ( is_array( $delivery_time_range ) ) {
            $delivery_time_range = implode( ', ', $delivery_time_range );
        }
        $delivery_date_formatted = get_post_meta( $order_id, 'delivery_date_formatted', true );
        $fulfill          = get_post_meta( $order_id, 'usbs_order_fulfillment_data', true );
        $fulfillment_data = is_array( $fulfill ) ? wp_json_encode( $fulfill ) : '';

        $cart_discount     = get_post_meta( $order_id, '_cart_discount', true );
        $created_via       = $order->get_meta( 'created_via', true );
        if ( empty( $created_via ) ) {
            $created_via = get_post_meta( $order_id, '_created_via', true );
        }
        $customer_user_meta = get_post_meta( $order_id, '_customer_user', true );
        if ( empty( $customer_user_meta ) ) {
            $customer_user_meta = $order->get_customer_id();
        }
        $funds_removed   = get_post_meta( $order_id, '_funds_removed', true );
        $funds_used      = get_post_meta( $order_id, '_funds_used', true );
        $lkd_first_order_status_updated = get_post_meta( $order_id, '_lkd_first_order_status_updated', true );
        $order_total_meta = get_post_meta( $order_id, '_order_total', true );

        $payment_method_title = $order->get_payment_method_title();
        $recorded_sales       = get_post_meta( $order_id, '_recorded_sales', true );
        $wc_order_attribution_source_type = get_post_meta( $order_id, '_wc_order_attribution_source_type', true );

        $lddfw_delivery_date = get_post_meta( $order_id, '_lddfw_delivery_date', true );
        $lddfw_delivery_time = get_post_meta( $order_id, '_lddfw_delivery_time', true );
        $lddfw_driverid      = get_post_meta( $order_id, 'lddfw_driverid', true );
        $usbs_fulfillment_data = get_post_meta( $order_id, 'usbs_order_fulfillment_data', true );

        $customer_completed_orders = 0;
        $average_order_amount = 0.0;
        $lifetime_spend = 0.0;
        if ( $user_id ) {
            $completed_orders = wc_get_orders( [
                'customer_id' => $user_id,
                'status'      => 'wc-completed',
                'limit'       => -1,
                'return'      => 'ids',
            ] );
            $ccount = count( $completed_orders );
            if ( $ccount > 0 ) {
                $sum = 0;
                foreach ( $completed_orders as $cid ) {
                    $co = wc_get_order( $cid );
                    if ( $co ) {
                        $sum += (float) $co->get_total();
                    }
                }
                $customer_completed_orders = $ccount;
                $lifetime_spend = $sum;
                $average_order_amount = $sum / $ccount;
            }
        }

        // -----------------------------------------------------------------
        // Retrieve user status from the table, which stores data as serialized arrays
        $customer_warnings_text = '';
        $customer_special_needs_text = '';
        $customer_banned_val = 0;
        $customer_ban_lenght_val = '';

        if ( $user_id ) {
            $cs_table = 'customer_status_info'; // Because "prefix": false in Meta Box config
            // ID column in your table is the user ID
            $cs_row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$cs_table} WHERE ID = %d LIMIT 1", $user_id), ARRAY_A );
            aaa_oc_log("[Indexing] user_id={$user_id}, retrieved row: " . print_r($cs_row, true));

            if ( $cs_row ) {
                // Banned + ban length
                // The table columns store them as text, so check if '1'
                $customer_banned_val = ($cs_row['customer_banned'] === '1') ? 1 : 0;
                $customer_ban_lenght_val = isset($cs_row['customer_ban_lenght']) ? $cs_row['customer_ban_lenght'] : '';

                // Warnings is stored in serialized form
                $warnings_serial = $cs_row['customer_warnings'];
                $warnings_arr = maybe_unserialize($warnings_serial);
                aaa_oc_log("[Indexing] warnings_arr: " . print_r($warnings_arr, true));

                $warning_list = [];
                if ( is_array($warnings_arr) ) {
                    foreach ( $warnings_arr as $warn ) {
                        // Each $warn might have 'customer_warning', 'customer_warning_reason', 'customer_warning_reason_other'
                        if ( ! empty($warn['customer_warning']) && $warn['customer_warning'] === '1' ) {
                            $reason = isset($warn['customer_warning_reason']) ? $warn['customer_warning_reason'] : '';
                            if ( $reason === 'Other' ) {
                                // If reason is "Other", append the 'other' text
                                $other = isset($warn['customer_warning_reason_other']) ? $warn['customer_warning_reason_other'] : '';
                                if ( $other ) {
                                    $reason .= ': ' . $other;
                                }
                            }
                            if ( $reason ) {
                                $warning_list[] = $reason;
                            }
                        }
                    }
                }
                // Build a semicolon‐separated string
                $customer_warnings_text = implode('; ', $warning_list);

                // Special needs is also stored in serialized form
                $needs_serial = $cs_row['customer_special_needs'];
                $needs_arr = maybe_unserialize($needs_serial);
                aaa_oc_log("[Indexing] special_needs_arr: " . print_r($needs_arr, true));

                $needs_list = [];
                if ( is_array($needs_arr) ) {
                    foreach ( $needs_arr as $need ) {
                        // Each $need might have 'customer_special_needs_instructions', 'other_instructions'
                        $inst = isset($need['customer_special_needs_instructions']) ? $need['customer_special_needs_instructions'] : '';
                        if ( $inst === 'Other' ) {
                            $other = isset($need['other_instructions']) ? $need['other_instructions'] : '';
                            if ( $other ) {
                                $inst .= ': ' . $other;
                            }
                        }
                        if ( $inst ) {
                            $needs_list[] = $inst;
                        }
                    }
                }
                $customer_special_needs_text = implode('; ', $needs_list);

                // Log final aggregated strings
                aaa_oc_log("[Indexing] Aggregated warnings: {$customer_warnings_text}");
                aaa_oc_log("[Indexing] Aggregated special needs: {$customer_special_needs_text}");
            }
        }

        // Replace or insert into the index table.
        $res = $wpdb->replace(
            self::$table_name,
            [
                'order_id'              => $order_id,

                'lkd_upload_med'        => $lkd_upload_med,
                'lkd_upload_selfie'     => $lkd_upload_selfie,
                'lkd_upload_id'         => $lkd_upload_id,
                'lkd_birthday'          => $lkd_birthday ? $lkd_birthday : null,
                'lkd_dl_exp'            => $lkd_dl_exp ? $lkd_dl_exp : null,
                'lkd_dln'               => $lkd_dln,

                'status'                => $status,
                'order_number'          => $order_num,
                'time_published'        => $time_pub,
                'time_in_status'        => $time_stat,

                'total_amount'          => $total_amt,
                'subtotal'              => $subtotal,
                'shipping_total'        => $shipping_total,
                'tax_total'             => $tax_total,
                'discount_total'        => $discount_total,
                'tip_amount'            => $tip_amt,
                'currency'              => $currency,

                'customer_name'         => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                'customer_email'        => $order->get_billing_email(),
                'customer_phone'        => $order->get_billing_phone(),
                'customer_note'         => $order->get_customer_note(),
                'daily_order_number'    => $daily_order_number,
                'customer_completed_orders' => $customer_completed_orders,
                'average_order_amount'  => $average_order_amount,
                'lifetime_spend'        => $lifetime_spend,

                // Our aggregated columns
                'customer_warnings_text' => $customer_warnings_text,
                'customer_banned'        => $customer_banned_val,
                'customer_ban_lenght'    => $customer_ban_lenght_val,
                'customer_special_needs_text' => $customer_special_needs_text,

                'driver_id'             => $driver_meta ? (int)$driver_meta : null,
                'shipping_method'       => $shipping_method,
                'brand_list'            => $brand_list,
                'items'                 => $items_json,
                'coupons'               => $coupons_json,
                'billing_json'          => $billing_json,
                'fees_json'             => $fees_json,
                'delivery_time'         => $delivery_time,
                'fulfillment_data'      => $fulfillment_data,
                'delivery_time_range'   => $delivery_time_range,
                'delivery_date_formatted' => $delivery_date_formatted,
                'lddfw_delivery_date'   => $lddfw_delivery_date,
                'lddfw_delivery_time'   => $lddfw_delivery_time,
                'lddfw_driverid'        => is_numeric( $lddfw_driverid ) ? (int) $lddfw_driverid : null,
                'usbs_order_fulfillment_data' => is_array( $usbs_fulfillment_data ) ? wp_json_encode( $usbs_fulfillment_data ) : '',

                // NEW: Shipping address + verification
                'shipping_address_1'    => $shipping_address_1,
                'shipping_address_2'    => $shipping_address_2,
                'shipping_city'         => $shipping_city,
                'shipping_state'        => $shipping_state,
                'shipping_postcode'     => $shipping_postcode,
                'shipping_country'      => $shipping_country,
                'shipping_verified'     => $shipping_verified,
                'billing_verified'      => $billing_verified,

                // Payment fields from the custom table:
                'aaa_oc_payment_status'     => $payrec['aaa_oc_payment_status'],
                'aaa_oc_cash_amount'        => $payrec['aaa_oc_cash_amount'],
                'aaa_oc_zelle_amount'       => $payrec['aaa_oc_zelle_amount'],
                'aaa_oc_venmo_amount'       => $payrec['aaa_oc_venmo_amount'],
                'aaa_oc_applepay_amount'    => $payrec['aaa_oc_applepay_amount'],
                'aaa_oc_cashapp_amount'     => $payrec['aaa_oc_cashapp_amount'],
                'aaa_oc_creditcard_amount'  => $payrec['aaa_oc_creditcard_amount'],
                'aaa_oc_tip_total'          => $payrec['aaa_oc_tip_total'],
                'aaa_oc_payrec_total'       => $payrec['aaa_oc_payrec_total'],
                'aaa_oc_order_balance'      => $payrec['aaa_oc_order_balance'],
                'aaa_oc_epayment_total'     => $payrec['aaa_oc_epayment_total'],
                'epayment_tip'              => $payrec['epayment_tip'],
                'total_order_tip'           => $payrec['total_order_tip'],
                'envelope_outstanding'      => $envelope_outstanding, // NEW

                'fulfillment_status'        => $fulfillment_status,
                'picked_items'              => $picked_items_json,
                '_cart_discount'            => is_numeric( $cart_discount ) ? (float) $cart_discount : 0,
                '_created_via'              => $created_via,
                '_customer_user'            => is_numeric( $customer_user_meta ) ? (int) $customer_user_meta : 0,
                '_funds_removed'            => is_numeric( $funds_removed ) ? (float) $funds_removed : 0,
                '_funds_used'               => is_numeric( $funds_used ) ? (float) $funds_used : 0,
                '_lkd_first_order_status_updated' => $lkd_first_order_status_updated ?: null,
                '_order_total'              => is_numeric( $order_total_meta ) ? (float) $order_total_meta : 0,
                '_payment_method_title'     => $payment_method_title,
                '_recorded_sales'           => is_numeric( $recorded_sales ) ? (float) $recorded_sales : 0,
                '_wc_order_attribution_source_type' => $wc_order_attribution_source_type,
                '_wpslash_tip'              => $tip_amt,
            ],
            [
                '%d',  // order_id

                '%s','%s','%s','%s','%s','%s', // lkd_upload_*, etc.

                '%s','%s','%s','%s',  // status, order_number, time_published, time_in_status

                '%f','%f','%f','%f','%f','%f','%s', // total_amount..currency

                '%s','%s','%s','%s','%d','%d','%f','%f', // customer_name..lifetime_spend

                '%s','%d','%s','%s', // warnings, banned, ban_length, special_needs

                '%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s', 
                // driver_id, shipping_method..usbs_order_fulfillment_data

                '%s','%s','%s','%s','%s','%s','%d','%d', 
                // shipping_* (6 strings) + 2 flags
                '%s','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f',
                '%d', // envelope_outstanding
                '%s','%s', // fulfillment_status, picked_items

                '%f','%s','%d','%f','%f','%s','%f','%s','%f',
                // _cart_discount => %f, _created_via => %s, _customer_user => %d,
                // _funds_removed => %f, _funds_used => %f,
                // _lkd_first_order_status_updated => %s, _order_total => %f,
                // _payment_method_title => %s, _recorded_sales => %f

                '%s','%s',
                // … [rest unchanged] …
            ]
        );

        if ( false !== $res ) {
            aaa_oc_log("[Indexing] Order #$order_id indexed successfully.");
            return true;
        } else {
            aaa_oc_log("[Indexing] Order #$order_id index failed: " . $wpdb->last_error);
            return false;
        }
    }

    private static function maybe_prepend_upload_url( $filename ): string {
        if ( empty( $filename ) ) {
            return '';
        }
        if ( preg_match( '#^https?://#', $filename ) ) {
            return $filename;
        }
        $base = site_url('/wp-content/uploads/sites/9/addify_registration_uploads/');
        return $base . ltrim($filename, '/');
    }
}

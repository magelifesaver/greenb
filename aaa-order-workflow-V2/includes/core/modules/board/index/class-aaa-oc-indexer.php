<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/index/class-aaa-oc-indexer.php
 * Purpose: Build/refresh a single row in `aaa_oc_order_index` (schema 1.0.6/1.0.7).
 * Strategy: Keyed, non-destructive writes (no positional REPLACE; no format drift).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Indexing {

	private function log( string $m ): void {
		if ( function_exists('aaa_oc_log') ) { aaa_oc_log('[Indexing] ' . $m); }
		else { error_log('[Indexing] ' . $m); }
	}

	public function index_order( int $order_id ): bool {
		if ( $order_id <= 0 ) return false;

		$order = wc_get_order( $order_id );
		if ( ! $order ) { $this->log("FAIL #$order_id : no order"); return false; }

		global $wpdb;
		$table = $wpdb->prefix . 'aaa_oc_order_index';
		$like  = $wpdb->esc_like( $table );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$like}'" ) !== $table ) {
			$this->log("FAIL #$order_id : base table missing"); return false;
		}

		// Ensure row exists (so keyed update always has a target).
		$exists = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `{$table}` WHERE order_id=%d", $order_id) );
		if ( ! $exists ) { $wpdb->insert( $table, [ 'order_id' => $order_id ], [ '%d' ] ); }

		// --- Times / status
		$status         = $order->get_status();
		$order_number   = (string) ( method_exists( $order, 'get_order_number') ? $order->get_order_number() : $order_id );
		$time_published = $order->get_date_created() ? gmdate('Y-m-d H:i:s', $order->get_date_created()->getTimestamp() ) : current_time('mysql', true);
		$time_in_status = current_time('mysql');

		// --- Totals
		$total_amount   = (float) $order->get_total();
		$subtotal       = (float) $order->get_subtotal();
		$shipping_total = (float) $order->get_shipping_total();
		$tax_total      = (float) $order->get_total_tax();
		$discount_total = (float) $order->get_discount_total();
		$cart_discount  = (float) get_post_meta( $order_id, '_cart_discount', true );

		// --- Customer
		$customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$customer_email = (string) $order->get_billing_email();
		$customer_phone = (string) $order->get_billing_phone();
		$customer_note  = (string) $order->get_customer_note();

		// --- Customer stats (cheap baseline; heavy lifetime calcs can be added by another module)
		$daily_order_number        = (int) get_post_meta( $order_id, '_daily_order_number', true );
		$customer_completed_orders = 0;
		$average_order_amount      = 0.0;
		$lifetime_spend            = 0.0;

		// --- Products snapshot (items/brands/fees/currency)
		$items_list  = [];
		$brands_seen = [];
		foreach ( $order->get_items() as $it ) {
			if ( ! is_a( $it, 'WC_Order_Item_Product' ) ) continue;
			$pid  = $it->get_product_id();
			$sku  = '';
			$prod = $it->get_product();
			if ( $prod ) $sku = (string) $prod->get_sku();

			$brand = '';
			if ( $pid ) {
				$terms = wp_get_post_terms( $pid, 'berocket_brand' );
				if ( ! is_wp_error($terms) && ! empty($terms) ) {
					$brand = $terms[0]->name;
					$brands_seen[ $terms[0]->term_id ] = $brand;
				}
			}

			$items_list[] = [
				'name'       => $it->get_name(),
				'brand'      => $brand,
				'sku'        => $sku,
				'product_id' => $pid,
				'quantity'   => (int) $it->get_quantity(),
				'subtotal'   => (float) $it->get_subtotal(),
				'total'      => (float) $it->get_total(),
			];
		}
		$brand_list_str = implode( ', ', array_values( array_unique( $brands_seen ) ) );
		$items_json     = wp_json_encode( $items_list );

		$fees = [];
		foreach ( $order->get_items('fee') as $fee ) {
			$fees[] = [ 'name' => $fee->get_name(), 'amount' => (float) $fee->get_total() ];
		}
		$fees_json = wp_json_encode( $fees );
		$currency  = (string) $order->get_currency();

		$codes        = $order->get_coupon_codes();
		$coupons_json = wp_json_encode( is_array($codes) ? array_values( array_filter($codes) ) : [] );

		// --- Addresses JSON + shipping method
		$billing_json = wp_json_encode([
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
		]);

		$ship_method = '';
		$rates = $order->get_shipping_methods();
		if ( ! empty($rates) ) {
			$first = reset($rates);
			$ship_method = $first ? (string) $first->get_name() : '';
		}
		$shipping_json = wp_json_encode([
			'address_1' => $order->get_shipping_address_1(),
			'address_2' => $order->get_shipping_address_2(),
			'city'      => $order->get_shipping_city(),
			'state'     => $order->get_shipping_state(),
			'postcode'  => $order->get_shipping_postcode(),
			'country'   => $order->get_shipping_country(),
			'method'    => $ship_method,
		]);

		// --- Mirrors / meta
		$created_via = (string) ( $order->get_meta('created_via', true) ?: get_post_meta($order_id,'_created_via',true) );
		$cust_user   = (int) ( get_post_meta($order_id,'_customer_user',true) ?: $order->get_customer_id() );
		$recorded    = (string) get_post_meta($order_id,'_recorded_sales',true);
		$attr_src    = (string) get_post_meta($order_id,'_wc_order_attribution_source_type',true);
		$tip_meta    = (float) get_post_meta($order_id,'_wpslash_tip',true);

		// --- WC transaction ids (use getters; do NOT read internal meta keys)
		$wc_txn = (string) $order->get_transaction_id();
		$gw_txn = (string) get_post_meta( $order_id, 'aaa_oc_gateway_ref', true );

		// --- Payment status snapshot column
		$payment_status = (string) get_post_meta( $order_id, 'aaa_oc_payment_status', true );
		if ( $payment_status === '' ) $payment_status = $order->is_paid() ? 'paid' : 'unpaid';

		// --- Keyed update (only columns in installer schema)
		$data = [
			'payment_status'    => $payment_status,
			'status'            => $status,
			'order_number'      => $order_number,
			'time_published'    => $time_published,
			'time_in_status'    => $time_in_status,

			'total_amount'      => $total_amount,
			'subtotal'          => $subtotal,
			'shipping_total'    => $shipping_total,
			'tax_total'         => $tax_total,
			'discount_total'    => $discount_total,
			'_cart_discount'    => $cart_discount,

			'fees_json'         => $fees_json,
			'currency'          => $currency,

			'customer_name'     => $customer_name,
			'customer_email'    => $customer_email,
			'customer_phone'    => $customer_phone,
			'customer_note'     => $customer_note,

			'daily_order_number'=> $daily_order_number,
			'customer_completed_orders' => $customer_completed_orders,
			'average_order_amount'      => $average_order_amount,
			'lifetime_spend'            => $lifetime_spend,

			'brand_list'        => $brand_list_str,
			'items'             => $items_json,
			'coupons'           => $coupons_json,

			'billing_json'      => $billing_json,
			'shipping_json'     => $shipping_json,
			'shipping_method'   => $ship_method,

			'shipping_address_1'=> (string) $order->get_shipping_address_1(),
			'shipping_address_2'=> (string) $order->get_shipping_address_2(),
			'shipping_city'     => (string) $order->get_shipping_city(),
			'shipping_state'    => (string) $order->get_shipping_state(),
			'shipping_postcode' => (string) $order->get_shipping_postcode(),
			'shipping_country'  => (string) $order->get_shipping_country(),

			'billing_address_1' => (string) $order->get_billing_address_1(),
			'billing_address_2' => (string) $order->get_billing_address_2(),
			'billing_city'      => (string) $order->get_billing_city(),
			'billing_state'     => (string) $order->get_billing_state(),
			'billing_postcode'  => (string) $order->get_billing_postcode(),
			'billing_country'   => (string) $order->get_billing_country(),

			'_created_via'      => $created_via,
			'_customer_user'    => $cust_user,
			'_order_total'      => $total_amount,
			'_recorded_sales'   => $recorded,
			'_wc_order_attribution_source_type' => $attr_src,
			'_wpslash_tip'      => $tip_meta,

			'wc_transaction_id'      => $wc_txn,
			'gateway_transaction_id' => $gw_txn,
			'last_updated'           => current_time('mysql'),
		];

		// Build formats to match keys in $data (order-safe).
		$formats = [];
		$float_cols = [
			'total_amount','subtotal','shipping_total','tax_total','discount_total','_cart_discount',
			'average_order_amount','lifetime_spend','_order_total','_wpslash_tip'
		];
		$int_cols = [ 'daily_order_number','customer_completed_orders','_customer_user' ];
		foreach ( $data as $k => $_ ) {
			$formats[] = in_array($k, $float_cols, true) ? '%f' : ( in_array($k, $int_cols, true) ? '%d' : '%s' );
		}

		$ok = $wpdb->update( $table, $data, [ 'order_id' => $order_id ], $formats, [ '%d' ] );
		if ( $ok === false ) { $this->log("FAIL #$order_id : " . $wpdb->last_error); return false; }

		$this->log("OK #$order_id (status={$status})");
		return true;
	}
}

<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/compat/index/class-aaa-oc-compat-indexer.php
 * Purpose: Populate order_index compat columns for Account Funds, Store Credit, and FluentCRM snapshots.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Compat_Indexer {

	public static function init(): void {
		// Fire on order creation/update paths
		add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'queue' ], 99, 2 );
		add_action( 'woocommerce_thankyou',                   [ __CLASS__, 'queue' ], 99, 1 );
		add_action( 'save_post_shop_order',                   [ __CLASS__, 'queue_on_save' ], 99, 3 );

		// Optional: a lightweight API for other modules to call
		add_action( 'aaa_oc_reindex_compat', [ __CLASS__, 'reindex' ], 10, 1 );
	}

	public static function queue( $order_id ): void {
		$order_id = (int) $order_id;
		if ( $order_id > 0 ) self::reindex( $order_id );
	}

	public static function queue_on_save( $post_id, $post, $update ): void {
		if ( $post && $post->post_type === 'shop_order' ) self::reindex( (int) $post_id );
	}

	/**
	 * Reindex compat snapshots for an order.
	 */
	public static function reindex( int $order_id ): void {
		global $wpdb;
		if ( $order_id <= 0 ) return;

		$order = function_exists('wc_get_order') ? wc_get_order( $order_id ) : null;
		if ( ! $order ) return;

		$user_id = (int) $order->get_customer_id();
		$email   = (string) $order->get_billing_email();

		// -------- Account Funds / Store Credit (order-level usage) --------
		// Allow overrides via filter if plugin stores custom keys.
		$map_order = apply_filters( 'aaa_oc_compat_meta_map', [
			// common/legacy
			'_funds_used'            => 'af_funds_used',
			'_store_credit_used'     => 'sc_credit_used',
			// popular alternates (guarded)
			'wc_store_credit_amount' => 'sc_credit_used',
		], $order_id );

		$af_funds_used = 0.0;
		$sc_used       = 0.0;
		foreach ( (array) $map_order as $meta_key => $target_col ) {
			$val = get_post_meta( $order_id, $meta_key, true );
			if ( $val !== '' && is_numeric( $val ) ) {
				if ( $target_col === 'af_funds_used' ) $af_funds_used = (float) $val;
				if ( $target_col === 'sc_credit_used' ) $sc_used       = (float) $val;
			}
		}

		// -------- Customer balances (snapshot at index time) --------
		$af_balance = null;
		$sc_balance = null;

		$map_balance = apply_filters( 'aaa_oc_compat_balance_map', [
			// try common keys (plugins differ; keep guarded)
			'account_funds'       => 'af_funds_balance', // e.g. "WooCommerce Account Funds"
			'woo_account_funds'   => 'af_funds_balance',
			'wc_store_credits'    => 'sc_credit_balance', // e.g. "WooCommerce Store Credit"
			'store_credit_balance'=> 'sc_credit_balance',
		], $user_id );

		if ( $user_id ) {
			foreach ( (array) $map_balance as $u_key => $target_col ) {
				$val = get_user_meta( $user_id, $u_key, true );
				if ( $val !== '' && is_numeric( $val ) ) {
					if ( $target_col === 'af_funds_balance' ) $af_balance = (float) $val;
					if ( $target_col === 'sc_credit_balance' ) $sc_balance = (float) $val;
				}
			}
		}

		// -------- FluentCRM snapshot (contact id + lists/tags + reg source) --------
		$crm_contact_id = null;
		$crm_reg_source = null;
		$crm_lists      = null;
		$crm_tags       = null;

		// Try FluentCRM model if available (guarded)
		if ( class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			try {
				$subscriber = null;
				if ( $user_id ) {
					$subscriber = \FluentCrm\App\Models\Subscriber::where('user_id', $user_id)->first();
				}
				if ( ! $subscriber && $email ) {
					$subscriber = \FluentCrm\App\Models\Subscriber::where('email', $email)->first();
				}
				if ( $subscriber ) {
					$crm_contact_id = (int) $subscriber->id;
					// Optional pulls (guarded)
					$crm_reg_source = (string) ( $subscriber->source ?? '' );

					// Lists/Tags as CSV of names; keep short to avoid bloat
					$_lists = method_exists($subscriber, 'lists') ? $subscriber->lists()->get(['title'])->pluck('title')->toArray() : [];
					$_tags  = method_exists($subscriber, 'tags')  ? $subscriber->tags()->get(['title'])->pluck('title')->toArray()  : [];
					if ( $_lists ) $crm_lists = implode( ',', array_map( 'strval', $_lists ) );
					if ( $_tags )  $crm_tags  = implode( ',', array_map( 'strval', $_tags ) );
				}
			} catch ( \Throwable $e ) {
				// no-op; snapshot stays null
			}
		}

		// -------- Write to aaa_oc_order_index (guard formats) --------
		$tbl = $wpdb->prefix . 'aaa_oc_order_index';
		$wpdb->update( $tbl, [
			'af_funds_used'     => (float) $af_funds_used,
			'af_funds_balance'  => is_null($af_balance) ? null : (float) $af_balance,
			'sc_credit_used'    => (float) $sc_used,
			'sc_credit_balance' => is_null($sc_balance) ? null : (float) $sc_balance,
			'crm_contact_id'    => $crm_contact_id ?: null,
			'crm_reg_source'    => $crm_reg_source ?: null,
			'crm_lists'         => $crm_lists ?: null,
			'crm_tags'          => $crm_tags ?: null,
		], [ 'order_id' => $order_id ], [
			'%f','%s','%f','%s','%d','%s','%s','%s'
		], [ '%d' ] );
	}
}

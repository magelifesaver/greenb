<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/index/class-aaa-oc-customer-indexer.php
 * Purpose: Populate aaa_oc_customer table and copy snapshots to order_index for board rendering.
 * Version: 1.0.3
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class AAA_OC_Customer_Indexer {

	public static function init(): void {
		add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'queue' ], 98, 2 );
		add_action( 'woocommerce_thankyou',                   [ __CLASS__, 'queue' ], 98, 1 );
		add_action( 'save_post_shop_order',                   [ __CLASS__, 'queue_on_save' ], 98, 3 );

		// Optional programmatic hook
		add_action( 'aaa_oc_reindex_customer', [ __CLASS__, 'reindex' ], 10, 1 );
	}

	public static function queue( $order_id ): void {
		$order_id = (int) $order_id;
		if ( $order_id > 0 ) self::reindex( $order_id );
	}

	public static function queue_on_save( $post_id, $post, $update ): void {
		if ( $post && $post->post_type === 'shop_order' ) self::reindex( (int) $post_id );
	}

	public static function reindex( int $order_id ): void {
		global $wpdb;
		if ( $order_id <= 0 ) return;

		$order = function_exists('wc_get_order') ? wc_get_order( $order_id ) : null;
		if ( ! $order ) return;

		$user_id = (int) $order->get_customer_id();
		$email   = (string) $order->get_billing_email();

		// ---- CRM pull (FluentCRM) ----
		$crm_contact_id = null;
		$dob  = null; $dl_number = null; $dl_exp = null;
		$up_med = null; $up_selfie = null; $up_id = null;

		if ( class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			try {
				$sub = null;
				if ( $user_id ) $sub = \FluentCrm\App\Models\Subscriber::where('user_id',$user_id)->first();
				if ( ! $sub && $email ) $sub = \FluentCrm\App\Models\Subscriber::where('email',$email)->first();
				if ( $sub ) {
					$crm_contact_id = (int) $sub->id;

					// Pull by FluentCRM field slugs (not labels)
					$fields = is_array($sub->custom_fields) ? $sub->custom_fields : (method_exists($sub,'custom_fields') ? (array)$sub->custom_fields : []);
					$get = function(string $slug) use ($fields){ return isset($fields[$slug]) ? trim((string)$fields[$slug]) : ''; };

					$dob       = self::to_date( $get('date_of_birth') ?: $get('contact_dob') );
					$dl_number = $get('contact_id_number');
					$dl_exp    = self::to_date( $get('contact_id_expiration') );

					$up_id     = $get('contact_id_upload');
					$up_selfie = $get('contact_selfie_upload');
					$up_med    = $get('contact_rec');
				}
			} catch ( \Throwable $e ) { /* ignore */ }
		}

		// ---- Legacy USER META fallbacks (by NAME/KEY as on your site) ----
		if ( $user_id ) {
			if ( ! $up_med )    $up_med    = get_user_meta( $user_id, 'afreg_additional_4630', true );
			if ( ! $up_selfie ) $up_selfie = get_user_meta( $user_id, 'afreg_additional_4627', true );
			if ( ! $up_id )     $up_id     = get_user_meta( $user_id, 'afreg_additional_4626', true );
			if ( ! $dob )       $dob       = self::to_date( get_user_meta( $user_id, 'afreg_additional_4625', true ) );
			if ( ! $dl_exp )    $dl_exp    = self::to_date( get_user_meta( $user_id, 'afreg_additional_4623', true ) );
			if ( ! $dl_number ) $dl_number = get_user_meta( $user_id, 'afreg_additional_4532', true );
		}

		// Normalize upload paths/URLs to safe, absolute strings (never null)
		$up_med    = self::maybe_prepend_upload_url( $up_med );
		$up_selfie = self::maybe_prepend_upload_url( $up_selfie );
		$up_id     = self::maybe_prepend_upload_url( $up_id );

		// ---- Admin flags (warnings/special/ban) from legacy table if present ----
		$warnings_text = ''; $special_text = ''; $banned = 0; $ban_length = '';
		$legacy_table = $wpdb->prefix . 'customer_status_info';
		if ( $user_id && $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $legacy_table) ) === $legacy_table ) {
			$row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$legacy_table} WHERE ID=%d LIMIT 1", $user_id ), ARRAY_A );
			if ( $row ) {
				$banned     = (isset($row['customer_banned']) && $row['customer_banned'] === '1') ? 1 : 0;
				$ban_length = isset($row['customer_ban_lenght']) ? (string)$row['customer_ban_lenght'] : '';

				$warnings_arr = maybe_unserialize( $row['customer_warnings'] ?? '' );
				if ( is_array($warnings_arr) ) {
					$tmp = [];
					foreach ( $warnings_arr as $w ) {
						if ( ! empty($w['customer_warning']) && $w['customer_warning'] === '1' ) {
							$r = $w['customer_warning_reason'] ?? '';
							if ( $r === 'Other' && ! empty($w['customer_warning_reason_other']) ) $r .= ': '.$w['customer_warning_reason_other'];
							if ( $r ) $tmp[] = $r;
						}
					}
					if ( $tmp ) $warnings_text = implode('; ', $tmp);
				}

				$needs_arr = maybe_unserialize( $row['customer_special_needs'] ?? '' );
				if ( is_array($needs_arr) ) {
					$tmp = [];
					foreach ( $needs_arr as $n ) {
						$r = $n['customer_special_needs_instructions'] ?? '';
						if ( $r === 'Other' && ! empty($n['other_instructions']) ) $r .= ': '.$n['other_instructions'];
						if ( $r ) $tmp[] = $r;
					}
					if ( $tmp ) $special_text = implode('; ', $tmp);
				}
			}
		}

		// ---- Overlay from authoritative USER META (if present) — additive, does NOT remove legacy fallbacks ----
		if ( $user_id ) {
			$meta_needs = (array) get_user_meta( $user_id, '_aaa_oc_special_needs', true );
			$meta_warn  = (string) get_user_meta( $user_id, '_aaa_oc_warning_reason', true );
			$meta_ban   = (string) get_user_meta( $user_id, '_aaa_oc_warning_is_ban', true );
			$meta_until = (int)    get_user_meta( $user_id, '_aaa_oc_warning_ban_until', true );

			if ( ! empty( $meta_needs ) ) {
				$special_text = implode( ', ', array_filter( array_map( 'sanitize_text_field', (array) $meta_needs ) ) );
			}
			if ( $meta_warn !== '' ) {
				$warnings_text = sanitize_text_field( $meta_warn );
			}
			if ( $meta_ban === 'yes' ) {
				$banned = 1;
				if ( $meta_until > time() ) {
					$ban_length = 'until ' . date_i18n( get_option('date_format'), $meta_until );
				} elseif ( $ban_length === '' ) {
					$ban_length = 'indefinite';
				}
				// Append ban tag to warnings text
				if ( $warnings_text !== '' ) {
					$warnings_text .= ' ';
				}
				$warnings_text .= '(' . ( $meta_until > time() ? 'Banned until ' . date_i18n( get_option('date_format'), $meta_until ) : 'Banned' ) . ')';
			}
		}

		// ---- UPSERT into aaa_oc_customer (profile) ----
		$tbl_cust = $wpdb->prefix . 'aaa_oc_customer';
		if ( $user_id ) {
			$wpdb->replace( $tbl_cust, [
				'user_id'                    => $user_id,
				'crm_contact_id'             => $crm_contact_id ?: null,
				'customer_banned'            => (int) $banned,
				'customer_ban_length'        => $ban_length ?: null,
				'customer_warnings_text'     => $warnings_text ?: null,
				'customer_special_needs_text'=> $special_text ?: null,
				'dob'                        => $dob ?: null,
				'dl_number'                  => $dl_number ?: null,
				'dl_exp'                     => $dl_exp ?: null,
				'upload_med'                 => $up_med ?: null,
				'upload_selfie'              => $up_selfie ?: null,
				'upload_id'                  => $up_id ?: null,
			], [
				'%d','%d','%d','%s','%s','%s','%s','%s','%s','%s','%s','%s'
			] );
		}

		// ---- Copy snapshot to order_index (so board renders fast w/o joins) ----
		$tbl_idx = $wpdb->prefix . 'aaa_oc_order_index';
		$wpdb->update( $tbl_idx, [
			'customer_banned'            => (int) $banned,
			'customer_ban_length'        => $ban_length ?: null,
			'customer_warnings_text'     => $warnings_text ?: null,
			'customer_special_needs_text'=> $special_text ?: null,
			'lkd_birthday'               => $dob ?: null,
			'lkd_dl_exp'                 => $dl_exp ?: null,
			'lkd_upload_med'             => $up_med ?: null,
			'lkd_upload_selfie'          => $up_selfie ?: null,
			'lkd_upload_id'              => $up_id ?: null,
			'lkd_dln'                    => $dl_number ?: null,
		], [ 'order_id' => $order_id ], [
			'%d','%s','%s','%s','%s','%s','%s','%s','%s','%s'
		], [ '%d' ] );
	}

	private static function to_date( $val ): ?string {
		$val = trim( (string) $val );
		if ( $val === '' ) return null;
		if ( is_numeric( $val ) ) {
			$ts = (int) $val;
		} else {
			try { $ts = ( new DateTime( $val, wp_timezone() ) )->getTimestamp(); }
			catch ( \Throwable $e ) { return null; }
		}
		return gmdate( 'Y-m-d', $ts );
	}

	/**
	 * Normalize a possibly-relative upload path to an absolute URL.
	 * Always returns a string (never null) to avoid downstream type notices.
	 */
	private static function maybe_prepend_upload_url( $filename ) : string {
		$filename = is_string( $filename ) ? trim( $filename ) : '';
		if ( $filename === '' ) return '';

		// Already absolute?
		if ( preg_match( '#^https?://#i', $filename ) ) {
			return $filename;
		}

		// Strip leading slash so we can safely join
		$filename = ltrim( $filename, '/' );

		$up = function_exists('wp_get_upload_dir') ? wp_get_upload_dir() : null;
		if ( ! is_array( $up ) || ! empty( $up['error'] ) || empty( $up['baseurl'] ) ) {
			return site_url( '/' . $filename );
		}

		$baseUrl = rtrim( (string) $up['baseurl'], '/' );
		return $baseUrl . '/' . $filename;
	}
}

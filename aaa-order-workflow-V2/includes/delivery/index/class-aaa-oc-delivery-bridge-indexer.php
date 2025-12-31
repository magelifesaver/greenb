<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/index/class-aaa-oc-delivery-bridge-indexer.php
 * Purpose: Bridge delivery-related metas into order_index during core indexing.
 * Version: 1.1.0
 *
 * Changes in 1.1.0:
 * - Populate new fields: delivery_latitude, delivery_longitude, delivery_address_line,
 *   travel_time_seconds, travel_distance_meters,
 *   was_rescheduled, reschedule_count, last_rescheduled_at, original_delivery_ts,
 *   reschedule_reason, rescheduled_by, is_asap_zone, asap_zone_id, asap_eta_minutes,
 *   asap_eta_computed_at, asap_fee.
 */
if ( ! defined('ABSPATH') ) exit;

final class AAA_OC_Delivery_Bridge_Indexer {

	public static function init() : void {
		// Original hook used by some builds
		add_filter( 'aaa_oc_indexer_base_row',          [ __CLASS__, 'merge_row' ], 10, 2 );
		// Other collectors used by newer builds
		add_filter( 'aaa_oc_collect_order_index',       [ __CLASS__, 'merge_slice' ], 10, 2 );
		add_filter( 'aaa_oc_order_index_collect_slice', [ __CLASS__, 'merge_slice' ], 10, 2 );
		add_filter( 'aaa_oc_order_index_slice',         [ __CLASS__, 'merge_slice' ], 10, 2 );
	}

	/** Merge into a ROW shape: filter signature ($row, WC_Order) */
	public static function merge_row( array $row, $order ) : array {
		if ( ! $order || ! method_exists( $order, 'get_id' ) ) return $row;
		$bridge = self::collect_for_order( (int) $order->get_id() );
		foreach ( $bridge as $k => $v ) { if ( $v !== null ) $row[ $k ] = $v; }
		return $row;
	}

	/** Merge into a SLICE shape: filter signature ($slice, $order_id) */
	public static function merge_slice( array $slice, int $order_id ) : array {
		if ( $order_id <= 0 ) return $slice;
		$bridge = self::collect_for_order( $order_id );
		foreach ( $bridge as $k => $v ) { $slice[ $k ] = $v; }
		return $slice;
	}

	/** Read metas and produce delivery columns for OI */
	private static function collect_for_order( int $order_id ) : array {
		$get = static function( string $k ) use ( $order_id ) {
			$v = get_post_meta( $order_id, $k, true );
			return ($v === '' ? null : $v);
		};

		// --- existing date/time/driver slice ---
		$ts         = $get('delivery_date');                  // unix ts (string/int)
		$formatted  = $get('delivery_date_formatted');        // YYYY-MM-DD
		$locale     = $get('delivery_date_locale');           // "November 8, 2025"
		$time       = $get('delivery_time');                  // "10:45 am"
		$time_range = $get('delivery_time_range');            // "From 10:45 am to 11:30 am"
		$driver_id  = (int) ( $get('lddfw_driverid') ?? 0 );

		// --- coords (order meta first, then user fallback) ---
		$lat = null; $lng = null;
		$json = (string) ( $get('szbd_picked_delivery_location') ?? '' );
		if ( $json !== '' ) {
			$dec = json_decode( $json, true );
			if ( is_array( $dec ) ) {
				if ( isset($dec['lat']) && isset($dec['lng']) ) {
					$lat = is_numeric($dec['lat']) ? (float)$dec['lat'] : null;
					$lng = is_numeric($dec['lng']) ? (float)$dec['lng'] : null;
				}
			}
		}
		if ( $lat === null || $lng === null ) {
			$user_id = (int) get_post_meta( $order_id, '_customer_user', true );
			if ( $user_id > 0 ) {
				$uj = get_user_meta( $user_id, 'shipping_szbd-picked-location', true );
				if ( is_string($uj) && $uj !== '' ) {
					$ud = json_decode( $uj, true );
					if ( is_array($ud) && isset($ud['lat'],$ud['lng']) ) {
						$lat = is_numeric($ud['lat']) ? (float)$ud['lat'] : $lat;
						$lng = is_numeric($ud['lng']) ? (float)$ud['lng'] : $lng;
					}
				}
			}
		}

		// --- single-line address (from shipping fields) ---
		$addr_1 = (string) ( $get('_shipping_address_1') ?? '' );
		$city   = (string) ( $get('_shipping_city') ?? '' );
		$state  = (string) ( $get('_shipping_state') ?? '' );
		$zip    = (string) ( $get('_shipping_postcode') ?? '' );
		$address_line = '';
		if ( $addr_1 !== '' || $city !== '' || $state !== '' || $zip !== '' ) {
			$parts = array_filter( [ $addr_1, trim($city), trim($state . ( $zip ? " {$zip}" : '' )) ] );
			$address_line = implode(', ', $parts);
		}

		// --- travel metrics (if present) ---
		$travel_secs   = null;
		$travel_meters = null;
		// Accept several possible meta keys; use first non-empty
		foreach ( ['travel_time_seconds','_travel_time_seconds','szbd_travel_time_seconds'] as $mk ) {
			$val = $get($mk);
			if ( is_numeric($val) ) { $travel_secs = (int)$val; break; }
		}
		foreach ( ['travel_distance_meters','_travel_distance_meters','szbd_travel_distance_meters'] as $mk ) {
			$val = $get($mk);
			if ( is_numeric($val) ) { $travel_meters = (int)$val; break; }
		}

		// --- reschedule metas ---
		$res_count   = (int) ( $get('aaa_oc_reschedule_count') ?? 0 );
		$was_res     = (int) ( $res_count > 0 ? 1 : (int) ( $get('aaa_oc_was_rescheduled') ?? 0 ) );
		$last_res_at = $get('aaa_oc_last_rescheduled_at');    // 'YYYY-mm-dd HH:ii:ss'
		$orig_ts     = $get('aaa_oc_original_delivery_ts');   // BIGINT ts (string/int)
		$res_reason  = $get('aaa_oc_reschedule_reason');
		$res_by      = $get('aaa_oc_rescheduled_by');

		// --- ASAP/zone metas (best-effort; leave nulls if absent) ---
		$is_asap_zone = (int) ( $get('is_asap_zone') ?? 0 );
		$asap_zone_id = $get('asap_zone_id');
		$asap_eta_min = is_numeric( $get('asap_eta_minutes') ) ? (int) $get('asap_eta_minutes') : null;
		$asap_eta_at  = $get('asap_eta_computed_at'); // DATETIME
		$asap_fee     = is_numeric( $get('asap_fee') ) ? (float) $get('asap_fee') : null;

		return [
			// existing
			'delivery_date_ts'        => $ts !== null ? (string)$ts : null,
			'delivery_date_formatted' => $formatted ?: null,
			'delivery_date_locale'    => $locale ?: null,
			'delivery_time'           => $time ?: null,
			'delivery_time_range'     => $time_range ?: null,
			'driver_id'               => $driver_id > 0 ? $driver_id : null,
			'is_scheduled'            => $formatted ? 1 : 0,
			'is_same_day'             => (int) ( $get('is_same_day') ?? 0 ),
			'is_asap'                 => (int) ( $get('is_asap') ?? 0 ),

			// new: coords + address + travel
			'delivery_latitude'       => ( $lat !== null ? (float)$lat : null ),
			'delivery_longitude'      => ( $lng !== null ? (float)$lng : null ),
			'delivery_address_line'   => ( $address_line !== '' ? $address_line : null ),
			'travel_time_seconds'     => $travel_secs,
			'travel_distance_meters'  => $travel_meters,

			// new: reschedule
			'was_rescheduled'         => $was_res,
			'reschedule_count'        => $res_count ?: 0,
			'last_rescheduled_at'     => $last_res_at ?: null,
			'original_delivery_ts'    => ( $orig_ts !== null && $orig_ts !== '' ) ? (string)$orig_ts : null,
			'reschedule_reason'       => $res_reason ?: null,
			'rescheduled_by'          => $res_by ?: null,

			// new: ASAP zone metadata
			'is_asap_zone'            => $is_asap_zone,
			'asap_zone_id'            => $asap_zone_id ?: null,
			'asap_eta_minutes'        => $asap_eta_min,
			'asap_eta_computed_at'    => $asap_eta_at ?: null,
			'asap_fee'                => $asap_fee,
		];
	}
}
AAA_OC_Delivery_Bridge_Indexer::init();

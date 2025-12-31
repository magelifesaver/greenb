<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/helpers/class-aaa-oc-delivery-key-bridge.php
 * Purpose: Bridge new scheduler keys (tpfw_*) into legacy delivery_* keys on order creation/save.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'AAA_OC_Delivery_Key_Bridge' ) ) {

	final class AAA_OC_Delivery_Key_Bridge {

		const DEBUG_THIS_FILE = true;

		public static function init() : void {
			// Checkout flow (order creation)
			add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'maybe_bridge_keys' ], 20, 2 );
			// Thank-you fallback (in case 3P plugin writes late)
			add_action( 'woocommerce_thankyou', [ __CLASS__, 'maybe_bridge_keys_thankyou' ], 20, 1 );
			// Admin save fallback
			add_action( 'save_post_shop_order', [ __CLASS__, 'maybe_bridge_keys_admin' ], 20, 3 );
		}

		private static function bridge_for_order_id( int $order_id ) : void {
			$mode            = get_post_meta( $order_id, 'tpfw_delivery_mode', true );
			$time_localized  = get_post_meta( $order_id, 'tpfw_picked_time_localized', true );           // "October 29, 2025 11:00 am"
			$time_plain      = get_post_meta( $order_id, 'tpfw_picked_time', true );                     // "2025-10-29 11:00 am"
			$ts              = get_post_meta( $order_id, 'tpfw_picked_time_timestamp', true );           // 1761760800
			$range_end_local = get_post_meta( $order_id, 'tpfw_picked_time_range_end_localized', true ); // "11:45 am"

			if ( empty( $ts ) && empty( $time_plain ) && empty( $time_localized ) ) {
				return;
			}

			$site_tz  = wp_timezone();
			$timestamp = is_numeric( $ts ) ? (int) $ts : 0;

			if ( ! $timestamp && ! empty( $time_plain ) ) {
				try {
					$timestamp = ( new DateTime( $time_plain, $site_tz ) )->getTimestamp();
				} catch ( Exception $e ) {
					// try localized
					if ( ! empty( $time_localized ) ) {
						try {
							$timestamp = ( new DateTime( $time_localized, $site_tz ) )->getTimestamp();
						} catch ( Exception $e2 ) {
							$timestamp = 0;
						}
					}
				}
			}

			if ( ! $timestamp ) {
				self::log( $order_id, 'No valid timestamp parsed; aborting bridge.' );
				return;
			}

			$dt        = ( new DateTime( 'now', $site_tz ) )->setTimestamp( $timestamp );
			$time_12h  = $dt->format( 'g:i a' );   // "11:00 am"
			$date_y_m_d= $dt->format( 'Y-m-d' );   // "2025-10-29"
			$date_long = $dt->format( 'F j, Y' );  // "October 29, 2025"

			// --- FIX: tie the range end to the SAME DATE + TZ as the picked time ---
			$range_end = '';
			if ( ! empty( $range_end_local ) ) {
				$range_end_local = trim( (string) $range_end_local );
				try {
					// Compose a full datetime string using the same date as the picked time.
					$end_dt = new DateTime( $date_y_m_d . ' ' . $range_end_local, $site_tz );
					$range_end = $end_dt->format( 'g:i a' ); // "11:45 am"
				} catch ( Exception $e ) {
					// best-effort fallback: keep the raw time but normalize spacing/case
					$range_end = strtolower( preg_replace( '/\s+/', ' ', $range_end_local ) );
				}
			}

			$delivery_time         = $time_12h;                                           // "11:00 am"
			$delivery_time_range   = $range_end ? sprintf( 'From %s to %s', $time_12h, $range_end ) : $time_12h;
			$delivery_date         = (string) $timestamp;                                  // "1761760800"
			$delivery_date_fmt     = $date_y_m_d;                                         // "2025-10-29"
			$delivery_date_locale  = $date_long;                                          // "October 29, 2025"

			update_post_meta( $order_id, 'delivery_time',            $delivery_time );
			update_post_meta( $order_id, 'delivery_time_range',      $delivery_time_range );
			update_post_meta( $order_id, 'delivery_date',            $delivery_date );
			update_post_meta( $order_id, 'delivery_date_formatted',  $delivery_date_fmt );
			update_post_meta( $order_id, 'delivery_date_locale',     $delivery_date_locale );

			self::log( $order_id, sprintf(
				'Bridged tpfw_* → delivery_* (mode:%s, ts:%d, time:%s, range_end:%s)',
				$mode ?: 'n/a', $timestamp, $delivery_time, $range_end ?: 'n/a'
			) );
		}

		public static function maybe_bridge_keys( int $order_id, array $posted_data ) : void {
			self::bridge_for_order_id( $order_id );
		}

		public static function maybe_bridge_keys_thankyou( $order_id ) : void {
			$order_id = (int) $order_id;
			if ( $order_id > 0 ) {
				self::bridge_for_order_id( $order_id );
			}
		}

		public static function maybe_bridge_keys_admin( int $post_id, WP_Post $post, bool $update ) : void {
			if ( 'shop_order' === $post->post_type ) {
				self::bridge_for_order_id( $post_id );
			}
		}

		private static function log( int $order_id, string $msg ) : void {
			if ( self::DEBUG_THIS_FILE && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[AAA-OC][DELIVERY-BRIDGE] #%d %s', $order_id, $msg ) );
			}
		}
	}

	AAA_OC_Delivery_Key_Bridge::init();
}

<?php
/**
 * ============================================================================
 * File Path: /wp-content/mu-plugins/lokey-sales-reports/helpers/helpers-dates.php
 * Version: 1.2.0
 * Updated: 2025-12-01
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Provides date range utilities for LokeyReports endpoints:
 *     - Supports 'preset' (e.g., today, last_7_days, this_month)
 *     - Ensures consistent timezone and format for WooCommerce queries
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * Parse a date range from preset, from, and to parameters.
 * --------------------------------------------------------------------------
 *
 * @param array $params {
 *     @type string $preset Optional preset range (today|yesterday|last_7_days|this_month).
 *     @type string $from   Optional start date (YYYY-MM-DD).
 *     @type string $to     Optional end date (YYYY-MM-DD).
 * }
 * @return array {
 *     @type DateTime     $from
 *     @type DateTime     $to
 *     @type DateTimeZone $timezone
 * }
 */
if ( ! function_exists( 'lokey_reports_parse_date_range' ) ) {
	function lokey_reports_parse_date_range( array $params ) {
		$tz      = lokey_reports_get_store_timezone();
		$preset  = isset( $params['preset'] ) ? lokey_reports_sanitize_preset( $params['preset'] ) : '';
		$from_in = isset( $params['from'] ) ? (string) $params['from'] : '';
		$to_in   = isset( $params['to'] ) ? (string) $params['to'] : '';

		if ( $preset ) {
			$now = new DateTime( 'now', $tz );

			switch ( $preset ) {
				case 'today':
					$from = ( clone $now )->setTime( 0, 0, 0 );
					$to   = ( clone $now )->setTime( 23, 59, 59 );
					break;

				case 'yesterday':
					$from = new DateTime( 'yesterday', $tz );
					$from->setTime( 0, 0, 0 );
					$to = new DateTime( 'yesterday', $tz );
					$to->setTime( 23, 59, 59 );
					break;

				case 'last_7_days':
					$from = ( clone $now )->modify( '-6 days' )->setTime( 0, 0, 0 );
					$to   = ( clone $now )->setTime( 23, 59, 59 );
					break;

				case 'last_30_days':
					$from = ( clone $now )->modify( '-29 days' )->setTime( 0, 0, 0 );
					$to   = ( clone $now )->setTime( 23, 59, 59 );
					break;

				case 'this_month':
					$from = new DateTime( 'first day of this month', $tz );
					$from->setTime( 0, 0, 0 );
					$to = new DateTime( 'last day of this month', $tz );
					$to->setTime( 23, 59, 59 );
					break;

				case 'last_month':
					$from = new DateTime( 'first day of last month', $tz );
					$from->setTime( 0, 0, 0 );
					$to = new DateTime( 'last day of last month', $tz );
					$to->setTime( 23, 59, 59 );
					break;

				default:
					$from = new DateTime( 'today', $tz );
					$from->setTime( 0, 0, 0 );
					$to = ( clone $from )->setTime( 23, 59, 59 );
					break;
			}
		} else {
			$from = DateTime::createFromFormat( 'Y-m-d', $from_in, $tz );
			$to   = DateTime::createFromFormat( 'Y-m-d', $to_in, $tz );

			if ( ! $from ) $from = new DateTime( 'today', $tz );
			if ( ! $to )   $to   = clone $from;

			$from->setTime( 0, 0, 0 );
			$to->setTime( 23, 59, 59 );
		}

		if ( $from > $to ) {
			$tmp  = $from;
			$from = $to;
			$to   = $tmp;
		}

		return [
			'from'     => $from,
			'to'       => $to,
			'timezone' => $tz,
		];
	}
}

/**
 * --------------------------------------------------------------------------
 * Build period key string for grouping (day/week/month).
 * --------------------------------------------------------------------------
 *
 * @param DateTimeInterface $date     A date object.
 * @param string            $group_by Grouping method (day|week|month|none).
 * @return string
 */
if ( ! function_exists( 'lokey_reports_build_period_key' ) ) {
	function lokey_reports_build_period_key( DateTimeInterface $date, $group_by ) {
		switch ( $group_by ) {
			case 'day':
				return $date->format( 'Y-m-d' );
			case 'week':
				return $date->format( 'o-\WW' ); // e.g., 2025-W47
			case 'month':
				return $date->format( 'Y-m' );
			default:
				return 'all';
		}
	}
}

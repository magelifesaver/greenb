<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-nightly.php
 * Purpose: Optional nightly enqueue of all enabled products for forecasting.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_OC_FORECAST_NIGHTLY_DEBUG' ) ) {
	define( 'AAA_OC_FORECAST_NIGHTLY_DEBUG', true );
}

class AAA_OC_Forecast_Nightly {

	private const HOOK = 'aaa_oc_forecast_nightly_enqueue';

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'sync_schedule' ] );
		add_action( self::HOOK, [ __CLASS__, 'run' ] );
	}

	public static function sync_schedule(): void {
		if ( ! function_exists( 'aaa_oc_get_option' ) ) { return; }

		$enabled = (int) aaa_oc_get_option( 'forecast_enable_nightly_rerun', 'forecast', 0 );

		if ( $enabled ) {
			if ( ! wp_next_scheduled( self::HOOK ) ) {
				wp_schedule_event( self::next_run_timestamp(), 'daily', self::HOOK );
				if ( AAA_OC_FORECAST_NIGHTLY_DEBUG ) {
					error_log( '[AAA_OC Forecast Nightly] Scheduled nightly enqueue.' );
				}
			}
		} else {
			wp_clear_scheduled_hook( self::HOOK );
		}
	}

	public static function run(): void {
		$q = new WP_Query( [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => [
				[ 'key' => 'forecast_enable_reorder', 'value' => 'yes' ],
			],
		] );

		$ids = array_values( array_filter( array_map( 'absint', (array) $q->posts ) ) );

		if ( ! empty( $ids ) && class_exists( 'AAA_OC_Forecast_Queue' ) ) {
			AAA_OC_Forecast_Queue::queue_products_for_forecast( $ids );
		}

		if ( AAA_OC_FORECAST_NIGHTLY_DEBUG ) {
			error_log( '[AAA_OC Forecast Nightly] Enqueued enabled products: ' . count( $ids ) );
		}
	}

	private static function next_run_timestamp(): int {
		$tz  = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$now = new DateTime( 'now', $tz );
		$run = new DateTime( $now->format( 'Y-m-d' ) . ' 02:05:00', $tz );
		if ( $run->getTimestamp() <= $now->getTimestamp() ) { $run->modify( '+1 day' ); }
		return $run->getTimestamp();
	}
}

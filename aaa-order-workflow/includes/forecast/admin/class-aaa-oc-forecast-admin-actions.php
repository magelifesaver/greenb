<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/class-aaa-oc-forecast-admin-actions.php
 * Purpose: Admin-post handlers used by Forecast Settings tab (queue tools).
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG' ) ) {
	define( 'AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG', true );
}

class AAA_OC_Forecast_Admin_Actions {

	public static function init(): void {
		add_action( 'admin_post_aaa_oc_forecast_queue_all_enabled', [ __CLASS__, 'queue_all_enabled' ] );
		add_action( 'admin_post_aaa_oc_forecast_process_queue_now', [ __CLASS__, 'process_queue_now' ] );
	}

	public static function queue_all_enabled(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'aaa-oc' ) );
		}
		check_admin_referer( 'aaa_oc_forecast_queue_all_enabled' );

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

		if ( AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG ) {
			error_log( '[AAA_OC Forecast Admin] Queued enabled products: ' . count( $ids ) );
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=aaa-oc-core-settings&tab=aaa-oc-forecast-settings' ) );
		exit;
	}

	public static function process_queue_now(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'aaa-oc' ) );
		}
		check_admin_referer( 'aaa_oc_forecast_process_queue_now' );

		if ( ! wp_next_scheduled( 'aaa_oc_process_forecast_queue' ) ) {
			wp_schedule_single_event( time() + 5, 'aaa_oc_process_forecast_queue' );
		}

		if ( AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG ) {
			error_log( '[AAA_OC Forecast Admin] Scheduled immediate queue processing.' );
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=aaa-oc-core-settings&tab=aaa-oc-forecast-settings' ) );
		exit;
	}
}

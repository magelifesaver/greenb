<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/class-aaa-oc-forecast-admin-actions.php
 * Purpose: Admin-post handlers used by Forecast settings tabs (queue tools).
 * Version: 0.1.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG' ) ) {
	define( 'AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG', false );
}

class AAA_OC_Forecast_Admin_Actions {

	public static function init(): void {
		add_action( 'admin_post_aaa_oc_forecast_queue_all_enabled', [ __CLASS__, 'queue_all_enabled' ] );
		add_action( 'admin_post_aaa_oc_forecast_process_queue_now', [ __CLASS__, 'process_queue_now' ] );
		add_action( 'admin_post_aaa_oc_forecast_repair_queue_tables', [ __CLASS__, 'repair_queue_tables' ] );
	}

	public static function queue_all_enabled(): void {
		self::require_manage_woo();
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

		self::redirect_back( [ 'aaa_oc_forecast_queued_all' => count( $ids ) ] );
	}

	public static function process_queue_now(): void {
		self::require_manage_woo();
		check_admin_referer( 'aaa_oc_forecast_process_queue_now' );

		if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
			AAA_OC_Forecast_Queue::process_forecast_queue();
		}

		if ( AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG ) {
			error_log( '[AAA_OC Forecast Admin] Ran one queue batch immediately.' );
		}

		self::redirect_back( [ 'aaa_oc_forecast_processed' => 1 ] );
	}

	public static function repair_queue_tables(): void {
		self::require_manage_woo();
		check_admin_referer( 'aaa_oc_forecast_repair_queue_tables' );

		if ( class_exists( 'AAA_OC_Forecast_Queue_Installer' ) ) {
			AAA_OC_Forecast_Queue_Installer::maybe_install_tables();
		}
		if ( class_exists( 'AAA_OC_Forecast_Table_Installer' ) ) {
			AAA_OC_Forecast_Table_Installer::maybe_install_table();
		}

		self::redirect_back( [ 'aaa_oc_forecast_repaired' => 1 ] );
	}

	private static function require_manage_woo(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'aaa-oc' ) );
		}
	}

	private static function redirect_back( array $args = [] ): void {
		$ref = wp_get_referer();
		if ( ! $ref ) {
			$ref = admin_url( 'admin.php?page=aaa-oc-core-settings&tab=aaa-oc-forecast-queue' );
		}
		if ( ! empty( $args ) ) {
			$ref = add_query_arg( $args, $ref );
		}
		wp_safe_redirect( $ref );
		exit;
	}
}

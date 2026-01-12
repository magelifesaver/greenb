<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/class-aaa-oc-forecast-admin-actions.php
 * Purpose: Admin-post handlers used by Forecast settings tabs (queue tools).
 * Version: 0.1.3
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG' ) ) {
	define( 'AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG', false );
}

class AAA_OC_Forecast_Admin_Actions {

	public static function init(): void {
		add_action( 'admin_post_aaa_oc_forecast_queue_all_enabled', [ __CLASS__, 'queue_all_enabled' ] );
		add_action( 'admin_post_aaa_oc_forecast_process_queue_now', [ __CLASS__, 'process_queue_now' ] );
		add_action( 'admin_post_aaa_oc_forecast_process_po_queue', [ __CLASS__, 'process_po_queue' ] );
		add_action( 'admin_post_aaa_oc_forecast_repair_queue_tables', [ __CLASS__, 'repair_queue_tables' ] );
	}

	public static function queue_all_enabled(): void {
		self::require_manage_woo();
		check_admin_referer( 'aaa_oc_forecast_queue_all_enabled' );

		if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
			AAA_OC_Forecast_Queue::schedule_queue_all_enabled();
		}

		if ( AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG ) {
			error_log( '[AAA_OC Forecast Admin] Scheduled queueing of enabled products.' );
		}

		self::redirect_back( [ 'aaa_oc_forecast_queue_scheduled' => 1 ] );
	}

	public static function process_queue_now(): void {
		self::require_manage_woo();
		check_admin_referer( 'aaa_oc_forecast_process_queue_now' );

		if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
			AAA_OC_Forecast_Queue::schedule_process_queue( 10 );
		}

		if ( AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG ) {
			error_log( '[AAA_OC Forecast Admin] Scheduled queue processing.' );
		}

		self::redirect_back( [ 'aaa_oc_forecast_process_scheduled' => 1 ] );
	}

	public static function process_po_queue(): void {
		self::require_manage_woo();
		check_admin_referer( 'aaa_oc_forecast_process_po_queue' );

		if ( class_exists( 'AAA_OC_Forecast_Queue' ) ) {
			if ( method_exists( 'AAA_OC_Forecast_Queue', 'schedule_process_po_queue' ) ) {
				AAA_OC_Forecast_Queue::schedule_process_po_queue( 10 );
			} elseif ( method_exists( 'AAA_OC_Forecast_Queue', 'schedule_po_run' ) ) {
				// Backward compatibility if an older method name exists.
				AAA_OC_Forecast_Queue::schedule_po_run( 10 );
			} else {
				// Last resort: schedule the PO hook directly if exposed by WP-Cron only.
				if ( AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG ) {
					error_log( '[AAA_OC Forecast Admin] No PO scheduling method found on AAA_OC_Forecast_Queue.' );
				}
			}
		}

		if ( AAA_OC_FORECAST_ADMIN_ACTIONS_DEBUG ) {
			error_log( '[AAA_OC Forecast Admin] Scheduled PO queue processing.' );
		}

		self::redirect_back( [ 'aaa_oc_forecast_po_process_scheduled' => 1 ] );
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

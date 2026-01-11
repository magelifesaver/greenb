<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/class-aaa-oc-forecast-settings.php
 * Purpose: Registers Forecast settings tabs on the Workflow Settings page.
 * Version: 0.1.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Settings {

	public static function init(): void {
		add_filter( 'aaa_oc_core_settings_tabs', [ __CLASS__, 'register_tabs' ] );
	}

	public static function register_tabs( array $tabs ): array {
		$tabs['aaa-oc-forecast-settings'] = [
			'label' => __( 'Forecast', 'aaa-oc' ),
			'file'  => __DIR__ . '/tabs/aaa-oc-forecast-settings.php',
		];

		$tabs['aaa-oc-forecast-field-settings'] = [
			'label' => __( 'Forecast Fields', 'aaa-oc' ),
			'file'  => __DIR__ . '/tabs/aaa-oc-forecast-field-settings.php',
		];

		return $tabs;
	}
}

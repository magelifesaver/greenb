<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/class-aaa-oc-forecast-settings.php
 * Purpose: Registers a Forecast settings tab on the Workflow Settings page and
 *          points it to a dedicated tab file. This class hooks into the
 *          `aaa_oc_core_settings_tabs` filter so the core settings page can
 *          discover our tab. The tab itself lives under admin/tabs.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Settings {

    /**
     * Boot the settings registration. Hooks into the core settings filter.
     */
    public static function init(): void {
        add_filter( 'aaa_oc_core_settings_tabs', [ __CLASS__, 'register_tab' ] );
    }

    /**
     * Adds our forecast tab to the collection of settings tabs. The core
     * settings page will include our provided file when this tab is active.
     *
     * @param array $tabs Existing tabs keyed by id.
     * @return array Modified tabs array with forecast tab.
     */
    public static function register_tab( array $tabs ): array {
        $tabs['aaa-oc-forecast-settings'] = [
            'label' => __( 'Forecast', 'aaa-oc' ),
            'file'  => __DIR__ . '/tabs/aaa-oc-forecast-settings.php',
        ];
        return $tabs;
    }
}

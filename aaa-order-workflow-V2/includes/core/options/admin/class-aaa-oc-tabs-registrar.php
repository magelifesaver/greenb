<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/admin/class-aaa-oc-tabs-registrar.php
 * Purpose: Register Settings tabs; enforce Control Panel first; add Board Preferences core tab.
 * Version: 1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'AAA_OC_TABS_DEBUG' ) ) define( 'AAA_OC_TABS_DEBUG', false );

final class AAA_OC_Tabs_Registrar {

	public static function init() : void {
		add_filter( 'aaa_oc_core_settings_tabs', [ __CLASS__, 'add_core_tabs' ], 5 );
		add_filter( 'aaa_oc_core_settings_tabs', [ __CLASS__, 'add_enabled_module_tabs' ], 15 );
		add_filter( 'aaa_oc_core_settings_tabs', [ __CLASS__, 'sort_tabs_enforce_order' ], 99 );
	}

	/**
	 * Ensure core tabs exist:
	 * - Control Panel (WFCP)  -> must be FIRST
	 * - Workflow Settings     -> second
	 * - Board Preferences     -> third (new)
	 */
	public static function add_core_tabs( array $tabs ) : array {
		$base = plugin_dir_path( __FILE__ ) . 'tabs/';

		// 1) Control Panel (WFCP) — always present, always first
		if ( empty( $tabs['aaa-oc-wfcp'] ) ) {
			$wfcp = $base . 'aaa-oc-wfcp.php';
			if ( file_exists( $wfcp ) ) {
				$tabs['aaa-oc-wfcp'] = [
					'label'    => 'Control Panel',
					'file'     => $wfcp,
					'priority' => 0,
				];
			}
		} else {
			$tabs['aaa-oc-wfcp']['priority'] = 0;
		}

		// 2) Workflow Settings
		if ( empty( $tabs['aaa-oc-workflow-settings'] ) ) {
			$ws = $base . 'aaa-oc-workflow-settings.php';
			if ( file_exists( $ws ) ) {
				$tabs['aaa-oc-workflow-settings'] = [
					'label'    => 'Workflow Settings',
					'file'     => $ws,
					'priority' => 10,
				];
			}
		} else {
			$tabs['aaa-oc-workflow-settings']['priority'] = 10;
		}

		// 3) Board Preferences (new)
		if ( empty( $tabs['aaa-oc-board-preferences'] ) ) {
			$bp = $base . 'aaa-oc-board-preferences.php';
			if ( file_exists( $bp ) ) {
				$tabs['aaa-oc-board-preferences'] = [
					'label'    => 'Board Preferences',
					'file'     => $bp,
					'priority' => 20,
				];
			}
		} else {
			$tabs['aaa-oc-board-preferences']['priority'] = 20;
		}

		return $tabs;
	}

	/**
	 * Add tabs for ENABLED modules (legacy single file or multiple).
	 * Modules default to priority=50 (after core tabs).
	 */
	public static function add_enabled_module_tabs( array $tabs ) : array {
		if ( ! class_exists('AAA_OC_Modules_Registry') ) return $tabs;

		$enabled_map = AAA_OC_Modules_Registry::enabled_map();

		foreach ( AAA_OC_Modules_Registry::all() as $key => $m ) {
			if ( ! empty( $m['always_on'] ) ) continue;
			if ( empty( $enabled_map[ $key ] ) ) continue;

			// Multiple tab files
			if ( ! empty( $m['tabs'] ) && is_array( $m['tabs'] ) ) {
				foreach ( $m['tabs'] as $t ) {
					$id   = $t['id']   ?? '';
					$file = $t['file'] ?? '';
					$lbl  = $t['label']?? '';
					if ( ! $id || ! $file || ! file_exists( $file ) ) continue;

					$tabs[ $id ] = [
						'label'    => $lbl ?: $id,
						'file'     => $file,
						'priority' => isset($t['priority']) ? (int)$t['priority'] : 50,
					];
					if ( AAA_OC_TABS_DEBUG ) error_log( "[Tabs] add {$key}::{$id} => {$file}" );
				}
				continue;
			}

			// Legacy single tab
			if ( ! empty( $m['tab_file'] ) && file_exists( $m['tab_file'] ) ) {
				$label = ! empty( $m['label'] ) ? $m['label'] : ucwords( str_replace( '-', ' ', $m['slug'] ) );
				$tabs[ 'aaa-oc-' . $m['slug'] ] = [
					'label'    => $label,
					'file'     => $m['tab_file'],
					'priority' => 50,
				];
				if ( AAA_OC_TABS_DEBUG ) error_log( "[Tabs] add {$key} => {$m['tab_file']}" );
			}
		}
		return $tabs;
	}

	/**
	 * Final sort: enforce Control Panel first, then other core tabs in order,
	 * then all module tabs by their priority (default 50).
	 */
	public static function sort_tabs_enforce_order( array $tabs ) : array {
		// Attach priorities if missing
		foreach ( $tabs as $id => $t ) {
			if ( ! isset( $tabs[$id]['priority'] ) ) {
				$tabs[$id]['priority'] = 50;
			}
			// Never allow anything to outrank CP
			if ( $id === 'aaa-oc-wfcp' ) $tabs[$id]['priority'] = -100;
		}

		uasort( $tabs, function( $a, $b ) {
			$pa = (int)($a['priority'] ?? 50);
			$pb = (int)($b['priority'] ?? 50);
			if ( $pa === $pb ) return 0;
			return ( $pa < $pb ) ? -1 : 1;
		});

		return $tabs;
	}
}

AAA_OC_Tabs_Registrar::init();

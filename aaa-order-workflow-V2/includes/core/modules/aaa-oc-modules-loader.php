<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/aaa-oc-modules-loader.php
 * Purpose: Load AAA Order Workflow modules dynamically (no hardcoding).
 *          - Always load per-module "declarations" so WFCP can see resources even if module is disabled
 *          - Load module loaders only when enabled
 *          - Optionally load each module’s assets loader when present
 *
 * Discovery (handled by Modules Registry):
 *   /includes/<module>/aaa-oc-<slug>-loader.php            (required when enabled)
 *   /includes/<module>/aaa-oc-<slug>-assets-loader.php     (optional when enabled)
 *   /includes/<module>/index/aaa-oc-<slug>-declarations.php (optional; always loaded if found)
 *   /includes/<module>/admin/tabs/aaa-oc-<slug>.php        (optional; tab added only when enabled)
 *
 * Version: 1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Per-file debug toggle (guarded) */
if ( ! defined( 'AAA_OC_MODULES_LOADER_DEBUG' ) ) {
	define( 'AAA_OC_MODULES_LOADER_DEBUG', false );
}

/** Idempotent guard */
if ( defined( 'AAA_OC_MODULES_LOADER_READY' ) ) { return; }
define( 'AAA_OC_MODULES_LOADER_READY', true );

/** Shared helper: require + quiet log */
if ( ! function_exists( 'aaa_oc_require_or_log' ) ) {
	function aaa_oc_require_or_log( $file ) {
		if ( $file && file_exists( $file ) ) { require_once $file; return true; }
		if ( defined('AAA_OC_MODULES_LOADER_DEBUG') && AAA_OC_MODULES_LOADER_DEBUG ) {
			error_log('[ModulesLoader] Missing: ' . $file);
		}
		return false;
	}
}

/**
 * Ensure dependencies are present: Version helper + Modules Registry.
 * (Options Loader usually loads these already; this is a safe fallback.)
 */
$base_core = __DIR__; // /includes/core
if ( ! class_exists( 'AAA_OC_Version' ) ) {
	aaa_oc_require_or_log( $base_core . '/version/class-aaa-oc-version.php' );
}
if ( ! class_exists( 'AAA_OC_Modules_Registry' ) ) {
	aaa_oc_require_or_log( $base_core . '/modules/class-aaa-oc-modules-registry.php' );
}

/**
 * Always-on declarations pass:
 * - For every discovered module, attempt to load its declarations file
 *   so WFCP filters (expected tables/columns) exist whether or not it’s enabled.
 * - We’ll look for $mod['declarations'] first; if absent, infer by convention:
 *   dirname($mod['loader']).'/index/aaa-oc-{slug}-declarations.php'
 */
add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'AAA_OC_Modules_Registry' ) ) return;

	$all = AAA_OC_Modules_Registry::all();
	foreach ( $all as $key => $mod ) {
		$decl = $mod['declarations'] ?? '';
		if ( ! $decl || ! file_exists( $decl ) ) {
			$slug   = isset( $mod['slug'] ) ? sanitize_key( $mod['slug'] ) : sanitize_key( $key );
			$loader = $mod['loader'] ?? '';
			if ( $loader && file_exists( $loader ) ) {
				// infer /index/aaa-oc-{slug}-declarations.php next to the loader's module dir
				$decl = dirname( $loader ) . '/index/aaa-oc-' . $slug . '-declarations.php';
			}
		}
		aaa_oc_require_or_log( $decl );
	}
}, 3); // early so filters exist before the settings page renders

/**
 * Load enabled modules:
 * - Always-on modules (core/board) are handled by Core Loader and skipped here.
 * - For enabled modules: require the module loader; optionally require assets loader.
 */
add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'AAA_OC_Modules_Registry' ) ) {
		if ( AAA_OC_MODULES_LOADER_DEBUG ) error_log('[ModulesLoader] Registry missing; abort.');
		return;
	}

	$enabled_map = AAA_OC_Modules_Registry::enabled_map();
	$all         = AAA_OC_Modules_Registry::all();

	foreach ( $all as $key => $mod ) {
		if ( ! empty( $mod['always_on'] ) ) continue;

		$is_enabled = ! empty( $enabled_map[ $key ] );
		if ( ! $is_enabled ) continue;

		$loader = $mod['loader']  ?? '';
		$assets = $mod['assets']  ?? '';
		$label  = $mod['label']   ?? $key;

		// Require module loader
		if ( $loader ) {
			aaa_oc_require_or_log( $loader );
			if ( AAA_OC_MODULES_LOADER_DEBUG ) {
				error_log( sprintf('[ModulesLoader] loaded: %s (%s)', $label, $loader) );
			}
		}

		// Optionally require module assets loader (only if present)
		if ( $assets ) {
			$load_assets = apply_filters( 'aaa_oc_load_module_assets', true, $key, $mod );
			if ( $load_assets ) {
				aaa_oc_require_or_log( $assets );
				if ( AAA_OC_MODULES_LOADER_DEBUG ) {
					error_log( sprintf('[ModulesLoader] assets: %s (%s)', $label, $assets) );
				}
			}
		}
	}
}, 0);

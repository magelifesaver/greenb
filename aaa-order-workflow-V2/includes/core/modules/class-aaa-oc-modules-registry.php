<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/class-aaa-oc-modules-registry.php
 * Purpose: Central registry for AAA Order Workflow modules.
 *          - Auto-discovers modules by naming convention (no hardcoding)
 *          - Builds a list for WFCP (recognition != enablement)
 *          - Provides toggles persistence (aaa_oc_options, scope 'modules')
 *          - Exposes loader paths, optional tab files, versions
 *
 * Discovery rules (recognition):
 *   - Scan: /includes/* (excluding 'core', 'modules', 'options', 'version')
 *   - Main loader (required): <dir>/aaa-oc-<slug>-loader.php
 *   - Optional assets loader: <dir>/aaa-oc-<slug>-assets-loader.php
 *   - Optional settings tab:  <dir>/admin/tabs/aaa-oc-<slug>.php
 *
 * Enabling:
 *   - WFCP stores map in aaa_oc_options (key 'aaa_oc_modules_enabled', scope 'modules')
 *   - Enabled modules are actually loaded by the Modules Loader using the 'loader' path
 *
 * Version: 1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'AAA_OC_MODULES_DEBUG' ) ) {
	define( 'AAA_OC_MODULES_DEBUG', false );
}

if ( ! class_exists( 'AAA_OC_Modules_Registry' ) ) :

final class AAA_OC_Modules_Registry {

	const OPT_KEY = 'aaa_oc_modules_enabled'; // scope 'modules'

	/**
	 * Return the full registry: core entries + discovered modules.
	 * Core entries are always-on; discovered modules are recognition only.
	 */
	public static function all() : array {
		$core = self::core_entries();
		$fs   = self::discover_fs(); // recognition only
		$mods = array_merge( $core, $fs );

		// Allow other code to adjust the registry (labels, versions, callbacks, etc.)
		return apply_filters( 'aaa_oc_module_registry', $mods );
	}

	/**
	 * Read the enabled map from aaa_oc_options (scope 'modules').
	 * Returns associative array: ['payment' => 1, 'payconfirm' => 1, ...]
	 */
	public static function enabled_map() : array {
		if ( ! function_exists( 'aaa_oc_get_option' ) ) {
			// Fallback: keep resilient during early boot if wrapper not loaded yet
			$raw = get_option( self::OPT_KEY, [] );
			return is_array( $raw ) ? $raw : (array) maybe_unserialize( $raw );
		}
		$val = aaa_oc_get_option( self::OPT_KEY, 'modules', [] );
		return is_array( $val ) ? $val : (array) maybe_unserialize( $val );
	}

	/**
	 * Persist a new enabled map and fire activate/deactivate callbacks when provided.
	 * Callbacks are optional fields in registry entries: ['activate' => callable, 'deactivate' => callable]
	 */
	public static function set_enabled_map( array $new_map ) : void {
		if ( ! function_exists( 'aaa_oc_set_option' ) ) return;

		$old = self::enabled_map();
		aaa_oc_set_option( self::OPT_KEY, $new_map, 'modules' );

		// Lifecycle notifications (optional)
		foreach ( self::all() as $key => $m ) {
			if ( ! empty( $m['always_on'] ) ) continue;

			$was = ! empty( $old[ $key ] );
			$is  = ! empty( $new_map[ $key ] );

			if ( $is && ! $was && is_callable( $m['activate'] ?? null ) ) {
				call_user_func( $m['activate'] );
			} elseif ( $was && ! $is && is_callable( $m['deactivate'] ?? null ) ) {
				call_user_func( $m['deactivate'] );
			}
		}
	}

	/* ======================================================================
	 * Internals
	 * ====================================================================== */

	/**
	 * Always-on core entries (informational; loaded elsewhere by Core Loader).
	 */
	private static function core_entries() : array {
		$ver = defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : 'n/a';
		$get_ver = function( $slug ) use ( $ver ) {
			if ( class_exists( 'AAA_OC_Version' ) && method_exists( 'AAA_OC_Version', 'module' ) ) {
				return AAA_OC_Version::module( $slug );
			}
			return $ver;
		};

		return [
			'core' => [
				'label'      => 'Core',
				'slug'       => 'core',
				'version'    => $get_ver( 'CORE' ),
				'always_on'  => true,
				'active'     => true,
				'loader'     => '',
				'assets'     => '',
				'tab_file'   => '', // core tabs live under /includes/core/options/admin/tabs
				'scope'      => 'core',
			],
			'board' => [
				'label'      => 'Workflow Board',
				'slug'       => 'board',
				'version'    => $get_ver( 'BOARD' ),
				'always_on'  => true,
				'active'     => true,
				'loader'     => '', // loaded by Core Loader
				'assets'     => '',
				'tab_file'   => '', // board settings tab is provided in core tabs
				'scope'      => 'board',
			],
		];
	}

	/**
	 * Filesystem discovery (recognition only).
	 * Guarantees we select the MAIN loader, never the assets loader.
	 */
	private static function discover_fs() : array {
		$root = defined( 'AAA_OC_PLUGIN_DIR' )
			? AAA_OC_PLUGIN_DIR
			: trailingslashit( dirname( dirname( dirname( __FILE__ ) ) ) );

		$inc = trailingslashit( $root . 'includes' );
		if ( ! is_dir( $inc ) ) return [];

		$out  = [];
		$skip = [ 'core', 'modules', 'options', 'version' ];

		foreach ( glob( $inc . '*', GLOB_ONLYDIR ) as $dir ) {
			$dir_slug = basename( $dir );
			if ( in_array( $dir_slug, $skip, true ) ) continue;

			// Prefer exact main loader by convention
			$main_loader = trailingslashit( $dir ) . "aaa-oc-{$dir_slug}-loader.php";
			$loader = null;

			if ( file_exists( $main_loader ) ) {
				$loader = [
					'path' => $main_loader,
					'slug' => strtolower( $dir_slug ),
				];
			} else {
				// Fallback: wildcard scan but EXCLUDE assets loaders
				$list = glob( trailingslashit( $dir ) . 'aaa-oc-*-loader.php' ) ?: [];
				$list = array_values( array_filter( $list, static function ( $p ) {
					return ( strpos( $p, '-assets-loader.php' ) === false );
				} ) );

				if ( ! empty( $list ) && preg_match( '/aaa-oc-(.+)-loader\.php$/i', $list[0], $m ) ) {
					$loader = [
						'path' => $list[0],
						'slug' => strtolower( trim( $m[1] ) ),
					];
				}
			}

			// If no MAIN loader, optionally expose asset-only pseudo-module or skip entirely
			if ( ! $loader ) {
				$assets_only = trailingslashit( $dir ) . "aaa-oc-{$dir_slug}-assets-loader.php";
				if ( file_exists( $assets_only ) ) {
					$out[ "{$dir_slug}-assets" ] = [
						'label'      => ucwords( str_replace( [ '-', '_' ], ' ', "{$dir_slug}-assets" ) ),
						'slug'       => "{$dir_slug}-assets",
						'version'    => defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : 'n/a',
						'always_on'  => false,
						'active'     => false,
						'loader'     => '',           // no main loader
						'assets'     => $assets_only,
						'tab_file'   => '',           // no settings tab for assets-only
						'scope'      => "{$dir_slug}-assets",
					];
					if ( AAA_OC_MODULES_DEBUG ) error_log( "[Modules][discover] asset-only '{$dir_slug}-assets' ({$assets_only})" );
				} else {
					if ( AAA_OC_MODULES_DEBUG ) error_log( "[Modules][discover] skipped '{$dir_slug}' (no main loader)" );
				}
				continue;
			}

			$slug     = $loader['slug'];
			$loader_p = $loader['path'];

			// Optional assets loader and single tab file
			$assets   = self::first_path( trailingslashit( $dir ) . "aaa-oc-{$slug}-assets-loader.php" );
			$tab_file = self::first_path( trailingslashit( $dir ) . "admin/tabs/aaa-oc-{$slug}.php" );

			// Version resolution
			$ver = defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : 'n/a';
			if ( class_exists( 'AAA_OC_Version' ) && method_exists( 'AAA_OC_Version', 'module' ) ) {
				$ver = AAA_OC_Version::module( strtoupper( $slug ) );
			}

			$out[ $slug ] = [
				'label'      => ucwords( str_replace( [ '-', '_' ], ' ', $slug ) ),
				'slug'       => $slug,
				'version'    => $ver,
				'always_on'  => false,
				'active'     => false,  // actual enabled state stored in options (WFCP)
				'loader'     => $loader_p,
				'assets'     => $assets,
				'tab_file'   => $tab_file,
				'scope'      => $slug,
			];

			if ( AAA_OC_MODULES_DEBUG ) {
				error_log(
					"[Modules][discover] {$slug} loader={$loader_p}" .
					( $assets ? " assets={$assets}" : '' ) .
					( $tab_file ? " tab={$tab_file}" : '' )
				);
			}
		}

		return $out;
	}

	/** Helper: return first path if it exists; else empty string. */
	private static function first_path( string $path ) : string {
		return file_exists( $path ) ? $path : '';
	}
}

endif;

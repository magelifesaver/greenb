<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/helpers/class-aaa-oc-loader-util.php
 * Purpose: Shared loader/util helpers for all AAA-OC modules (safe requires, logging, board checks, assets).
 * Version: 1.0.1
 *
 * Notes:
 * - Keep this file lean and dependency-free (no echo/markup).
 * - All methods are static and side-effect free.
 * - Modules can include this early; duplicate includes are guarded.
 *
 * Additions in 1.0.1:
 * - Non-invasive loader tracking for require attempts (in-memory, per-request).
 *   Access via AAA_OC_Loader_Util::get_tracker().
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Idempotent guard */
if ( defined( 'AAA_OC_LOADER_UTIL_READY' ) ) return;
define( 'AAA_OC_LOADER_UTIL_READY', true );

/** Per-file debug toggle (off by default) */
if ( ! defined( 'AAA_OC_LOADER_UTIL_DEBUG' ) ) define( 'AAA_OC_LOADER_UTIL_DEBUG', false );

if ( ! class_exists( 'AAA_OC_Loader_Util' ) ) {
	final class AAA_OC_Loader_Util {

		/** -------- internal tracker (optional) -------- */

		/** Ensure global tracker array exists (in-memory, per-request) */
		private static function ensure_tracker(): void {
			if ( isset( $GLOBALS['AAA_OC_LOADER_TRACKER'] ) && is_array( $GLOBALS['AAA_OC_LOADER_TRACKER'] ) ) return;
			$GLOBALS['AAA_OC_LOADER_TRACKER'] = [
				'first_boot_ts' => function_exists('current_time') ? current_time( 'mysql' ) : date( 'Y-m-d H:i:s' ),
				'attempts'      => [],
			];
		}

		/** Append a single attempt row to tracker */
		private static function track_attempt( string $file, bool $ok, string $tag ): void {
			self::ensure_tracker();
			$GLOBALS['AAA_OC_LOADER_TRACKER']['attempts'][] = [
				'file'            => $file,
				'ok'              => $ok,
				'tag'             => $tag,
				'ts'              => function_exists('current_time') ? current_time( 'mysql' ) : date( 'Y-m-d H:i:s' ),
				'backtrace_first' => function_exists('wp_debug_backtrace_summary') ? wp_debug_backtrace_summary( null, 1 ) : '',
			];
			if ( AAA_OC_LOADER_UTIL_DEBUG && function_exists( 'error_log' ) ) {
				error_log( sprintf( '[AAA-OC][UTIL][%s] %s => %s', strtoupper($tag), $file, $ok ? 'OK' : 'MISS' ) );
			}
		}

		/** Public read-only accessor for the tracker */
		public static function get_tracker(): array {
			self::ensure_tracker();
			return $GLOBALS['AAA_OC_LOADER_TRACKER'];
		}

		/** (Optional) Clear attempts (keeps first_boot_ts) */
		public static function reset_attempts(): void {
			self::ensure_tracker();
			$GLOBALS['AAA_OC_LOADER_TRACKER']['attempts'] = [];
		}

		/** -------- existing API (unchanged signatures/behavior) -------- */

		/**
		 * Safe require with optional logging.
		 *
		 * @param string $file  Absolute file path.
		 * @param bool   $fatal If true and file missing, returns false (caller can decide to bail).
		 * @param string $tag   Log tag (module slug), e.g. 'payment', 'fulfillment'.
		 * @return bool  True when required; otherwise returns ! $fatal (preserves legacy behavior).
		 */
		public static function require_or_log( string $file, bool $fatal = false, string $tag = 'core' ) : bool {
			if ( $file && file_exists( $file ) ) {
				/** @noinspection PhpIncludeInspection */
				require_once $file;
				self::track_attempt( $file, true, $tag );   // NEW: track OK
				return true;
			}
			self::dlog( '[BOOT][' . $tag . '] Missing: ' . $file, true, strtoupper( $tag ) );
			self::track_attempt( $file, false, $tag );      // NEW: track MISS
			return ! $fatal; // preserve legacy return contract
		}

		/**
		 * Debug logger; uses global aaa_oc_log() when available.
		 *
		 * @param string $message
		 * @param bool   $on   When false, skip logging (allows per-file debug flags).
		 * @param string $tag  Optional tag prefix, e.g. 'PAYMENT'.
		 * @return void
		 */
		public static function dlog( string $message, bool $on = true, string $tag = 'CORE' ) : void {
			if ( ! $on ) return;
			if ( function_exists( 'aaa_oc_log' ) ) {
				aaa_oc_log( '[' . $tag . '] ' . $message );
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[AAA-OC][' . $tag . '] ' . $message );
			}
		}

		/**
		 * Is current admin screen the Workflow Board?
		 * Safe to call from anywhere (checks the screen and the ?page param).
		 *
		 * @return bool
		 */
		public static function is_board_screen() : bool {
			if ( ! is_admin() ) return false;
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( empty( $screen ) ) return false;
			$by_id   = strpos( (string) $screen->id, 'aaa-oc' ) !== false;
			$by_page = isset( $_GET['page'] ) && $_GET['page'] === 'aaa-oc-workflow-board';
			return ( $by_id && $by_page );
		}

		/**
		 * Enqueue a pair of board-only assets if we are on the Board screen.
		 * Returns true when enqueued, false otherwise.
		 *
		 * @param string      $handle_base Base handle suffix, e.g., 'productsearch'.
		 * @param string|null $css_rel     Relative path from this util's directory OR absolute URL.
		 * @param string|null $js_rel      Relative path from this util's directory OR absolute URL.
		 * @param array       $deps        Script deps.
		 * @param string      $ver         Version string (fallback to AAA_OC_VERSION if defined).
		 * @return bool
		 */
		public static function enqueue_board_assets( string $handle_base, ?string $css_rel, ?string $js_rel, array $deps = [ 'jquery' ], string $ver = '' ) : bool {
			if ( ! self::is_board_screen() ) return false;

			$ver = $ver ?: ( defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : '1.0.0' );

			// Resolve relative to the module file when a relative path is given.
			$to_url = function( ?string $rel_or_url ) : ?string {
				if ( empty( $rel_or_url ) ) return null;
				if ( preg_match( '#^https?://#i', $rel_or_url ) ) return $rel_or_url;
				// Prefer callers to pass absolute URLs. If relative is passed and plugin URL is known, resolve it.
				if ( defined( 'AAA_OC_PLUGIN_URL' ) ) {
					return trailingslashit( AAA_OC_PLUGIN_URL ) . ltrim( $rel_or_url, '/' );
				}
				return $rel_or_url; // fallback: allow absolute URL from caller
			};

			$h_css = 'aaa-oc-' . $handle_base;
			$h_js  = 'aaa-oc-' . $handle_base;

			if ( $css_rel ) {
				wp_enqueue_style( $h_css, $to_url( $css_rel ), [], $ver );
			}
			if ( $js_rel ) {
				wp_enqueue_script( $h_js, $to_url( $js_rel ), $deps, $ver, true );
			}
			return true;
		}
	}

	// Initial debug ping (optional)
	AAA_OC_Loader_Util::dlog( 'Loader Util ready', AAA_OC_LOADER_UTIL_DEBUG, 'UTIL' );
}

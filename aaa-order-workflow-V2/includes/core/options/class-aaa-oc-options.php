<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/options/class-aaa-oc-options.php
 * Purpose: Core logic and helpers for managing plugin options in the aaa_oc_options table.
 * Version: 1.2.0 (isolated per-file debug flag)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Local per-file debug toggle.
 * Safe even if other files define their own debug flags.
 */
if ( ! defined( 'AAA_OC_OPTIONS_FILE_DEBUG' ) ) {
	define( 'AAA_OC_OPTIONS_FILE_DEBUG', false );
}

class AAA_OC_Options {

	const TABLE = 'aaa_oc_options';

	/** Initialize global wrappers for backwards compatibility. */
	public static function init() {
		if ( ! function_exists( 'aaa_oc_get_option' ) ) {
			function aaa_oc_get_option( $key, $scope = 'global', $default = false ) {
				return AAA_OC_Options::get( $key, $scope, $default );
			}
		}
		if ( ! function_exists( 'aaa_oc_set_option' ) ) {
			function aaa_oc_set_option( $key, $value, $scope = 'global' ) {
				return AAA_OC_Options::set( $key, $value, $scope );
			}
		}
		if ( ! function_exists( 'aaa_oc_delete_option' ) ) {
			function aaa_oc_delete_option( $key, $scope = 'global' ) {
				return AAA_OC_Options::delete( $key, $scope );
			}
		}
	}

	/** Get an option value from the table. */
	public static function get( $key, $scope = 'global', $default = false ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM $table WHERE option_key = %s AND scope = %s LIMIT 1",
				$key,
				$scope
			)
		);

		if ( null === $value ) {
			return $default;
		}

		return maybe_unserialize( $value );
	}

	/** Set or update an option value in the table. */
	public static function set( $key, $value, $scope = 'global' ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$serialized = maybe_serialize( $value );

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE option_key = %s AND scope = %s LIMIT 1",
				$key,
				$scope
			)
		);

		if ( $exists ) {
			return $wpdb->update(
				$table,
				[ 'option_value' => $serialized, 'updated_at' => current_time( 'mysql' ) ],
				[ 'id' => $exists ],
				[ '%s', '%s' ],
				[ '%d' ]
			);
		}

		return $wpdb->insert(
			$table,
			[
				'option_key'   => $key,
				'option_value' => $serialized,
				'scope'        => $scope,
			],
			[ '%s', '%s', '%s' ]
		);
	}

	/** Delete an option by key/scope. */
	public static function delete( $key, $scope = 'global' ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		return $wpdb->delete(
			$table,
			[ 'option_key' => $key, 'scope' => $scope ],
			[ '%s', '%s' ]
		);
	}
}

if ( AAA_OC_OPTIONS_FILE_DEBUG ) {
	error_log( '[AAA_OC_Options] Loaded core option helpers.' );
}

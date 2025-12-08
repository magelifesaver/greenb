<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board-order-counter/helpers/board-order-counter-helpers.php
 * Purpose: Core logic and logging helpers for the Daily Order Counter (V1, using aaa_oc_options table).
 * Version: 1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Local debug flag (guarded to prevent “already defined” warnings)
if ( ! defined( 'DEBUG_THIS_FILE' ) ) {
	define( 'DEBUG_THIS_FILE', true );
}

/**
 * Fetch module setting from custom table aaa_oc_options.
 */
function aaa_oc_odc_get_setting( string $key, $default = false ) {
	global $wpdb;
	$table = $wpdb->prefix . 'aaa_oc_options';
	$sql   = $wpdb->prepare(
		"SELECT option_value FROM {$table} WHERE option_key = %s AND scope = %s LIMIT 1",
		$key,
		'odc'
	);
	$value = $wpdb->get_var( $sql );

	if ( $value === null ) {
		return $default;
	}

	$maybe = maybe_unserialize( $value );

	if ( defined( 'DEBUG_THIS_FILE' ) && DEBUG_THIS_FILE ) {
		error_log( "[ODC] Get setting {$key} => " . print_r( $maybe, true ) );
	}
	return $maybe;
}

/**
 * Save module setting into custom table aaa_oc_options.
 */
function aaa_oc_odc_set_setting( string $key, $value ) {
	global $wpdb;
	$table = $wpdb->prefix . 'aaa_oc_options';
	$serialized = maybe_serialize( $value );

	$exists = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE option_key = %s AND scope = %s",
			$key,
			'odc'
		)
	);

	if ( $exists > 0 ) {
		$wpdb->update(
			$table,
			[
				'option_value' => $serialized,
				'updated_at'   => current_time( 'mysql' ),
			],
			[
				'option_key' => $key,
				'scope'      => 'odc',
			]
		);
	} else {
		$wpdb->insert(
			$table,
			[
				'option_key'   => $key,
				'option_value' => $serialized,
				'autoload'     => 'no',
				'scope'        => 'odc',
				'updated_at'   => current_time( 'mysql' ),
			]
		);
	}

	if ( defined( 'DEBUG_THIS_FILE' ) && DEBUG_THIS_FILE ) {
		error_log( "[ODC] Set setting {$key} => " . print_r( $value, true ) );
	}
}

/**
 * Assigns / increments daily order count.
 */
function aaa_oc_board_counter_set_daily_number( int $order_id ) : void {
	$existing = get_post_meta( $order_id, '_daily_order_number', true );
	if ( $existing ) {
		if ( aaa_oc_odc_get_setting( 'enable_logging' ) ) {
			error_log( "[ODC] Skip – Order #{$order_id} already has DON={$existing}" );
		}
		return;
	}

	$today = current_time( 'Y-m-d' );
	$key   = "daily_order_count_{$today}";
	$count = (int) aaa_oc_odc_get_setting( $key, 0 );
	$count++;

	aaa_oc_odc_set_setting( $key, $count );
	update_post_meta( $order_id, '_daily_order_number', $count );

	if ( aaa_oc_odc_get_setting( 'enable_logging' ) ) {
		error_log( "[ODC] Order #{$order_id} → DON={$count}" );
	}
	if ( aaa_oc_odc_get_setting( 'enable_file_logging' ) ) {
		aaa_oc_board_counter_file_log( "Order #{$order_id} → DON={$count}" );
	}
}

/**
 * Append a message to /wp-content/logs/odc.log
 */
function aaa_oc_board_counter_file_log( string $message ) : void {
	$dir = WP_CONTENT_DIR . '/logs';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	$file = "{$dir}/odc.log";
	$ts   = current_time( 'Y-m-d H:i:s' );
	file_put_contents( $file, "[{$ts}] {$message}\n", FILE_APPEND );
}

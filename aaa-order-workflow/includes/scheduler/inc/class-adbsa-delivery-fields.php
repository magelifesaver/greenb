<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/scheduler/inc/class-adbsa-delivery-fields.php
 * Purpose: Register dynamic checkout fields that switch between Same-Day and Scheduled modes.
 * Version: 1.6.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Local debug toggle.
 * To enable logs only for this file, define('ADBSA_FIELDS_DEBUG', true); in wp-config.php.
 */
if ( ! defined( 'ADBSA_FIELDS_DEBUG' ) ) {
	define( 'ADBSA_FIELDS_DEBUG', false );
}

if ( ! class_exists( 'ADBSA_Delivery_Fields' ) ) :

final class ADBSA_Delivery_Fields {

	public static function init() : void {
		add_action( 'woocommerce_init', [ __CLASS__, 'register_fields' ] );
	}

	public static function register_fields() : void {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			self::log( 'Checkout Fields API not available.' );
			return;
		}

		$mode = self::detect_active_mode();
		self::log( 'Detected checkout mode', $mode );

		// Build option arrays
		$date_options = self::build_date_options( $mode );
		$time_options = self::build_time_options( $mode );

		// Only register when options exist
		if ( ! empty( $date_options ) ) {
			woocommerce_register_additional_checkout_field( [
				'id'            => 'adbsa/delivery-date',
				'label'         => 'Delivery Date',
				'optionalLabel' => 'Delivery Date',
				'location'      => 'order',
				'type'          => 'select',
				'required'      => true,
				'placeholder'   => 'Select a date',
				'options'       => $date_options,
			] );
			self::log( 'Registered delivery-date (' . count( $date_options ) . ' options)' );
		} else {
			self::log( 'Skipped registering delivery-date (no options)' );
		}

		if ( ! empty( $time_options ) ) {
			woocommerce_register_additional_checkout_field( [
				'id'            => 'adbsa/delivery-time',
				'label'         => 'Delivery Time',
				'optionalLabel' => 'Delivery Time',
				'location'      => 'order',
				'type'          => 'select',
				'required'      => true,
				'placeholder'   => 'Select a time',
				'options'       => $time_options,
			] );
			self::log( 'Registered delivery-time (' . count( $time_options ) . ' options)' );
		} else {
			self::log( 'Skipped registering delivery-time (no options)' );
		}

		self::log( 'Registration check complete for mode ' . $mode );
	}

	private static function detect_active_mode() : string {
		global $wpdb;
		$table = $wpdb->prefix . 'aaa_oc_options';

		$sameday_row   = $wpdb->get_var( "SELECT option_value FROM {$table} WHERE option_key = 'adbsa_options_sameday' LIMIT 1" );
		$scheduled_row = $wpdb->get_var( "SELECT option_value FROM {$table} WHERE option_key = 'adbsa_options_scheduled' LIMIT 1" );

		$sameday   = maybe_unserialize( $sameday_row );
		$scheduled = maybe_unserialize( $scheduled_row );

		$chosen_methods = ( function_exists( 'WC' ) && WC()->session )
			? (array) WC()->session->get( 'chosen_shipping_methods' )
			: [];

		$chosen = $chosen_methods[0] ?? '';
		self::log( 'Chosen shipping method', $chosen );

		if ( isset( $sameday['enabled'], $sameday['method_instance_id'] )
			&& $sameday['enabled']
			&& strpos( $chosen, (string) $sameday['method_instance_id'] ) !== false ) {
			return 'sameday';
		}

		if ( isset( $scheduled['enabled'], $scheduled['method_instance_id'] )
			&& $scheduled['enabled']
			&& strpos( $chosen, (string) $scheduled['method_instance_id'] ) !== false ) {
			return 'scheduled';
		}

		return 'none';
	}

	private static function build_date_options( string $mode ) : array {
		$tz  = wp_timezone();
		$now = new DateTimeImmutable( 'now', $tz );

		if ( $mode === 'sameday' ) {
			$ymd = $now->format( 'Y-m-d' );
			$lab = wp_date( 'l, F j, Y', $now->getTimestamp(), $tz );
			return [ [ 'value' => $ymd, 'label' => $lab ] ];
		}

		if ( $mode === 'scheduled' ) {
			global $wpdb;
			$table = $wpdb->prefix . 'aaa_oc_options';
			$row = $wpdb->get_var( "SELECT option_value FROM {$table} WHERE option_key = 'adbsa_options_scheduled' LIMIT 1" );
			$cfg = maybe_unserialize( $row );
			$start_offset = (int) ( $cfg['start_offset_days'] ?? 1 );
			$total_days   = (int) ( $cfg['total_days'] ?? 7 );

			$out = [];
			for ( $i = $start_offset; $i < $start_offset + $total_days; $i++ ) {
				$d    = $now->modify( "+{$i} day" );
				$ymd  = $d->format( 'Y-m-d' );
				$lab  = wp_date( 'l, F j, Y', $d->getTimestamp(), $tz );
				$out[] = [ 'value' => $ymd, 'label' => $lab ];
			}
			return $out;
		}

		return [];
	}

	private static function build_time_options( string $mode ) : array {
		if ( $mode === 'sameday' && class_exists( 'ADBSA_Delivery_SameDay' ) ) {
			return ADBSA_Delivery_SameDay::build_slots();
		}

		if ( $mode === 'scheduled' ) {
			global $wpdb;
			$table = $wpdb->prefix . 'aaa_oc_options';
			$row = $wpdb->get_var( "SELECT option_value FROM {$table} WHERE option_key = 'adbsa_options_scheduled' LIMIT 1" );
			$cfg = maybe_unserialize( $row );
			$start  = $cfg['start_time'] ?? '11:00';
			$end    = $cfg['end_time'] ?? '21:45';
			$window = max( 1, (int) ( $cfg['slot_window_minutes'] ?? 60 ) );
			$step   = max( 1, (int) ( $cfg['slot_step_minutes'] ?? $window ) );

			$out = [];
			$tz = wp_timezone();
			$tStart = DateTimeImmutable::createFromFormat( 'H:i', $start, $tz );
			$tEnd   = DateTimeImmutable::createFromFormat( 'H:i', $end, $tz );
			if ( ! $tStart || ! $tEnd ) return $out;

			for ( $t = $tStart; $t < $tEnd; $t = $t->modify("+{$step} minutes") ) {
				$t2 = $t->modify("+{$window} minutes");
				if ( $t2 > $tEnd ) $t2 = $tEnd;
				$label = sprintf(
					'From %s - To %s',
					strtolower( wp_date( 'g:i a', $t->getTimestamp(), $tz ) ),
					strtolower( wp_date( 'g:i a', $t2->getTimestamp(), $tz ) )
				);
				$out[] = [ 'value' => $label, 'label' => $label ];
			}
			return $out;
		}

		return [];
	}

	private static function log( $msg, $ctx = null ) : void {
		if ( ! ADBSA_FIELDS_DEBUG ) { return; }
		$line = '[ADBSA-Fields] ' . ( is_string( $msg ) ? $msg : wp_json_encode( $msg ) );
		if ( $ctx !== null ) {
			$line .= ' | ' . ( is_string( $ctx ) ? $ctx : wp_json_encode( $ctx ) );
		}
		error_log( $line );
	}
}

ADBSA_Delivery_Fields::init();
endif;

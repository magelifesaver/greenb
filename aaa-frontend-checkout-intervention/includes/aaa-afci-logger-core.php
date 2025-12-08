<?php
/**
 * File: /includes/aaa-afci-logger-core.php
 * Purpose: Core logging logic — REST endpoint, cookies, file logs,
 *          and writes via Table Managers (with noisy-event de-dup).
 * Version: 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* --------------------------------------------------------------------------
 * CONSTANTS (guarded)
 * -------------------------------------------------------------------------- */
if ( ! defined( 'AAA_FCI_LOG_DIR' ) ) {
	define( 'AAA_FCI_LOG_DIR', AAA_FCI_PATH . 'logs/' );
	define( 'AAA_FCI_LOG_FILE', AAA_FCI_LOG_DIR . 'aaa-checkout-intervention.log' );
	define( 'AAA_FCI_COOKIE',   'aaa_cis' );
}

/* --------------------------------------------------------------------------
 * SESSION COOKIE
 * -------------------------------------------------------------------------- */
function aaa_fci_get_session_key() {
	if ( isset( $_COOKIE[ AAA_FCI_COOKIE ] ) && preg_match( '/^[a-zA-Z0-9\-_]{10,64}$/', $_COOKIE[ AAA_FCI_COOKIE ] ) ) {
		return $_COOKIE[ AAA_FCI_COOKIE ];
	}
	$key = wp_generate_password( 32, false, false );
	setcookie( AAA_FCI_COOKIE, $key, time() + DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'New session cookie set', [ 'key' => $key ] );
	}
	return $key;
}
add_action( 'wp_logout', function() {
	if ( isset( $_COOKIE[ AAA_FCI_COOKIE ] ) ) {
		setcookie( AAA_FCI_COOKIE, '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN );
		if ( function_exists( 'aaa_fci_debug_log' ) ) {
			aaa_fci_debug_log( 'Session cookie cleared on logout' );
		}
	}
});

/* --------------------------------------------------------------------------
 * FILE LOG
 * -------------------------------------------------------------------------- */
function aaa_fci_file_log( $line ) {
	if ( ! file_exists( AAA_FCI_LOG_DIR ) ) wp_mkdir_p( AAA_FCI_LOG_DIR );
	$ts = date_i18n( 'Y-m-d H:i:s' );
	@error_log( "[$ts] $line\n", 3, AAA_FCI_LOG_FILE );
	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'FileLog', [ 'line' => $line ] );
	}
}

/* --------------------------------------------------------------------------
 * DATABASE LOGGING (keeps logging — filters noisy script errors)
 * -------------------------------------------------------------------------- */
function aaa_fci_db_log( $session_key, $type, $payload = [] ) {
	$user_id = get_current_user_id() ?: 0;

	// 🔹 Ignore generic external script errors
	if ( $type === 'js_error' && isset( $payload['message'] ) ) {
		$msg = strtolower( trim( (string) $payload['message'] ) );
		$src = isset( $payload['src'] ) ? trim( (string) $payload['src'] ) : '';

		if ( $msg === 'script error.' || ( $src && strpos( $src, home_url() ) === false ) ) {
			if ( function_exists( 'aaa_fci_debug_log' ) ) {
				aaa_fci_debug_log( 'Skipped cross-domain js_error', [ 'msg' => $msg, 'src' => $src ] );
			}
			return; // ✅ skip harmless cross-domain analytics/ad errors
		}
	}

	// De-dup fetch & wc_fetch to avoid explosion
	$event_id = AAA_AFCI_Table_Manager::insert_or_bump_event( $session_key, $type, $payload, $user_id );

	// Optional: granular details for diagnostic-rich events
	if ( $event_id && is_array( $payload ) && ! empty( $payload ) ) {
		foreach ( $payload as $key => $val ) {
			if ( is_array( $val ) ) {
				foreach ( $val as $subkey => $subval ) {
					AAA_AFCI_Detail_Manager::insert_detail( $event_id, $subkey, $type, $subval );
				}
			} else {
				AAA_AFCI_Detail_Manager::insert_detail( $event_id, $key, $type, $val );
			}
		}

		// Address bucketing (friendly context)
		if ( isset( $payload['name'] ) && preg_match( '/address|city|zip|state|postcode|country/i', (string) $payload['name'] ) ) {
			AAA_AFCI_Detail_Manager::insert_detail(
				$event_id,
				$payload['name'],
				'address',
				$payload['value'] ?? ''
			);
		}
	}

	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'DB logged event', [
			'id'      => (int) $event_id,
			'type'    => (string) $type,
			'user_id' => (int) $user_id,
			'keys'    => is_array( $payload ) ? array_keys( $payload ) : []
		] );
	}

	aaa_fci_file_log( "session={$session_key} type={$type}" );
}

/* --------------------------------------------------------------------------
 * FRONTEND LOGGER INJECTION (checkout only)
 * -------------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! function_exists('is_checkout') || ! is_checkout() ) return;

	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'Enqueue frontend logger on checkout' );
	}

	wp_enqueue_script(
		'aaa-fci-logger',
		AAA_FCI_URL . 'assets/js/aaa-fci-logger.js',
		[],
		AAA_FCI_VERSION,
		true
	);

	wp_add_inline_script(
		'aaa-fci-logger',
		'window.AAA_FCI = ' . wp_json_encode([
			'rest'         => esc_url_raw( rest_url( 'aaa-fci/v1/log' ) ),
			'session_key'  => aaa_fci_get_session_key(),
			'is_checkout'  => true,
			'rest_nonce'   => wp_create_nonce( 'wp_rest' ),
			'debug'        => function_exists( 'aaa_fci_debug_enabled' ) && aaa_fci_debug_enabled(),
		]) . ';',
		'before'
	);
});

/* --------------------------------------------------------------------------
 * REST ENDPOINT /aaa-fci/v1/log
 * -------------------------------------------------------------------------- */
add_action( 'rest_api_init', function() {
	register_rest_route( 'aaa-fci/v1', '/log', [
		'methods'  => 'POST',
		'permission_callback' => '__return_true',
		'callback' => function( WP_REST_Request $r ) {
			$session = sanitize_text_field( $r->get_param( 'session_key' ) );
			$events  = $r->get_param( 'events' );

			// Allow either array or JSON string
			if ( is_string( $events ) ) {
				$decoded = json_decode( $events, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$events = $decoded;
				}
			}

			if ( ! $session || ! is_array( $events ) ) {
				if ( function_exists( 'aaa_fci_debug_log' ) ) {
					aaa_fci_debug_log( 'REST invalid payload', [ 'session' => $session, 'events_type' => gettype( $events ) ] );
				}
				return new WP_REST_Response( [ 'error' => 'invalid payload' ], 400 );
			}

			foreach ( $events as $e ) {
				$type    = sanitize_text_field( $e['type'] ?? 'event' );
				$payload = is_array( $e['payload'] ?? null ) ? $e['payload'] : [];
				aaa_fci_db_log( $session, $type, $payload );
			}

			if ( function_exists( 'aaa_fci_debug_log' ) ) {
				aaa_fci_debug_log( 'REST received events', [ 'count' => count( $events ), 'session' => $session ] );
			}

			return new WP_REST_Response( [ 'received' => count( $events ) ], 200 );
		},
	] );
});

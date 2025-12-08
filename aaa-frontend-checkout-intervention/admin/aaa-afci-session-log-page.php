<?php
/**
 * File: /admin/aaa-afci-session-log-page.php
 * Purpose: Unified "Session Log" page with inline expanders (Sessions → Events → Details)
 *          + checkbox-based bulk export of selected sessions.
 * Version: 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin Menu
 */
add_action( 'admin_menu', function() {
	add_menu_page(
		'Checkout Sessions',
		'Checkout Sessions',
		'manage_woocommerce',
		'aaa-afci-settings',
		'aaa_afci_session_log_render',
		'dashicons-list-view',
		58
	);
});

/**
 * Page Render
 */
function aaa_afci_session_log_render() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'Render: Checkout Sessions page' );
	}

	$summaries = AAA_AFCI_Table_Manager::get_sessions_summary( 100, false );
	$nonce     = wp_create_nonce( 'afci_admin' );

	?>
	<div class="wrap afci-wrap">
		<h1>Checkout Sessions</h1>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="afci_export_selected_sessions" />
			<?php wp_nonce_field( 'afci_export_selected_sessions' ); ?>

			<div class="afci-toolbar">
				<button type="button" id="afci-refresh" class="button">🔁 Refresh</button>

				<select id="afci-filter" class="afci-filter">
					<option value="">All Sessions</option>
					<option value="errors">Only Sessions with Errors</option>
				</select>

				<button type="submit" name="afci_export_selected_sessions_btn" class="button button-primary">
					📥 Export Selected
				</button>

				<span class="afci-legend">
					<span class="afci-badge ok">OK</span>
					<span class="afci-badge warn">Warn</span>
					<span class="afci-badge err">Error</span>
				</span>
				<?php if ( function_exists( 'aaa_fci_debug_enabled' ) && aaa_fci_debug_enabled() ) : ?>
					<span class="afci-badge info" title="Debug mode is ON">DEBUG</span>
				<?php endif; ?>
			</div>

			<table class="widefat striped afci-table" id="afci-sessions-table">
				<thead>
					<tr>
						<th style="width:28px;">
							<!-- Per-row checkboxes live in tbody; header left blank or used later for "select all" -->
						</th>
						<th style="width:36px;"></th>
						<th>Session</th>
						<th>User</th>
						<th>IP</th>
						<th># Events</th>
						<th>First</th>
						<th>Last</th>
						<th>Status</th>
						<th style="width:120px;">Export</th>
					</tr>
				</thead>
				<tbody>
					<?php echo esc_html( '' ); // placeholder to avoid PHPCS ?>
					<?php foreach ( $summaries as $s ) :
						$err     = intval( $s->err_count );
						$status  = $err > 0 ? 'err' : 'ok';
						$badge   = ( $status === 'ok' ) ? '✅ OK' : '❌ Error';
						$user    = absint( $s->user_id );
						$first   = $s->first_at ? get_date_from_gmt( $s->first_at, 'Y-m-d H:i:s' ) : '';
						$last    = $s->last_at  ? get_date_from_gmt( $s->last_at,  'Y-m-d H:i:s' ) : '';
						?>
						<tr class="afci-session-row" data-session="<?php echo esc_attr( $s->session_key ); ?>">
							<td>
								<input type="checkbox"
								       class="afci-select-session"
								       name="session_keys[]"
								       value="<?php echo esc_attr( $s->session_key ); ?>" />
							</td>
							<td>
								<button class="button afci-expand" type="button" title="Show events" aria-expanded="false">▸</button>
							</td>
							<td><code><?php echo esc_html( $s->session_key ); ?></code></td>
							<td>
								<?php
								echo $user
									? '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $user ) ) . '">#' . intval( $user ) . '</a>'
									: '—';
								?>
							</td>
							<td><?php echo esc_html( $s->ip_address ?: '—' ); ?></td>
							<td><?php echo esc_html( $s->event_count ); ?></td>
							<td><?php echo esc_html( $first ); ?></td>
							<td><?php echo esc_html( $last ); ?></td>
							<td>
								<span class="afci-badge <?php echo esc_attr( $status ); ?>">
									<?php echo esc_html( $badge ); ?>
								</span>
							</td>
							<td>
								<button class="button afci-export-session" type="button" data-session="<?php echo esc_attr( $s->session_key ); ?>">
									JSON
								</button>
							</td>
						</tr>
						<tr class="afci-expander-row" data-session="<?php echo esc_attr( $s->session_key ); ?>" style="display:none;">
							<td colspan="10">
								<div class="afci-expander-body">
									<div class="afci-expander-toolbar">
										<strong>Events for session:</strong>
										<code><?php echo esc_html( $s->session_key ); ?></code>
									</div>
									<table class="widefat striped afci-events">
										<thead>
											<tr>
												<th style="width:36px;"></th>
												<th>Type</th>
												<th>Created</th>
												<th>Updated</th>
												<th>Repeat</th>
												<th>Summary</th>
												<th>Status</th>
												<th style="width:90px;">Actions</th>
											</tr>
										</thead>
										<tbody class="afci-events-body" data-session="<?php echo esc_attr( $s->session_key ); ?>">
											<tr><td colspan="8">Loading…</td></tr>
										</tbody>
									</table>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<input type="hidden" id="afci-admin-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
		</form>
	</div>
	<?php
}

/**
 * ============= AJAX ENDPOINTS (admin only) =============
 */

function afci_check_ajax_cap() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		if ( function_exists( 'aaa_fci_debug_log' ) ) {
			aaa_fci_debug_log( 'AJAX cap check failed' );
		}
		wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'afci_admin' ) ) {
		if ( function_exists( 'aaa_fci_debug_log' ) ) {
			aaa_fci_debug_log( 'AJAX bad nonce' );
		}
		wp_send_json_error( [ 'message' => 'Bad nonce.' ], 400 );
	}

	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'AJAX cap check passed' );
	}
}

/**
 * Refresh sessions list
 */
add_action( 'wp_ajax_afci_list_sessions', function() {
	afci_check_ajax_cap();

	$errors_only = ! empty( $_POST['errors_only'] );
	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'afci_list_sessions', [ 'errors_only' => (int) $errors_only ] );
	}

	$rows = AAA_AFCI_Table_Manager::get_sessions_summary( 100, (bool) $errors_only );

	ob_start();
	foreach ( $rows as $s ) {
		$err     = intval( $s->err_count );
		$status  = $err > 0 ? 'err' : 'ok';
		$badge   = ( $status === 'ok' ) ? '✅ OK' : '❌ Error';
		$user    = absint( $s->user_id );
		$first   = $s->first_at ? get_date_from_gmt( $s->first_at, 'Y-m-d H:i:s' ) : '';
		$last    = $s->last_at  ? get_date_from_gmt( $s->last_at,  'Y-m-d H:i:s' ) : '';
		?>
		<tr class="afci-session-row" data-session="<?php echo esc_attr( $s->session_key ); ?>">
			<td>
				<input type="checkbox"
				       class="afci-select-session"
				       name="session_keys[]"
				       value="<?php echo esc_attr( $s->session_key ); ?>" />
			</td>
			<td>
				<button class="button afci-expand" type="button" title="Show events" aria-expanded="false">▸</button>
			</td>
			<td><code><?php echo esc_html( $s->session_key ); ?></code></td>
			<td>
				<?php
				echo $user
					? '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $user ) ) . '">#' . intval( $user ) . '</a>'
					: '—';
				?>
			</td>
			<td><?php echo esc_html( $s->ip_address ?: '—' ); ?></td>
			<td><?php echo esc_html( $s->event_count ); ?></td>
			<td><?php echo esc_html( $first ); ?></td>
			<td><?php echo esc_html( $last ); ?></td>
			<td>
				<span class="afci-badge <?php echo esc_attr( $status ); ?>">
					<?php echo esc_html( $badge ); ?>
				</span>
			</td>
			<td>
				<button class="button afci-export-session" type="button" data-session="<?php echo esc_attr( $s->session_key ); ?>">
					JSON
				</button>
			</td>
		</tr>
		<tr class="afci-expander-row" data-session="<?php echo esc_attr( $s->session_key ); ?>" style="display:none;">
			<td colspan="10">
				<div class="afci-expander-body">
					<div class="afci-expander-toolbar">
						<strong>Events for session:</strong>
						<code><?php echo esc_html( $s->session_key ); ?></code>
					</div>
					<table class="widefat striped afci-events">
						<thead>
							<tr>
								<th style="width:36px;"></th>
								<th>Type</th>
								<th>Created</th>
								<th>Updated</th>
								<th>Repeat</th>
								<th>Summary</th>
								<th>Status</th>
								<th style="width:90px;">Actions</th>
							</tr>
						</thead>
						<tbody class="afci-events-body" data-session="<?php echo esc_attr( $s->session_key ); ?>">
							<tr><td colspan="8">Loading…</td></tr>
						</tbody>
					</table>
				</div>
			</td>
		</tr>
		<?php
	}
	$html = ob_get_clean();

	wp_send_json_success( [ 'html' => $html ] );
});

/**
 * Fetch events for a session
 */
add_action( 'wp_ajax_afci_fetch_session_events', function() {
	afci_check_ajax_cap();

	$session_key = sanitize_text_field( $_POST['session_key'] ?? '' );
	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'afci_fetch_session_events', [ 'session_key' => $session_key ] );
	}
	if ( ! $session_key ) {
		wp_send_json_error( [ 'message' => 'Missing session_key' ], 400 );
	}

	$events = AAA_AFCI_Table_Manager::get_events_by_session( $session_key, 500 );

	ob_start();
	foreach ( $events as $e ) {
		$summary = '';
		$payload = json_decode( (string) $e->event_payload, true );
		switch ( $e->event_type ) {
			case 'click':
				$summary = trim( ( $payload['tag'] ?? '' ) . ' #' . ( $payload['id'] ?? '' ) . ' ' . ( $payload['text'] ?? '' ) );
				break;
			case 'input':
				$summary = 'Field: ' . ( $payload['name'] ?? '' ) . ' = ' . ( isset( $payload['value'] ) ? '[masked?]' : '' );
				break;
			case 'wc_fetch':
			case 'fetch':
				$summary = ( $payload && isset( $payload['url'] ) ) ? $payload['url'] : '(no url)';
				break;
			case 'js_error':
			case 'js_unhandled':
				$summary = ( $payload && isset( $payload['message'] ) ) ? $payload['message'] : 'JS error';
				break;
			case 'block_validation':
				$summary = 'Field: ' . ( $payload['field'] ?? 'unknown' );
				break;
			default:
				$summary = $payload ? substr( wp_json_encode( $payload ), 0, 140 ) : '';
				break;
		}

		$created = $e->created_at ? get_date_from_gmt( $e->created_at, 'Y-m-d H:i:s' ) : '';
		$updated = $e->updated_at ? get_date_from_gmt( $e->updated_at, 'Y-m-d H:i:s' ) : '';
		$bad    = in_array( $e->event_type, [ 'js_error', 'js_unhandled', 'block_validation' ], true );
		$status = $bad ? 'err' : 'ok';
		$badge  = $bad ? '❌' : '✅';

		?>
		<tr class="afci-event-row" data-event="<?php echo esc_attr( $e->id ); ?>">
			<td>
				<button class="button afci-event-expand" type="button" title="Show details" aria-expanded="false">▸</button>
			</td>
			<td><code><?php echo esc_html( $e->event_type ); ?></code></td>
			<td><?php echo esc_html( $created ); ?></td>
			<td><?php echo esc_html( $updated ); ?></td>
			<td><span class="afci-badge info"><?php echo intval( $e->repeat_count ); ?></span></td>
			<td class="afci-summary"><?php echo esc_html( $summary ); ?></td>
			<td>
				<span class="afci-badge <?php echo esc_attr( $status ); ?>">
					<?php echo esc_html( $badge ); ?>
				</span>
			</td>
			<td>
				<button class="button afci-export-event" type="button" data-event="<?php echo esc_attr( $e->id ); ?>">
					JSON
				</button>
			</td>
		</tr>
		<tr class="afci-event-expander-row" data-event="<?php echo esc_attr( $e->id ); ?>" style="display:none;">
			<td colspan="8">
				<div class="afci-event-details">
					Loading…
				</div>
			</td>
		</tr>
		<?php
	}
	$html = ob_get_clean();

	wp_send_json_success( [ 'html' => $html ] );
});

/**
 * Fetch event details (detail table + payload pretty)
 */
add_action( 'wp_ajax_afci_fetch_event_details', function() {
	afci_check_ajax_cap();

	$event_id = absint( $_POST['event_id'] ?? 0 );
	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'afci_fetch_event_details', [ 'event_id' => $event_id ] );
	}
	if ( ! $event_id ) {
		wp_send_json_error( [ 'message' => 'Missing event_id' ], 400 );
	}

	$event   = AAA_AFCI_Table_Manager::get_event( $event_id );
	$details = AAA_AFCI_Detail_Manager::get_details_by_event( $event_id );

	$payload = [];
	if ( $event && ! empty( $event->event_payload ) ) {
		$decoded = json_decode( $event->event_payload, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			$payload = $decoded;
		}
	}

	ob_start();
	?>
	<div class="afci-details-wrap">
		<div class="afci-details-columns">
			<div class="afci-details-col">
				<h4>Detail Fields</h4>
				<?php if ( ! empty( $details ) ) : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>Field</th>
								<th>Context</th>
								<th>Value</th>
								<th>Logged</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $details as $d ) : ?>
								<tr>
									<td><code><?php echo esc_html( $d->field ?: '-' ); ?></code></td>
									<td><?php echo esc_html( $d->context ?: '-' ); ?></td>
									<td>
										<code>
											<?php
											$val = maybe_unserialize( $d->value );
											echo esc_html( is_scalar( $val ) ? (string) $val : print_r( $val, true ) ); // phpcs:ignore
											?>
										</code>
									</td>
									<td>
										<?php
										echo esc_html(
											$d->created_at
												? get_date_from_gmt( $d->created_at, 'Y-m-d H:i:s' )
												: ''
										);
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p>No granular details recorded for this event.</p>
				<?php endif; ?>
			</div>
			<div class="afci-details-col">
				<h4>Raw Payload</h4>
				<pre><?php echo esc_html( wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
			</div>
		</div>
	</div>
	<?php
	$html = ob_get_clean();

	wp_send_json_success(
		[
			'html'  => $html,
			'event' => $event_id,
		]
	);
});

/**
 * Exporters (JSON blobs) — session and event
 */
add_action( 'wp_ajax_afci_export_session', function() {
	afci_check_ajax_cap();

	$session_key = sanitize_text_field( $_POST['session_key'] ?? '' );
	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'afci_export_session', [ 'session_key' => $session_key ] );
	}
	if ( ! $session_key ) {
		wp_send_json_error( [ 'message' => 'Missing session_key' ], 400 );
	}

	$events = AAA_AFCI_Table_Manager::get_events_by_session( $session_key, 1000 );
	$out    = [];
	foreach ( $events as $e ) {
		$out[] = [
			'id'          => (int) $e->id,
			'session_key' => $e->session_key,
			'user_id'     => (int) $e->user_id,
			'type'        => $e->event_type,
			'repeat'      => (int) $e->repeat_count,
			'payload'     => json_decode( (string) $e->event_payload, true ),
			'created_at'  => $e->created_at,
			'updated_at'  => $e->updated_at,
		];
	}
	wp_send_json_success( [ 'json' => $out ] );
});

add_action( 'wp_ajax_afci_export_event', function() {
	afci_check_ajax_cap();

	$event_id = absint( $_POST['event_id'] ?? 0 );
	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'afci_export_event', [ 'event_id' => $event_id ] );
	}
	if ( ! $event_id ) {
		wp_send_json_error( [ 'message' => 'Missing event_id' ], 400 );
	}

	$event   = AAA_AFCI_Table_Manager::get_event( $event_id );
	$details = AAA_AFCI_Detail_Manager::get_details_by_event( $event_id );

	$out = [
		'event'   => [
			'id'          => (int) $event->id,
			'session_key' => $event->session_key,
			'user_id'     => (int) $event->user_id,
			'type'        => $event->event_type,
			'repeat'      => (int) $event->repeat_count,
			'payload'     => json_decode( (string) $event->event_payload, true ),
			'created_at'  => $event->created_at,
			'updated_at'  => $event->updated_at,
		],
		'details' => array_map(
			function( $d ) {
				return [
					'id'         => (int) $d->id,
					'field'      => $d->field,
					'context'    => $d->context,
					'value'      => maybe_serialize( maybe_unserialize( $d->value ) ), // keep as stored
					'created_at' => $d->created_at,
				];
			},
			$details
		),
	];

	wp_send_json_success( [ 'json' => $out ] );
});

/**
 * Bulk export handler (admin-post) for selected sessions
 *
 * Uses the same JSON structure as the dedicated Export page:
 * meta + sessions + details.
 */
add_action( 'admin_post_afci_export_selected_sessions', function() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Permission denied.' );
	}

	check_admin_referer( 'afci_export_selected_sessions' );

	$session_keys = isset( $_POST['session_keys'] ) ? (array) $_POST['session_keys'] : [];
	$session_keys = array_filter( array_map( 'sanitize_text_field', $session_keys ) );

	if ( empty( $session_keys ) ) {
		wp_die( 'No sessions selected.' );
	}

	global $wpdb;
	$table_s = $wpdb->prefix . 'aaa_checkout_sessions';
	$table_d = $wpdb->prefix . 'aaa_checkout_event_details';

	// Fetch events for selected sessions
	$placeholders = implode( ',', array_fill( 0, count( $session_keys ), '%s' ) );
	$sql          = "SELECT * FROM {$table_s} WHERE session_key IN ({$placeholders}) ORDER BY id ASC";
	$sessions     = $wpdb->get_results( $wpdb->prepare( $sql, $session_keys ) );

	$details = [];
	if ( $sessions ) {
		$event_ids = wp_list_pluck( $sessions, 'id' );
		$event_ids = array_filter( array_map( 'absint', $event_ids ) );

		if ( $event_ids ) {
			$in      = implode( ',', $event_ids );
			$details = $wpdb->get_results( "SELECT * FROM {$table_d} WHERE event_id IN ({$in}) ORDER BY id ASC" );
		}
	}

	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log(
			'Bulk export selected sessions',
			[
				'keys'          => $session_keys,
				'total_events'  => count( $sessions ),
				'total_details' => count( $details ),
			]
		);
	}

	// Reuse the same JSON download routine if available
	if ( function_exists( 'aaa_afci_output_json_export' ) ) {
		aaa_afci_output_json_export(
			$sessions,
			$details,
			[
				'mode'         => 'selected_sessions',
				'session_keys' => $session_keys,
			]
		);
	}

	// Fallback: simple JSON output (should not normally be used)
	$payload = [
		'meta'     => [
			'exported_at'    => current_time( 'mysql' ),
			'mode'           => 'selected_sessions',
			'session_keys'   => $session_keys,
			'site'           => get_bloginfo( 'name' ),
			'version'        => defined( 'AAA_FCI_VERSION' ) ? AAA_FCI_VERSION : 'unknown',
			'total_sessions' => count( $sessions ),
			'total_details'  => count( $details ),
		],
		'sessions' => $sessions,
		'details'  => $details,
	];

	$json     = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	$filename = 'afci-selected-sessions-' . date( 'Ymd-His' ) . '.json';

	if ( ! headers_sent() ) {
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Length: ' . strlen( $json ) );
	}
	echo $json; // phpcs:ignore
	exit;
});

<?php
/**
 * File: /wp-content/plugins/aaa-admin-controler/includes/timeclock/aaa-ac-timeclock-admin.php
 * Purpose: Adds a Settings page for Admin "Timeclock" style reporting (filters + table scaffold).
 * Notes:
 *  - Step 1: UI only (date range + IP allowlist, table headers, basic plumbing).
 *  - Step 2: Wire to real session log source and compute first login / last logout per user/day.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AAA_AC_Timeclock_Admin' ) ) {

	class AAA_AC_Timeclock_Admin {

		const DEBUG = true;
		const SLUG  = 'aaa-ac-timeclock';

		public static function init() {
			add_action( 'admin_menu', [ __CLASS__, 'register_page' ] );
		}

		public static function register_page() {
			// Settings → Admin Timeclock (kept separate from other pages to avoid collisions)
			add_options_page(
				'Admin Timeclock',
				'Admin Timeclock',
				'manage_options',
				self::SLUG,
				[ __CLASS__, 'render_page' ]
			);
		}

		/**
		 * Step 1 renders:
		 * - Date range filters (start/end)
		 * - Qualifying IP allowlist (comma/line separated)
		 * - Empty results scaffold (Start Time, End Time, Total Time)
		 */
		public static function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to view this page.', 'aaa-ac' ) );
			}

			$action_url = esc_url( admin_url( 'options-general.php?page=' . self::SLUG ) );

			// Defaults: today
			$today      = ( new DateTime( 'now', wp_timezone() ) )->format( 'Y-m-d' );
			$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : $today;
			$end_date   = isset( $_GET['end_date'] )   ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) )   : $today;
			$ip_raw     = isset( $_GET['ip_allow'] )   ? wp_unslash( $_GET['ip_allow'] ) : '';
			$ips        = self::parse_ip_list( $ip_raw );

			// Nonce
			$nonce_ok = isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::SLUG . '_filter' );

			// Placeholder: In Step 2 we'll fetch + compute real data.
			$rows = [];
			if ( $nonce_ok && isset( $_GET['run'] ) ) {
				// This is where we’ll call the real aggregator in Step 2.
				// Example shape we will produce:
				// $rows[] = [
				//   'user_id'   => 123,
				//   'user_name' => 'Jane Admin',
				//   'date'      => '2025-08-25',
				//   'start'     => '09:12:04',
				//   'end'       => '17:44:19',
				//   'start_ip'  => '203.0.113.10',
				//   'end_ip'    => '203.0.113.10',
				//   'total'     => self::format_interval( '09:12:04', '17:44:19' ),
				// ];
				if ( self::DEBUG ) {
					error_log( '[AAA Timeclock] Step 1 UI submitted. start_date=' . $start_date . ' end_date=' . $end_date . ' ips=' . implode( ',', $ips ) );
				}
			}

			?>
			<div class="wrap">
				<h1>Admin Timeclock</h1>
				<p style="max-width:800px;">
					Track working time based on <strong>first login</strong> and <strong>last logout</strong> from qualifying IP addresses.
					This page is Step 1 (UI scaffold). In Step 2 we’ll wire live session logs and calculations.
				</p>

				<form method="get" action="<?php echo $action_url; ?>" style="margin:1rem 0 1.5rem; padding:1rem; background:#fff; border:1px solid #ccd0d4;">
					<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
					<?php wp_nonce_field( self::SLUG . '_filter' ); ?>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="start_date">Start date</label></th>
							<td><input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="end_date">End date</label></th>
							<td><input type="date" id="end_date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="ip_allow">Qualifying IPs</label></th>
							<td>
								<textarea id="ip_allow" name="ip_allow" rows="3" cols="60" placeholder="One per line or comma-separated (e.g. 203.0.113.10, 198.51.100.42)"><?php echo esc_textarea( $ip_raw ); ?></textarea>
								<p class="description">Only sessions that started from one of these IPs will qualify as working hours.</p>
							</td>
						</tr>
					</table>

					<p>
						<button class="button button-primary" type="submit" name="run" value="1">Run Report</button>
					</p>
				</form>

				<h2>Results</h2>
				<table class="widefat striped" style="max-width:100%;">
					<thead>
						<tr>
							<th>User</th>
							<th>Date</th>
							<th>Start Time</th>
							<th>End Time</th>
							<th>Total Time</th>
							<th>Start IP</th>
							<th>End IP</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr>
								<td colspan="7">No data yet. (Step 2 will connect to your session logs and compute first login / last logout per user/day.)</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $rows as $r ) : ?>
								<tr>
									<td><?php echo esc_html( $r['user_name'] ?? ( 'User #' . (int) ( $r['user_id'] ?? 0 ) ) ); ?></td>
									<td><?php echo esc_html( $r['date'] ?? '' ); ?></td>
									<td><?php echo esc_html( $r['start'] ?? '' ); ?></td>
									<td><?php echo esc_html( $r['end'] ?? '' ); ?></td>
									<td><?php echo esc_html( $r['total'] ?? '' ); ?></td>
									<td><?php echo esc_html( $r['start_ip'] ?? '' ); ?></td>
									<td><?php echo esc_html( $r['end_ip'] ?? '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<p style="margin-top:1rem; color:#555;">
					<strong>Next:</strong> We’ll attach your existing admin session logs and compute:
					<em>first login</em> (by timestamp, from qualifying IP) and <em>last logout</em> per user per day, then derive Total Time.
				</p>
			</div>
			<?php
		}

		/**
		 * Parse comma/line separated IPs into a clean array.
		 */
		private static function parse_ip_list( $raw ) {
			$raw = (string) $raw;
			$raw = str_replace( [ "\r\n", "\r" ], "\n", $raw );
			$parts = preg_split( '/[,\n]+/', $raw );
			$out = [];
			foreach ( (array) $parts as $ip ) {
				$ip = trim( $ip );
				if ( $ip !== '' ) {
					$out[] = $ip;
				}
			}
			return array_values( array_unique( $out ) );
		}

		/**
		 * Utility to format a duration between HH:MM:SS times (same day) – used later in Step 2.
		 */
		public static function format_interval( $start_hms, $end_hms ) {
			try {
				$tz   = wp_timezone();
				$base = ( new DateTime( 'today', $tz ) )->format( 'Y-m-d' );
				$dt1  = new DateTime( $base . ' ' . $start_hms, $tz );
				$dt2  = new DateTime( $base . ' ' . $end_hms,   $tz );
				if ( $dt2 < $dt1 ) {
					// If end wrapped past midnight (safety), add a day.
					$dt2->modify( '+1 day' );
				}
				$diff = $dt1->diff( $dt2 );
				return sprintf( '%02d:%02d:%02d',
					( $diff->days * 24 ) + $diff->h,
					$diff->i,
					$diff->s
				);
			} catch ( Exception $e ) {
				if ( self::DEBUG ) {
					error_log( '[AAA Timeclock] format_interval error: ' . $e->getMessage() );
				}
				return '';
			}
		}
	}

	AAA_AC_Timeclock_Admin::init();
}

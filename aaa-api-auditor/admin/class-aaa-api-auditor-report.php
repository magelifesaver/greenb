<?php
/**
 * File: /wp-content/plugins/aaa-api-auditor/admin/class-aaa-api-auditor-report.php
 * Purpose: Admin page – discover all REST routes and show access status per host.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_API_Auditor_Report {
	const DEBUG_THIS_FILE = true;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_aaa_api_auditor_routes_scan', array( __CLASS__, 'scan' ) );
	}

	public static function menu() {
		// Adds a second page under Tools (next to the existing auditor page)
		add_management_page(
			'API Endpoint Report',
			'API Endpoint Report',
			'manage_options',
			'aaa-api-auditor-report',
			array( __CLASS__, 'render' )
		);
	}

	/* -------- core helpers (local, to avoid editing scanner class) -------- */

	private static function http_get( $url, $args = array() ) {
		$opts  = get_option( 'aaa_api_auditor_opts', array() );
		$base  = array( 'timeout' => max(3, intval($opts['timeout'] ?? 12)), 'redirection' => 3 );
		$res   = wp_remote_get( $url, $args + $base );
		if ( is_wp_error( $res ) ) return array('code'=>0,'body'=>'','err'=>$res->get_error_message());
		return array(
			'code' => wp_remote_retrieve_response_code($res),
			'body' => wp_remote_retrieve_body($res),
			'hdrs' => wp_remote_retrieve_headers($res),
		);
	}

	private static function with_query_auth( $url, $ck, $cs ) {
		$sep = (strpos($url,'?')!==false) ? '&' : '?';
		return $url.$sep.'consumer_key='.rawurlencode($ck).'&consumer_secret='.rawurlencode($cs);
	}

	/* -------- scanning logic -------- */

	public static function scan() {
		if ( ! current_user_can('manage_options') ) wp_die('Forbidden');
		check_admin_referer( 'aaa_api_auditor_routes_scan' );

		$opts   = get_option( 'aaa_api_auditor_opts', array() );
		$hosts  = array_filter( array_map('trim', explode(',', $opts['hosts'] ?? '') ) );
		$ck     = $opts['ck']  ?? '';
		$cs     = $opts['cs']  ?? '';
		$jwt    = $opts['jwt'] ?? '';

		$full = array();

		foreach ( $hosts as $host ) {
			$host = rtrim($host,'/');
			$index = self::http_get( $host.'/wp-json' );
			$routes = array();

			if ( $index['code'] === 200 ) {
				$decoded = json_decode( $index['body'], true );
				if ( isset($decoded['routes']) && is_array($decoded['routes']) ) {
					$routes = $decoded['routes']; // keys like "/wp/v2", "/wc/v3/orders"
				}
			}

			$rows = array();
			foreach ( $routes as $route => $meta ) {
				// Test GET-able routes only (fast + meaningful for “open vs protected”)
				$methods = array();
				if ( isset($meta['endpoints']) && is_array($meta['endpoints']) ) {
					foreach ( $meta['endpoints'] as $ep ) {
						if ( !empty($ep['methods']) ) {
							foreach ( (array)$ep['methods'] as $m ) { $methods[$m] = true; }
						}
					}
				}
				if ( empty($methods['GET']) ) continue;

				$url = $host.'/wp-json'. $route;
				$probe = array(
					'public' => self::http_get($url),
				);

				if ( $ck && $cs ) {
					$probe['ckcs_query'] = self::http_get( self::with_query_auth($url,$ck,$cs) );
					$probe['basic']      = self::http_get( $url, array(
						'headers'=>array('Authorization'=>'Basic '.base64_encode($ck.':'.$cs))
					));
				}
				if ( $jwt ) {
					$probe['jwt'] = self::http_get( $url, array(
						'headers'=>array('Authorization'=>'Bearer '.$jwt)
					));
				}

				$access = 'Unknown';
				$pub = intval($probe['public']['code'] ?? 0);
				if ( $pub >= 200 && $pub < 300 ) {
					$access = 'Public';
				} else {
					$codes = array(
						intval($probe['ckcs_query']['code'] ?? 0),
						intval($probe['basic']['code'] ?? 0),
						intval($probe['jwt']['code'] ?? 0),
					);
					if ( max($codes) >= 200 && max($codes) < 300 ) $access = 'Protected';
				}

				$rows[] = array(
					'route'   => $route,
					'methods' => implode(',', array_keys($methods)),
					'codes'   => array(
						'public'     => $pub,
						'ckcs_query' => intval($probe['ckcs_query']['code'] ?? 0),
						'basic'      => intval($probe['basic']['code'] ?? 0),
						'jwt'        => intval($probe['jwt']['code'] ?? 0),
					),
					'access'  => $access,
				);
			}

			$full[] = array('host'=>$host, 'rows'=>$rows);
		}

		set_transient( 'aaa_api_auditor_routes_report', $full, 10 * MINUTE_IN_SECONDS );
		wp_redirect( add_query_arg( array('page'=>'aaa-api-auditor-report','scanned'=>'1'), admin_url('tools.php') ) );
		exit;
	}

	public static function render() {
		if ( ! current_user_can('manage_options') ) return;

		$report = get_transient( 'aaa_api_auditor_routes_report' );
		$opts   = get_option( 'aaa_api_auditor_opts', array() );
		?>
		<div class="wrap">
			<h1>API Endpoint Report</h1>
			<p>This page discovers <code>/wp-json</code> routes for each host and tests GET endpoints for access: <strong>Public</strong> (200 without auth) vs <strong>Protected</strong> (requires auth).</p>

			<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
				<?php wp_nonce_field( 'aaa_api_auditor_routes_scan' ); ?>
				<input type="hidden" name="action" value="aaa_api_auditor_routes_scan">
				<?php submit_button( 'Scan All Hosts', 'primary', 'submit', false ); ?>
				<span class="description" style="margin-left:8px">Hosts: <?php echo esc_html( $opts['hosts'] ?? '' ); ?></span>
			</form>

			<?php if ( $report ) : ?>
				<?php foreach ( $report as $host ) : ?>
					<h2 style="margin-top:20px"><?php echo esc_html( $host['host'] ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th style="width:40%">Route</th>
								<th>Methods</th>
								<th>Public</th>
								<th>CK/CS</th>
								<th>Basic</th>
								<th>JWT</th>
								<th>Access</th>
                            </tr>
						</thead>
						<tbody>
							<?php if ( empty($host['rows']) ) : ?>
								<tr><td colspan="7">No GET routes discovered or index unavailable.</td></tr>
							<?php else : foreach ( $host['rows'] as $r ) : ?>
								<tr>
									<td><code><?php echo esc_html( $r['route'] ); ?></code></td>
									<td><?php echo esc_html( $r['methods'] ); ?></td>
									<td><?php echo esc_html( $r['codes']['public'] ); ?></td>
									<td><?php echo esc_html( $r['codes']['ckcs_query'] ); ?></td>
									<td><?php echo esc_html( $r['codes']['basic'] ); ?></td>
									<td><?php echo esc_html( $r['codes']['jwt'] ); ?></td>
									<td><strong><?php echo esc_html( $r['access'] ); ?></strong></td>
								</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
					<p class="description">Legend: 200–299 OK, 401/403 = needs auth, 404 = missing.</p>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}

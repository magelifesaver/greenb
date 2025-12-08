<?php
/**
 * File: /wp-content/plugins/aaa-api-auditor/inc/class-aaa-api-auditor-scanner.php
 * Purpose: Endpoint scanner – probes Woo & ATUM endpoints and auth responses.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_API_Auditor_Scanner {
	const DEBUG_THIS_FILE = true;

	private static function http_get( $url, $args = array() ) {
		$defaults = array( 'timeout' => self::timeout(), 'redirection' => 3 );
		$res = wp_remote_get( $url, $args + $defaults );
		if ( is_wp_error( $res ) ) { return array( 'code' => 0, 'error' => $res->get_error_message() ); }
		return array(
			'code' => wp_remote_retrieve_response_code( $res ),
			'body' => wp_remote_retrieve_body( $res ),
			'hdrs' => wp_remote_retrieve_headers( $res ),
		);
	}

	private static function timeout() {
		$opts = get_option( 'aaa_api_auditor_opts', array() );
		return max( 3, intval( $opts['timeout'] ?? 12 ) );
	}

	private static function build_urls( $host ) {
		$host = rtrim( $host, '/' );
		return array(
			'index'     => $host . '/wp-json',
			'wc_orders' => $host . '/wp-json/wc/v3/orders?per_page=1',
			'wc_prods'  => $host . '/wp-json/wc/v3/products?per_page=1',
			'wc_cust'   => $host . '/wp-json/wc/v3/customers?per_page=1',
			'atum_prod' => $host . '/wp-json/atum/v3/products?per_page=1',
		);
	}

	private static function with_query_auth( $url, $ck, $cs ) {
		$sep = ( strpos( $url, '?' ) !== false ) ? '&' : '?';
		return $url . $sep . 'consumer_key=' . rawurlencode($ck) . '&consumer_secret=' . rawurlencode($cs);
	}

	public static function scan_hosts( $hosts, $ck, $cs, $jwt ) {
		$report = array();
		foreach ( $hosts as $host ) {
			$host = trim( $host );
			if ( $host === '' ) { continue; }
			$urls = self::build_urls( $host );

			$entry = array( 'host' => $host, 'index' => array(), 'endpoints' => array() );
			$entry['index']['public'] = self::http_get( $urls['index'] );

			$targets = array(
				'wc_orders' => 'Woo Orders',
				'wc_prods'  => 'Woo Products',
				'wc_cust'   => 'Woo Customers',
				'atum_prod' => 'ATUM Products',
			);

			foreach ( $targets as $key => $label ) {
				$row = array( 'label' => $label );
				$row['public'] = self::http_get( $urls[$key] );

				if ( $ck && $cs ) {
					$q_url = self::with_query_auth( $urls[$key], $ck, $cs );
					$row['ckcs_query'] = self::http_get( $q_url );
					$row['basic'] = self::http_get( $urls[$key], array(
						'headers' => array( 'Authorization' => 'Basic ' . base64_encode( $ck . ':' . $cs ) ),
					) );
				}

				if ( $jwt ) {
					$row['jwt'] = self::http_get( $urls[$key], array(
						'headers' => array( 'Authorization' => 'Bearer ' . $jwt ),
					) );
				}
				$entry['endpoints'][$key] = $row;
			}
			$report[] = $entry;
		}
		if ( self::DEBUG_THIS_FILE ) { aaa_api_auditor_log( 'scan_report', $report ); }
		return $report;
	}
}

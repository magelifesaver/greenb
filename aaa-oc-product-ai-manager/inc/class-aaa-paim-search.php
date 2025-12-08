<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/inc/class-aaa-paim-search.php
 * Purpose: Minimal web search + fetch for PAIM (SerpAPI or Bing).
 *          Returns concatenated plain text AND exposes last-run diagnostics for reporting.
 * Version: 0.6.1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_SEARCH' ) ) { define( 'AAA_PAIM_DEBUG_SEARCH', true ); }

class AAA_Paim_Search {

	/** @var array In-memory trace of the most recent search+fetch (for report writers) */
	private static $last_run = [
		'query'    => '',
		'provider' => '',
		'allow'    => [],  // array of domains
		'urls'     => [],  // [{url, fetched:bool, code:int, bytes:int}]
	];

	/**
	 * Public accessor for the most recent search diagnostics.
	 * @return array{query:string,provider:string,allow:array,urls:array}
	 */
	public static function last_run(): array {
		return is_array( self::$last_run ) ? self::$last_run : [];
	}

	/**
	 * Run web search, filter URLs, fetch pages, return concatenated plain text.
	 * Records detailed diagnostics in self::$last_run for downstream reporting.
	 */
	public static function run_and_fetch( string $query, array $allow_domains = [], int $max_pages = 3, int $max_chars = 20000 ) : string {
		$provider = (string) AAA_Paim_Options::get( 'web_search_provider', 'serpapi' );
		$apikey   = (string) AAA_Paim_Options::get( 'web_search_api_key', '' );
		if ( '' === trim( $apikey ) ) {
			// Clear trace but record intent
			self::$last_run = [ 'query' => $query, 'provider' => $provider, 'allow' => $allow_domains, 'urls' => [] ];
			return '';
		}

		// Initialize trace
		self::$last_run = [
			'query'    => $query,
			'provider' => $provider,
			'allow'    => array_values( array_filter( array_map( 'trim', $allow_domains ) ) ),
			'urls'     => [],
		];

		// Search → candidate URLs
		if ( 'bing' === $provider ) {
			$urls = self::bing_urls( $apikey, $query, 8 );
		} else {
			$urls = self::serpapi_urls( $apikey, $query, 8 );
		}

		// Filter + limit
		$urls = self::filter_by_domain( $urls, self::$last_run['allow'] );
		$urls = array_slice( array_values( array_unique( $urls ) ), 0, $max_pages );

		$out = '';
		self::$last_run['urls'] = [];

		foreach ( $urls as $u ) {
			$r    = wp_remote_get( $u, [ 'timeout' => 15 ] );
			$code = is_wp_error( $r ) ? 0 : (int) wp_remote_retrieve_response_code( $r );
			$body = is_wp_error( $r ) ? '' : (string) wp_remote_retrieve_body( $r );
			$text = $body ? wp_strip_all_tags( $body ) : '';

			self::$last_run['urls'][] = [
				'url'     => $u,
				'fetched' => ! is_wp_error( $r ),
				'code'    => $code,
				'bytes'   => strlen( $body ),
			];

			if ( $text !== '' ) {
				$out .= "\n\n===== SOURCE: {$u} =====\n" . $text;
				if ( strlen( $out ) > $max_chars ) { break; }
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && AAA_PAIM_DEBUG_SEARCH ) {
			error_log(
				'[AAA-PAIM][SEARCH] provider=' . $provider .
				' query=' . $query .
				' allow=' . wp_json_encode( self::$last_run['allow'] ) .
				' fetched=' . count( self::$last_run['urls'] )
			);
		}

		return substr( $out, 0, $max_chars );
	}

	private static function filter_by_domain( array $urls, array $allow_domains ) : array {
		$allow_domains = array_filter( array_map( 'trim', $allow_domains ) );
		if ( empty( $allow_domains ) ) { return $urls; }
		$keep = [];
		foreach ( $urls as $u ) {
			$host = wp_parse_url( $u, PHP_URL_HOST );
			if ( ! $host ) { continue; }
			foreach ( $allow_domains as $d ) {
				if ( stripos( $host, $d ) !== false ) { $keep[] = $u; break; }
			}
		}
		return $keep ?: $urls;
	}

	private static function serpapi_urls( string $key, string $q, int $n ) : array {
		$endpoint = add_query_arg( [
			'engine'  => 'google',
			'q'       => $q,
			'num'     => $n,
			'api_key' => $key,
		], 'https://serpapi.com/search.json' );

		$r = wp_remote_get( $endpoint, [ 'timeout' => 15 ] );
		if ( is_wp_error( $r ) ) { return []; }

		$body = json_decode( wp_remote_retrieve_body( $r ), true );
		$out  = [];
		if ( isset( $body['organic_results'] ) && is_array( $body['organic_results'] ) ) {
			foreach ( $body['organic_results'] as $row ) {
				if ( ! empty( $row['link'] ) ) { $out[] = $row['link']; }
			}
		}
		return $out;
	}

	private static function bing_urls( string $key, string $q, int $n ) : array {
		$r = wp_remote_get(
			'https://api.bing.microsoft.com/v7.0/search?count=' . (int) $n . '&q=' . rawurlencode( $q ),
			[
				'timeout' => 15,
				'headers' => [ 'Ocp-Apim-Subscription-Key' => $key ],
			]
		);
		if ( is_wp_error( $r ) ) { return []; }

		$body = json_decode( wp_remote_retrieve_body( $r ), true );
		$out  = [];
		if ( isset( $body['webPages']['value'] ) && is_array( $body['webPages']['value'] ) ) {
			foreach ( $body['webPages']['value'] as $row ) {
				if ( ! empty( $row['url'] ) ) { $out[] = $row['url']; }
			}
		}
		return $out;
	}
}

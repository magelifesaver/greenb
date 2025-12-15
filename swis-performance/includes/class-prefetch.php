<?php
/**
 * Class and methods to add DNS prefetch hints to your page.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches the page for linked domains and adds prefetch headers for the top 5 non-local domains.
 */
final class Prefetch extends Base {

	/**
	 * The local domain.
	 *
	 * @access protected
	 * @var string $local_domain
	 */
	protected $local_domain = '';

	/**
	 * Existing prefetch hints.
	 *
	 * @access protected
	 * @var array $hints
	 */
	protected $hints = array(
		'dns-prefetch' => array(),
		'preconnect'   => array(),
	);

	/**
	 * Register actions and filters for DNS Prefetch.
	 */
	public function __construct() {
		parent::__construct();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		if ( ! is_admin() ) {
			$this->local_domain = $this->parse_url( get_home_url(), PHP_URL_HOST );

			// Start an output buffer before any output starts.
			add_filter( $this->prefix . 'filter_page_output', array( $this, 'filter_page_output' ) );

			// Get a list of existing prefetch hints.
			add_filter( 'wp_resource_hints', array( $this, 'get_resource_hints' ), PHP_INT_MAX, 2 );

			// Apply internal exclusions.
			add_filter( 'swis_skip_prefetch', array( $this, 'skip_prefetch' ), 10, 2 );
			add_filter( 'swis_skip_preconnect', array( $this, 'skip_prefetch' ), 10, 3 );
			add_filter( 'swis_preconnect_domains', array( $this, 'add_domains' ) );
			add_filter( 'swis_prefetch_domains', array( $this, 'add_domains' ) );
		}
	}

	/**
	 * Add domains specified by the user.
	 *
	 * @param array $domains A list of third-party domains found in the content.
	 * @return array The list of domains, possibly with more added.
	 */
	public function add_domains( $domains ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$user_domains = $this->get_option( 'pre_hint_domains' );
		if ( $this->is_iterable( $user_domains ) ) {
			$this->debug_message( 'have domains to add' );
			foreach ( $user_domains as $domain ) {
				$domain = filter_var( $domain, FILTER_VALIDATE_DOMAIN );
				$this->debug_message( "attempting to add $domain" );
				if ( $domain && ! isset( $domains[ $domain ] ) ) {
					$this->debug_message( 'adding domain that did not exist' );
					$domains = array_merge( array( $domain => 9999 ), $domains );
				} elseif ( $domain && isset( $domains[ $domain ] ) && count( $domains ) > 5 ) {
					$this->debug_message( 're-prioritizing user-defined domain' );
					unset( $domains[ $domain ] );
					$domains = array_merge( array( $domain => 9999 ), $domains );
				}
			}
		}
		return $domains;
	}

	/**
	 * Exclude domain from being prefetched.
	 *
	 * @param bool   $skip Whether SWIS should skip processing.
	 * @param string $domain The domain name to prefetch.
	 * @param string $relationship_type The type of hint being filtered: dns-prefetch, preconnect, etc. Defaults to 'dns-prefetch'.
	 * @return bool True to skip the domain, unchanged otherwise.
	 */
	public function skip_prefetch( $skip, $domain, $relationship_type = 'dns-prefetch' ) {
		$skip_domains = apply_filters( 'swis_' . $relationship_type . '_skip_domains', array( $this->local_domain, 'gmpg.org', 'player.vimeo.com', 'www.w3.org', 'api.w.org', 'www.youtube.com' ) );
		foreach ( $skip_domains as $skip_domain ) {
			if ( $skip_domain === $domain ) {
				return true;
			}
		}
		foreach ( $this->hints[ $relationship_type ] as $dns_hint ) {
			if ( false !== strpos( $dns_hint, $domain ) ) {
				return true;
			}
		}
		return $skip;
	}

	/**
	 * Checks existing DNS prefetch hints and stores the list for later.
	 *
	 * @param array  $hints A list of hints for a particular relationship type.
	 * @param string $relationship_type The type of hint being filtered: dns-prefetch, preconnect, etc.
	 * @return array The list of hints, unaltered.
	 */
	public function get_resource_hints( $hints, $relationship_type ) {
		if ( is_array( $hints ) && ( 'dns-prefetch' === $relationship_type || 'preconnect' === $relationship_type ) ) {
			$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
			foreach ( $hints as $key => $hint ) {
				if ( $this->get_option( 'optimize_fonts' ) && $this->get_option( 'optimize_fonts_css' ) && is_string( $hint ) && false !== strpos( $hint, 'fonts.googleapis.com' ) ) {
					$this->debug_message( "removing $hint" );
					unset( $hints[ $key ] );
					continue;
				}
				if ( is_string( $hint ) ) {
					$this->debug_message( "$relationship_type hint: $hint" );
					$this->hints[ $relationship_type ][] = $hint;
				} elseif ( is_array( $hint ) && isset( $hint['href'] ) ) {
					$hint = $hint['href'];
					$this->debug_message( "$relationship_type hint: $hint" );
					$this->hints[ $relationship_type ][] = $hint;
				} elseif ( $this->function_exists( 'print_r' ) ) {
					$this->debug_message( print_r( $hint, true ) );
				}
			}
		}
		return $hints;
	}

	/**
	 * Get linked/resource domains from content.
	 *
	 * @param string $content The HTML content through which to search.
	 * @return array A list of domains found in the HTML content.
	 */
	public function get_domains( $content ) {
		if ( empty( $content ) || ! is_string( $content ) ) {
			return array();
		}
		$domains = array();
		$links   = array();
		preg_match_all( '#\s(?>rel|src)\s*=\s*["\']https?:\/\/([^\/?\'"]{4,256})[/?\'"]#is', $content, $links );
		if ( $this->is_iterable( $links[1] ) ) {
			foreach ( $links[1] as $domain ) {
				$domains[] = $domain;
			}
		}
		$links = array();
		preg_match_all( '#link[^>]+?href\s*=\s*["\']https?:\/\/([^\/?\'"]{4,256})[/?\'"]#is', $content, $links );
		if ( $this->is_iterable( $links[1] ) ) {
			foreach ( $links[1] as $domain ) {
				$domains[] = $domain;
			}
		}
		$links = array();
		preg_match_all( '#[^a-zA-Z0-9]url\s*?\(\s*["\']?\s*?https?:\/\/([^\/?\'"]{4,256})[/?\'"]#is', $content, $links );
		if ( $this->is_iterable( $links[1] ) ) {
			foreach ( $links[1] as $domain ) {
				$domains[] = $domain;
			}
		}
		if ( \str_contains( $content, 'data-aidaform-app' ) ) {
			$links = array();
			preg_match_all( '#\sdata-url\s*=\s*["\']https?:\/\/([^\/?\'"]{4,256})[/?\'"]#is', $content, $links );
			if ( $this->is_iterable( $links[1] ) ) {
				foreach ( $links[1] as $domain ) {
					$domains[] = $domain;
				}
			}
		}
		return $domains;
	}

	/**
	 * Add prefetch/preconnect directives to the page HTML.
	 *
	 * @param string $content The HTML content to parse.
	 * @return string The filtered HTML content.
	 */
	public function filter_page_output( $content ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( $this->is_json( $content ) ) {
			return $content;
		}
		if ( ! $this->is_frontend() ) {
			return $content;
		}
		if ( ! defined( 'SWIS_NO_PREFETCH' ) || ! SWIS_NO_PREFETCH ) {
			$preconnect_html    = '';
			$prefetch_html      = '';
			$preconnect_domains = array();
			$prefetch_domains   = array();
			$font_domains       = array(
				'use.fontawesome.com',
				'fonts.gstatic.com',
				'use.typekit.com',
				'use.typekit.net',
				'cloud.webtype.com',
				'fonts.bunny.net',
			);

			$domains = $this->get_domains( $content );
			foreach ( $domains as $domain ) {
				$this->debug_message( "found $domain for pre-hints" );
				if ( ! apply_filters( 'swis_skip_preconnect', false, $domain, 'preconnect' ) ) {
					$this->debug_message( "ensuring $domain preconnects" );
					if ( isset( $preconnect_domains[ $domain ] ) ) {
						++$preconnect_domains[ $domain ];
					} else {
						$preconnect_domains[ $domain ] = 1;
					}
				}
				if ( ! apply_filters( 'swis_skip_prefetch', false, $domain ) ) {
					$this->debug_message( "ensuring $domain is prefetched" );
					if ( isset( $prefetch_domains[ $domain ] ) ) {
						++$prefetch_domains[ $domain ];
					} else {
						$prefetch_domains[ $domain ] = 1;
					}
				}
			}
			if ( strpos( $content, '//fonts.bunny.net/' ) ) {
				$this->debug_message( 'adding fonts.bunny.net to pre-hints' );
				$preconnect_domains['fonts.bunny.net'] = 999;
				$prefetch_domains['fonts.bunny.net']   = 999;
			}
			if ( strpos( $content, '//fonts.gstatic.com/' ) || strpos( $content, '//fonts.googleapis.com/' ) ) {
				$this->debug_message( 'adding fonts.gstatic.com to pre-hints' );
				$preconnect_domains['fonts.gstatic.com'] = 999;
				$prefetch_domains['fonts.gstatic.com']   = 999;
			}

			arsort( $preconnect_domains, SORT_NUMERIC );
			arsort( $prefetch_domains, SORT_NUMERIC );
			/**
			 * Allow adding or removing preconnect domains (or altering the priority).
			 * Increase the array value for a given domain to increase the priority.
			 *
			 * @param array $preconnect_domains Single-dimensional associative array.
			 *              The array keys are the domains found within the page.
			 *              Array values indicate how many times a domain occurred within the page.
			 */
			$preconnect_domains = apply_filters( 'swis_preconnect_domains', $preconnect_domains );
			/**
			 * Allow adding or removing dns-prefetch domains (or altering the priority).
			 * Increase the array value for a given domain to increase the priority.
			 *
			 * @param array $prefetch_domains Single-dimensional associative array.
			 *              The array keys are the domains found within the page.
			 *              Array values indicate how many times a domain occurred within the page.
			 */
			$prefetch_domains = apply_filters( 'swis_prefetch_domains', $prefetch_domains );

			/**
			 * Adjust how many DNS records may be prefetched.
			 *
			 * @param int 5 How many records will be prefetched, prioritized by the # of references within any given page.
			 */
			$max_prefetch_hints = apply_filters( 'swis_prefetch_max_domains', 5 );
			/**
			 * Adjust how many preconnect hints are allowed.
			 *
			 * @param int $max_prefetch_hints How many preconnect hints are allowed, defaults to the same # as the dns-prefetch hints.
			 */
			$max_preconnect_hints  = apply_filters( 'swis_preconnect_max_domains', $max_prefetch_hints );
			$max_prefetch_hints   -= count( $this->hints['dns-prefetch'] );
			$max_preconnect_hints -= count( $this->hints['preconnect'] );

			foreach ( $prefetch_domains as $domain => $count ) {
				if ( $max_prefetch_hints > 0 ) {
					$this->debug_message( "prefetching $domain" );
					$prefetch_html .= "<link rel='dns-prefetch' href='//$domain' />\n";
				} else {
					$this->debug_message( "not prefetching $domain" );
				}
				--$max_prefetch_hints;
			}
			foreach ( $preconnect_domains as $domain => $count ) {
				if ( $max_preconnect_hints > 0 ) {
					$this->debug_message( "preconnecting $domain" );
					if ( 'fonts.gstatic.com' === $domain ) {
						$preconnect_html .= "<link rel='preconnect' href='//$domain' crossorigin />\n";
					} elseif ( in_array( $domain, $font_domains, true ) ) {
						$preconnect_html .= "<link rel='preconnect' href='//$domain' />\n";
						$preconnect_html .= "<link rel='preconnect' href='//$domain' crossorigin />\n";
					} else {
						$preconnect_html .= "<link rel='preconnect' href='//$domain' />\n";
					}
				} else {
					$this->debug_message( "not preconnecting $domain" );
				}
				--$max_preconnect_hints;
			}
			$easyio_crossorigin = false;
			if ( $this->get_option( 'crossorigin_fonts' ) && $this->is_easyio_active() ) {
				global $exactdn;
				if ( \is_object( $exactdn ) && \method_exists( $exactdn, 'get_exactdn_domain' ) ) {
					$exactdn_domain = $exactdn->get_exactdn_domain();
					if ( $exactdn_domain ) {
						$this->debug_message( 'should add crossorigin due to fonts found in CSS' );
						$easyio_crossorigin = $exactdn_domain;
					}
				}
			} elseif ( ! empty( \swis()->fonts->crossorigin ) && is_string( \swis()->fonts->crossorigin ) ) {
				$this->debug_message( 'should add crossorigin due to inline fonts' );
				$easyio_crossorigin = \swis()->fonts->crossorigin;
			}
			// Add the preconnect cross-origin for Easy IO, if it doesn't exist, and we've detected fonts loading over Easy IO.
			if ( $easyio_crossorigin && is_string( $easyio_crossorigin ) && ! preg_match( '#<link\s+?rel=[\'"]?preconnect[\'"]?\s+?href=[\'"]?//' . $easyio_crossorigin . '[\'"]?\s*crossorigin#is', $content ) ) {
				$this->debug_message( "adding a crossorigin preconnect for $easyio_crossorigin" );
				$preconnect_html .= "<link rel='preconnect' href='//" . esc_attr( $easyio_crossorigin ) . "' crossorigin />\n";
			}
			// 'preconnect' hints go first.
			if ( $prefetch_html || $preconnect_html ) {
				$pos = strpos( $content, '</title>' );
				if ( false !== $pos ) {
					$content = substr_replace( $content, "</title>\n$preconnect_html" . $prefetch_html, $pos, strlen( '</title>' ) );
				}
			}
		}
		return $content;
	}
}

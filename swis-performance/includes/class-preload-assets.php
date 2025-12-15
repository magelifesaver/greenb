<?php
/**
 * Class and methods to preload assets on the site.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preload the specified assets/resources.
 */
final class Preload_Assets extends Page_Parser {

	/**
	 * All the preload URLs.
	 *
	 * @access protected
	 * @var array $preloads_urls
	 */
	protected $preload_urls = array();

	/**
	 * All the preloads, parsed and categorized.
	 *
	 * @access protected
	 * @var array $preloads
	 */
	protected $preloads = array(
		'font'   => array(),
		'image'  => array(),
		'script' => array(),
		'style'  => array(),
	);

	/**
	 * Register actions and filters for DNS Prefetch.
	 */
	public function __construct() {
		parent::__construct();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		if ( ! is_admin() ) {
			$this->preload_urls = $this->get_option( 'preload_assets' );
			if ( empty( $this->preload_urls ) || ! is_array( $this->preload_urls ) ) {
				$this->preload_urls = array();
			}

			// Check all the JS/CSS URLs and compare them to the user-defined URLs to update query strings/domains.
			// Must happen after minify and CDN rewriting so that we have the final form of the URL.
			\add_filter( 'style_loader_src', array( $this, 'check_asset_url' ), 10001 );
			\add_filter( 'swis_elements_link_href', array( $this, 'check_asset_url' ), 10001 );
			\add_filter( 'script_loader_src', array( $this, 'check_asset_url' ), 10001 );
			\add_filter( 'swis_elements_script_src', array( $this, 'check_asset_url' ), 10001 );
			// Get URLs for JS/CSS via user-configured handles.
			\add_filter( 'style_loader_tag', array( $this, 'get_style_url_by_handle' ), 30, 2 );
			\add_filter( 'swis_elements_link_tag', array( $this, 'get_style_url_by_handle' ), 30, 2 );
			\add_filter( 'script_loader_tag', array( $this, 'get_script_url_by_handle' ), 30, 2 );
			\add_filter( 'swis_elements_script_tag', array( $this, 'get_script_url_by_handle' ), 30, 2 );

			// Allow other functions to swap out preload URLs, in case they change with minification, delivery method, etc.
			\add_action( 'swis_replace_preload_url', array( $this, 'replace_preload_url' ), 10, 2 );

			// Hook into the global SWIS output buffer, after everything else is done, to make sure the above hooks have all run.
			\add_filter( $this->prefix . 'filter_page_output', array( $this, 'filter_page_output' ), 20 );
		}
	}

	/**
	 * Check the asset URL against the user-defined preloads.
	 *
	 * @param string $url The asset URL.
	 * @return string The potentially modified URL.
	 */
	public function check_asset_url( $url ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( empty( $url ) || ! is_string( $url ) ) {
			return $url;
		}
		foreach ( $this->preload_urls as $index => $preload_url ) {
			if ( empty( $preload_url ) || ! is_string( $preload_url ) ) {
				continue;
			}
			// URL is actually a handle, move along.
			if ( ! str_contains( $preload_url, '/' ) ) {
				continue;
			}
			$preload_path = $this->parse_url( $preload_url, PHP_URL_PATH );
			$url_path     = $this->parse_url( $url, PHP_URL_PATH );
			// If the preload URL path matches the given URL, swap it out.
			if ( $preload_path === $url_path ) {
				$this->debug_message( "replacing $preload_url with $url" );
				$this->preload_urls[ $index ] = $url;
			}
		}
		return $url;
	}

	/**
	 * Replace a user-defined preloads with an updated version.
	 *
	 * Can also be used to remove a preload URL if the new URL is empty.
	 *
	 * @param string $old_url The original asset URL.
	 * @param string $new_url The modified asset URL.
	 */
	public function replace_preload_url( $old_url, $new_url ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( empty( $old_url ) || ! is_string( $old_url ) ) {
			return;
		}
		if ( ! is_string( $new_url ) ) {
			return;
		}
		foreach ( $this->preload_urls as $index => $preload_url ) {
			if ( empty( $preload_url ) || ! is_string( $preload_url ) ) {
				continue;
			}
			// URL is actually a handle, move along.
			if ( ! str_contains( $preload_url, '/' ) ) {
				continue;
			}
			// If the preload URL path matches the given URL, swap it out.
			if ( $preload_url === $old_url ) {
				$this->debug_message( "replacing $old_url with $new_url" );
				$this->preload_urls[ $index ] = $new_url;
			}
		}
	}

	/**
	 * Get the style URL by handle.
	 *
	 * @param string $tag The HTML tag for the stylesheet.
	 * @param string $handle The handle of the stylesheet. Optional.
	 * @return string The unaltered HTML tag.
	 */
	public function get_style_url_by_handle( $tag, $handle = '' ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! str_contains( $tag, 'href=' ) ) {
			$this->debug_message( 'no href found in tag, skipping' );
			return $tag;
		}
		if ( ! empty( $handle ) && is_string( $handle ) ) {
			foreach ( $this->preload_urls as $index => $preload_url ) {
				if ( empty( $preload_url ) || ! is_string( $preload_url ) ) {
					continue;
				}
				// URL is not a handle, just a normal URL.
				if ( str_contains( $preload_url, '/' ) ) {
					continue;
				}
				if ( $preload_url === $handle ) {
					$this->debug_message( "found tag connected to handle $handle, looking for URL/href" );
					$href = $this->get_attribute( $tag, 'href' );
					if ( ! empty( $href ) ) {
						$this->debug_message( "found href $href, replacing $handle in URL list" );
						$this->preload_urls[ $index ] = $href;
					}
				}
			}
		}
		return $tag;
	}

	/**
	 * Get the script URL by handle.
	 *
	 * @param string $tag The HTML tag for the script.
	 * @param string $handle The handle of the script. Optional.
	 * @return string The unaltered HTML tag.
	 */
	public function get_script_url_by_handle( $tag, $handle = '' ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! str_contains( $tag, 'src=' ) ) {
			$this->debug_message( 'no src found in tag, skipping' );
			return $tag;
		}
		if ( ! empty( $handle ) && is_string( $handle ) ) {
			foreach ( $this->preload_urls as $index => $preload_url ) {
				if ( empty( $preload_url ) || ! is_string( $preload_url ) ) {
					continue;
				}
				// URL is not a handle, just a normal URL.
				if ( str_contains( $preload_url, '/' ) ) {
					continue;
				}
				if ( $preload_url === $handle ) {
					$this->debug_message( "found tag connected to handle $handle, looking for URL/src" );
					$src = $this->get_attribute( $tag, 'src' );
					if ( ! empty( $src ) ) {
						$this->debug_message( "found src $src, replacing $handle in URL list" );
						$this->preload_urls[ $index ] = $src;
					}
				}
			}
		}
		return $tag;
	}

	/**
	 * Validate preloads specified by the user.
	 */
	protected function validate_preloads() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( $this->is_iterable( $this->preload_urls ) ) {
			$this->preload_urls = apply_filters( 'swis_preload_urls', $this->preload_urls );
			if ( ! is_array( $this->preload_urls ) ) {
				$this->preload_urls = array();
				return;
			}
			$this->debug_message( 'have preloads to validate' );
			foreach ( $this->preload_urls as $preload_url ) {
				if ( empty( $preload_url ) ) {
					continue;
				}
				// URL is actually a handle, which should have been replaced, but wasn't.
				if ( ! str_contains( $preload_url, '/' ) ) {
					$this->debug_message( "did not find URL for $preload_url" );
					continue;
				}
				$preload_path = $this->parse_url( $preload_url, PHP_URL_PATH );
				$this->debug_message( "attempting to parse $preload_url" );
				if ( empty( $preload_path ) ) {
					continue;
				}
				$this->debug_message( "path is $preload_path" );
				$preload_ext = \pathinfo( $preload_path, PATHINFO_EXTENSION );
				if ( ! empty( $preload_ext ) ) {
					$this->debug_message( "extension is $preload_ext" );
					switch ( \strtolower( $preload_ext ) ) {
						case 'otf':
							$this->preloads['font'][] = array(
								'url'         => $preload_url,
								'type'        => 'font/otf',
								'crossorigin' => true,
							);
							break;
						case 'ttf':
							$this->preloads['font'][] = array(
								'url'         => $preload_url,
								'type'        => 'font/ttf',
								'crossorigin' => true,
							);
							break;
						case 'woff':
							$this->preloads['font'][] = array(
								'url'         => $preload_url,
								'type'        => 'font/woff',
								'crossorigin' => true,
							);
							break;
						case 'woff2':
							$this->preloads['font'][] = array(
								'url'         => $preload_url,
								'type'        => 'font/woff2',
								'crossorigin' => true,
							);
							break;
						case 'avif':
							$this->preloads['image'] = array(
								'url'         => $preload_url,
								'type'        => 'image/avif',
								'crossorigin' => false,
							);
							break;
						case 'gif':
							$this->preloads['image'][] = array(
								'url'         => $preload_url,
								'type'        => 'image/gif',
								'crossorigin' => false,
							);
							break;
						case 'jpg':
						case 'jpeg':
						case 'jpe':
							$this->preloads['image'][] = array(
								'url'         => $preload_url,
								'type'        => 'image/png',
								'crossorigin' => false,
							);
							break;
						case 'png':
							$this->preloads['image'][] = array(
								'url'         => $preload_url,
								'type'        => 'image/png',
								'crossorigin' => false,
							);
							break;
						case 'webp':
							$this->preloads['image'][] = array(
								'url'         => $preload_url,
								'type'        => 'image/webp',
								'crossorigin' => false,
							);
							break;
						case 'js':
							$this->preloads['script'][] = array(
								'url'         => $preload_url,
								'crossorigin' => false,
							);
							break;
						case 'css':
							$this->preloads['style'][] = array(
								'url'         => $preload_url,
								'crossorigin' => false,
							);
							break;
					}
				}
			}
		}
	}

	/**
	 * Add preload directives to the page HTML.
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
		if ( ( ! defined( 'SWIS_NO_PRELOADS' ) || ! SWIS_NO_PRELOADS ) && is_array( $this->preload_urls ) ) {
			$this->validate_preloads();
			$this->preloads = apply_filters( 'swis_preload_array', $this->preloads );
			if ( ! is_array( $this->preloads ) || empty( $this->preloads ) ) {
				$this->debug_message( 'no preloads found, skipping' );
				return $content;
			}

			$this->debug_message( 'preloads found, adding to the page' );
			// Build the preload HTML.
			$preload_html = '';
			foreach ( $this->preloads as $category => $preloads ) {
				foreach ( $preloads as $preload ) {
					$this->debug_message( "processing a single preload for $category" );
					// Add an asset to the preload HTML.
					if ( ! empty( $preload['url'] ) ) {
						if ( ! empty( $preload['crossorigin'] ) ) {
							$this->debug_message( "adding a crossorigin preload for {$preload['url']}" );
							$preload_html .= "<link rel='preload' as='" . esc_attr( $category ) . "'" . ( ! empty( $preload['type'] ) ? " type='" . esc_attr( $preload['type'] ) . "'" : '' ) . " href='" . esc_url( $preload['url'] ) . "' crossorigin />\n";
						} else {
							$this->debug_message( "adding a standard preload for {$preload['url']}" );
							$preload_html .= "<link rel='preload' as='" . esc_attr( $category ) . "'" . ( ! empty( $preload['type'] ) ? " type='" . esc_attr( $preload['type'] ) . "'" : '' ) . " href='" . esc_url( $preload['url'] ) . "' />\n";
						}
					}
				}
			}
			if ( $preload_html ) {
				$this->debug_message( "adding preload HTML to the page:\n$preload_html" );
				$pos = strpos( $content, '</title>' );
				if ( false !== $pos ) {
					$content = substr_replace( $content, "</title>\n$preload_html", $pos, strlen( '</title>' ) );
				}
			}
		}
		return $content;
	}
}

<?php
/**
 * Class and methods to optimize third-party fonts.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;
use MatthiasMullie\Minify;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enables plugin to inline and optimize fonts.
 */
final class Optimize_Fonts extends Page_Parser {

	/**
	 * The list of CSS (font) assets and associated information.
	 *
	 * @var array
	 */
	private $assets = array();

	/**
	 * The directory in which to store font files.
	 *
	 * @access protected
	 * @var string $font_cache_dir
	 */
	protected $font_cache_dir = '';

	/**
	 * The URL to the directory where font files are stored.
	 *
	 * @access protected
	 * @var string $font_cache_dir_url
	 */
	protected $font_cache_dir_url = '';

	/**
	 * Whether a crossorigin preconnect is needed for fonts that were found.
	 * But only true if the fonts will be delivered via Easy IO.
	 *
	 * @var bool
	 */
	public $crossorigin = false;

	/**
	 * Registers actions and filters for font optimization.
	 */
	public function __construct() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! $this->get_option( 'optimize_fonts' ) ) {
			return;
		}
		parent::__construct();
		$permissions = \apply_filters( 'swis_performance_admin_permissions', 'manage_options' );
		if ( ! \is_admin() ) {
			if ( \current_user_can( $permissions ) && ! $this->get_option( 'optimize_fonts_css' ) ) {
				// Auto-detect fonts and save the CSS code/handles to the db.
				\add_action( 'wp_head', array( $this, 'find_assets' ), 9999 );
				\add_action( 'wp_footer', array( $this, 'find_assets' ), 9999 );
				\add_action( 'wp_footer', array( $this, 'stash_css' ), 10000 );
			}
			if ( $this->get_option( 'optimize_fonts_css' ) && ! $this->test_mode_active() ) {
				\add_action( 'wp', array( $this, 'disable_assets' ) );
				\add_action( 'wp_head', array( $this, 'inline_font_css' ) );
				\add_filter( 'style_loader_src', array( $this, 'replace_font_stylesheet' ), 1, 2 );
				\add_filter( 'swis_elements_link_href', array( $this, 'replace_font_stylesheet' ), 1, 2 );
			}
			\add_filter( 'swis_skip_css_minify', array( $this, 'skip_css_minify' ), 10, 2 );
		}
		\add_action( 'admin_notices', array( $this, 'check_replacement_cache' ) );
		\add_action( 'admin_notices', array( $this, 'font_css_cleared_notice' ) );
		\add_action( 'admin_action_swis_remove_font_css', array( $this, 'remove_font_css' ) );

		$this->cache_dir = $this->content_dir . 'cache/css/';
		if ( ! \is_dir( $this->cache_dir ) ) {
			if ( ! \wp_mkdir_p( $this->cache_dir ) ) {
				\add_action( 'admin_notices', array( $this, 'requirements_failed' ) );
				return;
			}
		}
		if ( ! \is_writable( $this->cache_dir ) ) {
			\add_action( 'admin_notices', array( $this, 'requirements_failed' ) );
			return;
		}
		$this->cache_dir_url = $this->content_url . 'cache/css/';

		if ( $this->get_option( 'self_host_fonts' ) ) {
			$this->font_cache_dir = $this->content_dir . 'cache/fonts/';
			if ( ! \is_dir( $this->font_cache_dir ) ) {
				if ( ! \wp_mkdir_p( $this->font_cache_dir ) ) {
					\add_action( 'admin_notices', array( $this, 'requirements_failed' ) );
					return;
				}
			}
			if ( ! \is_writable( $this->font_cache_dir ) ) {
				\add_action( 'admin_notices', array( $this, 'requirements_failed' ) );
				return;
			}
			$this->font_cache_dir_url = $this->content_url . 'cache/fonts/';
		}
	}

	/**
	 * Inlines the font CSS in the <head>.
	 */
	public function inline_font_css() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$font_css = $this->get_option( 'optimize_fonts_css' );
		if ( empty( $font_css ) || ! \is_string( $font_css ) ) {
			return;
		}
		if ( $this->is_easyio_active() && ( \get_option( 'exactdn_all_the_things' ) || \get_site_option( 'exactdn_all_the_things' ) ) ) {
			global $exactdn;
			$exactdn_domain = false;
			if ( \is_object( $exactdn ) && \method_exists( $exactdn, 'get_exactdn_domain' ) ) {
				$exactdn_domain = $exactdn->get_exactdn_domain();
				if ( $exactdn_domain && false !== \strpos( $font_css, $exactdn_domain ) ) {
					$this->debug_message( 'easyio fonts found' );
					$this->crossorigin = $exactdn_domain;
				}
			}
			$local_domain = $this->parse_url( \get_site_url(), PHP_URL_HOST );
			if ( $local_domain && false !== strpos( $font_css, $local_domain ) ) {
				$this->debug_message( 'local fonts found, likely to be replaced by easyio' );
				$this->crossorigin = $exactdn_domain;
			}
		}
		$minifier = new Minify\CSS( $font_css );
		echo "<style id='swis-font-css'>\n" . \wp_kses( $minifier->minify(), 'strip' ) . "\n</style>\n";
	}

	/**
	 * Checks stylesheet for cached version with fonts stripped out.
	 *
	 * @param string $url URL to the stylesheet.
	 * @param string $handle The registered handle for the resource.
	 * @return string The URL for the resource, minus fonts if a cached version is available.
	 */
	public function replace_font_stylesheet( $url, $handle = '' ) {
		if ( ! $this->is_frontend() ) {
			return $url;
		}
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$replace_styles = $this->get_option( 'optimize_fonts_replace' );
		if ( $this->is_iterable( $replace_styles ) && count( $replace_styles ) ) {
			/**
			 * Each style has 'original_url' and 'cache_url' (path only), 'original_file',
			 * 'cache_file', 'mod_time', and 'handle'.
			 */
			foreach ( $replace_styles as $replace_style ) {
				if ( $replace_style['handle'] === $handle ) {
					$this->debug_message( "$handle matches a replacement, checking $url for {$replace_style['original_url']}" );
					if ( strpos( $url, $replace_style['original_url'] ) ) {
						$this->debug_message( "$url also matches" );
						if ( (int) filemtime( $replace_style['original_file'] ) === (int) $replace_style['mod_time'] ) {
							$this->debug_message( 'modification time still good' );
						} else {
							$this->debug_message( 'mod time no good, replacing anyway, but will warn admin' );
						}
						if ( $this->is_file( $replace_style['cache_file'] ) ) {
							$this->debug_message( "{$replace_style['cache_file']} exists, all good!" );
							$url = str_replace( $replace_style['original_url'], $replace_style['cache_url'], $url );
						}
					}
				}
			}
		}
		return $url;
	}

	/**
	 * Checks stylesheet for cached version with fonts stripped out. Thus it is already minified and should not be processed again.
	 *
	 * Note that since $this->replace_font_stylesheet() runs before minify, this shouldn't even be an issue. But, just in case...
	 *
	 * @param bool   $skip Whether or not to skip CSS Minify for the given URL. Defaults to false, but may be altered by other filters.
	 * @param string $url URL to the stylesheet.
	 * @return bool True to skip CSS Minify, false otherwise.
	 */
	public function skip_css_minify( $skip, $url ) {
		$replace_styles = $this->get_option( 'optimize_fonts_replace' );
		if ( $this->is_iterable( $replace_styles ) && count( $replace_styles ) ) {
			foreach ( $replace_styles as $replace_style ) {
				if ( strpos( $url, $replace_style['original_url'] ) ) {
					$this->debug_message( "$url matches existing CSS font resource, skipping CSS Minify" );
					return true;
				}
			}
		}
		return $skip;
	}

	/**
	 * Checks replacement stylesheets to see if the original file has been modified.
	 */
	public function check_replacement_cache() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$permissions = \apply_filters( 'swis_performance_admin_permissions', 'manage_options' );
		if ( ! \is_admin() || ! \current_user_can( $permissions ) || ! $this->get_option( 'optimize_fonts_css' ) ) {
			return;
		}
		$replace_styles = $this->get_option( 'optimize_fonts_replace' );
		if ( $this->is_iterable( $replace_styles ) && count( $replace_styles ) ) {
			foreach ( $replace_styles as $replace_style ) {
				$original_file = $replace_style['original_file'];
				if ( $this->is_file( $original_file ) && $this->is_file( $replace_style['cache_file'] ) && (int) filemtime( $original_file ) === (int) $replace_style['mod_time'] ) {
					$this->debug_message( "modification time still good for $original_file" );
					continue;
				}
				$this->debug_message( 'mod time no good, or file gone, cache/optimize settings need refresh' );
				?>
				<div class="notice notice-warning">
					<p>
				<?php
						\printf(
							/* translators: 1: path to modified CSS file 2: SWIS Performance */
							\esc_html__( '%1$s has been modified, the Google Fonts CSS must be removed from the %2$s settings to refresh the font settings.', 'swis-performance' ),
							'<strong>' . \esc_html( $original_file ) . '</strong>',
							'<strong>SWIS Performance</strong>',
						);
				?>
					</p>
					<p>
						<a class="button button-primary" href="<?php echo \esc_url( \wp_nonce_url( admin_url( 'admin.php?action=swis_remove_font_css' ), 'swis_cache_clear_nonce' ) ); ?>"><?php \esc_html_e( 'Remove Google Fonts CSS', 'swis-performance' ); ?></a>
					</p>
				</div>
				<?php
				break;
			}
		}
	}

	/**
	 * Display notice after clearing the font CSS.
	 */
	public function font_css_cleared_notice() {
		// Check user has permissions to clear cache.
		if ( ! \current_user_can( \apply_filters( 'swis_performance_admin_permissions', 'manage_options' ) ) ) {
			return;
		}
		if ( get_transient( 'swis_font_css_removed' ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'The Google Fonts CSS has been removed, visit your home page to refresh the font settings.', 'swis-performance' )
			);
			delete_transient( 'swis_font_css_removed' );
		}
	}

	/**
	 * Process a request to clear the Font CSS setting.
	 */
	public function remove_font_css() {
		// Check if this is a request to remove the font CSS.
		if (
			empty( $_GET['action'] ) || // The action arg is empty.
			'swis_remove_font_css' !== $_GET['action'] // The action param isn't one we recognize.
		) {
			return;
		}
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		// Verify nonce.
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'swis_cache_clear_nonce' ) ) {
			wp_die( esc_html__( 'Access denied', 'swis-performance' ) );
		}

		// Check user has permissions to modify font setting.
		if ( ! \current_user_can( \apply_filters( 'swis_performance_admin_permissions', 'manage_options' ) ) ) {
			wp_die( esc_html__( 'Access denied', 'swis-performance' ) );
		}

		$this->set_option( 'optimize_fonts_css', '' );
		$this->set_option( 'optimize_fonts_list', $font_list );
		$this->set_option( 'optimize_fonts_replace', $replace_fonts );

		set_transient( 'swis_font_css_removed', 1 );

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Checks if an asset is from an external site.
	 *
	 * @param string $url The asset URL.
	 * @return bool True for external asset, false for local asset.
	 */
	public function is_external( $url ) {
		if ( 0 === \strpos( $url, '/' ) && 0 !== \strpos( $url, '//' ) ) {
			return false;
		}
		$asset_url_parts = $this->parse_url( $url );
		$local_url_parts = $this->parse_url( \get_site_url() );
		if ( ! empty( $asset_url_parts['host'] ) && ! empty( $local_url_parts['host'] ) && 0 === \strcasecmp( $asset_url_parts['host'], $local_url_parts['host'] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Gets the contents of a CSS file.
	 *
	 * @param string $url The asset URL.
	 * @return array {
	 *     @type string The CSS contents with Google or local font-face rules.
	 *     @type array Path information for the CSS file if it should be replaced, empty string otherwise.
	 * }
	 */
	public function get_font_css( $url ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$css      = '';
		$replace  = '';
		$url_bits = \explode( '?', $url );

		$site_url    = \get_site_url();
		$site_domain = $this->parse_url( $site_url, PHP_URL_HOST );
		if ( false !== strpos( $url, $site_domain ) ) {
			$this->debug_message( "found $site_domain, replacing in $url" );
			$asset_path = \trailingslashit( ABSPATH ) . \str_replace( \trailingslashit( $site_url ), '', $this->prepend_url_scheme( $url_bits[0] ) );
		} elseif ( '/' === \substr( $url, 0, 1 ) && '/' !== \substr( $url, 1, 1 ) ) {
			// Handle relative URLs like /wp-includes/css/something.css.
			$asset_path = ABSPATH . \ltrim( $url, '/' );
		} else {
			// Check for CDN URLs by swapping domains.
			$asset_domain = $this->parse_url( $url, PHP_URL_HOST );
			// Swapping $asset_domain with $site_domain to get a local URL (possibly).
			$possible_url = \str_replace( $asset_domain, $site_domain, $url );
			$this->debug_message( "swapped $asset_domain for $site_domain to find a file via $possible_url" );
			$url_bits   = \explode( '?', $possible_url );
			$asset_path = \trailingslashit( ABSPATH ) . \str_replace( trailingslashit( $site_url ), '', $this->prepend_url_scheme( $url_bits[0] ) );
			$this->debug_message( "now we have $asset_path, we'll see if it is local" );
		}

		if ( $url !== $asset_path && $this->is_file( $asset_path ) ) {
			$this->debug_message( "checking CSS from $asset_path" );
			$css = \file_get_contents( $asset_path );
		} elseif ( \strpos( $url, 'fonts.googleapis.com' ) ) {
			$url = \add_query_arg( 'display', 'swap', $url );
			$this->debug_message( "getting CSS from $url" );
			$response = \wp_remote_get(
				$url,
				array(
					'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
				)
			);
			if ( \is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				$this->debug_message( "request for $url failed: $error_message (" . \wp_remote_retrieve_response_code( $response ) . ')' );
			} elseif ( ! empty( $response['body'] ) ) {
				return array( $this->maybe_self_host_fonts( $response['body'] ), '' );
			}
			return array( '', '' );
		} else {
			// Not a Google API URL or could not retrieve local file.
			$this->debug_message( "could not get CSS from $url" );
			return array( '', '' );
		}
		// If there are no Google font URLs in the CSS, bail.
		if ( false === \strpos( $css, 'fonts.gstatic.com' ) || false === \strpos( $css, '@font-face' ) ) {
			// But not before we check for local fonts that might be rewritten with Easy IO.
			$this->has_local_or_easyio_fonts( $css );
			$this->debug_message( "no Google Fonts in $url" );
			return array( '', '' );
		}
		// Grok through the CSS for @font-face rules, and if there is anything extra, separate the CSS
		// and cache the font-less version.
		$remaining_css = preg_replace( '/@font-face\s*?{[^}{]+?}/', '', $css );
		$minifier      = new Minify\CSS( $remaining_css );
		$remaining_css = $minifier->minify();
		$css_replace   = '';
		if ( $remaining_css ) {
			$this->has_local_or_easyio_fonts( $css );
			$this->debug_message( "extra CSS found in $url, seeing if we can cache it without font-face rules" );
			if ( $asset_path && \preg_match_all( '/@font-face\s*?{[^}{]+?}/', $css, $font_faces ) ) {
				if ( ! empty( $font_faces[0] ) && $this->is_iterable( $font_faces[0] ) ) {
					$this->debug_message( "retrieved font-face declarations from $url" );
					$css = '';
					foreach ( $font_faces[0] as $font_face ) {
						$css .= rtrim( $font_face ) . "\n";
					}
					// Now that we have the font-only CSS, save the font-free CSS in the cache dir.
					$cache_file = $this->get_cache_path( $asset_path, filemtime( $asset_path ) );
					$cache_url  = $this->get_cache_url( $asset_path, filemtime( $asset_path ), $this->parse_url( $url, PHP_URL_QUERY ) );
					if ( $cache_file ) {
						$this->debug_message( "saving remaining css to $cache_file" );
						file_put_contents( $cache_file, $remaining_css );
						$css_replace = array(
							'original_url'  => $this->parse_url( $url, PHP_URL_PATH ),
							'cache_url'     => $this->parse_url( $cache_url, PHP_URL_PATH ),
							'original_file' => $asset_path,
							'cache_file'    => $cache_file,
							'mod_time'      => filemtime( $asset_path ),
						);
					}
				}
			}
			if ( empty( $css_replace ) ) {
				$this->debug_message( 'no font swap possible, or cache file could not be saved' );
				return array( '', '' );
			}
		}
		if ( false === strpos( $css, 'swap;' ) ) {
			$css = preg_replace( '/\s*?font-style:/', "\nfont-display: swap;\n    font-style:", $css );
		}
		// At this point, we have a bit of CSS with nothing but @font-face rules, and confirmed Google font URLs.
		// Next, we might make them local for self-hosting.
		$css = $this->maybe_self_host_fonts( $css );
		return array( $css, $css_replace );
	}

	/**
	 * Checks if self-hosting is enabled, then fetches fonts and stores them in the cache folder.
	 *
	 * @param string $css The font CSS to parse.
	 * @return string The CSS contents, potentially swapped for local URLs.
	 */
	public function maybe_self_host_fonts( $css ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( $this->is_easyio_active() && ( \get_option( 'exactdn_all_the_things' ) || \get_site_option( 'exactdn_all_the_things' ) ) ) {
			return $css;
		}
		if ( \preg_match_all( '#src:\s*url\(\s*(https://fonts.gstatic.com[^)\s]+?)\s*\)#', $css, $font_url_matches ) && ! empty( $font_url_matches[1] ) ) {
			if ( $this->is_iterable( $font_url_matches[1] ) ) {
				foreach ( $font_url_matches[1] as $font_url ) {
					$font_cache_file = $this->get_font_cache_path( $font_url );
					if ( empty( $font_cache_file ) ) {
						continue;
					}
					if ( $this->is_file( $font_cache_file ) ) {
						$this->debug_message( "$font_cache_file already exists, skipping $font_url" );
					}
					$font_cache_url = $this->get_font_cache_url( $font_url );
					$this->debug_message( "downloading font at $font_url" );
					$response = \wp_remote_get( $font_url );
					if ( \is_wp_error( $response ) ) {
						$error_message = $response->get_error_message();
						$this->debug_message( "request for $font_url failed: $error_message (" . \wp_remote_retrieve_response_code( $response ) . ')' );
						continue;
					} elseif ( empty( $response['body'] ) ) {
						$this->debug_message( "empty response for $font_url (" . \wp_remote_retrieve_response_code( $response ) . ')' );
						continue;
					}
					$font_data = $response['body'];
					if ( file_put_contents( $font_cache_file, $font_data ) ) {
						$this->debug_message( "saved to $font_cache_file" );
						$css = str_replace( $font_url, $font_cache_url, $css );
					}
				}
			}
		}
		return $css;
	}

	/**
	 * Build a path to cache the font file on disk after retrieval.
	 *
	 * @param string $url A font URL.
	 * @return string The location to store the font file in the cache.
	 */
	public function get_font_cache_path( $url ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( false === strpos( $url, 'fonts.gstatic.com' ) ) {
			$this->debug_message( "$url not a Google Font" );
			return false;
		}
		$font_url_path = $this->parse_url( $url, PHP_URL_PATH );
		if ( empty( $font_url_path ) ) {
			$this->debug_message( "$url does not have a path" );
			return false;
		}
		$cache_file = $this->font_cache_dir . ltrim( $font_url_path, '/\\' );
		$cache_dir  = dirname( $cache_file );
		if ( ! is_dir( $cache_dir ) ) {
			if ( ! wp_mkdir_p( $cache_dir ) ) {
				return false;
			}
		}
		if ( ! is_writable( $cache_dir ) ) {
			return false;
		}
		return $cache_file;
	}

	/**
	 * Build a URL for the cached file.
	 *
	 * @param string $url A font URL.
	 * @return string The URL of the font file in the cache.
	 */
	public function get_font_cache_url( $url ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( false === strpos( $url, 'fonts.gstatic.com' ) ) {
			$this->debug_message( "$url not a Google Font" );
			return false;
		}
		$font_url_path = $this->parse_url( $url, PHP_URL_PATH );
		if ( empty( $font_url_path ) ) {
			$this->debug_message( "$url does not have a path" );
			return false;
		}
		$cache_url = $this->font_cache_dir_url . ltrim( $font_url_path, '/\\' );
		return $cache_url;
	}

	/**
	 * Search for local fonts that might be rewritten with Easy IO.
	 *
	 * @param string $css The CSS to grok.
	 */
	public function has_local_or_easyio_fonts( $css ) {
		if ( $this->is_easyio_active() && ( \get_option( 'exactdn_all_the_things' ) || \get_site_option( 'exactdn_all_the_things' ) ) ) {
			if ( $this->get_option( 'crossorigin_fonts' ) ) {
				return;
			}
			if ( \strpos( $css, '@font-face' ) && \preg_match_all( '/@font-face\s*?{[^}{]+?}/', $css, $font_faces ) ) {
				if ( ! empty( $font_faces[0] ) && $this->is_iterable( $font_faces[0] ) ) {
					$this->debug_message( 'possible local fonts in: ' . substr( $css, 0, 200 ) );
					$site_url    = \get_site_url();
					$site_domain = $this->parse_url( $site_url, PHP_URL_HOST );
					global $exactdn;
					$exactdn_domain = false;
					if ( \is_object( $exactdn ) && \method_exists( $exactdn, 'get_exactdn_domain' ) ) {
						$exactdn_domain = $exactdn->get_exactdn_domain();
					}
					foreach ( $font_faces[0] as $font_face ) {
						if ( \strpos( $font_face, $site_domain ) ) {
							$this->debug_message( 'local fonts found' );
							$this->set_option( 'crossorigin_fonts', true );
							break;
						}
						if ( $exactdn_domain && \strpos( $font_face, $exactdn_domain ) ) {
							$this->debug_message( 'easyio fonts found' );
							$this->set_option( 'crossorigin_fonts', true );
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Check to see which JS/CSS files have been registered for the current page.
	 */
	public function find_assets() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$assets = wp_styles();

		foreach ( $assets->done as $handle ) {
			$url = $this->prepend_url_scheme( $assets->registered[ $handle ]->src );

			$asset = array(
				'url'      => $url,
				'external' => (int) $this->is_external( $url ),
			);

			$this->assets[ $handle ] = $asset;
		}
	}

	/**
	 * Make sure protocol-relative URLs like //www.example.com/wp-includes/script.js get a scheme added.
	 *
	 * @param string $url The URL to potentially fix.
	 * @return string The properly-schemed URL.
	 */
	public function prepend_url_scheme( $url ) {
		if ( 0 === strpos( $url, '//' ) ) {
			return ( is_ssl() ? 'https:' : 'http' ) . $url;
		}
		return $url;
	}

	/**
	 * Remove Google Font CSS files.
	 */
	public function disable_assets() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$fonts_list = $this->get_option( 'optimize_fonts_list' );
		if ( $this->is_iterable( $fonts_list ) ) {
			foreach ( $fonts_list as $font_handle ) {
				swis()->slim->add_exclusion( $font_handle );
			}
		}
	}

	/**
	 * Go through the list of discovered CSS files for the current page, retrieve the font CSS, and record the CSS handles.
	 */
	public function stash_css() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$font_css      = '';
		$font_list     = array();
		$replace_fonts = array();
		$this->clear_dir( $this->font_cache_dir );
		if ( ! empty( $this->assets ) ) {
			foreach ( $this->assets as $handle => $asset ) {
				$css     = '';
				$replace = '';
				if ( ! $asset['external'] ) {
					list( $css, $replace ) = $this->get_font_css( $asset['url'] );
					$this->debug_message( "retrieved CSS code for $handle with length: " . strlen( $css ) );
				} elseif ( strpos( $asset['url'], 'fonts.googleapis.com' ) ) {
					list( $css, $replace ) = $this->get_font_css( $asset['url'] );
					$this->debug_message( "retrieved CSS code for $handle with length: " . strlen( $css ) );
				} else {
					list( $css, $replace ) = $this->get_font_css( $asset['url'] );
					$this->debug_message( "retrieved CSS code for $handle with length: " . strlen( $css ) );
				}
				if ( ! empty( $css ) ) {
					$font_css .= rtrim( $css ) . "\n";
					if ( $replace && is_array( $replace ) ) {
						$replace['handle'] = $handle;
						$replace_fonts[]   = $replace;
					} else {
						$font_list[] = $handle;
					}
				}
			}
		}
		if ( $font_css && ( $font_list || $replace_fonts ) ) {
			$this->debug_message( 'Obtained font CSS, along with a list of fonts to suppress or replace, saving!' );
			$this->set_option( 'optimize_fonts_css', $font_css );
			$this->set_option( 'optimize_fonts_list', $font_list );
			$this->set_option( 'optimize_fonts_replace', $replace_fonts );
		}
	}
}

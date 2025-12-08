<?php
/**
 * Class and methods to minify CSS.
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
 * Enables plugin to filter CSS tags and minify the stylesheets.
 */
final class Minify_CSS extends Page_Parser {

	/**
	 * A list of user-defined exclusions, populated by validate_user_exclusions().
	 *
	 * @access protected
	 * @var array $user_exclusions
	 */
	protected $user_exclusions = array();

	/**
	 * Register actions and filters for CSS Minify.
	 */
	public function __construct() {
		if ( ! $this->get_option( 'minify_css' ) ) {
			return;
		}
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return;
		}
		parent::__construct();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		$uri = add_query_arg( '', '' );
		$this->debug_message( "request uri is $uri" );

		/**
		 * Allow pre-empting CSS minify by page.
		 *
		 * @param bool Whether to skip parsing the page.
		 * @param string $uri The URL of the page.
		 */
		if ( apply_filters( 'swis_skip_css_minify_by_page', false, $uri ) ) {
			return;
		}

		$this->cache_dir = $this->content_dir . 'cache/css/';
		if ( ! is_dir( $this->cache_dir ) ) {
			if ( ! wp_mkdir_p( $this->cache_dir ) ) {
				add_action( 'admin_notices', array( $this, 'requirements_failed' ) );
				return;
			}
		}
		if ( ! is_writable( $this->cache_dir ) ) {
			add_action( 'admin_notices', array( $this, 'requirements_failed' ) );
			return;
		}
		$this->cache_dir_url = $this->content_url . 'cache/css/';

		// Overrides for user exclusions.
		add_filter( 'swis_skip_css_minify', array( $this, 'skip_css_minify' ), 10, 2 );

		// Get all the stylesheet URLs and minify them (if necessary).
		add_filter( 'style_loader_src', array( $this, 'minify_styles' ) );
		add_filter( 'swis_elements_link_href', array( $this, 'minify_styles' ) );

		// Minify any custom CSS.
		add_filter( 'wp_get_custom_css', array( $this, 'minify_raw_css' ), 20 );

		$this->validate_user_exclusions();
	}

	/**
	 * Validate the user-defined exclusions.
	 */
	public function validate_user_exclusions() {
		$this->user_exclusions = array(
			'admin-ajax.php',
			'.build.',
			'.min.',
			'.min-',
			'-min.',
			'autoptimize',
			'/bb-plugin/cache/',
			'brizy/public/editor',
			'/cache/et/',
			'/cache/min/',
			'/cache/wpfc',
			'/comet-cache/',
			'cornerstone/assets/',
			'Divi/includes/builder/',
			'/Divi/style.css',
			'/et-cache/',
			'jch-optimize',
			'/plg_jchoptimize/',
			'/siteground-optimizer-assets/',
			'/thrive/theme-template',
			'/thrive-theme/',
			'/wp-includes/',
		);

		$user_exclusions = $this->get_option( 'minify_css_exclude' );
		if ( ! empty( $user_exclusions ) ) {
			if ( is_string( $user_exclusions ) ) {
				$user_exclusions = array( $user_exclusions );
			}
			if ( is_array( $user_exclusions ) ) {
				foreach ( $user_exclusions as $exclusion ) {
					if ( ! is_string( $exclusion ) ) {
						continue;
					}
					$this->user_exclusions[] = $exclusion;
				}
			}
		}
	}

	/**
	 * Exclude CSS from being processed based on user specified list.
	 *
	 * @param boolean $skip Whether SWIS should skip processing.
	 * @param string  $url The script URL.
	 * @return boolean True to skip the resource, unchanged otherwise.
	 */
	public function skip_css_minify( $skip, $url ) {
		if ( $this->test_mode_active() ) {
			return true;
		}
		if ( $this->user_exclusions ) {
			foreach ( $this->user_exclusions as $exclusion ) {
				if ( false !== strpos( $url, $exclusion ) ) {
					$this->debug_message( __METHOD__ . "(); excluded $url via $exclusion" );
					return true;
				}
			}
		}
		return $skip;
	}

	/**
	 * Purge CSS cache.
	 */
	public function purge_cache() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$this->clear_dir( $this->cache_dir );
	}

	/**
	 * Minifies CSS file if necessary.
	 *
	 * @param string $url URL to the stylesheet.
	 * @return string The minified URL for the resource, if it was allowed.
	 */
	public function minify_styles( $url ) {
		if ( ! $this->is_frontend() ) {
			return $url;
		}
		if ( apply_filters( 'swis_skip_css_minify', false, $url ) ) {
			return $url;
		}
		if ( ! $this->function_exists( 'filemtime' ) ) {
			return $url;
		}
		$file = $this->get_local_path( $url );
		if ( ! $file ) {
			return $url;
		}
		$mod_time   = filemtime( $file );
		$cache_file = $this->get_cache_path( $file, $mod_time );
		$cache_url  = $this->get_cache_url( $file, $mod_time, $this->parse_url( $url, PHP_URL_QUERY ) );
		if ( $cache_file && ! $this->is_file( $cache_file ) ) {
			$minifier = new Minify\CSS( $file );
			$minifier->minify( $cache_file );
		}
		if ( $this->is_file( $cache_file ) && ! empty( $cache_url ) ) {
			return $cache_url;
		}
		return $url;
	}

	/**
	 * Minifies raw CSS code.
	 *
	 * @param string $css CSS to minify.
	 * @return string The minified CSS.
	 */
	public function minify_raw_css( $css ) {
		if ( ! $this->is_frontend() ) {
			return $css;
		}
		if ( ! empty( $css ) ) {
			$minifier = new Minify\CSS( $css );
			$new_css  = $minifier->minify();
			if ( ! empty( $new_css ) ) {
				$css = $new_css;
			}
		}
		return $css;
	}
}

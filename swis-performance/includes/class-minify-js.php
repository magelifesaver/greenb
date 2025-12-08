<?php
/**
 * Class and methods to minify JS.
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
 * Enables plugin to filter JS tags and minify the scripts.
 */
final class Minify_JS extends Page_Parser {

	/**
	 * A list of user-defined exclusions, populated by validate_user_exclusions().
	 *
	 * @access protected
	 * @var array $user_exclusions
	 */
	protected $user_exclusions = array();

	/**
	 * Register actions and filters for JS Minify.
	 */
	public function __construct() {
		if ( ! $this->get_option( 'minify_js' ) ) {
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
		 * Allow pre-empting JS minify by page.
		 *
		 * @param bool Whether to skip parsing the page.
		 * @param string $uri The URL of the page.
		 */
		if ( apply_filters( 'swis_skip_js_minify_by_page', false, $uri ) ) {
			return;
		}

		$this->cache_dir = $this->content_dir . 'cache/js/';
		if ( ! is_dir( $this->cache_dir ) ) {
			if ( ! wp_mkdir_p( $this->cache_dir ) ) {
				return;
			}
		}
		if ( ! is_writable( $this->cache_dir ) ) {
			return;
		}
		$this->cache_dir_url = $this->content_url . 'cache/js/';

		// Overrides for user exclusions.
		add_filter( 'swis_skip_js_minify', array( $this, 'skip_js_minify' ), 10, 2 );

		// Get all the script URLs and minify them (if necessary).
		add_filter( 'script_loader_src', array( $this, 'minify_scripts' ) );
		add_filter( 'swis_elements_script_src', array( $this, 'minify_scripts' ) );

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
			'assets/min/',
			'/assets/slim.js',
			'/bb-plugin/cache/',
			'/bb-plugin/js/build/',
			'brizy/public/editor',
			'/cache/et/',
			'/cache/min/',
			'/cache/wpfc',
			'/comet-cache/',
			'cornerstone/assets/',
			'debug-bar/js/debug-bar-js.js',
			'debug-bar/js/debug-bar.js',
			'Divi/includes/builder/',
			'/et-cache/',
			'/eventin-pro/multivendor/build/index.js',
			'fusion-app',
			'fusion-builder',
			'/includes/lazysizes-pre.js',
			'/includes/lazysizes.js',
			'/includes/lazysizes-post.js',
			'/includes/ls.unveilhooks.js',
			'/includes/ls.unveilhooks-addon.js',
			'/includes/check-webp.js',
			'/includes/load-webp.js',
			'jch-optimize',
			'/plg_jchoptimize/',
			'/kali-forms',
			'/main/dist/js/bundle',
			'/revslider',
			'/siteground-optimizer-assets/',
			'/spx/assets/',
			'/wp-includes/',
		);

		$user_exclusions = $this->get_option( 'minify_js_exclude' );
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
	 * Exclude JS from being processed based on user specified list.
	 *
	 * @param boolean $skip Whether SWIS should skip processing.
	 * @param string  $url The script URL.
	 * @return boolean True to skip the resource, unchanged otherwise.
	 */
	public function skip_js_minify( $skip, $url ) {
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
	 * Purge JS cache.
	 */
	public function purge_cache() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$this->clear_dir( $this->cache_dir );
	}

	/**
	 * Minifies scripts if necessary.
	 *
	 * @param string $url URL to the script.
	 * @return string The minified URL for the resource, if it was allowed.
	 */
	public function minify_scripts( $url ) {
		if ( ! $this->is_frontend() ) {
			return $url;
		}
		if ( apply_filters( 'swis_skip_js_minify', false, $url ) ) {
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
		$cache_file = $this->get_cache_path( $file, $mod_time, 'js' );
		$cache_url  = $this->get_cache_url( $file, $mod_time, $this->parse_url( $url, PHP_URL_QUERY ), 'js' );
		if ( $cache_file && ! $this->is_file( $cache_file ) ) {
			$minifier = new Minify\JS( $file );
			$minifier->minify( $cache_file );
		}
		if ( $this->is_file( $cache_file ) && ! empty( $cache_url ) ) {
			return $cache_url;
		}
		return $url;
	}
}

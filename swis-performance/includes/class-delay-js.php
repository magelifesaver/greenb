<?php
/**
 * Class and methods to delay JS.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enables plugin to filter JS tags and delay them.
 */
final class Delay_JS extends Page_Parser {

	/**
	 * A list of valid JavaScript MIME types.
	 *
	 * @access private
	 * @var array $valid_mimes
	 */
	private $valid_mimes = array(
		'application/ecmascript',
		'application/javascript',
		'text/ecmascript',
		'text/javascript',
	);

	/**
	 * A list of user-defined rules/exclusions.
	 *
	 * @access protected
	 * @var array $delay_exclusions
	 */
	protected $delay_exclusions = array();

	/**
	 * Register actions and filters for JS Delay.
	 */
	public function __construct() {
		$all_rules = $this->get_option( 'slim_js_css' );
		if ( empty( $all_rules['delay'] ) ) {
			return;
		}
		$this->delay_exclusions = $all_rules['delay'];

		// Not sure if we need this either. Most of the "work" will be done in Slim, and this just alters the tags.
		parent::__construct();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		$uri = add_query_arg( '', '' );
		$this->debug_message( "request uri is $uri" );

		// Handle built-in exclusions.
		add_filter( 'swis_skip_js_delay_by_page', array( $this, 'skip_js_delay' ), 10, 2 );

		/**
		 * Allow pre-empting JS delay by page, even though there is a UI for that.
		 *
		 * @param bool Whether to skip parsing the page.
		 * @param string $uri The URL of the page.
		 */
		if ( apply_filters( 'swis_skip_js_delay_by_page', false, $uri ) ) {
			return;
		}

		// Hook into the SWIS output buffer filter.
		add_filter( $this->prefix . 'filter_page_output', array( $this, 'inline_js_delay' ) );

		// Get all the script elements and rewrite them (if enabled).
		add_filter( 'script_loader_tag', array( $this, 'delay_scripts' ), 19, 2 );
		add_filter( 'swis_elements_script_tag', array( $this, 'delay_scripts' ), 9, 2 );
	}

	/**
	 * Prevent JS from being delayed.
	 *
	 * @param boolean $skip Whether SWIS should skip processing.
	 * @param string  $uri The current page.
	 * @return boolean True to prevent JS delay, unchanged otherwise.
	 */
	public function skip_js_delay( $skip, $uri ) {
		if ( function_exists( 'affwp_is_affiliate_portal' ) && affwp_is_affiliate_portal() ) {
			return true;
		}
		if ( $this->test_mode_active() ) {
			return true;
		}
		if ( ! $this->is_frontend() ) {
			return true;
		}
		if ( $this->is_amp() ) {
			return true;
		}
		return $skip;
	}

	/**
	 * Rewrites a script element to be delayed.
	 *
	 * @param string $element HTML of the script element.
	 * @param string $handle The JS handle. Optional.
	 * @return string The delayed version of the resource, if it was allowed.
	 */
	public function delay_scripts( $element, $handle = '' ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! $this->is_frontend() ) {
			return $element;
		}
		if ( false !== strpos( $element, 'jquery.js' ) ) {
			return $element;
		}
		if ( false !== strpos( $element, 'jquery.min.js' ) ) {
			return $element;
		}
		if ( false !== strpos( $element, 'asset-clean' ) ) {
			return $element;
		}
		if ( false !== strpos( $element, 'data-cfasync' ) ) {
			return $element;
		}
		if ( false !== strpos( $element, 'lazysizes.min.js' ) ) {
			return $element;
		}
		$script_parts = $this->script_parts( $element );
		// Process as an inline script block/element.
		if ( ! empty( $script_parts['open'] ) && ! empty( trim( $script_parts['content'] ) ) && ! $this->get_attribute( $script_parts['open'], 'src' ) ) {
			$handle    = 'inline-scripts';
			$mime_type = $this->get_attribute( $script_parts['open'], 'type' );
			if ( ! empty( $mime_type ) && in_array( $mime_type, $this->valid_mimes, true ) ) {
				return $element;
			}
		}
		// JS is not delayed by default, which is filtered/modified by Slim delay rules.
		if ( apply_filters( 'swis_skip_js_delay', true, $element, $handle ) ) {
			return $element;
		}
		$this->debug_message( trim( $element ) );

		$closing = '';
		if (
			empty( $script_parts['open'] ) &&
			0 === strpos( trim( $element ), '<script' ) &&
			$this->str_ends_with( trim( $element ), '>' )
		) {
			$script_parts['open']    = trim( $element );
			$script_parts['content'] = '';
			$script_parts['close']   = '';
		}

		$src = $this->get_attribute( $script_parts['open'], 'src' );
		if ( ! empty( $src ) && empty( trim( $script_parts['content'] ) ) && ! $this->get_attribute( $script_parts['open'], 'data-swis-src' ) ) {
			$this->debug_message( 'delaying src to data-swis-src' );
			$delayed_tag = $script_parts['open'];
			$this->set_attribute( $delayed_tag, 'data-swis-src', $src, true );
			$this->remove_attribute( $delayed_tag, 'src' );
			if ( ! $this->get_attribute( $delayed_tag, 'data-cfasync' ) ) {
				$this->set_attribute( $delayed_tag, 'data-cfasync', 'false', true );
			}
			if ( ! $this->get_attribute( $delayed_tag, 'data-no-optimize' ) ) {
				$this->set_attribute( $delayed_tag, 'data-no-optimize', '1', true );
			}
			if ( ! $this->get_attribute( $delayed_tag, 'data-no-defer' ) ) {
				$this->set_attribute( $delayed_tag, 'data-no-defer', '1', true );
			}
			if ( ! $this->get_attribute( $delayed_tag, 'data-no-minify' ) ) {
				$this->set_attribute( $delayed_tag, 'data-no-minify', '1', true );
			}
			$element = $delayed_tag . "</script>\n";
		} elseif ( ! $src && ! empty( trim( $script_parts['content'] ) ) && ! $this->get_attribute( $script_parts['open'], 'data-swis-src' ) ) {
			$this->debug_message( 'possibly delaying inline to data-swis-src' );
			// Process inline script tags.
			$process = true;
			foreach ( $this->inline_exclusions as $inline_exclusion ) {
				if ( false !== strpos( $script_parts['content'], $inline_exclusion ) ) {
					$this->debug_message( "stock delay exclusion $inline_exclusion applied" );
					$process = false;
				}
			}
			if ( preg_match( '/^\s*?(?:var\s*?.*?;\n?)+?\s*$/', $script_parts['content'] ) ) {
				$this->debug_message( 'var= exclusion applied' );
				$process = false;
			}
			if ( $process ) {
				$this->debug_message( 'delaying inline JS' );
				$delayed_tag = $script_parts['open'];
				$this->set_attribute(
					$delayed_tag,
					'data-swis-src',
					'data:text/javascript,' . $this->encode_data_uri( $script_parts['content'] )
				);
				$element = $delayed_tag . "</script>\n";
			}
		}
		$this->debug_message( trim( $element ) );
		return $element;
	}

	/**
	 * Insert the JS to load the delayed JS.
	 *
	 * @param string $buffer The HTML content of the page.
	 * @return string The altered HTML.
	 */
	public function inline_js_delay( $buffer ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$delay_timeout = apply_filters( 'swis_js_delay_timeout', 10 );
		$script_name   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'delayed.js' : 'delayed.min.js';
		$inline_script = file_get_contents( SWIS_PLUGIN_PATH . 'assets/' . $script_name );
		return preg_replace( '#</body>#i', '<script data-cfasync="false" data-no-optimize="1" data-no-defer="1" data-no-minify="1">const swisAutoLoadDuration = ' . (int) $delay_timeout . ';' . $inline_script . '</script></body>', $buffer, 1 );
	}
}

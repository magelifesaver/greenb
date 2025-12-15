<?php
/**
 * Class and methods to defer JS.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enables plugin to filter JS tags and defer them.
 */
final class Defer_JS extends Page_Parser {

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
	 * The default exclusions for deferring scripts.
	 *
	 * @access private
	 * @var array $default_exclusions
	 */
	private $default_exclusions = array(
		'app.mailerlite.com',
		'asset-clean',
		'content.jwplatform.com',
		'data-cfasync',
		'data-no-optimize',
		'data-no-defer',
		'facetwp/assets/js/dist/front',
		'gist.github.com',
		'google.com/recaptcha',
		'www.idxhome.com',
		'/js/dist/api-fetch.',
		'/js/dist/hooks.',
		'/js/dist/i18n.',
		'/js/dist/url.',
		'/js/dist/vendor/lodash.',
		'/js/dist/vendor/wp-polyfill.',
		'/js/domaincheck',
		'/js/tinymce/',
		'js.hsforms.net',
		'lib/admin/assets/lib/webfont/webfont.min.js',
		'/oxygen/component-framework/vendor/aos/aos.js',
		'www.paypal.com/sdk/js',
		'static.mailerlite.com/data/',
		'www.uplaunch.com',
		'verify.authorize.net/anetseal',
		'cdn.voxpow.com/static/libs/v1/',
		'cdn.voxpow.com/media/trackers/js/',
		'SR7',
		'widget.reviews.co.uk',
		'widget.reviews.io',
		'/wpfront-notification-bar/js/wpfront-notification-bar.',
	);

	/**
	 * A list of user-defined exclusions, populated by validate_user_exclusions().
	 *
	 * @access protected
	 * @var array $user_exclusions
	 */
	protected $user_exclusions = array();

	/**
	 * Enable jQuery safe mode.
	 *
	 * @access public
	 * @var bool
	 */
	public $jquery_safe_mode = false;

	/**
	 * Register actions and filters for JS Defer.
	 */
	public function __construct() {
		if ( ! $this->get_option( 'defer_js' ) ) {
			return;
		}
		parent::__construct();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		$this->validate_user_exclusions();
		$uri = \add_query_arg( '', '' );
		$this->debug_message( "request uri is $uri" );

		\add_filter( 'swis_skip_js_defer_by_page', array( $this, 'skip_page' ), 10, 2 );

		/**
		 * Allow pre-empting JS defer by page.
		 *
		 * @param bool Whether to skip parsing the page.
		 * @param string $uri The URL of the page.
		 */
		if ( apply_filters( 'swis_skip_js_defer_by_page', false, $uri ) ) {
			return;
		}

		// Hook into the SWIS output buffer filter.
		add_filter( $this->prefix . 'filter_page_output', array( $this, 'filter_page_output' ) );

		// Overrides for user exclusions.
		add_filter( 'swis_skip_js_defer', array( $this, 'skip_js_defer' ), 10, 2 );

		// Get all the <script> elements and rewrite them (if enabled).
		add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 20, 2 );
		add_filter( 'swis_elements_script_tag', array( $this, 'defer_scripts' ), 10, 2 );
	}

	/**
	 * Validate the user-defined exclusions.
	 */
	public function validate_user_exclusions() {
		$user_exclusions = $this->get_option( 'defer_js_exclude' );
		if ( ! empty( $user_exclusions ) ) {
			if ( \is_string( $user_exclusions ) ) {
				$user_exclusions = array( $user_exclusions );
			}
			if ( \is_array( $user_exclusions ) ) {
				foreach ( $user_exclusions as $exclusion ) {
					if ( ! \is_string( $exclusion ) ) {
						continue;
					}
					$exclusion = \trim( $exclusion );
					if ( 0 === \strpos( $exclusion, 'page:' ) ) {
						$this->user_page_exclusions[] = \str_replace( 'page:', '', $exclusion );
						continue;
					}
					$this->user_exclusions[] = $exclusion;
					if ( 'inline-scripts' === $exclusion ) {
						$this->debug_message( 'enabling jQuery safe mode to prevent inline script issues' );
						$this->jquery_safe_mode = true;
					}
				}
			}
		}
		$this->user_exclusions = array_merge( $this->default_exclusions, $this->user_exclusions );
	}

	/**
	 * Parse page content looking for jQuery script tag to rewrite.
	 *
	 * @param string $content The HTML content to parse.
	 * @return string The filtered HTML content.
	 */
	public function filter_page_output( $content ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! $this->is_frontend() ) {
			return $content;
		}
		if ( $this->is_json( $content ) ) {
			return $content;
		}
		if ( $this->jquery_safe_mode && false === strpos( $content, 'jQuery' ) ) {
			$this->debug_message( 'no inline jQuery detected, looking for jQuery script to defer' );
			preg_match( "#<script\s+(?:type='text/javascript'\s+)?src='[^']+?/jquery(\.min)?\.js[^']*?'[^>]*?>#is", $content, $jquery_tags );
			if ( ! empty( $jquery_tags[0] ) && false === strpos( $jquery_tags[0], 'defer' ) && false === strpos( $jquery_tags[0], 'async' ) ) {
				$this->debug_message( 'found jQuery script to defer' );
				$deferred_jquery = str_replace( '>', ' defer>', $jquery_tags[0] );
				if ( $deferred_jquery && $deferred_jquery !== $jquery_tags[0] ) {
					$this->debug_message( 'replacing jQuery tag with deferred version' );
					$content = str_replace( $jquery_tags[0], $deferred_jquery, $content );
				}
			}
		}
		return $content;
	}

	/**
	 * Exclude JS from being processed based on user specified list.
	 *
	 * @param boolean $skip Whether SWIS should skip processing.
	 * @param string  $tag The script tag HTML.
	 * @return boolean True to skip the resource, unchanged otherwise.
	 */
	public function skip_js_defer( $skip, $tag ) {
		if ( function_exists( 'affwp_is_affiliate_portal' ) && affwp_is_affiliate_portal() ) {
			return true;
		}
		if ( $this->test_mode_active() ) {
			return true;
		}
		if ( $this->user_exclusions ) {
			foreach ( $this->user_exclusions as $exclusion ) {
				if ( false !== strpos( $tag, $exclusion ) ) {
					$this->debug_message( __METHOD__ . "(); user excluded $tag via $exclusion" );
					return true;
				}
			}
		}
		return $skip;
	}

	/**
	 * Rewrites a script element to be deferred.
	 *
	 * @param string $element Script HTML.
	 * @param string $handle The CSS/JS handle. Optional.
	 * @return string The deferred version of the resource, if it was allowed.
	 */
	public function defer_scripts( $element, $handle = '' ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! $this->is_frontend() ) {
			return $element;
		}
		if ( false !== strpos( $element, 'async' ) ) {
			return $element;
		}
		if ( false !== strpos( $element, 'defer' ) ) {
			return $element;
		}
		if ( ! $this->jquery_safe_mode && $this->get_option( 'defer_jquery_safe' ) ) {
			$this->jquery_safe_mode = true;
		}
		if ( $this->jquery_safe_mode && false !== strpos( $element, 'jquery.js' ) ) {
			$this->debug_message( 'jQuery safe mode skipped jquery.js (for now)' );
			return $element;
		}
		if ( $this->jquery_safe_mode && false !== strpos( $element, 'jquery.min.js' ) ) {
			$this->debug_message( 'jQuery safe mode skipped jquery.js (for now)' );
			return $element;
		}
		$script_parts = $this->script_parts( $element );
		// Process as an inline script block/element.
		if ( ! empty( $script_parts['open'] ) && ! empty( trim( $script_parts['content'] ) ) && ! $this->get_attribute( $script_parts['open'], 'src' ) ) {
			$handle    = 'inline-scripts';
			$mime_type = $this->get_attribute( $script_parts['open'], 'type' );
			if ( ! empty( $mime_type ) && ! in_array( $mime_type, $this->valid_mimes, true ) ) {
				return $element;
			}
		}
		if ( apply_filters( 'swis_skip_js_defer', false, $element, $handle ) ) {
			return $element;
		}
		if ( false !== strpos( $element, 'lazysizes.min.js' ) ) {
			return str_replace( '></script', ' async></script', $element );
		}
		$this->debug_message( trim( $element ) );
		// If we don't have the ending script tag, usually from \SWIS\Element_Filter (but not anymore).
		if ( false === strpos( $element, '</script>' ) ) {
			$this->debug_message( 'ending script tag missing, doing simple replace' );
			$deferred_element = str_replace( '>', ' defer>', $element );
		} else {
			if ( ! $script_parts ) {
				$this->debug_message( 'full script tag not found' );
				return $element;
			} elseif ( $this->get_attribute( $script_parts['open'], 'src' ) ) {
				$this->debug_message( 'src attr found, adding defer to open tag' );
				$deferred_tag     = rtrim( $script_parts['open'], ' >' ) . ' defer>';
				$deferred_element = str_replace( $script_parts['open'], $deferred_tag, $element );
			} elseif ( ! empty( trim( $script_parts['content'] ) ) ) {
				$this->debug_message( 'possibly deferring inline script' );
				// Process inline script tags.
				$process = true;
				foreach ( $this->inline_exclusions as $inline_exclusion ) {
					if ( false !== strpos( $script_parts['content'], $inline_exclusion ) ) {
						$this->debug_message( "stock defer exclusion $inline_exclusion applied" );
						$process = false;
					}
				}
				if ( in_array( 'inline-scripts', $this->user_exclusions, true ) ) {
					$this->debug_message( 'user exclusion (inline-scripts) applied' );
					$process = false;
				} elseif ( preg_match( '#^\s*?(?:/\*\s<!\[CDATA\[\s\*/\s)?(?:var\s*?.*?;\n?)+?\s*(?:/\*\s]]>\s\*/)?$#', $script_parts['content'] ) ) {
					$this->debug_message( 'var= exclusion applied' );
					$process = false;
				}
				if ( $process ) {
					$this->debug_message( 'deferring inline JS' );
					$open_tag = $script_parts['open'];
					$this->set_attribute(
						$open_tag,
						'src',
						'data:text/javascript,' . $this->encode_data_uri( $script_parts['content'] )
					);
					$deferred_element = rtrim( $open_tag, ' >' ) . ' defer></script>';
				}
			}
		}
		if ( ! empty( $deferred_element ) && $deferred_element !== $element ) {
			$this->debug_message( trim( $deferred_element ) );
			return $deferred_element;
		}
		$this->debug_message( 'unchanged' );
		return $element;
	}
}

<?php
/**
 * Implements basic and common utility functions for all sub-classes.
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
 * HTML element and attribute parsing, replacing, etc.
 */
class Base {

	/**
	 * Content directory (URL) for the plugin to use.
	 *
	 * @access protected
	 * @var string $content_url
	 */
	protected $content_url = WP_CONTENT_URL . 'swis/';

	/**
	 * Content directory (path) for the plugin to use.
	 *
	 * @access protected
	 * @var string $content_dir
	 */
	protected $content_dir = WP_CONTENT_DIR . '/swis/';

	/**
	 * The debug buffer for the plugin.
	 *
	 * @access public
	 * @var string $debug
	 */
	public static $debug = '';

	/**
	 * Temporarily enable debug mode, used to collect system info on specific pages.
	 *
	 * @access public
	 * @var bool $temp_debug
	 */
	public static $temp_debug = false;

	/**
	 * Site (URL) for the plugin to use.
	 *
	 * @access public
	 * @var string $site_url
	 */
	public $site_url = '';

	/**
	 * Home (URL) for the plugin to use.
	 *
	 * @access public
	 * @var string $home_url
	 */
	public $home_url = '';

	/**
	 * Relative home (URL) for the plugin to use.
	 *
	 * @access public
	 * @var string $relative_home_url
	 */
	public $relative_home_url = '';

	/**
	 * Upload directory (URL).
	 *
	 * @access public
	 * @var string $upload_url
	 */
	public $upload_url = '';

	/**
	 * Upload domain.
	 *
	 * @access public
	 * @var string $upload_domain
	 */
	public $upload_domain = '';

	/**
	 * Plugin version for the plugin.
	 *
	 * @access protected
	 * @var float $version
	 */
	protected $version = 0;

	/**
	 * Prefix to be used by plugin in option and hook names.
	 *
	 * @access protected
	 * @var string $prefix
	 */
	protected $prefix = 'swis_';

	/**
	 * Is media offload to S3 (or similar)?
	 *
	 * @access public
	 * @var bool $s3_active
	 */
	public $s3_active = false;

	/**
	 * The S3 object prefix.
	 *
	 * @access public
	 * @var bool $s3_object_prefix
	 */
	public $s3_object_prefix = '';

	/**
	 * Do offloaded URLs contain versioning?
	 *
	 * @access public
	 * @var bool $s3_object_version
	 */
	public $s3_object_version = false;

	/**
	 * Set class properties for children.
	 */
	public function __construct() {
		$this->home_url          = trailingslashit( get_site_url() );
		$this->relative_home_url = preg_replace( '/https?:/', '', $this->home_url );
		$this->content_url       = content_url( 'swis/' );
		$this->version           = SWIS_PLUGIN_VERSION;
		if ( defined( 'SWIS_CONTENT_DIR' ) ) {
			$this->content_dir = SWIS_CONTENT_DIR;
			$this->content_url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $this->content_dir );
		}
	}

	/**
	 * Get the path to the current debug log, if one exists. Otherwise, generate a new filename.
	 *
	 * @return string The full path to the debug log.
	 */
	public function debug_log_path() {
		if ( is_dir( $this->content_dir ) ) {
			$potential_logs = \scandir( $this->content_dir );
			if ( $this->is_iterable( $potential_logs ) ) {
				foreach ( $potential_logs as $potential_log ) {
					if ( $this->str_ends_with( $potential_log, '.log' ) && false !== strpos( $potential_log, strtolower( __NAMESPACE__ ) . '-debug-' ) && is_file( $this->content_dir . $potential_log ) ) {
						return $this->content_dir . $potential_log;
					}
				}
			}
		}
		return $this->content_dir . strtolower( __NAMESPACE__ ) . '-debug-' . uniqid() . '.log';
	}

	/**
	 * Saves the in-memory debug log to a logfile in the plugin folder.
	 */
	public function debug_log() {
		$debug_log = $this->debug_log_path();
		if ( ! is_dir( $this->content_dir ) && \is_writable( WP_CONTENT_DIR ) ) {
			\wp_mkdir_p( $this->content_dir );
		}
		$debug_enabled = $this->get_option( $this->prefix . 'debug' );
		if (
			! empty( self::$debug ) &&
			empty( self::$temp_debug ) &&
			$this->get_option( $this->prefix . 'debug' ) &&
			\is_dir( $this->content_dir ) &&
			\is_writable( $this->content_dir )
		) {
			$memory_limit = $this->memory_limit();
			\clearstatcache();
			$timestamp = \gmdate( 'Y-m-d H:i:s' ) . "\n";
			if ( ! \file_exists( $debug_log ) ) {
				\touch( $debug_log );
			} else {
				if ( \filesize( $debug_log ) + 4000000 + \memory_get_usage( true ) > $memory_limit ) {
					\unlink( $debug_log );
					\clearstatcache();
					$debug_log = $this->debug_log_path();
					\touch( $debug_log );
				}
			}
			if ( \filesize( $debug_log ) + strlen( self::$debug ) + 4000000 + \memory_get_usage( true ) <= $memory_limit && is_writable( $debug_log ) ) {
				self::$debug = \str_replace( '<br>', "\n", self::$debug );
				\file_put_contents( $debug_log, $timestamp . self::$debug, FILE_APPEND );
			}
		}
		self::$debug = '';
	}

	/**
	 * Adds information to the in-memory debug log.
	 *
	 * @param string $message Debug information to add to the log.
	 */
	public function debug_message( $message ) {
		if ( ! \is_string( $message ) && ! \is_int( $message ) && ! \is_float( $message ) ) {
			return;
		}
		$message = "$message";
		if ( \defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::debug( $message );
			return;
		}
		if ( self::$temp_debug || $this->get_option( $this->prefix . 'debug' ) ) {
			$memory_limit = $this->memory_limit();
			if ( \strlen( $message ) + 4000000 + \memory_get_usage( true ) <= $memory_limit ) {
				$message      = \str_replace( "\n\n\n", '<br>', $message );
				$message      = \str_replace( "\n\n", '<br>', $message );
				$message      = \str_replace( "\n", '<br>', $message );
				self::$debug .= "$message<br>";
			} else {
				self::$debug = "not logging message, memory limit is $memory_limit";
			}
		}
	}

	/**
	 * Checks if a function is disabled or does not exist.
	 *
	 * @param string $function_name The name of a function to test.
	 * @param bool   $debug Whether to output debugging.
	 * @return bool True if the function is available, False if not.
	 */
	public function function_exists( $function_name, $debug = false ) {
		if ( function_exists( 'ini_get' ) ) {
			$disabled = @ini_get( 'disable_functions' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( $debug ) {
				$this->debug_message( "disable_functions: $disabled" );
			}
		}
		if ( extension_loaded( 'suhosin' ) && function_exists( 'ini_get' ) ) {
			$suhosin_disabled = @ini_get( 'suhosin.executor.func.blacklist' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( $debug ) {
				$this->debug_message( "suhosin_blacklist: $suhosin_disabled" );
			}
			if ( ! empty( $suhosin_disabled ) ) {
				$suhosin_disabled = explode( ',', $suhosin_disabled );
				$suhosin_disabled = array_map( 'trim', $suhosin_disabled );
				$suhosin_disabled = array_map( 'strtolower', $suhosin_disabled );
				if ( function_exists( $function_name ) && ! in_array( $function_name, $suhosin_disabled, true ) ) {
					return true;
				}
				return false;
			}
		}
		return function_exists( $function_name );
	}

	/**
	 * Check for GD support.
	 *
	 * @return bool Debug True if GD support detected.
	 */
	public function gd_support() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( function_exists( 'gd_info' ) ) {
			$gd_support = gd_info();
			$this->debug_message( 'GD found, supports:' );
			if ( $this->is_iterable( $gd_support ) ) {
				foreach ( $gd_support as $supports => $supported ) {
					$this->debug_message( "$supports: $supported" );
				}
				if ( ( ! empty( $gd_support['JPEG Support'] ) || ! empty( $gd_support['JPG Support'] ) ) && ! empty( $gd_support['PNG Support'] ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Run sanity checks on license key provided by user.
	 *
	 * @param string $input The license key as entered.
	 * @return string The license key after validation.
	 */
	public function sanitize_license( $input ) {
		if ( empty( $input ) ) {
			return false;
		}
		$input = trim( $input );
		if ( preg_match( '/^[a-zA-Z0-9]+$/', $input ) ) {
			return $input;
		}
		return false;
	}

	/**
	 * Sanitize the CDN domain provided.
	 *
	 * @param string $domain A CDN domain name.
	 * @return string The sanitized domain name.
	 */
	public function sanitize_cdn_domain( $domain ) {
		$domain = sanitize_text_field( $domain );
		$domain = preg_replace( '#https?://#', '', $domain );
		$domain = trim( $domain, '/' );
		return $domain;
	}

	/**
	 * Sanitize the folders/patterns to exclude from any given option.
	 *
	 * @param string $input A list of paths/URL-matching strings, from a textarea.
	 * @param bool   $strip_wildcards True by default, so that wildcards will be implied.
	 * @return array The sanitized list of exclusions.
	 */
	public function sanitize_textarea_exclusions( $input, $strip_wildcards = true ) {
		$this->debug_message( '<b>' . __FUNCTION__ . '()</b>' );
		if ( empty( $input ) ) {
			return '';
		}
		$path_array = array();
		if ( is_array( $input ) ) {
			$paths = $input;
		} elseif ( is_string( $input ) ) {
			$paths = explode( "\n", $input );
		}
		if ( $this->is_iterable( $paths ) ) {
			$i = 0;
			foreach ( $paths as $path ) {
				++$i;
				$this->debug_message( "validating textarea exclusion: $path" );
				if ( $strip_wildcards ) {
					$path = trim( sanitize_text_field( $path ), "* \t\n\r\0\x0B" );
				} else {
					$path = trim( sanitize_text_field( $path ), " \t\n\r\0\x0B" );
				}
				if ( ! empty( $path ) ) {
					$path_array[] = $path;
				}
			}
		}
		return $path_array;
	}

	/**
	 * Retrieve option: use override/constant setting if defined, otherwise use 'blog' setting or $default.
	 *
	 * Overrides are only available for integer and boolean options.
	 *
	 * @param string $option_name The name of the option to retrieve.
	 * @param mixed  $default_value The default to use if not found/set, defaults to false, but not currently used.
	 * @param bool   $single Use single-site setting regardless of multisite activation. Default is off/false.
	 * @return mixed The value of the option.
	 */
	public function get_option( $option_name, $default_value = false, $single = false ) {
		if ( 0 === strpos( $option_name, 'easyio_' ) && function_exists( 'easyio_get_option' ) ) {
			return easyio_get_option( $option_name );
		}
		if ( 0 === strpos( $option_name, 'ewww_image_optimizer_' ) && function_exists( 'ewww_image_optimizer_get_option' ) ) {
			return ewww_image_optimizer_get_option( $option_name, $default_value, $single );
		}
		if ( 0 === strpos( $option_name, 'swis_' ) ) {
			$option_name = str_replace( 'swis_', '', $option_name );
		}
		$constant_name = strtoupper( $this->prefix . $option_name );
		if ( defined( $constant_name ) && ( is_int( constant( $constant_name ) ) || is_bool( constant( $constant_name ) ) ) ) {
			return constant( $constant_name );
		}
		if ( 'cdn_domain' === $option_name && \defined( $constant_name ) ) {
			return $this->sanitize_cdn_domain( \constant( $constant_name ) );
		}
		$options = get_option( 'swis_performance' );
		if ( empty( $options ) || ! is_array( $options ) ) {
			return $default_value;
		}
		if ( isset( $options[ $option_name ] ) ) {
			return $options[ $option_name ];
		}
		return $default_value;
	}

	/**
	 * Set an option: use 'site' setting if plugin is network activated, otherwise use 'blog' setting.
	 *
	 * @param string $option_name The name of the option to save.
	 * @param mixed  $option_value The value to save for the option.
	 * @return bool True if the operation was successful.
	 */
	public function set_option( $option_name, $option_value ) {
		$constant_name = strtoupper( $this->prefix . $option_name );
		if ( defined( $constant_name ) && ( is_int( constant( $constant_name ) ) || is_bool( constant( $constant_name ) ) ) ) {
			return false;
		}
		$options = get_option( 'swis_performance' );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$options[ $option_name ] = $option_value;
		return update_option( 'swis_performance', $options, true );
	}

	/**
	 * Retrieves plugin header data.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @return array Plugin data. Values will be empty if not supplied by the plugin.
	 */
	public function get_plugin_data( $plugin_file ) {
		$default_headers = array(
			'Name'      => 'Plugin Name',
			'PluginURI' => 'Plugin URI',
			'Version'   => 'Version',
			'AuthorURI' => 'Author URI',
		);

		$plugin_data = \get_file_data( $plugin_file, $default_headers, 'plugin' );

		$plugin_data['Title'] = '';
		if ( ! empty( $plugin_data['Name'] ) ) {
			$plugin_data['Title'] = $plugin_data['Name'];
		}
		return $plugin_data;
	}

	/**
	 * See if background mode is allowed/enabled.
	 *
	 * @return bool True if it is, false if it ain't.
	 */
	public function background_mode_enabled() {
		if ( defined( 'SWIS_DISABLE_ASYNC' ) && SWIS_DISABLE_ASYNC ) {
			$this->debug_message( 'background mode disabled by admin' );
			return false;
		}
		if ( $this->detect_wpsf_location_lock() ) {
			$this->debug_message( 'background mode disabled by shield IP location lock' );
			return false;
		}
		return (bool) $this->get_option( 'background_processing' );
	}

	/**
	 * Check to see if Easy IO is active.
	 *
	 * @return bool True if Easy IO is active.
	 */
	public function is_easyio_active() {
		if ( function_exists( '\ewww_image_optimizer_easy_active' ) && \ewww_image_optimizer_easy_active() ) {
			return true;
		}
		if ( \get_option( 'easyio_exactdn' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check to see if Shield's location lock option is enabled.
	 *
	 * @return bool True if the IP location lock is enabled.
	 */
	public function detect_wpsf_location_lock() {
		if ( function_exists( '\icwp_wpsf_init' ) ) {
			$this->debug_message( 'Shield Security detected' );
			$shield_user_man = get_option( 'icwp_wpsf_user_management_options' );
			if ( ! isset( $shield_user_man['session_lock_location'] ) ) {
				$this->debug_message( 'Shield security lock location setting does not exist, weird?' );
			}
			if ( ! empty( $shield_user_man['session_lock_location'] ) && 'Y' === $shield_user_man['session_lock_location'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a Jetpack Boost option is enabled.
	 *
	 * @param string $option_name The Jetpack Boost option to check.
	 * @return bool True if the option is enabled, false otherwise.
	 */
	public function is_jetpack_boost_option_enabled( $option_name ) {
		if ( class_exists( '\Automattic\Jetpack_Boost\Jetpack_Boost' ) ) {
			if ( \get_option( 'jetpack_boost_status_' . $option_name ) ) {
				$this->debug_message( "JB $option_name enabled" );
				return true;
			}
		}
		return false;
	}

	/**
	 * Implode a multi-dimensional array without throwing errors. Arguments can be reverse order, same as implode().
	 *
	 * @param string $delimiter The character to put between the array items (the glue).
	 * @param array  $data The array to output with the glue.
	 * @return string The array values, separated by the delimiter.
	 */
	public function implode( $delimiter, $data = '' ) {
		if ( is_array( $delimiter ) ) {
			$temp_data = $delimiter;
			$delimiter = $data;
			$data      = $temp_data;
		}
		if ( is_array( $delimiter ) ) {
			return '';
		}
		$output = '';
		foreach ( $data as $value ) {
			if ( is_string( $value ) || is_numeric( $value ) ) {
				$output .= $value . $delimiter;
			} elseif ( is_bool( $value ) ) {
				$output .= ( $value ? 'true' : 'false' ) . $delimiter;
			} elseif ( is_array( $value ) ) {
				$output .= 'Array,';
			}
		}
		return rtrim( $output, ',' );
	}

	/**
	 * Checks to see if test mode is enabled, and whether the current user is a logged-in SWIS admin.
	 *
	 * @return bool True if test mode should be effective and prevent optimizations for guest users. False otherwise.
	 */
	public function test_mode_active() {
		if (
			$this->get_option( 'test_mode' ) &&
			( ! is_user_logged_in() || ! current_user_can( apply_filters( 'swis_performance_admin_permissions', 'manage_options' ) ) )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Checks to see if the current page being output is an AMP page.
	 *
	 * @return bool True for an AMP endpoint, false otherwise.
	 */
	public function is_amp() {
		// Just return false if we can't properly check yet.
		if ( ! did_action( 'parse_request' ) ) {
			$this->debug_message( 'parse_request not run yet' );
			return false;
		}
		if ( ! did_action( 'parse_query' ) ) {
			$this->debug_message( 'parse_query not run yet' );
			return false;
		}
		if ( ! did_action( 'wp' ) ) {
			$this->debug_message( 'wp not run yet' );
			return false;
		}

		if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
			$this->debug_message( 'amp_is_request true' );
			return true;
		}
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			$this->debug_message( 'is_amp_endpoint true' );
			return true;
		}
		if ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) {
			$this->debug_message( 'ampforwp_is_amp_endpoint true' );
			return true;
		}
		return false;
	}

	/**
	 * Checks to see if the current buffer/output is a JSON-encoded string.
	 *
	 * Specifically, we are looking for JSON objects/strings, not just ANY JSON value.
	 * Thus, the check is rather "loose", only looking for {} or [] at the start/end.
	 *
	 * @param string $buffer The content to check for JSON.
	 * @return bool True for JSON, false for everything else.
	 */
	public function is_json( $buffer ) {
		if ( '{' === substr( $buffer, 0, 1 ) && '}' === substr( $buffer, -1 ) ) {
			return true;
		}
		if ( '[' === substr( $buffer, 0, 1 ) && ']' === substr( $buffer, -1 ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Make sure this is really and truly a "front-end request", excluding page builders and such.
	 *
	 * @return bool True for front-end requests, false for admin/builder requests.
	 */
	public function is_frontend() {
		if ( is_admin() ) {
			return false;
		}
		$uri = add_query_arg( '', '' );
		if (
			\strpos( $uri, 'bricks=run' ) !== false ||
			\strpos( $uri, '?brizy-edit' ) !== false ||
			\strpos( $uri, '&builder=true' ) !== false ||
			\strpos( $uri, 'cornerstone=' ) !== false ||
			\strpos( $uri, 'cornerstone-endpoint' ) !== false ||
			\strpos( $uri, 'cornerstone/edit/' ) !== false ||
			\strpos( $uri, 'ct_builder=' ) !== false ||
			\strpos( $uri, 'ct_render_shortcode=' ) !== false ||
			\strpos( $uri, 'action=oxy_render' ) !== false ||
			\did_action( 'cornerstone_boot_app' ) || \did_action( 'cs_before_preview_frame' ) ||
			\did_action( 'cs_element_rendering' ) || \did_action( 'cornerstone_before_boot_app' ) || \apply_filters( 'cs_is_preview_render', false ) ||
			\is_customize_preview() ||
			'/print/' === \substr( $uri, -7 ) ||
			\strpos( $uri, 'elementor-preview=' ) !== false ||
			\strpos( $uri, 'et_fb=' ) !== false ||
			\strpos( $uri, 'fb-edit=' ) !== false ||
			\strpos( $uri, '?fl_builder' ) !== false ||
			\strpos( $uri, 'is-editor-iframe=' ) !== false ||
			\strpos( $uri, 'tatsu=' ) !== false ||
			\strpos( $uri, 'tve=true' ) !== false ||
			\strpos( $uri, 'tge=true' ) !== false ||
			( ! empty( $_POST['action'] ) && 'tatsu_get_concepts' === \sanitize_text_field( \wp_unslash( $_POST['action'] ) ) ) || // phpcs:ignore WordPress.Security.NonceVerification
			\strpos( $uri, 'vc_editable=' ) !== false ||
			\strpos( $uri, 'wp-login.php' ) !== false ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST )
		) {
			return false;
		}
		global $wp_query;
		if ( isset( $wp_query ) && ( $wp_query instanceof \WP_Query ) ) {
			if (
				is_embed() ||
				is_feed() ||
				is_preview()
			) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if file exists, and that it is local rather than using a protocol like http:// or phar://
	 *
	 * @param string $file The path of the file to check.
	 * @return bool True if the file exists and is local, false otherwise.
	 */
	public function is_file( $file ) {
		if ( empty( $file ) ) {
			return false;
		}
		if ( false !== strpos( $file, '://' ) ) {
			return false;
		}
		if ( false !== strpos( $file, 'phar://' ) ) {
			return false;
		}
		return is_file( $file );
	}

	/**
	 * Make sure an array/object can be parsed by a foreach().
	 *
	 * @since 0.1
	 * @param mixed $value A variable to test for iteration ability.
	 * @return bool True if the variable is iterable.
	 */
	public function is_iterable( $value ) {
		return ! empty( $value ) && ( is_array( $value ) || $value instanceof Traversable );
	}

	/**
	 * Performs a case-sensitive check indicating if
	 * the haystack ends with needle.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the `$haystack`.
	 * @return bool True if `$haystack` ends with `$needle`, otherwise false.
	 */
	public function str_ends_with( $haystack, $needle ) {
		if ( '' === $haystack && '' !== $needle ) {
			return false;
		}

		$len = strlen( $needle );

		return 0 === substr_compare( $haystack, $needle, -$len, $len );
	}

	/**
	 * Trims the given 'needle' from the end of the 'haystack'.
	 *
	 * @param string $haystack The string to be modified if it contains needle.
	 * @param string $needle The string to remove if it is at the end of the haystack.
	 * @return string The haystack with needle removed from the end.
	 */
	public function remove_from_end( $haystack, $needle ) {
		$needle_length = strlen( $needle );
		if ( substr( $haystack, -$needle_length ) === $needle ) {
			return substr( $haystack, 0, -$needle_length );
		}
		return $haystack;
	}

	/**
	 * Perform basic sanitation of CSS.
	 *
	 * @param string $css The raw CSS code.
	 * @return string The sanitized code or an empty string.
	 */
	public function sanitize_css( $css ) {
		$css = str_replace( '&gt;', '>', $css );
		$css = \trim( \wp_strip_all_tags( $css ) );
		$css = str_replace( '&gt;', '>', $css );
		if ( empty( $css ) ) {
			return '';
		}

		if ( preg_match( '#</?\w+#', $css ) ) {
			return '';
		}

		$blacklist = array( '#!/', 'function(', '<script', '<?php' );
		foreach ( $blacklist as $blackmark ) {
			if ( false !== strpos( $css, $blackmark ) ) {
				$this->debug_message( 'CSS contained unsafe content' );
				return '';
			}
		}

		$needlist = array( '{', '}', ':' );
		foreach ( $needlist as $needed ) {
			if ( false === strpos( $css, $needed ) ) {
				$this->debug_message( "missing $needed in CSS, invalid" );
				return '';
			}
		}
		$minifier = new Minify\CSS( $css );
		$css      = $minifier->minify();
		return $css;
	}

	/**
	 * Check if a user can clear the cache.
	 *
	 * @return bool True if they can, false if they can't.
	 */
	protected function user_can_clear_cache() {
		// Check user permissions.
		$permissions = apply_filters( 'swis_performance_admin_permissions', 'manage_options' );
		if ( apply_filters( 'user_can_clear_cache', current_user_can( $permissions ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the cache cleared transient name used for the clear notice.
	 *
	 * @return string The transient name based on the user ID.
	 */
	protected function get_cache_cleared_transient_name() {
		return 'swis_cache_cleared_' . get_current_user_id();
	}

	/**
	 * Clear the contents of a given directory.
	 *
	 * @param string $dir The directory path.
	 */
	protected function clear_dir( $dir ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$dir = \untrailingslashit( $dir );

		if ( empty( $dir ) ) {
			$this->debug_message( 'give me something man!' );
			return;
		}

		$this->debug_message( "clearing $dir" );

		if ( ! is_dir( $dir ) ) {
			$this->debug_message( 'not a dir' );
			return;
		}
		if ( ! is_readable( $dir ) ) {
			$this->debug_message( 'not readable' );
			return;
		}
		if ( ! is_writable( $dir ) ) {
			$this->debug_message( 'not writable' );
			return;
		}

		$dir_objects = $this->get_dir_objects( $dir );

		foreach ( $dir_objects as $dir_object ) {
			// Create the full path.
			$dir_object = \trailingslashit( $dir ) . $dir_object;

			if ( is_dir( $dir_object ) ) {
				$this->clear_dir( $dir_object );
			} elseif ( $this->is_file( $dir_object ) && is_writable( $dir_object ) ) {
				unlink( $dir_object );
			}
		}

		// Doing this to make sure the directory is empty before we try and delete it.
		clearstatcache();
		$dir_objects = $this->get_dir_objects( $dir );

		// If the directory is empty now. No need to do error suppression here.
		if ( empty( $dir_objects ) ) {
			rmdir( $dir );
		}
		clearstatcache();
	}

	/**
	 * Get the number of files in a cache folder, recursively.
	 *
	 * @param string $dir Path of a cache folder.
	 * @param string $file Only count files matching this pattern.
	 * @return int Number of files found.
	 */
	public function get_cache_count( $dir = null, $file = '' ) {
		$cache_count = 0;

		// Get a list of the files in a folder.
		if ( is_dir( $dir ) && is_readable( $dir ) ) {
			$dir_objects = $this->get_dir_objects( $dir );
		}

		if ( empty( $dir_objects ) ) {
			return $cache_count;
		}

		foreach ( $dir_objects as $dir_object ) {
			// Create the full path.
			$dir_object = \trailingslashit( $dir ) . $dir_object;

			if ( is_dir( $dir_object ) ) {
				$cache_count += $this->get_cache_count( $dir_object, $file );
			} elseif ( is_file( $dir_object ) && is_readable( $dir_object ) ) {
				if ( ! empty( $file ) && false === strpos( $dir_object, $file ) ) {
					continue;
				}
				++$cache_count;
			}
		}

		return $cache_count;
	}

	/**
	 * Get all the files in a directory.
	 *
	 * @param string $dir The directory's path.
	 * @return array A list of files found.
	 */
	public function get_dir_objects( $dir ) {
		$this->debug_message( __METHOD__ );
		if ( ! is_readable( $dir ) ) {
			return array();
		}
		// Scan the directory.
		$dir_objects = @scandir( $dir );

		if ( is_array( $dir_objects ) ) {
			$dir_objects = array_diff( $dir_objects, array( '..', '.' ) );
		} else {
			$dir_objects = array();
		}
		return $dir_objects;
	}

	/**
	 * Get the absolute path from a URL.
	 *
	 * @param string $url The resource URL to translate to a local path.
	 * @return string|bool The file path, if found, or false.
	 */
	public function get_local_path( $url ) {
		// Need to strip query strings.
		$url_parts = explode( '?', $url );
		if ( ! is_array( $url_parts ) || empty( $url_parts[0] ) ) {
			return false;
		}
		$url  = $url_parts[0];
		$file = $this->url_to_path_exists( $url );
		if ( ! $file ) {
			$local_domain = $this->parse_url( $this->content_url, PHP_URL_HOST );
			if ( false === strpos( $url, $local_domain ) ) {
				$remote_domain      = $this->parse_url( $url, PHP_URL_HOST );
				$possible_local_url = str_replace( $remote_domain, $local_domain, $url );
				$file               = $this->url_to_path_exists( $possible_local_url );
			}
		}
		return $file;
	}

	/**
	 * Build a path to cache the file on disk after minification.
	 *
	 * @param string $file The absolute path to the original file.
	 * @param string $mod_time The modification time of the file.
	 * @param string $default_extension The file extension to use if one does not exist in $file.
	 * @return string The location to store the minified file in the cache.
	 */
	public function get_cache_path( $file, $mod_time, $default_extension = 'css' ) {
		if ( 0 === strpos( $file, WP_CONTENT_DIR ) ) {
			$path_to_keep = str_replace( WP_CONTENT_DIR, '', $file );
		} elseif ( 0 === strpos( $file, ABSPATH ) ) {
			$path_to_keep = str_replace( ABSPATH, '', $file );
		} else {
			return false;
		}
		$extension = pathinfo( $file, PATHINFO_EXTENSION );
		if ( $extension && $mod_time ) {
			$path_to_keep = preg_replace( "/\.$extension/", "-$mod_time.min.$extension", $path_to_keep );
		} elseif ( $mod_time ) {
			$path_to_keep = $path_to_keep . "-$mod_time.min." . $default_extension;
		}
		$cache_file = $this->cache_dir . ltrim( $path_to_keep, '/\\' );
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
	 * @param string $file The absolute path to the original file.
	 * @param string $mod_time The modification time of the file.
	 * @param string $query_string The query string from the original URL. If none, defaults to null.
	 * @param string $default_extension The file extension to use if one does not exist in $file.
	 * @return string The URL of the minified file in the cache.
	 */
	public function get_cache_url( $file, $mod_time, $query_string = null, $default_extension = 'css' ) {
		if ( 0 === strpos( $file, WP_CONTENT_DIR ) ) {
			$path_to_keep = str_replace( WP_CONTENT_DIR, '', $file );
		} elseif ( 0 === strpos( $file, ABSPATH ) ) {
			$path_to_keep = str_replace( ABSPATH, '', $file );
		} else {
			return false;
		}
		$extension = pathinfo( $file, PATHINFO_EXTENSION );
		if ( $extension && $mod_time ) {
			$path_to_keep = preg_replace( "/\.$extension/", "-$mod_time.min.$extension", $path_to_keep );
		} elseif ( $mod_time ) {
			$path_to_keep = $path_to_keep . "-$mod_time.min." . $default_extension;
		}
		$cache_url = $this->cache_dir_url . ltrim( $path_to_keep, '/\\' ) . ( $query_string ? "?$query_string" : '' );
		return $cache_url;
	}

	/**
	 * Display a notice that the plugin requirements check failed.
	 */
	public function requirements_failed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		// Permission check, can't do much without a writable cache directory.
		echo '<div class="notice notice-warning"><p>' .
			sprintf(
				/* translators: 1: SWIS Performance 2: 755 3: wp-content/swis/ 4: file permissions */
				esc_html__( '%1$s requires write permissions (%2$s) in the %3$s directory. Please change the %4$s or set SWIS_CONTENT_DIR to a writable location.', 'swis-performance' ),
				'<strong>SWIS Performance</strong>',
				'<code>755</code>',
				'<code>wp-content/swis/</code>',
				'<a href="https://wordpress.org/support/article/changing-file-permissions/" target="_blank">' . esc_html__( 'file permissions', 'swis-performance' ) . '</a>'
			) .
		'</p></div>';
	}

	/**
	 * Displays a help icon linked to the docs.
	 *
	 * @since 1.2.1
	 * @param string $link A link to the documentation.
	 * @param string $hsid The HelpScout ID for the docs article. Optional.
	 */
	public function help_link( $link, $hsid = '' ) {
		$beacon_attr = '';
		$link_class  = 'swis-help-icon';
		if ( strpos( $hsid, ',' ) ) {
			$beacon_attr = 'data-beacon-articles';
			$link_class  = 'swis-help-beacon-multi';
		} elseif ( $hsid ) {
			$beacon_attr = 'data-beacon-article';
			$link_class  = 'swis-help-beacon-single';
		}
		if ( empty( $hsid ) ) {
			echo '<a class="swis-help-icon swis-help-external" title="' . esc_attr__( 'Help', 'swis-performance' ) . '" href="' . esc_url( $link ) . '" target="_blank">' .
				'<span class="dashicons dashicons-insert"></span>' .
				'</a>';
			return;
		}
		echo '<a class="swis-help-icon ' . esc_attr( $link_class ) . '" title="' . esc_attr__( 'Help', 'swis-performance' ) . '" href="' . esc_url( $link ) . '" target="_blank" ' . esc_attr( $beacon_attr ) . '="' . esc_attr( $hsid ) . '">' .
			'<span class="dashicons dashicons-insert"></span>' .
			'</a>';
	}

	/**
	 * Finds the current PHP memory limit or a reasonable default.
	 *
	 * @return int The memory limit in bytes.
	 */
	public function memory_limit() {
		if ( defined( 'EIO_MEMORY_LIMIT' ) && EIO_MEMORY_LIMIT ) {
			$memory_limit = EIO_MEMORY_LIMIT;
		} elseif ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			if ( ! defined( 'EIO_MEMORY_LIMIT' ) ) {
				// Conservative default, current usage + 16M.
				$current_memory = memory_get_usage( true );
				$memory_limit   = round( $current_memory / ( 1024 * 1024 ) ) + 16;
				define( 'EIO_MEMORY_LIMIT', $memory_limit );
			}
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::debug( "memory limit is set at $memory_limit" );
		}
		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}
		if ( stripos( $memory_limit, 'g' ) ) {
			$memory_limit = intval( $memory_limit ) * 1024 * 1024 * 1024;
		} else {
			$memory_limit = intval( $memory_limit ) * 1024 * 1024;
		}
		return $memory_limit;
	}

	/**
	 * Clear output buffers without throwing a fit.
	 */
	public function ob_clean() {
		if ( ob_get_length() ) {
			ob_end_clean();
		}
	}

	/**
	 * Converts a URL to a file-system path and checks if the resulting path exists.
	 *
	 * @param string $url The URL to mangle.
	 * @param string $extension An optional extension to append during is_file().
	 * @return bool|string The path if a local file exists correlating to the URL, false otherwise.
	 */
	public function url_to_path_exists( $url, $extension = '' ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( 0 === strpos( $url, WP_CONTENT_URL ) ) {
			$path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $url );
		} elseif ( 0 === strpos( $url, $this->relative_home_url ) ) {
			$path = str_replace( $this->relative_home_url, ABSPATH, $url );
		} elseif ( 0 === strpos( $url, $this->home_url ) ) {
			$path = str_replace( $this->home_url, ABSPATH, $url );
		} else {
			$this->debug_message( 'not a valid local asset' );
			return false;
		}
		$path_parts = explode( '?', $path );
		if ( $this->is_file( $path_parts[0] . $extension ) ) {
			$this->debug_message( 'local file found' );
			return $path_parts[0];
		}
		return false;
	}

	/**
	 * A wrapper for PHP's parse_url, prepending assumed scheme for network path
	 * URLs. PHP versions 5.4.6 and earlier do not correctly parse without scheme.
	 *
	 * @param string  $url The URL to parse.
	 * @param integer $component Retrieve specific URL component.
	 * @return mixed Result of parse_url.
	 */
	public function parse_url( $url, $component = -1 ) {
		if ( 0 === strpos( $url, '//' ) ) {
			$url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
		}
		if ( false === strpos( $url, 'http' ) && '/' !== substr( $url, 0, 1 ) ) {
			$url = ( is_ssl() ? 'https://' : 'http://' ) . $url;
		}
		// Because encoded ampersands in the filename break things.
		$url = str_replace( '&#038;', '&', $url );
		return parse_url( $url, $component );
	}

	/**
	 * Get the shortest version of the content URL.
	 *
	 * @return string The URL where the content lives.
	 */
	public function content_url() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( $this->site_url ) {
			return $this->site_url;
		}
		$this->site_url = get_home_url();
		if ( class_exists( 'Amazon_S3_And_CloudFront' ) ) {
			global $as3cf;
			$s3_scheme = $as3cf->get_url_scheme();
			$s3_region = $as3cf->get_setting( 'region' );
			$s3_bucket = $as3cf->get_setting( 'bucket' );
			if ( is_wp_error( $s3_region ) ) {
				$s3_region = '';
			}
			$s3_domain = '';
			if ( ! empty( $s3_bucket ) && ! is_wp_error( $s3_bucket ) && method_exists( $as3cf, 'get_provider' ) ) {
				$s3_domain = $as3cf->get_provider()->get_url_domain( $s3_bucket, $s3_region, null, array(), true );
			} elseif ( ! empty( $s3_bucket ) && ! is_wp_error( $s3_bucket ) && method_exists( $as3cf, 'get_storage_provider' ) ) {
				$s3_domain = $as3cf->get_storage_provider()->get_url_domain( $s3_bucket, $s3_region );
			}
			if ( ! empty( $s3_domain ) && $as3cf->get_setting( 'serve-from-s3' ) ) {
				$this->s3_active = true;
				$this->debug_message( "found S3 domain of $s3_domain with bucket $s3_bucket and region $s3_region" );
			}
		}

		if ( $this->s3_active ) {
			$this->site_url = defined( 'EXACTDN_LOCAL_DOMAIN' ) && EXACTDN_LOCAL_DOMAIN ? EXACTDN_LOCAL_DOMAIN : $s3_scheme . '://' . $s3_domain;
		} else {
			// Normally, we use this one, as it will be shorter for sub-directory installs.
			$home_url    = get_home_url();
			$site_url    = get_site_url();
			$upload_dir  = wp_get_upload_dir();
			$home_domain = $this->parse_url( $home_url, PHP_URL_HOST );
			$site_domain = $this->parse_url( $site_url, PHP_URL_HOST );
			// If the home domain does not match the upload url, and the site domain does match...
			if ( false === strpos( $upload_dir['baseurl'], $home_domain ) && false !== strpos( $upload_dir['baseurl'], $site_domain ) ) {
				$this->debug_message( "using WP URL (via get_site_url) with $site_domain rather than $home_domain" );
				$home_url = $site_url;
			}
			$this->site_url = defined( 'EXACTDN_LOCAL_DOMAIN' ) && EXACTDN_LOCAL_DOMAIN ? EXACTDN_LOCAL_DOMAIN : $home_url;
		}
		return $this->site_url;
	}
}

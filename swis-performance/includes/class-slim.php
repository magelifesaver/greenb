<?php
/**
 * Class and methods to eliminate unused JS/CSS.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enables plugin to Lazy Load images.
 */
final class Slim extends Page_Parser {

	/**
	 * The list of (properly registered) JS/CSS assets and associated information.
	 *
	 * @var array
	 */
	private $assets = array();

	/**
	 * The list of (un-registered) JS/CSS assets and associated information.
	 *
	 * @var array
	 */
	private $more_assets = array();

	/**
	 * The list of active plugin data.
	 *
	 * @var array
	 */
	private $active_plugins = array();

	/**
	 * The URI of the currently requested page.
	 *
	 * @var string
	 */
	private $current_page = '';

	/**
	 * The URI of an example/sample page.
	 *
	 * @var string
	 */
	private $sample_page = '';

	/**
	 * The type of the current page.
	 *
	 * @var string
	 */
	private $content_type = '';

	/**
	 * The type of the example/sample page.
	 *
	 * @var string
	 */
	private $sample_type = '';

	/**
	 * A list of registered content types.
	 *
	 * @var array
	 */
	private $content_types = array();

	/**
	 * Store asset dependencies.
	 *
	 * @var array
	 */
	private $deps = array();

	/**
	 * Indicate whether we are processing an AJAX submission from the front-end panel.
	 *
	 * @var bool
	 */
	private $frontend_editor = false;

	/**
	 * A list of user-defined exclusions, populated by validate_rules().
	 *
	 * @access protected
	 * @var array $user_exclusions
	 */
	public $user_exclusions = array();

	/**
	 * A list of user-defined defer exeptions, populated by validate_rules().
	 *
	 * @access protected
	 * @var array $defer_exclusions
	 */
	public $defer_exclusions = array();

	/**
	 * A list of user-defined delay (include) rules, populated by validate_rules().
	 *
	 * @access protected
	 * @var array $delay_inclusions
	 */
	public $delay_inclusions = array();

	/**
	 * A list of assets to output in an admin-ajax.php request.
	 *
	 * @access private
	 * @var array $output_assets
	 */
	private $output_assets = array();

	/**
	 * Whether the plugin should output the assets JSON for an admin-ajax.php request.
	 *
	 * @access private
	 * @var bool $output_assets_json
	 */
	private $output_assets_json = false;

	/**
	 * CSS/JS (handles) that should not ever be excluded.
	 *
	 * @var array $whitelist
	 */
	private $whitelist = array(
		'admin-bar',
		'admin-bar-css',
		'admin-bar-js',
		'swis-performance-slim',
		'swis-performance-slim-js',
	);

	/**
	 * CSS/JS (URL sub-patterns) that should not be touched by Slim.
	 *
	 * @var array $whitelist_urls
	 */
	private $whitelist_urls = array(
		'autoptimize',
		'/assets/slim.js',
		'/bb-plugin/cache/',
		'/bb-plugin/js/build/',
		'brizy/public/editor',
		'/cache/css/',
		'/cache/et/',
		'/cache/js/',
		'/cache/min/',
		'/cache/wpfc',
		'/comet-cache/',
		'cornerstone/assets/',
		'data:text/javascript',
		'debug-bar/js/debug-bar-js.js',
		'debug-bar/js/debug-bar.js',
		'Divi/includes/builder/',
		'/et-cache/',
		'fusion-app',
		'fusion-builder',
		'jch-optimize',
		'/plg_jchoptimize/',
		'/siteground-optimizer-assets/',
		'/spx/assets/',
	);

	/**
	 * Register actions and filters for JS/CSS Slim.
	 */
	public function __construct() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		parent::__construct();

		if ( function_exists( 'is_login' ) && is_login() ) {
			return;
		}

		if ( ! is_admin() ) {
			$permissions = apply_filters( 'swis_performance_admin_permissions', 'manage_options' );
			if ( current_user_can( $permissions ) && ( ! defined( 'SWIS_SLIM_DISABLE_FRONTEND_MENU' ) || ! SWIS_SLIM_DISABLE_FRONTEND_MENU ) ) {
				add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu_item' ), 100 );
				add_action( 'wp_head', array( $this, 'catch_errors_script' ), -999 );
				add_action( 'wp_head', array( $this, 'dash_css' ) );
				add_action( 'wp_head', array( $this, 'find_assets' ), 9999 );
				add_action( 'wp_enqueue_scripts', array( $this, 'frontend_script' ), 9999 );
				add_action( 'wp_footer', array( $this, 'find_assets' ), 9999 );
				add_action( 'wp_footer', array( $this, 'display_assets' ), 10001 );
				add_filter( 'swis_elements_script_tag', array( $this, 'find_more_assets' ), 1, 2 );
				add_filter( 'swis_elements_link_tag', array( $this, 'find_more_assets' ), 1, 2 );
				add_filter( $this->prefix . 'filter_page_output', array( $this, 'output_scripts' ), 99 );
			}
			if ( $this->get_option( 'slim_js_css' ) || $this->get_option( 'optimize_fonts_list' ) ) {
				$this->validate_rules();
				if ( ! $this->test_mode_active() ) {
					add_action( 'template_redirect', array( $this, 'get_content_type' ) );
					add_action( 'template_redirect', array( $this, 'maybe_remove_emoji' ), 11 );
					add_filter( 'script_loader_src', array( $this, 'disable_assets' ), 10, 2 );
					add_filter( 'style_loader_src', array( $this, 'disable_assets' ), 10, 2 );
					add_filter( 'swis_elements_script_tag', array( $this, 'disable_assets' ), 5, 2 );
					add_filter( 'swis_elements_link_tag', array( $this, 'disable_assets' ), 5, 2 );
					add_filter( 'swis_skip_css_defer', array( $this, 'skip_css_defer' ), 10, 3 );
					add_filter( 'swis_skip_js_defer', array( $this, 'skip_js_defer' ), 10, 3 );
					add_filter( 'swis_skip_js_delay', array( $this, 'skip_js_delay' ), 10, 3 );
				}
			}
			if ( ! empty( $_GET['swis_slim_check'] ) && ! is_user_logged_in() ) {  // phpcs:ignore WordPress.Security.NonceVerification
				$this->output_assets_json = true;
				add_action( 'wp_head', array( $this, 'find_assets' ), 9999 );
				add_action( 'wp_footer', array( $this, 'find_assets' ), 9999 );
				add_filter( 'swis_elements_script_tag', array( $this, 'find_more_assets' ), 1, 2 );
				add_filter( 'swis_elements_link_tag', array( $this, 'find_more_assets' ), 1, 2 );
				add_filter( $this->prefix . 'filter_page_output', array( $this, 'output_scripts' ), 99 );
			}
		}
		add_action( 'wp_ajax_swis_slim_rule_edit', array( $this, 'edit_rule' ) );
		add_action( 'wp_ajax_swis_slim_get_assets_html', array( $this, 'get_assets_html' ) );
		add_action( 'wp_ajax_swis_slim_check_assets', array( $this, 'check_assets' ) );
	}

	/**
	 * Adds the Slim menu item to the wp admin bar.
	 *
	 * @param object $wp_admin_bar The WP Admin Bar object, passed by reference.
	 */
	public function add_admin_bar_menu_item( $wp_admin_bar ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$permissions = apply_filters( 'swis_performance_admin_permissions', 'manage_options' );
		if (
			! current_user_can( $permissions ) ||
			! is_admin_bar_showing() ||
			$this->is_amp() ||
			! $this->is_frontend()
		) {
			return;
		}
		if ( defined( 'SWIS_SLIM_DISABLE_FRONTEND_MENU' ) && SWIS_SLIM_DISABLE_FRONTEND_MENU ) {
			return;
		}
		$wp_admin_bar->add_node(
			array(
				'id'     => 'swis-slim',
				'parent' => 'swis',
				'title'  => '<span id="swis-slim-show"><span class="ab-item">' . __( 'Manage JS/CSS', 'swis-performance' ) . '</span></span>',
			)
		);
	}

	/**
	 * Add JS to header to catch all errors for display in Slim front-end assets pane.
	 */
	public function catch_errors_script() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		if ( ! $this->is_frontend() ) {
			return;
		}
		if ( $this->is_amp() ) {
			return;
		}
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		echo '<script data-cfasync="false" data-no-optimize="1" data-no-defer="1" data-no-minify="1">var swisJSErrors=[];window.onerror=function(a,b,c){swisJSErrors.push([a,b,c])}</script>';
	}

	/**
	 * Enqueue JS needed for the front-end assets pane.
	 */
	public function frontend_script() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		if ( $this->is_amp() ) {
			return;
		}
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_script( 'swis-performance-slim', plugins_url( '/assets/slim.js', SWIS_PLUGIN_FILE ), array(), SWIS_PLUGIN_VERSION, true );
		} else {
			wp_enqueue_script( 'swis-performance-slim', plugins_url( '/assets/slim.min.js', SWIS_PLUGIN_FILE ), array(), SWIS_PLUGIN_VERSION, true );
		}
		wp_localize_script(
			'swis-performance-slim',
			'swisperformance_vars',
			array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'_wpnonce'         => wp_create_nonce( 'swis-performance-settings' ),
				'invalid_response' => esc_html__( 'Received an invalid response from your website, please check for errors in the Developer Tools console of your browser.', 'swis-performance' ),
				'remove_rule'      => esc_html__( 'Are you sure you want to remove this rule?', 'swis-performance' ),
				'removing_message' => esc_html__( 'Deleting...', 'swis-performance' ),
				'saving_message'   => esc_html__( 'Saving...', 'swis-performance' ),
				'check_assets'     => ! defined( 'SWIS_SLIM_CHECK_ASSETS' ) || SWIS_SLIM_CHECK_ASSETS,
			)
		);
	}

	/**
	 * Adds some dashicon CSS for our admin bar item.
	 */
	public function dash_css() {
		if ( ! $this->is_frontend() ) {
			return;
		}
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		if ( $this->is_amp() ) {
			return;
		}
		$slim_css = file_get_contents( SWIS_PLUGIN_PATH . 'assets/slim.css' );
		?>
		<style>
		<?php echo $slim_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
		<?php
	}

	/**
	 * Adds the script for our admin bar action.
	 *
	 * @param string $buffer The HTML content of the page.
	 * @return string The altered HTML.
	 */
	public function output_scripts( $buffer ) {
		if ( $this->output_assets_json ) {
			$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
			$this->debug_message( 'dumping assets in JSON for swis_slim_check=true' );
			return wp_json_encode( $this->output_assets );
		}
		if ( ! $this->is_frontend() ) {
			return $buffer;
		}
		if ( ! is_admin_bar_showing() ) {
			return $buffer;
		}
		if ( $this->is_amp() ) {
			return $buffer;
		}
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$slim_script =
		"function toggleSWISPane() {
			var SWISPane = document.getElementById('swis-slim-assets-pane');
			SWISPane.classList.toggle('swis-slim-hidden');
			SWISPane.classList.toggle('swis-slim-visible');
			document.body.classList.toggle('swis-slim-unscroll');
		}
		function addSWISClickers() {
			document.getElementById('swis-slim-show-top').addEventListener('click', toggleSWISPane);
			document.getElementById('swis-slim-show').addEventListener('click', toggleSWISPane);
			document.getElementById('swis-slim-close-pane').addEventListener('click', toggleSWISPane);
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', addSWISClickers);
		} else {
			addSWISClickers();
		}";
		if ( false === strpos( $buffer, 'jquery-core-js' ) && false === strpos( $buffer, '/jquery.js' ) && false === strpos( $buffer, '/jquery.min.js' ) ) {
			$buffer = preg_replace(
				'#(<script[^>]*?/assets/slim.(?:min.)?js[^>]*?></script>)#is',
				'<script data-cfasync="false" src="' . esc_url( home_url( '/wp-includes/js/jquery/jquery.min.js?ver=' . SWIS_PLUGIN_VERSION ) ) . '" defer></script>' .
				"\n$1\n",
				$buffer
			);
		}
		return preg_replace(
			'#</body>#i',
			'<script data-cfasync="false" data-no-optimize="1" data-no-defer="1" data-no-minify="1">' .
			$slim_script .
			'</script><script data-cfasync="false" data-no-optimize="1" data-no-defer="1" data-no-minify="1">' .
			'var swis_slim_more_assets = \'' . ( ! empty( $this->more_assets ) ? wp_json_encode( $this->more_assets ) : false ) . '\';' .
			'</script></body>',
			$buffer,
			1
		);
	}

	/**
	 * Handle a rule update via AJAX. Possible actions are "create", "update", and "delete".
	 *
	 * There are potentially three types of rules: disable, defer, delay.
	 * On success, returns the updated HTML for the rule/handle, an error message otherwise.
	 */
	public function edit_rule() {
		\session_write_close();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$permissions = \apply_filters( 'swis_performance_admin_permissions', 'manage_options' );
		if ( ! \current_user_can( $permissions ) || ! \check_ajax_referer( 'swis-performance-settings', 'swis_wpnonce', false ) ) {
			die( \wp_json_encode( array( 'error' => \esc_html__( 'Access token has expired, please reload the page.', 'swis-performance' ) ) ) );
		}
		if ( empty( $_POST['swis_slim_action'] ) ) {
			die( wp_json_encode( array( 'error' => esc_html__( 'Invalid operation requested.', 'swis-performance' ) ) ) );
		}
		if ( empty( $_POST['swis_slim_handle'] ) ) {
			die( wp_json_encode( array( 'error' => esc_html__( 'No handle provided.', 'swis-performance' ) ) ) );
		}
		if ( empty( $_POST['swis_slim_rule_type'] ) ) {
			die( wp_json_encode( array( 'error' => esc_html__( 'Rule type not submitted.', 'swis-performance' ) ) ) );
		}
		if ( empty( $_POST['swis_slim_mode'] ) ) {
			die( wp_json_encode( array( 'error' => esc_html__( 'Rule mode not submitted.', 'swis-performance' ) ) ) );
		}
		$this->frontend_editor = ! empty( $_POST['swis_slim_frontend'] ) ? true : false;

		$output     = '';
		$status     = '';
		$action     = sanitize_text_field( wp_unslash( $_POST['swis_slim_action'] ) );
		$type       = sanitize_text_field( wp_unslash( $_POST['swis_slim_rule_type'] ) );
		$mode       = sanitize_text_field( wp_unslash( $_POST['swis_slim_mode'] ) );
		$asset_type = '';
		if ( ! empty( $_POST['swis_slim_asset_type'] ) ) {
			$asset_type = sanitize_text_field( wp_unslash( $_POST['swis_slim_asset_type'] ) );
		}

		$allowed_types = array( 'disable', 'defer', 'delay' );
		$allowed_modes = array( 'include', 'exclude', 'all' );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			die( wp_json_encode( array( 'error' => esc_html__( 'Invalid rule type.', 'swis-performance' ) ) ) );
		}
		if ( ! in_array( $mode, $allowed_modes, true ) ) {
			die( wp_json_encode( array( 'error' => esc_html__( 'Invalid rule mode.', 'swis-performance' ) ) ) );
		}
		if ( ! empty( $_POST['swis_slim_current_page'] ) ) {
			$this->current_page = trim( sanitize_text_field( wp_unslash( $_POST['swis_slim_current_page'] ) ) );
		}
		if ( empty( $_POST['swis_slim_exclusions'] ) ) {
			$mode = 'all';
		}
		$user_exclusions = $this->get_option( 'slim_js_css' );
		if ( empty( $user_exclusions ) ) {
			$user_exclusions = array();
		}
		if ( ! is_array( $user_exclusions ) ) {
			if ( is_string( $user_exclusions ) ) {
				$this->migrate_user_exclusions();
			}
			$user_exclusions = $this->get_option( 'slim_js_css' );
			if ( ! is_array( $user_exclusions ) ) {
				$user_exclusions = array();
			}
		}
		switch ( $action ) {
			case 'create':
				$raw        = ! empty( $_POST['swis_slim_exclusions'] ) && 'all' !== $mode ? sanitize_text_field( wp_unslash( $_POST['swis_slim_exclusions'] ) ) : false;
				$exclusions = $raw ? explode( ',', trim( $raw ) ) : array();
				$handle     = sanitize_text_field( wp_unslash( $_POST['swis_slim_handle'] ) );
				if ( $this->is_iterable( $user_exclusions ) && isset( $user_exclusions[ $type ][ $handle ] ) ) {
					die( wp_json_encode( array( 'error' => esc_html__( 'A rule already exists for that handle, edit the existing rule or remove it before adding a new rule.', 'swis-performance' ) ) ) );
				}
				$new_rule = array(
					'handle'  => $handle,
					'include' => 'include' === $mode ? $exclusions : array(),
					'exclude' => 'exclude' === $mode ? $exclusions : array(),
					'raw'     => $raw,
				);
				$this->debug_message( "adding $mode rule for $handle with $raw:" );
				if ( $this->function_exists( 'print_r' ) ) {
					$this->debug_message( print_r( $user_exclusions, true ) );
				}
				$user_exclusions[ $type ][ $handle ] = $new_rule;
				$this->debug_message( 'now slim exclusions are:' );
				if ( $this->function_exists( 'print_r' ) ) {
					$this->debug_message( print_r( $user_exclusions, true ) );
				}
				$result = $this->set_option( 'slim_js_css', $user_exclusions );
				if ( ! $result ) {
					die( wp_json_encode( array( 'error' => esc_html__( 'Unable to save rule.', 'swis-performance' ) ) ) );
				}
				// Load up the rules before we build the HTML.
				$this->validate_rules();
				$output = $this->get_rule_html( $handle, $new_rule, $type, $asset_type );
				$status = $this->get_asset_status_html( $handle, $asset_type );
				break;
			case 'update':
				$raw        = ! empty( $_POST['swis_slim_exclusions'] ) && 'all' !== $mode ? sanitize_text_field( wp_unslash( $_POST['swis_slim_exclusions'] ) ) : false;
				$exclusions = $raw ? explode( ',', trim( $raw ) ) : array();
				$handle     = sanitize_text_field( wp_unslash( $_POST['swis_slim_handle'] ) );
				if ( ! $this->is_iterable( $user_exclusions ) || ! isset( $user_exclusions[ $type ][ $handle ] ) ) {
					die(
						wp_json_encode(
							array(
								'error' => sprintf(
									/* translators: %s: registered handle for a JS/CSS resource */
									esc_html__( 'Could not find a match for %s to update.', 'swis-performance' ),
									esc_html( $handle )
								),
							)
						)
					);
				}
				$new_rule = array(
					'handle'  => $handle,
					'include' => 'include' === $mode ? $exclusions : array(),
					'exclude' => 'exclude' === $mode ? $exclusions : array(),
					'raw'     => $raw,
				);

				$user_exclusions[ $type ][ $handle ] = $new_rule;
				if ( $this->function_exists( 'print_r' ) ) {
					$this->debug_message( print_r( $user_exclusions, true ) );
				}
				$this->set_option( 'slim_js_css', $user_exclusions );
				// Load up the rules before we build the HTML.
				$this->validate_rules();
				$output = $this->get_rule_html( $handle, $new_rule, $type, $asset_type );
				$status = $this->get_asset_status_html( $handle, $asset_type );
				break;
			case 'delete':
				$handle = sanitize_text_field( wp_unslash( $_POST['swis_slim_handle'] ) );
				if ( ! $this->is_iterable( $user_exclusions ) || ! isset( $user_exclusions[ $type ][ $handle ] ) ) {
					die(
						wp_json_encode(
							array(
								'error' => sprintf(
									/* translators: %s: registered handle for a JS/CSS resource */
									esc_html__( 'Could not find a match for %s to remove.', 'swis-performance' ),
									esc_html( $handle )
								),
							)
						)
					);
				}
				unset( $user_exclusions[ $type ][ $handle ] );
				$this->set_option( 'slim_js_css', $user_exclusions );
				// Load up the rules before we build the HTML.
				$this->validate_rules();
				$output = $this->get_rule_html( $handle, array(), $type, $asset_type );
				$status = $this->get_asset_status_html( $handle, $asset_type );
				break;
			default:
				die( wp_json_encode( array( 'error' => esc_html__( 'Unknown operation requested.', 'swis-performance' ) ) ) );
		}
		die(
			wp_json_encode(
				array(
					'success' => 1,
					'message' => $output,
					'status'  => $status,
				)
			)
		);
	}

	/**
	 * Retrieve the HTML for a given rule.
	 *
	 * @param string $handle The CSS/JS handle.
	 * @param array  $rule Exclusions and inclusions for the given $handle.
	 * @param string $type Type of rule being handled: disable, defer, or delay.
	 * @param string $asset_type The asset type (js/css). Optional, empty for backend requests.
	 * @return string The HTML produced for Slim rule.
	 */
	private function get_rule_html( $handle, $rule, $type, $asset_type = '' ) {
		ob_start();
		if ( $this->frontend_editor ) {
			$this->display_frontend_rule_form( $handle, $rule, $type, $asset_type );
		} else {
			if ( empty( $rule ) ) {
				return '';
			}
			$this->display_backend_rule( $handle, $rule, $type );
		}
		return trim( ob_get_clean() );
	}

	/**
	 * Retrieve the status HTML for a given asset.
	 *
	 * @param string $handle The CSS/JS handle.
	 * @param string $asset_type The asset type (js/css). Optional, empty for backend requests.
	 * @return string The HTML produced for asset status.
	 */
	private function get_asset_status_html( $handle, $asset_type = '' ) {
		ob_start();
		if ( ! $this->frontend_editor ) {
			return '';
		}
		$this->display_asset_status( $handle, $asset_type );
		return trim( ob_get_clean() );
	}

	/**
	 * Request the given page as a guest user to check which assets load for normal visitors.
	 */
	public function check_assets() {
		\session_write_close();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$permissions = \apply_filters( 'swis_performance_admin_permissions', 'manage_options' );
		if ( ! \current_user_can( $permissions ) || ! \check_ajax_referer( 'swis-performance-settings', 'swis_wpnonce', false ) ) {
			die( \wp_json_encode( array( 'error' => \esc_html__( 'Access token has expired, please reload the page.', 'swis-performance' ) ) ) );
		}
		if ( empty( $_POST['swis_slim_current_page'] ) ) {
			die( wp_json_encode( array( 'error' => esc_html__( 'Current page not detected.', 'swis-performance' ) ) ) );
		}

		$url = home_url( trim( sanitize_text_field( wp_unslash( $_POST['swis_slim_current_page'] ) ) ) );
		$url = add_query_arg( 'swis_slim_check', '1', $url );

		$result = wp_remote_get( $url );
		if ( is_wp_error( $result ) ) {
			$this->debug_message( 'Slim check HTTP error: ' . $result->get_error_message() );
			die( \wp_json_encode( array( 'error' => $result->get_error_message() ) ) );
		}

		$status = wp_remote_retrieve_response_code( $result );
		$this->debug_message( "Slim check HTTP status was $status" );
		if ( 200 !== (int) $status ) {
			die(
				\wp_json_encode(
					array(
						/* translators: %d: a non-200 HTTP status code */
						'error' => sprintf( esc_html__( 'Asset verification failed, HTTP status code %d', 'swis-performance' ), $status ),
					)
				)
			);
		}
		$body = \json_decode( wp_remote_retrieve_body( $result ), true );
		if ( ! $this->is_iterable( $body ) ) {
			$this->debug_message( wp_remote_retrieve_body( $result ) );
			die( wp_json_encode( array( 'error' => esc_html__( 'Invalid response received.', 'swis-performance' ) ) ) );
		}
		die( wp_json_encode( $body ) );
	}

	/**
	 * Get the HTML for a list of assets via AJAX.
	 */
	public function get_assets_html() {
		\session_write_close();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$permissions = \apply_filters( 'swis_performance_admin_permissions', 'manage_options' );
		if ( ! \current_user_can( $permissions ) || ! \check_ajax_referer( 'swis-performance-settings', 'swis_wpnonce', false ) ) {
			die( \wp_json_encode( array( 'error' => \esc_html__( 'Access token has expired, please reload the page.', 'swis-performance' ) ) ) );
		}
		if ( ! isset( $_POST['swis_slim_assets'] ) ) {
			die( wp_json_encode( array( 'error' => esc_html__( 'No asset data provided.', 'swis-performance' ) ) ) );
		}
		if ( empty( $_POST['swis_slim_current_page'] ) ) {
			die( wp_json_encode( array( 'error' => esc_html__( 'Current page not detected.', 'swis-performance' ) ) ) );
		}
		$this->current_page    = trim( sanitize_text_field( wp_unslash( $_POST['swis_slim_current_page'] ) ) );
		$this->frontend_editor = true;

		$this->assets = ! empty( $_POST['swis_slim_assets'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['swis_slim_assets'] ) ), true ) : array();

		$this->get_content_types();
		$random_type_key   = array_rand( $this->content_types );
		$this->sample_type = $this->content_types[ $random_type_key ];
		$this->sample_page = '/example-page/';

		$posts = get_posts( 'post_type=page&numberposts=1&fields=ids' );
		if ( ! empty( $posts[0] ) ) {
			$potential_sample = str_replace( get_home_url(), '', get_permalink( $posts[0] ) );
			if ( ! empty( $potential_sample ) && false === strpos( $potential_sample, 'http' ) && $this->current_page !== $potential_sample ) {
				$this->sample_page = $potential_sample;
			}
		}
		$this->active_plugins['root_url'] = trailingslashit( plugins_url() );
		$this->validate_rules();

		$assets_html = array();

		if ( ! empty( $this->assets['core'] ) ) {
			foreach ( $this->assets['core'] as $asset_type => $data ) {
				foreach ( $data as $handle => $asset ) {
					ob_start();
					$this->display_asset_info( $handle, $asset, $asset_type );
					$asset_html    = trim( ob_get_clean() );
					$assets_html[] = array(
						'type'   => 'core',
						'handle' => $handle,
						'html'   => $asset_html,
					);
				}
			}
		}
		if ( ! empty( $this->assets['plugins'] ) ) {
			$this->active_plugins['plugin_files'] = get_option( 'active_plugins', array() );
			foreach ( $this->assets['plugins'] as $plugin => $asset_types ) {
				foreach ( $asset_types as $asset_type => $data ) {
					foreach ( $data as $handle => $asset ) {
						ob_start();
						$this->display_asset_info( $handle, $asset, $asset_type );
						$asset_html    = trim( ob_get_clean() );
						$assets_html[] = array(
							'type'   => 'plugin',
							'handle' => $handle,
							'html'   => $asset_html,
						);
					}
				}
			}
		}
		if ( ! empty( $this->assets['theme'] ) ) {
			foreach ( $this->assets['theme'] as $asset_type => $data ) {
				foreach ( $data as $handle => $asset ) {
					ob_start();
					$this->display_asset_info( $handle, $asset, $asset_type );
					$asset_html    = trim( ob_get_clean() );
					$assets_html[] = array(
						'type'   => 'theme',
						'handle' => $handle,
						'html'   => $asset_html,
					);
				}
			}
		}
		if ( ! empty( $this->assets['misc'] ) ) {
			foreach ( $this->assets['misc'] as $asset_type => $data ) {
				foreach ( $data as $handle => $asset ) {
					ob_start();
					$this->display_asset_info( $handle, $asset, $asset_type );
					$asset_html    = trim( ob_get_clean() );
					$assets_html[] = array(
						'type'   => 'misc',
						'handle' => $handle,
						'html'   => $asset_html,
					);
				}
			}
		}
		die(
			wp_json_encode(
				array(
					'success' => 1,
					'message' => $assets_html,
				)
			)
		);
	}

	/**
	 * Display the HTML for a given rule on the settings.
	 *
	 * @param string $handle The CSS/JS handle.
	 * @param array  $rule Exclusions and inclusions for the given $handle.
	 * @param string $type Type of rule being handled: disable, defer, or delay.
	 */
	private function display_backend_rule( $handle, $rule, $type ) {
		$rule_id = preg_replace( '/[\W_]/', '', uniqid( '', true ) );
		if ( isset( $rule['raw'] ) && is_array( $rule['raw'] ) && ! empty( $rule['raw'][0] ) && is_string( $rule['raw'][0] ) ) {
			$rule['raw'] = $rule['raw'][0];
		}
		?>
		<div id="swis-slim-rule-<?php echo esc_attr( $rule_id ); ?>" class="swis-slim-rule" data-slim-handle="<?php echo esc_attr( $handle ); ?>" data-slim-rule-id="<?php echo esc_attr( $rule_id ); ?>">
		<?php if ( $rule['include'] ) : ?>
			<?php
			$includes = array();
			foreach ( $rule['include'] as $include ) {
				if ( 0 === strpos( $include, 'T>' ) ) {
					$includes[] = '<i>' . substr( $include, 2 ) . '</i> ' . esc_html__( 'content type', 'swis-performance' );
				} else {
					$includes[] = $include;
				}
			}
			?>
			<div class="swis-slim-rule-description">
				<?php /* translators: %s: registered handle for a JS/CSS resource */ ?>
				<?php printf( esc_html__( '%s disabled everywhere except:', 'swis-performance' ), '<strong>' . esc_html( $handle ) . '</strong>' ); ?>
				<input style="display:none;" type="radio" id="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="include" checked />
				<div class="swis-slim-pretty-rule">
					<?php echo wp_kses_post( implode( ', ', $includes ) ); ?>
				</div>
				<div class="swis-slim-error-message"></div>
				<div class="swis-slim-hidden">
					<div class="swis-slim-raw-rule">
						<input type="text" id="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" name="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" value="<?php echo esc_attr( $rule['raw'] ); ?>" />
						<button type="button" class="button-primary swis-slim-rule-save"><?php esc_html_e( 'Save', 'swis-performance' ); ?></button>
					</div>
					<p class="swis-slim-edit-rule-description description">
						<?php esc_html_e( 'Enter a comma-separated list of pages, URL patterns (use * as wildcard), or content types in the form T>post or T>page.', 'swis-performance' ); ?>
					</p>
				</div>
			</div>
			<div class="swis-slim-rule-actions">
				<button type="button" class="button-link button-link-edit"><?php esc_html_e( 'Edit', 'swis-performance' ); ?></button>
				|
				<button type="button" class="button-link button-link-delete"><?php esc_html_e( 'Delete', 'swis-performance' ); ?></button>
			</div>
		<?php elseif ( $rule['exclude'] ) : ?>
			<?php
			$excludes = array();
			foreach ( $rule['exclude'] as $exclude ) {
				if ( 0 === strpos( $exclude, 'T>' ) ) {
					$excludes[] = '<i>' . substr( $exclude, 2 ) . '</i> ' . esc_html__( 'content type', 'swis-performance' );
				} else {
					$excludes[] = $exclude;
				}
			}
			?>
			<div class="swis-slim-rule-description">
				<?php /* translators: %s: A JS/CSS handle, like 'jquery-form' */ ?>
				<?php printf( esc_html__( '%s disabled on:', 'swis-performance' ), '<strong>' . esc_html( $handle ) . '</strong>' ); ?><br>
				<input style="display:none;" type="radio" id="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="exclude" checked />
				<div class="swis-slim-pretty-rule">
					<?php echo wp_kses_post( implode( ', ', $excludes ) ); ?>
				</div>
				<div class="swis-slim-error-message"></div>
				<div class="swis-slim-hidden">
					<div class="swis-slim-raw-rule">
						<input type="text" id="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" name="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" value="<?php echo esc_attr( $rule['raw'] ); ?>" />
						<button type="button" class="button-primary swis-slim-rule-save"><?php esc_html_e( 'Save', 'swis-performance' ); ?></button>
					</div>
					<p class="swis-slim-edit-rule-description description">
						<?php esc_html_e( 'Enter a comma-separated list of pages, URL patterns (use * as wildcard), or content types in the form T>post or T>page.', 'swis-performance' ); ?>
					</p>
				</div>
			</div>
			<div class="swis-slim-rule-actions">
				<button type="button" class="button-link button-link-edit"><?php esc_html_e( 'Edit', 'swis-performance' ); ?></button>
				|
				<button type="button" class="button-link button-link-delete"><?php esc_html_e( 'Delete', 'swis-performance' ); ?></button>
			</div>
		<?php else : ?>
			<div class="swis-slim-rule-description">
				<?php /* translators: %s: A JS/CSS handle, like 'jquery-form' */ ?>
				<?php printf( esc_html__( '%s disabled everywhere', 'swis-performance' ), '<strong>' . esc_html( $handle ) . '</strong>' ); ?>
				<input style="display:none;" type="radio" id="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="all" checked />
				<div class="swis-slim-error-message"></div>
				<div class="swis-slim-hidden">
					<div class="swis-slim-column">
						<div class="swis-slim-row">
							<input type="radio" id="swis_slim_mode_include_<?php echo esc_attr( $rule_id ); ?>" class="swis-slim-radio" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="include" />
							<strong><label for="swis_slim_mode_include_<?php echo esc_attr( $rule_id ); ?>"><?php esc_html_e( 'disable everywhere except:', 'swis-performance' ); ?></label></strong>
						</div>
						<div class="swis-slim-row">
							<input type="radio" id="swis_slim_mode_exclude_<?php echo esc_attr( $rule_id ); ?>" class="swis-slim-radio" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="exclude" />
							<strong><label for="swis_slim_mode_exclude_<?php echo esc_attr( $rule_id ); ?>"><?php esc_html_e( 'disable on:', 'swis-performance' ); ?></label></strong>
						</div>
						<div class="swis-slim-raw-rule">
							<input type="text" id="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" name="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" value="" />
							<button type="button" class="button-primary swis-slim-rule-save"><?php esc_html_e( 'Save', 'swis-performance' ); ?></button>
						</div>
						<p class="swis-slim-edit-rule-description description">
							<?php esc_html_e( 'Enter a comma-separated list of pages, URL patterns (use * as wildcard), or content types in the form T>post or T>page.', 'swis-performance' ); ?>
						</p>
					</div>
				</div>
			</div>
			<div class="swis-slim-rule-actions">
				<button type="button" class="button-link button-link-edit"><?php esc_html_e( 'Add Exclusion', 'swis-performance' ); ?></button>
				|
				<button type="button" class="button-link button-link-delete"><?php esc_html_e( 'Delete', 'swis-performance' ); ?></button>
			</div>
		<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Display existing rules on the settings.
	 */
	public function display_backend_rules() {
		$this->validate_rules();
		if ( empty( $this->user_exclusions ) ) {
			return;
		}
		foreach ( $this->user_exclusions as $handle => $rule ) {
			$this->display_backend_rule( $handle, $rule, 'disable' );
		}
	}

	/**
	 * Add more exclusions from third-party code.
	 *
	 * @param string $rule A handle or rule using our (legacy) SLIM syntax.
	 */
	public function add_exclusion( $rule ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( is_string( $rule ) ) {
			$this->parse_slim_rule( $rule );
		}
	}

	/**
	 * Migrate the old-style string rules.
	 */
	public function migrate_user_exclusions() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$user_exclusions = $this->get_option( 'slim_js_css' );
		if ( ! empty( $user_exclusions ) ) {
			if ( is_string( $user_exclusions ) ) {
				$user_exclusions = array( $user_exclusions );
			}
			if ( is_array( $user_exclusions ) ) {
				if ( isset( $user_exclusions['disable'] ) ) {
					return;
				}
				if ( isset( $user_exclusions['defer'] ) ) {
					return;
				}
				if ( isset( $user_exclusions['delay'] ) ) {
					return;
				}
				foreach ( $user_exclusions as $exclusion ) {
					if ( ! is_string( $exclusion ) ) {
						continue;
					}
					$this->parse_slim_rule( $exclusion );
				}
			}
			$all_rules['disable'] = $this->user_exclusions;
			$this->set_option( 'slim_js_css', $all_rules );
		}
	}

	/**
	 * Validate and split out the Slim rules.
	 */
	public function validate_rules() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$all_rules = $this->get_option( 'slim_js_css' );
		if ( ! empty( $all_rules ) ) {
			if ( is_string( $all_rules ) ) {
				$this->debug_message( 'slim option in string format, attempting migration' );
				$this->migrate_user_exclusions();
				$all_rules = $this->get_option( 'slim_js_css' );
				if ( is_string( $all_rules ) ) {
					$this->debug_message( 'slim options are in string format?' );
					$this->debug_message( $all_rules );
					return;
				}
			}
			if ( is_array( $all_rules ) ) {
				if ( ! empty( $all_rules['disable'] ) && is_array( $all_rules['disable'] ) ) {
					$this->user_exclusions = $all_rules['disable'];
				}
				if ( ! empty( $all_rules['defer'] ) && is_array( $all_rules['defer'] ) ) {
					$this->defer_exclusions = $all_rules['defer'];
					if ( isset( $this->defer_exclusions['inline-scripts'] ) && ! $this->asset_affected( 'defer', 'inline-scripts', 'js' ) ) {
						$this->debug_message( 'enabling jQuery safe mode for JS defer to prevent inline script issues' );
						swis()->defer_js->jquery_safe_mode = true;
					}
				}
				if ( ! empty( $all_rules['delay'] ) && is_array( $all_rules['delay'] ) ) {
					$this->delay_inclusions = $all_rules['delay'];
				}
			}
		}
	}

	/**
	 * Parse a user-supplied slim rule into an array and append to $this->user_exclusions.
	 *
	 * @param string $rule The user-supplied rule.
	 * @return array The parsed array-style version of the rule.
	 */
	public function parse_slim_rule( $rule ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$rule = trim( str_replace( '\\', '/', $rule ) );
		if ( 0 === strpos( $rule, '+' ) || 0 === strpos( $rule, '-' ) ) {
			$mode = substr( $rule, 0, 1 );
			$rule = ltrim( $rule, '+' );
			$rule = ltrim( $rule, '-' );
			if ( strpos( $rule, ':' ) ) {
				$parts = explode( ':', $rule );
				if ( empty( $parts[0] ) || empty( $parts[1] ) ) {
					return;
				}
				$handle = $parts[1];
				$except = explode( ',', trim( $parts[0] ) );

				$this->user_exclusions[ $handle ] = array(
					'handle'  => $handle,
					'include' => '-' !== $mode ? $except : array(),
					'exclude' => '-' === $mode ? $except : array(),
					'raw'     => trim( $parts[0] ),
				);
				return $this->user_exclusions[ $handle ];
			}
		} elseif ( false === strpos( $rule, ':' ) ) {
			// Found a "disable everywhere" rule.
			$this->user_exclusions[ $rule ] = array(
				'handle'  => $rule,
				'include' => array(),
				'exclude' => array(),
				'raw'     => '',
			);
			return $this->user_exclusions[ $rule ];
		}
	}

	/**
	 * Check if an asset is from an external site.
	 *
	 * @param string $url The asset URL.
	 * @return bool True for external asset, false for local asset.
	 */
	public function is_external( $url ) {
		if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
			return false;
		}
		$asset_url_parts = $this->parse_url( $url );
		$local_url_parts = $this->parse_url( get_site_url() );
		if ( ! empty( $asset_url_parts['host'] ) && ! empty( $local_url_parts['host'] ) && 0 === strcasecmp( $asset_url_parts['host'], $local_url_parts['host'] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check size of asset.
	 *
	 * @param string $url The asset URL.
	 * @return string A human-readable size.
	 */
	public function get_asset_size( $url ) {
		$size     = '';
		$url_bits = explode( '?', $url );

		$asset_path = ABSPATH . str_replace( get_site_url(), '', $this->prepend_url_scheme( $url_bits[0] ) );

		if ( $url !== $asset_path && is_file( $asset_path ) ) {
			$size = size_format( filesize( $asset_path ), 1 );
		}
		return $size;
	}

	/**
	 * Check to see which JS/CSS files have been registered for the current page.
	 */
	public function find_assets() {
		if ( ! $this->is_frontend() ) {
			return;
		}
		if ( ! is_admin_bar_showing() && ! $this->output_assets_json ) {
			return;
		}
		if ( $this->is_amp() ) {
			return;
		}
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$assets = array(
			'js'  => wp_scripts(),
			'css' => wp_styles(),
		);

		$core_url    = ! empty( $assets['js']->default_dirs[1] ) ? dirname( $assets['js']->default_dirs[1] ) : dirname( $assets['js']->default_dirs[0] );
		$plugins_url = plugins_url();
		$theme_url   = get_theme_root_uri();
		$this->debug_message( $core_url );
		$this->debug_message( $plugins_url );
		$this->debug_message( $theme_url );

		foreach ( $assets as $asset_type => $data ) {
			foreach ( $data->done as $handle ) {
				if ( ! in_array( $handle, $this->whitelist, true ) && ! empty( $data->registered[ $handle ] ) ) {
					if ( empty( $data->registered[ $handle ]->src ) ) {
						continue;
					}
					$url = $this->prepend_url_scheme( $data->registered[ $handle ]->src );
					if ( false !== strpos( $url, $plugins_url ) ) {
						$asset_source_type = 'plugins';

						// Get the plugin folder name.
						$plugin_path = ltrim( str_replace( $plugins_url, '', $url ), '/' );
						$plugin_path = explode( '/', $plugin_path );
						$plugin_dir  = $plugin_path[0];
						$this->debug_message( "found a plugin asset in $plugin_dir" );
					} elseif ( false !== strpos( $url, $theme_url ) ) {
						$asset_source_type = 'theme';
					} elseif ( false !== strpos( $url, $core_url ) || 'jquery' === $handle ) {
						$asset_source_type = 'core';
					} else {
						$asset_source_type = 'misc';
					}

					$this->debug_message( "adding registered $handle for $url ($asset_source_type/$asset_type) to assets list" );

					$url_info = pathinfo( $url );
					$asset    = array(
						'url'      => $url,
						'external' => (int) $this->is_external( $url ),
						'filename' => ! empty( $url_info['basename'] ) ? $url_info['basename'] : $url,
						'size'     => $this->get_asset_size( $url ),
						'disabled' => (int) $this->asset_affected( 'disable', $handle, $asset_type ),
						'deferred' => (int) $this->asset_affected( 'defer', $handle, $asset_type ),
						'delayed'  => (int) $this->asset_affected( 'delay', $handle, $asset_type ),
						'deps'     => isset( $data->registered[ $handle ]->deps ) ? $data->registered[ $handle ]->deps : array(),
					);
					if ( 'plugins' === $asset_source_type ) {
						$this->assets[ $asset_source_type ][ $plugin_dir ][ $asset_type ][ $handle ] = $asset;
						if ( $this->output_assets_json && ! in_array( $handle, $this->output_assets, true ) ) {
							$this->output_assets[] = $handle;
						}
					} else {
						$this->assets[ $asset_source_type ][ $asset_type ][ $handle ] = $asset;
						if ( $this->output_assets_json && ! in_array( $handle, $this->output_assets, true ) ) {
							$this->output_assets[] = $handle;
						}
					}
					$dependency_info_exists = false;
					foreach ( $this->deps as $existing_dep ) {
						if ( $handle === $existing_dep['name'] ) {
							$dependency_info_exists = true;
							break;
						}
					}
					if ( ! $dependency_info_exists ) {
						$this->deps[] = array(
							'name' => $handle,
							'deps' => $asset['deps'],
							'type' => $asset_type,
						);
					}
				}
			}
		}
		global $wp_version;
		if ( version_compare( $wp_version, '4.2', '>=' ) ) {
			$url = '/wp-includes/js/wp-emoji-release.min.js';

			$this->assets['core']['js']['wp-emoji'] = array(
				'url'      => $url,
				'external' => false,
				'filename' => 'wp-emoji-release.min.js',
				'size'     => $this->get_asset_size( $url ),
				'disabled' => (int) $this->asset_affected( 'disable', 'wp-emoji', 'js' ),
				'deferred' => (int) $this->asset_affected( 'defer', 'wp-emoji', 'js' ),
				'delayed'  => (int) $this->asset_affected( 'delay', 'wp-emoji', 'js' ),
				'deps'     => array(),
			);
			if ( $this->output_assets_json && ! in_array( 'wp-emoji', $this->output_assets, true ) ) {
				$this->output_assets[] = 'wp-emoji';
			}
		}
		$this->assets['misc']['js']['inline-scripts'] = array(
			'url'      => '',
			'external' => false,
			'filename' => 'Inline Scripts',
			'size'     => 'unknown',
			'disabled' => (int) $this->asset_affected( 'disable', 'inline-scripts', 'js' ),
			'deferred' => (int) $this->asset_affected( 'defer', 'inline-scripts', 'js' ),
			'delayed'  => (int) $this->asset_affected( 'delay', 'inline-scripts', 'js' ),
			'deps'     => array(),
		);
		if ( $this->output_assets_json && ! in_array( 'inline-scripts', $this->output_assets, true ) ) {
			$this->output_assets[] = 'inline-scripts';
		}
	}

	/**
	 * Parse through link/script tags to find non-registered JS/CSS.
	 *
	 * @param string $element HTML element code.
	 * @param string $handle The CSS/JS handle. Optional.
	 * @return string The unaltered element, this function is read-only.
	 */
	public function find_more_assets( $element, $handle = '' ) {
		if ( ! $this->is_frontend() ) {
			return $element;
		}
		if ( ! is_admin_bar_showing() && ! $this->output_assets_json ) {
			return $element;
		}
		if ( $this->is_amp() ) {
			return $element;
		}
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		if ( ! $handle ) {
			return $element;
		}

		$core_url    = '/wp-includes';
		$plugins_url = $this->parse_url( plugins_url(), PHP_URL_PATH );
		$theme_url   = $this->parse_url( get_theme_root_uri(), PHP_URL_PATH );

		$url = false;
		if ( 0 === strpos( $element, '<link' ) ) {
			$url        = $this->get_attribute( $element, 'href' );
			$asset_type = 'css';
		} elseif ( 0 === strpos( $element, '<script' ) ) {
			$script_parts = $this->script_parts( $element );
			// We have an opening <script*> tag and no inline content.
			if ( ! empty( $script_parts['open'] ) && empty( trim( $script_parts['content'] ) ) ) {
				$url = $this->get_attribute( $script_parts['open'], 'src' );
			}
			$asset_type = 'js';
		}
		if ( ! $url ) {
			return $element;
		}

		if ( ! in_array( $handle, $this->whitelist, true ) ) {
			foreach ( $this->whitelist_urls as $whitelist_url ) {
				if ( false !== strpos( $url, $whitelist_url ) ) {
					$this->debug_message( "ignoring $url because of whitelist $whitelist_url" );
					return $element;
				}
			}
			$url      = $this->prepend_url_scheme( $url );
			$url_path = $this->parse_url( $url, PHP_URL_PATH );
			if ( false !== strpos( $url, $plugins_url ) ) {
				$asset_source_type = 'plugins';

				// Get the plugin folder name.
				$plugin_path = ltrim( str_replace( $plugins_url, '', $url_path ), '/' );
				$plugin_path = explode( '/', $plugin_path );
				$plugin_dir  = $plugin_path[0];
				$this->debug_message( "found a plugin asset in $plugin_dir" );
			} elseif ( false !== strpos( $url, $theme_url ) ) {
				$asset_source_type = 'theme';
			} elseif ( false !== strpos( $url, $core_url ) || 'jquery' === $handle ) {
				$asset_source_type = 'core';
			} else {
				$asset_source_type = 'misc';
			}

			// See if we already found this asset.
			if ( 'plugins' === $asset_source_type && isset( $this->assets[ $asset_source_type ][ $plugin_dir ][ $asset_type ] ) ) {
				$this->debug_message( "searching plugins for $asset_type with $url_path and (handle) $handle in $plugin_dir" );
				foreach ( $this->assets[ $asset_source_type ][ $plugin_dir ][ $asset_type ] as $handle_exists => $asset ) {
					if ( $handle === $handle_exists . '-' . $asset_type ) {
						$this->debug_message( "already have $handle_exists for $url" );
						return $element;
					}
					$existing_url_path = $this->parse_url( $asset['url'], PHP_URL_PATH );
					// $this->debug_message( "how about $url_path and $existing_url_path" );
					if ( $url_path === $existing_url_path ) {
						$this->debug_message( "already have $existing_url_path for $url" );
						return $element;
					}
				}
			} elseif ( isset( $this->assets[ $asset_source_type ][ $asset_type ] ) ) {
				$this->debug_message( "searching $asset_source_type for $asset_type with $url_path and (handle) $handle" );
				foreach ( $this->assets[ $asset_source_type ][ $asset_type ] as $handle_exists => $asset ) {
					if ( $handle === $handle_exists . '-' . $asset_type ) {
						$this->debug_message( "already have $handle_exists for $url" );
						return $element;
					}
					if ( strpos( $url, 'fonts.googleapis.com' ) && strpos( $url, 'family=' ) ) {
						// If there are multiple Google Font URLs, they will all have the same path, so don't bother.
						continue;
					}
					$existing_url_path = $this->parse_url( $asset['url'], PHP_URL_PATH );
					// $this->debug_message( "how about $url_path and $existing_url_path" );
					if ( $url_path === $existing_url_path ) {
						$this->debug_message( "already have $existing_url_path for $url" );
						return $element;
					}
				}
			}
			$this->debug_message( "adding un-registered $handle for $url ($asset_source_type/$asset_type) to assets list" );

			$url_info = pathinfo( $url );
			$filename = ! empty( $url_info['basename'] ) ? $url_info['basename'] : $url;
			if ( strpos( $filename, '?' ) ) {
				$filename_parts = explode( '?', $filename );
				if ( ! empty( $filename_parts[0] ) ) {
					$filename = $filename_parts[0];
				}
			}
			$asset = array(
				'url'      => $url,
				'external' => (int) $this->is_external( $url ),
				'filename' => $filename,
				'size'     => $this->get_asset_size( $url ),
				'disabled' => (int) $this->asset_affected( 'disable', $handle, $asset_type ),
				'deferred' => (int) $this->asset_affected( 'defer', $handle, $asset_type ),
				'delayed'  => (int) $this->asset_affected( 'delay', $handle, $asset_type ),
				'deps'     => array(),
			);
			if ( 'plugins' === $asset_source_type ) {
				$this->more_assets[ $asset_source_type ][ $plugin_dir ][ $asset_type ][ $handle ] = $asset;
				if ( $this->output_assets_json && ! in_array( $handle, $this->output_assets, true ) ) {
					$this->output_assets[] = $handle;
				}
			} else {
				$this->more_assets[ $asset_source_type ][ $asset_type ][ $handle ] = $asset;
				if ( $this->output_assets_json && ! in_array( $handle, $this->output_assets, true ) ) {
					$this->output_assets[] = $handle;
				}
			}
			$this->deps[] = array(
				'name' => $handle,
				'deps' => array(),
				'type' => $asset_type,
			);
		}
		return $element;
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
	 * Get registered content types.
	 */
	public function get_content_types() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$this->content_types = get_post_types( array( 'public' => true ) );
	}

	/**
	 * Check the content type of the current page.
	 */
	public function get_content_type() {
		if ( is_singular() ) {
			$this->content_type = get_post_type();
		}
	}

	/**
	 * See if the current content type matches a content type rule.
	 *
	 * @param string $rule A content-type rule (prefixed with T>).
	 * @return bool True if the current type matches the rule, false otherwise.
	 */
	public function check_content_type( $rule ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( 0 === strpos( $rule, 'T>' ) ) {
			$rule_content_type = substr( $rule, 2 );
			$this->debug_message( "found rule content type: $rule_content_type" );
			if ( $rule_content_type === $this->content_type ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the user has disabled an asset everywhere.
	 *
	 * @param string $asset_type The type of asset: 'js' or 'css'.
	 * @param string $handle The handle/slug of the asset.
	 * @return bool True if the asset has been disabled site-wide, false otherwise.
	 */
	public function asset_disabled_everywhere( $asset_type, $handle ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( 'jquery-migrate' === $handle && ! isset( $this->user_exclusions[ $handle ] ) && isset( $this->user_exclusions['jquery-core'] ) ) {
			$this->debug_message( "using jquery-core exclusions for $handle" );
			return $this->asset_disabled_everywhere( $asset_type, 'jquery-core' );
		}
		if ( 'jquery-core' === $handle && ! isset( $this->user_exclusions[ $handle ] ) && isset( $this->user_exclusions['jquery'] ) ) {
			$this->debug_message( "using jquery exclusions for $handle" );
			return $this->asset_disabled_everywhere( $asset_type, 'jquery' );
		}
		if ( isset( $this->user_exclusions[ $handle ] ) ) {
			if ( empty( $this->user_exclusions[ $handle ]['include'] ) && empty( $this->user_exclusions[ $handle ]['exclude'] ) ) {
				$this->debug_message( "site-wide rule triggered for $handle" );
				return true;
			}
		}
		$this->debug_message( "no rules matched for $handle" );
		return false;
	}

	/**
	 * Check if the user has delayed an asset everywhere.
	 *
	 * @param string $asset_type The type of asset: 'js' or 'css'.
	 * @param string $handle The handle/slug of the asset.
	 * @return bool True if the asset has been delayed site-wide, false otherwise.
	 */
	public function asset_delayed_everywhere( $asset_type, $handle ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( 'jquery-migrate' === $handle && ! isset( $this->delay_inclusions[ $handle ] ) && isset( $this->delay_inclusions['jquery-core'] ) ) {
			$this->debug_message( "using jquery-core exclusions for $handle" );
			return $this->asset_delayed_everywhere( $asset_type, 'jquery-core' );
		}
		if ( 'jquery-core' === $handle && ! isset( $this->delay_inclusions[ $handle ] ) && isset( $this->delay_inclusions['jquery'] ) ) {
			$this->debug_message( "using jquery exclusions for $handle" );
			return $this->asset_delayed_everywhere( $asset_type, 'jquery' );
		}
		if ( isset( $this->delay_inclusions[ $handle ] ) ) {
			if ( empty( $this->delay_inclusions[ $handle ]['include'] ) && empty( $this->delay_inclusions[ $handle ]['exclude'] ) ) {
				$this->debug_message( "site-wide rule triggered for $handle" );
				return true;
			}
		}
		$this->debug_message( "no rules matched for $handle" );
		return false;
	}

	/**
	 * Check if the given ruleset (type) applies to an asset for this particular page.
	 *
	 * @param string $type Type of rule(set) being handled: disable, defer, or delay.
	 * @param string $handle The handle/slug of the asset.
	 * @param string $asset_type The type of asset: 'js' or 'css'. Optional.
	 * @return bool True if the asset is impacted/affected by the ruleset for the current page, false otherwise.
	 */
	public function asset_affected( $type, $handle, $asset_type = '' ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( 'defer' === $type && ! $this->get_option( 'defer_js' ) && ! $this->get_option( 'defer_css' ) ) {
			return false;
		}
		$uri = $this->parse_url( add_query_arg( '', '' ), PHP_URL_PATH );
		if ( wp_doing_ajax() && $this->frontend_editor ) {
			if ( $this->current_page !== $uri ) {
				$uri = $this->current_page;
			}
		}
		$this->debug_message( "request uri is $uri, rule type $type for $asset_type" );
		if ( 'disable' === $type && 'dashicons' === $handle && is_admin_bar_showing() ) {
			return false;
		}
		switch ( $type ) {
			case 'disable':
				$rules = $this->user_exclusions;
				break;
			case 'defer':
				$rules = $this->defer_exclusions;
				break;
			case 'delay':
				$rules = $this->delay_inclusions;
				break;
			default:
				return;
		}
		if ( 'jquery-migrate' === $handle && ! isset( $rules[ $handle ] ) && isset( $rules['jquery-core'] ) ) {
			$this->debug_message( "using jquery-core exclusions for $handle" );
			return $this->asset_affected( $type, 'jquery-core', $asset_type );
		}
		if ( 'jquery-core' === $handle && ! isset( $rules[ $handle ] ) && isset( $rules['jquery'] ) ) {
			$this->debug_message( "using jquery exclusions for $handle" );
			return $this->asset_affected( $type, 'jquery', $asset_type );
		}
		if ( isset( $rules[ $handle ] ) ) {
			if ( empty( $rules[ $handle ]['include'] ) && empty( $rules[ $handle ]['exclude'] ) ) {
				$this->debug_message( "site-wide rule triggered for $handle" );
				return true;
			} elseif ( ! empty( $rules[ $handle ]['include'] ) ) {
				foreach ( $rules[ $handle ]['include'] as $include ) {
					$include = trim( $include );
					if ( $this->check_content_type( $include ) ) {
						$this->debug_message( "content-include rule triggered for $handle" );
						return false;
					}
					if ( false !== strpos( $include, '*' ) ) {
						$pattern = str_replace( '#', '\#', trim( $include, '*' ) );
						$pattern = str_replace( '(*)', '.*', $pattern );
						if ( ! empty( $pattern ) && preg_match( "#$pattern#", $uri ) ) {
							$this->debug_message( "pattern-include ($include as $pattern) rule triggered for $handle" );
							return false;
						}
					} elseif ( $uri === $include ) {
						$this->debug_message( "page-include ($include) rule triggered for $handle" );
						return false;
					}
				}
				return true;
			} elseif ( ! empty( $rules[ $handle ]['exclude'] ) ) {
				foreach ( $rules[ $handle ]['exclude'] as $exclude ) {
					$exclude = trim( $exclude );
					if ( $this->check_content_type( $exclude ) ) {
						$this->debug_message( "content-exclude rule triggered for $handle" );
						return true;
					}
					if ( false !== strpos( $exclude, '*' ) ) {
						$pattern = str_replace( '#', '\#', trim( $exclude, '*' ) );
						$pattern = str_replace( '(*)', '.*', $pattern );
						if ( ! empty( $pattern ) && preg_match( "#$pattern#", $uri ) ) {
							$this->debug_message( "pattern-exclude ($exclude as $pattern) rule triggered for $handle" );
							return true;
						}
					} elseif ( $uri === $exclude ) {
						$this->debug_message( "page-exclude ($exclude) rule triggered for $handle" );
						return true;
					}
				}
			}
		}
		$this->debug_message( "no rules matched for $handle" );
		return false;
	}

	/**
	 * Remove JS/CSS files if the user has disabled them.
	 *
	 * @param string $url The address of the resource.
	 * @param string $handle The registered handle for the resource.
	 * @return string|bool False if asset is disabled, original URL otherwise.
	 */
	public function disable_assets( $url, $handle ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$this->debug_message( "checking $url" );
		$asset_type = current_filter() === 'script_loader_src' ? 'js' : 'css';
		return $this->asset_affected( 'disable', $handle, $asset_type ) ? false : $url;
	}

	/**
	 * Hook into the SWIS CSS defer function to bypass defer for specified assets.
	 *
	 * @param bool   $skip Whether CSS defer should be skipped or not. Defaults to false.
	 * @param string $tag The full link/style tag HTML.
	 * @param string $handle The asset handle/slug.
	 * @return bool Returns $skip, possibly altered.
	 */
	public function skip_css_defer( $skip, $tag, $handle ) {
		if ( $tag && $handle ) {
			if (
				isset( $this->defer_exclusions[ $handle ] ) &&
				! $this->asset_affected( 'defer', $handle, 'css' )
			) {
				return true;
			}
		}
		return $skip;
	}

	/**
	 * Hook into the SWIS JS defer function to bypass defer for specified assets.
	 *
	 * @param bool   $skip Whether JS defer should be skipped or not. Defaults to false.
	 * @param string $tag The full script tag HTML.
	 * @param string $handle The asset handle/slug.
	 * @return bool Returns $skip, possibly altered.
	 */
	public function skip_js_defer( $skip, $tag, $handle ) {
		if ( $tag && $handle ) {
			if (
				isset( $this->defer_exclusions[ $handle ] ) &&
				! $this->asset_affected( 'defer', $handle, 'js' )
			) {
				return true;
			}
		}
		return $skip;
	}

	/**
	 * Hook into the SWIS JS delay function to enable delay for specified assets.
	 *
	 * @param bool   $skip Whether JS delay should be skipped or not. Defaults to true.
	 * @param string $tag The full script tag HTML.
	 * @param string $handle The asset handle/slug.
	 * @return bool Returns $skip, possibly altered.
	 */
	public function skip_js_delay( $skip, $tag, $handle ) {
		if ( $tag && $handle ) {
			if (
				isset( $this->delay_inclusions[ $handle ] ) &&
				$this->asset_affected( 'delay', $handle, 'js' )
			) {
				return false;
			}
		}
		return $skip;
	}

	/**
	 * Disable emoji JS/CSS based on user preference.
	 */
	public function maybe_remove_emoji() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( $this->asset_affected( 'disable', 'wp-emoji' ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
		}
	}

	/**
	 * Check to see if Emoji is enqueued.
	 *
	 * @return bool True if it is, false if it ain't.
	 */
	public function is_emoji_active() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		return (bool) has_action( 'wp_head', 'print_emoji_detection_script' );
	}

	/**
	 * Get a list of dependent assets for any given asset.
	 *
	 * @param string $handle The asset handle/slug.
	 * @param string $asset_type The asset type (js/css).
	 * @return array A list assets that depend on the $handle asset.
	 */
	public function get_dependents( $handle, $asset_type ) {
		$dependents = array();
		foreach ( $this->deps as $asset ) {
			if ( in_array( $handle, $asset['deps'], true ) && $asset_type === $asset['type'] && 'jquery' !== $asset['name'] ) {
				$dependents[] = $asset['name'];
			}
		}
		if ( 'jquery-core' === $handle && empty( $dependents ) ) {
			return $this->get_dependents( 'jquery', 'js' );
		}
		if ( 'jquery-migrate' === $handle && empty( $dependents ) ) {
			return $this->get_dependents( 'jquery', 'js' );
		}
		return $dependents;
	}

	/**
	 * Display a list of discovered JS/CSS files for the current page.
	 */
	public function display_assets() {
		if ( ! $this->is_frontend() ) {
			return;
		}
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( $this->is_amp() ) {
			return;
		}
		echo "<div id='swis-slim-assets-pane' class='swis-slim-hidden'>\n";

		$this->get_content_types();
		$this->sample_type  = $this->get_content_type();
		$this->current_page = $this->parse_url( add_query_arg( '', '' ), PHP_URL_PATH );
		$this->sample_page  = '/example-page/';

		$posts = get_posts( 'post_type=page&numberposts=1&fields=ids' );
		if ( ! empty( $posts[0] ) ) {
			$potential_sample = str_replace( get_home_url(), '', get_permalink( $posts[0] ) );
			if ( ! empty( $potential_sample ) && false === strpos( $potential_sample, 'http' ) && $this->current_page !== $potential_sample ) {
				$this->sample_page = $potential_sample;
			}
		}
		$this->active_plugins['root_url'] = trailingslashit( plugins_url() );
		?>
		<div class='slim-header'>
			<div class='slim-left'>
				<div class='slim-main-heading'>
					SWIS Performance
					<?php $this->help_link( 'https://docs.ewww.io/article/97-disabling-unused-css-and-js' ); ?>
				</div>
				<div style="display:none;" id="swis-slim-current-page"><?php echo esc_html( $this->current_page ); ?></div>
				<ul>
					<li><?php esc_html_e( 'Note that the list of CSS/JS files may be different on each page.', 'swis-performance' ); ?></li>
					<li><?php esc_html_e( 'Always test your pages after each change.', 'swis-performance' ); ?></li>
					<li><?php esc_html_e( 'Then, if something breaks, undo it by removing the last rule you added.', 'swis-performance' ); ?></li>
				<?php if ( $this->get_option( 'test_mode' ) ) : ?>
					<li>
						<?php
						printf(
							/* translators: %s: plugin settings (link) */
							esc_html__( 'When you are done making a mess, be sure to disable Test Mode in the %s!', 'swis-performance' ),
							'<a href="' . esc_url( admin_url( 'options-general.php?page=swis-performance-options' ) ) . '">' . esc_html__( 'plugin settings', 'swis-performance' ) . '</a>'
						);
						?>
					</li>
				<?php endif; ?>
				</ul>
			</div>
			<div class='slim-right'>
				<div class="slim-close-container">
					<button id='swis-slim-close-pane' class="button-secondary"><span class="dashicons dashicons-no-alt"></span></button>
				</div>
				<div style="display:none;" id="swis-slim-processing">
					<img src="<?php echo esc_url( plugins_url( '/assets/images/spinner.gif', SWIS_PLUGIN_FILE ) ); ?>">
					<?php esc_html_e( 'Verifying asssets, please wait a moment...', 'swis-performance' ); ?>
				</div>
				<div style="display:none;" id="swis-slim-show-all-assets">
					<p><?php esc_html_e( 'JS/CSS assets not used for logged-out visitors have been hidden.', 'swis-performance' ); ?></p>
					<button type="button" class="button-secondary"><?php esc_html_e( 'Show All Assets', 'swis-performance' ); ?></button>
				</div>
			</div>
		</div>
		<?php do_action( 'swis_slim_before_all_sections' ); ?>
		<div style="display:none" class="swis-slim-caught-errors">
			<div class='swis-slim-error-head'>
				<p>
					<?php esc_html_e( 'JS errors have been detected', 'swis-performance' ); ?>
					<?php $this->help_link( 'https://docs.ewww.io/article/128-troubleshooting-js-errors' ); ?>
				</p>
				<button type="button" class="button-secondary swis-slim-show-errors"><?php esc_html_e( 'Show Errors', 'swis-performance' ); ?></button>
				<button style="display:none" type="button" class="button-secondary swis-slim-hide-errors"><?php esc_html_e( 'Hide Errors', 'swis-performance' ); ?></button>
			</div>
			<div style="display:none" class="swis-slim-error-log"><p><i><?php esc_html_e( 'Ensure that rules added below do not introduce new errors. More detail on specific errors may be found in the developer console of your browser.', 'swis-performance' ); ?></i></p></div>
		</div>
		<?php
		if ( ! empty( $this->assets['core'] ) ) {
			?>
			<?php do_action( 'swis_slim_before_core_section' ); ?>
			<div class='slim-section-heading'>
				<?php esc_html_e( 'Core', 'swis-performance' ); ?>
			</div>
			<table id="swis-slim-assets-core" class='swis-slim-assets'>
				<tr>
					<th class='swis-slim-asset-status'><?php esc_html_e( 'Status', 'swis-performance' ); ?></th>
					<th class='swis-slim-asset-details'><?php esc_html_e( 'Asset Details', 'swis-performance' ); ?></th>
					<th class='swis-slim-asset-size'><?php esc_html_e( 'Size', 'swis-performance' ); ?></th>
				</tr>
			<?php
			foreach ( $this->assets['core'] as $asset_type => $data ) {
				foreach ( $data as $handle => $asset ) {
					$this->display_asset_info( $handle, $asset, $asset_type );
				}
			}
			?>
			</table>
			<?php
		}
		if ( ! empty( $this->assets['plugins'] ) ) {
			$this->active_plugins['plugin_files'] = get_option( 'active_plugins', array() );
			?>
			<?php do_action( 'swis_slim_before_plugins_section' ); ?>
			<div class='slim-section-heading'>
				<?php esc_html_e( 'Plugins', 'swis-performance' ); ?>
			</div>
			<table id="swis-slim-assets-plugins" class='swis-slim-assets'>
				<tr>
					<th class='swis-slim-asset-active'><?php esc_html_e( 'Active', 'swis-performance' ); ?></th>
					<th class='swis-slim-asset-details'><?php esc_html_e( 'Asset Details', 'swis-performance' ); ?></th>
					<th class='swis-slim-asset-size'><?php esc_html_e( 'Size', 'swis-performance' ); ?></th>
				</tr>
			<?php
			foreach ( $this->assets['plugins'] as $plugin => $asset_types ) {
				foreach ( $asset_types as $asset_type => $data ) {
					foreach ( $data as $handle => $asset ) {
						$this->display_asset_info( $handle, $asset, $asset_type );
					}
				}
			}
			?>
			</table>
			<?php
		}
		if ( ! empty( $this->assets['theme'] ) ) {
			?>
			<?php do_action( 'swis_slim_before_theme_section' ); ?>
			<div class='slim-section-heading'>
				<?php esc_html_e( 'Theme', 'swis-performance' ); ?>
			</div>
			<table id="swis-slim-assets-theme" class='swis-slim-assets'>
				<tr>
					<th class='swis-slim-asset-active'><?php esc_html_e( 'Active', 'swis-performance' ); ?></th>
					<th class='swis-slim-asset-details'><?php esc_html_e( 'Asset Details', 'swis-performance' ); ?></th>
					<th class='swis-slim-asset-size'><?php esc_html_e( 'Size', 'swis-performance' ); ?></th>
				</tr>
			<?php
			foreach ( $this->assets['theme'] as $asset_type => $data ) {
				foreach ( $data as $handle => $asset ) {
					$this->display_asset_info( $handle, $asset, $asset_type );
				}
			}
			?>
			</table>
			<?php
		}
		if ( ! empty( $this->assets['misc'] ) ) {
			?>
			<div class='slim-section-heading'>
				<?php esc_html_e( 'Miscellaneous', 'swis-performance' ); ?>
			</div>
			<table id="swis-slim-assets-misc" class='swis-slim-assets'>
				<tr>
					<th class='swis-slim-asset-active'><?php esc_html_e( 'Active', 'swis-performance' ); ?></th>
					<th class='swis-slim-asset-details'><?php esc_html_e( 'Asset Details', 'swis-performance' ); ?></th>
					<th class='swis-slim-asset-size'><?php esc_html_e( 'Size', 'swis-performance' ); ?></th>
				</tr>
			<?php
			foreach ( $this->assets['misc'] as $asset_type => $data ) {
				foreach ( $data as $handle => $asset ) {
					$this->display_asset_info( $handle, $asset, $asset_type );
				}
			}
			?>
			</table>
			<?php
		}
		echo "</div>\n";
	}

	/**
	 * Display the table row for a particular asset.
	 *
	 * @param string $handle The asset handle.
	 * @param array  $asset The asset information.
	 * @param string $asset_type The asset type (js/css).
	 */
	public function display_asset_info( $handle, $asset, $asset_type ) {
		$dependents = $this->get_dependents( $handle, $asset_type );
		if ( 'yoast-seo-adminbar' === $handle ) {
			return;
		}
		if ( 'jquery' === $handle ) {
			return;
		}
		if ( 0 === strpos( $asset['url'], '/' ) && 0 !== strpos( $asset['url'], '//' ) ) {
			$asset['url'] = \get_site_url() . $asset['url'];
		}
		$disable_rule = isset( $this->user_exclusions[ $handle ] ) ? $this->user_exclusions[ $handle ] : array();
		$defer_rule   = isset( $this->defer_exclusions[ $handle ] ) ? $this->defer_exclusions[ $handle ] : array();
		$delay_rule   = isset( $this->delay_inclusions[ $handle ] ) ? $this->delay_inclusions[ $handle ] : array();
		if ( false !== strpos( $asset['url'], $this->active_plugins['root_url'] ) ) {
			$plugin_info = false;

			$half_url = str_replace( $this->active_plugins['root_url'], '', $asset['url'] );
			$url_bits = explode( '/', $half_url );
			if ( $this->is_iterable( $url_bits ) && isset( $url_bits[0] ) ) {
				$plugin_slug = $url_bits[0];
				foreach ( $this->active_plugins['plugin_files'] as $plugin_file ) {
					if ( false !== strpos( $plugin_file, $plugin_slug ) ) {
						$abs_plugin_file = \plugin_dir_path( SWIS_PLUGIN_PATH ) . $plugin_file;
						if ( $this->is_file( $abs_plugin_file ) ) {
							$plugin_info = $this->get_plugin_data( $abs_plugin_file, false );
						}
						break;
					}
				}
				if ( $this->is_iterable( $plugin_info ) ) {
					$asset['plugin_title'] = $plugin_info['Title'];
				}
			}
		}
		?>
				<tr data-slim-handle="<?php echo esc_html( $handle ); ?>">
					<td class='swis-slim-asset-status'>
						<?php $this->display_asset_status( $handle, $asset_type ); ?>
					</td>
					<td class='swis-slim-asset-details'>
						<a class='swis-slim-link' href='<?php echo esc_url( $asset['url'] ); ?>' target='_blank'>
							<?php echo $asset['external'] ? esc_html( $asset['url'] ) : esc_html( $asset['filename'] ); ?>
						</a>
						<div class='swis-slim-info'>
					<?php if ( ! empty( $asset['plugin_title'] ) ) : ?>
							<div><strong><?php esc_html_e( 'Plugin:', 'swis-performance' ); ?></strong> <?php echo esc_html( $asset['plugin_title'] ); ?></div>
					<?php endif; ?>
							<div><?php echo '<strong>' . esc_html__( 'Handle:', 'swis-performance' ) . '</strong> ' . esc_html( $handle ); ?></div>
					<?php if ( ! empty( $asset['deps'] ) ) : ?>
							<div><?php echo '<strong>' . esc_html__( 'Requires:', 'swis-performance' ) . '</strong> ' . esc_html( implode( ', ', $asset['deps'] ) ); ?></div>
					<?php endif; ?>
					<?php if ( ! empty( $dependents ) ) : ?>
							<div><?php echo '<strong>' . esc_html__( 'Required by:', 'swis-performance' ) . '</strong> ' . esc_html( implode( ', ', $dependents ) ); ?></div>
					<?php endif; ?>
					<?php if ( 'inline-scripts' !== $handle ) : ?>
						<?php $this->display_frontend_rule_form( $handle, $disable_rule, 'disable', $asset_type, $asset ); ?>
					<?php endif; ?>
					<?php if ( 'css' === $asset_type && $this->get_option( 'defer_css' ) && ! $this->asset_disabled_everywhere( $asset_type, $handle ) ) : ?>
						<?php $this->display_frontend_rule_form( $handle, $defer_rule, 'defer', $asset_type, $asset ); ?>
					<?php endif; ?>
					<?php if ( 'js' === $asset_type && $this->get_option( 'defer_js' ) && ! $this->asset_disabled_everywhere( $asset_type, $handle ) && ! $this->asset_delayed_everywhere( $asset_type, $handle ) ) : ?>
						<?php $this->display_frontend_rule_form( $handle, $defer_rule, 'defer', $asset_type, $asset ); ?>
					<?php endif; ?>
					<?php if ( 'js' === $asset_type && ! $this->asset_disabled_everywhere( $asset_type, $handle ) ) : ?>
						<?php $this->display_frontend_rule_form( $handle, $delay_rule, 'delay', $asset_type, $asset ); ?>
					<?php endif; ?>
						</div>
					</td>
					<td class='swis-slim-asset-size'>
						<?php echo esc_html( $asset['size'] ); ?>
					</td>
				</tr>
		<?php
	}

	/**
	 * Display the asset status for a specific JS/CSS asset.
	 *
	 * @param string $handle The handle/slug of the asset.
	 * @param string $asset_type The asset type (js/css).
	 */
	private function display_asset_status( $handle, $asset_type = '' ) {
		// Need to retool this to reflect Active, Deferred, Disabled, Delayed...
		if ( $this->asset_affected( 'disable', $handle, $asset_type ) ) {
			echo "<span class='swis-slim-status-disabled'>" . esc_html__( 'Disabled', 'swis-performance' ) . '</span>';
		} elseif ( $this->asset_affected( 'delay', $handle, $asset_type ) ) {
			echo "<span class='swis-slim-status-delayed'>" . esc_html__( 'Delayed', 'swis-performance' ) . '</span>';
		} elseif (
			(
				! isset( $this->defer_exclusions[ $handle ] ) ||
				$this->asset_affected( 'defer', $handle, $asset_type )
			) &&
			(
				'js' === $asset_type && $this->get_option( 'defer_js' ) ||
				'css' === $asset_type && $this->get_option( 'defer_css' ) ||
				( $this->get_option( 'defer_js' ) && $this->get_option( 'defer_css' ) )
			)
		) {
			echo "<span class='swis-slim-status-deferred'>" . esc_html__( 'Deferred', 'swis-performance' ) . '</span>';
		} else {
			echo "<span class='swis-slim-status-active'>" . esc_html__( 'Active', 'swis-performance' ) . '</span>';
		}
	}

	/**
	 * Display the HTML for a given rule on the settings.
	 *
	 * @param string $handle The CSS/JS handle.
	 * @param array  $rule Parsed rule for the given $handle.
	 * @param string $type Type of rule being handled: disable, defer, or delay.
	 * @param string $asset_type The asset type (js/css).
	 * @param array  $asset Asset info for the given $handle.
	 */
	private function display_frontend_rule_form( $handle, $rule, $type, $asset_type, $asset = array() ) {
		$rule_id = preg_replace( '/[\W_]/', '', uniqid( '', true ) );
		if ( 'disable' === $type ) {
			$effective          = ! empty( $asset['disabled'] ) ? true : $this->asset_affected( $type, $handle, $asset_type );
			$exclude_radio_text = __( 'disable on this page', 'swis-performance' );
			$include_radio_text = __( 'disable everywhere except this page', 'swis-performance' );
			$all_radio_text     = __( 'disable everywhere', 'swis-performance' );
		} elseif ( 'defer' === $type ) {
			$effective          = ! empty( $asset['deferred'] ) ? true : $this->asset_affected( $type, $handle, $asset_type );
			$exclude_radio_text = __( 'defer only on this page', 'swis-performance' );
			$include_radio_text = __( 'defer everywhere except this page', 'swis-performance' );
			$all_radio_text     = __( 'defer everywhere', 'swis-performance' );
		} elseif ( 'delay' === $type ) {
			$effective          = ! empty( $asset['delayed'] ) ? true : $this->asset_affected( $type, $handle, $asset_type );
			$exclude_radio_text = __( 'delay on this page', 'swis-performance' );
			$include_radio_text = __( 'delay everywhere except this page', 'swis-performance' );
			$all_radio_text     = __( 'delay everywhere', 'swis-performance' );
		} else {
			echo '<form class="swis-slim-rule">invalid rule type, if you see this, report it to support</form>';
			return;
		}
		if ( isset( $rule['raw'] ) && is_array( $rule['raw'] ) && ! empty( $rule['raw'][0] ) && is_string( $rule['raw'][0] ) ) {
			$rule['raw'] = $rule['raw'][0];
		}
		?>
		<form class="swis-slim-rule" data-slim-rule-type="<?php echo esc_attr( $type ); ?>" data-slim-handle="<?php echo esc_attr( $handle ); ?>" data-slim-rule-id="<?php echo esc_attr( $rule_id ); ?>">
			<input type="hidden" name="swis_slim_asset_type" value="<?php echo esc_attr( $asset_type ); ?>" />
		<?php if ( ! empty( $rule['include'] ) ) : ?>
			<?php
			$includes = array();
			foreach ( $rule['include'] as $include ) {
				if ( 0 === strpos( $include, 'T>' ) ) {
					$includes[] = '<i>' . substr( $include, 2 ) . '</i> ' . esc_html__( 'content type', 'swis-performance' );
				} else {
					$includes[] = $include;
				}
			}
			?>
			<div class="swis-slim-rule-description">
				<div class="swis-slim-rule-prefix">
					<?php if ( 'disable' === $type ) : ?>
						<?php esc_html_e( 'Disabled everywhere except:', 'swis-performance' ); ?>
					<?php elseif ( 'defer' === $type ) : ?>
						<?php esc_html_e( 'Defer everywhere except:', 'swis-performance' ); ?>
					<?php elseif ( 'delay' === $type ) : ?>
						<?php esc_html_e( 'Delay everywhere except:', 'swis-performance' ); ?>
					<?php endif; ?>
				</div>
				<input style="display:none;" type="radio" id="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="include" checked />
				<div class="swis-slim-pretty-rule">
					<?php echo wp_kses_post( implode( ', ', $includes ) ); ?>
				</div>
				<div class="swis-slim-error-message"></div>
				<div class="swis-slim-hidden">
					<div class="swis-slim-raw-rule">
						<input type="text" id="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" name="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" class="swis-slim-rule-exclusions" value="<?php echo esc_attr( $rule['raw'] . ( $effective ? ',' . $this->current_page : '' ) ); ?>" />
					</div>
					<p class="swis-slim-edit-rule-description description">
						<span>
							<?php esc_html_e( 'Comma-separated list of pages, Regex (must use *), or content types in the form T>post or T>page.', 'swis-performance' ); ?>
							<?php $this->help_link( 'https://docs.ewww.io/article/134-customize-slim-rules' ); ?>
						</span>
						<button type="button" class="button-primary swis-slim-rule-save"><?php esc_html_e( 'Save', 'swis-performance' ); ?></button>
					</p>
				</div>
			</div>
			<div class="swis-slim-rule-actions">
				<button type="button" class="button-secondary button-link-edit"><?php ( $effective ? esc_html_e( 'Add', 'swis-performance' ) : esc_html_e( 'Edit', 'swis-performance' ) ); ?></button>
				&nbsp;&nbsp;
				<button type="button" class="button-danger button-link-delete"><?php esc_html_e( 'Delete', 'swis-performance' ); ?></button>
			</div>
		<?php elseif ( ! empty( $rule['exclude'] ) ) : ?>
			<?php
			$excludes = array();
			foreach ( $rule['exclude'] as $exclude ) {
				if ( 0 === strpos( $exclude, 'T>' ) ) {
					$excludes[] = '<i>' . substr( $exclude, 2 ) . '</i> ' . esc_html__( 'content type', 'swis-performance' );
				} else {
					$excludes[] = $exclude;
				}
			}
			?>
			<div class="swis-slim-rule-description">
				<div class="swis-slim-rule-prefix">
					<?php if ( 'disable' === $type ) : ?>
						<?php esc_html_e( 'Disabled on:', 'swis-performance' ); ?>
					<?php elseif ( 'defer' === $type ) : ?>
						<?php esc_html_e( 'Defer on:', 'swis-performance' ); ?>
					<?php elseif ( 'delay' === $type ) : ?>
						<?php esc_html_e( 'Delay on:', 'swis-performance' ); ?>
					<?php endif; ?>
				</div>
				<input style="display:none;" type="radio" id="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="exclude" checked />
				<div class="swis-slim-pretty-rule">
					<?php echo wp_kses_post( implode( ', ', $excludes ) ); ?>
				</div>
				<div class="swis-slim-error-message"></div>
				<div class="swis-slim-hidden">
					<div class="swis-slim-raw-rule">
						<input type="text" id="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" name="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" class="swis-slim-rule-exclusions" value="<?php echo esc_attr( $rule['raw'] . ( $effective ? '' : ',' . $this->current_page ) ); ?>" />
					</div>
					<p class="swis-slim-edit-rule-description description">
						<span>
							<?php esc_html_e( 'Comma-separated list of pages, Regex (must use *), or content types in the form T>post or T>page.', 'swis-performance' ); ?>
							<?php $this->help_link( 'https://docs.ewww.io/article/134-customize-slim-rules' ); ?>
						</span>
						<button type="button" class="button-primary swis-slim-rule-save"><?php esc_html_e( 'Save', 'swis-performance' ); ?></button>
					</p>
				</div>
			</div>
			<div class="swis-slim-rule-actions">
				<button type="button" class="button-secondary button-link-edit"><?php ( $effective ? esc_html_e( 'Edit', 'swis-performance' ) : esc_html_e( 'Add', 'swis-performance' ) ); ?></button>
				&nbsp;&nbsp;
				<button type="button" class="button-danger button-link-delete"><?php esc_html_e( 'Delete', 'swis-performance' ); ?></button>
			</div>
		<?php elseif ( ! empty( $rule['handle'] ) ) : ?>
			<div class="swis-slim-rule-description">
				<div class="swis-slim-rule-prefix">
					<?php if ( 'disable' === $type ) : ?>
						<?php esc_html_e( 'Disabled everywhere', 'swis-performance' ); ?>
					<?php elseif ( 'defer' === $type ) : ?>
						<?php esc_html_e( 'Deferred everywhere', 'swis-performance' ); ?>
					<?php elseif ( 'delay' === $type ) : ?>
						<?php esc_html_e( 'Delayed everywhere', 'swis-performance' ); ?>
					<?php endif; ?>
				</div>
				<input style="display:none;" type="radio" id="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="all" checked />
				<div class="swis-slim-error-message"></div>
			</div>
			<div class="swis-slim-rule-actions">
				<button type="button" class="button-danger button-link-delete"><?php esc_html_e( 'Delete', 'swis-performance' ); ?></button>
			</div>
		<?php else : ?>
			<div class="swis-slim-rule-description swis-slim-column">
				<?php if ( 'jquery-migrate' === $handle && ! empty( $this->user_exclusions['jquery-core'] ) ) : ?>
					<?php esc_html_e( 'Rules for jquery/jquery-core will automatically apply to jquery-migrate.', 'swis-performance' ); ?>
				<?php endif; ?>
				<div class="swis-slim-error-message"></div>
				<div class="swis-slim-row">
					<div class="swis-slim-reversible">
						<div class="swis-slim-row">
							<input type="radio" id="swis_slim_mode_exclude_<?php echo esc_attr( $rule_id ); ?>" class="swis-slim-radio swis-slim-mode-exclude" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="exclude" />
							<strong><label for="swis_slim_mode_exclude_<?php echo esc_attr( $rule_id ); ?>"><?php echo esc_html( $exclude_radio_text ); ?></label></strong>
						</div>
						<div class="swis-slim-row">
							<input type="radio" id="swis_slim_mode_include_<?php echo esc_attr( $rule_id ); ?>" class="swis-slim-radio swis-slim-mode-include" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="include" />
							<strong><label for="swis_slim_mode_include_<?php echo esc_attr( $rule_id ); ?>"><?php echo esc_html( $include_radio_text ); ?></label></strong>
						</div>
			<?php if ( 'defer' !== $type ) : ?>
						<div class="swis-slim-row">
							<input type="radio" id="swis_slim_mode_all_<?php echo esc_attr( $rule_id ); ?>" class="swis-slim-radio swis-slim-mode-all" name="swis_slim_mode_<?php echo esc_attr( $rule_id ); ?>" value="all" />
							<strong><label for="swis_slim_mode_all_<?php echo esc_attr( $rule_id ); ?>"><?php echo esc_html( $all_radio_text ); ?></label></strong>
						</div>
			<?php endif; ?>
					</div>
					<div class="swis-slim-rule-actions">
						<button type="button" class="button-secondary swis-slim-rule-customize"><?php esc_html_e( 'Customize Rule', 'swis-performance' ); ?></button>
						&nbsp;&nbsp;
						<button type="button" class="button-primary swis-slim-rule-add"><?php esc_html_e( 'Add Rule', 'swis-performance' ); ?></button>
					</div>
				</div>
				<div class="swis-slim-column swis-slim-raw-rule">
					<input style="display:none;" type="text" id="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" name="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>" class="swis-slim-rule-exclusions" value="<?php echo esc_attr( $this->current_page ); ?>" />
					<label style="display:none;" for="swis-slim-rule-exclusions-<?php echo esc_attr( $rule_id ); ?>">
						<?php esc_html_e( 'Comma-separated list of pages, Regex (must use *), or content types in the form T>post or T>page.', 'swis-performance' ); ?>
						<?php $this->help_link( 'https://docs.ewww.io/article/134-customize-slim-rules' ); ?>
					</label>
				</div>
			</div>
		<?php endif; ?>
		</form>
		<?php
	}
}

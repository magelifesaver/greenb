<?php
/**
 * Addons Integration.
 */

namespace PremiumAddons\Includes;

use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Modules\Premium_Equal_Height\Module as Equal_Height;
use PremiumAddons\Modules\PA_Display_Conditions\Module as Display_Conditions;
use PremiumAddons\Modules\PremiumSectionFloatingEffects\Module as Floating_Effects;
use PremiumAddons\Modules\Woocommerce\Module as Woocommerce;
use PremiumAddons\Modules\PremiumGlobalTooltips\Module as GlobalTooltips;
use PremiumAddons\Modules\PremiumShapeDivider\Module as Shape_Divider;
use PremiumAddons\Modules\PremiumWrapperLink\Module as Wrapper_Link;
use PremiumAddons\Modules\PremiumGlassmorphism\Module as Glassmorphism;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Class Addons_Integration.
 */
class Addons_Integration {

	/**
	 * Class instance
	 *
	 * @var instance
	 */
	private static $instance = null;

	/**
	 * CSS Content
	 *
	 * @var css_content
	 */
	private static $css_content = null;

	/**
	 * Modules
	 *
	 * @var modules
	 */
	private static $modules = null;

	/**
	 * Integrations Keys
	 *
	 * @var integrations
	 */
	private static $integrations = null;

	/**
	 * Initialize integration hooks
	 *
	 * @return void
	 */
	public function __construct() {

		if ( ! Helper_Functions::check_elementor_version() ) {
			return;
		}

		Premium_Template_Tags::getInstance();

		if ( null === self::$modules ) {
			self::$modules = Admin_Helper::get_enabled_elements();
			self::$integrations = Admin_Helper::get_integrations_settings();
		}

		$this->register_hooks();

		$this->load_extensions();

		// Promote PAPRO Elements.
		add_filter( 'elementor/editor/localize_settings', array( $this, 'add_papro_elements' ) );

		// Handle everything related to widgets assets.
		Assets_Manager::get_instance( self::$modules, self::$integrations );

		// Handle everything related to feedback.
		Helpers\Element_Feedback::get_instance();
	}

	/**
	 * Register Hooks
	 *
	 * @since 4.11.53
	 * @access public
	 */
	public function register_hooks() {

		// Load Editor CSS Files.
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_preview_styles' ) );
		add_action( 'elementor/editor/before_enqueue_styles', array( $this, 'enqueue_editor_styles' ) );


		// Load Editor JS Files.
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'before_enqueue_scripts' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'after_enqueue_scripts' ) );

		// AJAX Handlers.
		add_action( 'wp_ajax_get_elementor_template_content', array( $this, 'get_template_content' ) );
		add_action( 'wp_ajax_nopriv_get_elementor_template_content', array( $this, 'get_template_content' ) );

		// Social Feed AJAX Handlers.
		add_action( 'wp_ajax_get_pinterest_token', array( $this, 'get_pinterest_token' ) );
		add_action( 'wp_ajax_get_pinterest_boards', array( $this, 'get_pinterest_boards' ) );
		add_action( 'wp_ajax_get_tiktok_token', array( $this, 'get_tiktok_token' ) );

		// Register Controls and Widgets Area.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_widgets_category' ), 9 );
		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

	}

	/**
	 * After Enqueue Scripts
	 *
	 * Loads editor scripts for our controls.
	 *
	 * @access public
	 * @return void
	 */
	public function after_enqueue_scripts() {

		wp_enqueue_script(
			'pa-controls-handlers',
			PREMIUM_ADDONS_URL . 'assets/editor/js/controls-handlers.js',
			array( 'elementor-editor', 'jquery' ),
			PREMIUM_ADDONS_VERSION,
			true
		);

		wp_localize_script(
			'pa-controls-handlers',
			'PremiumSettings',
			array(
				'ajaxurl'      => esc_url( admin_url( 'admin-ajax.php' ) ),
				'nonce'        => wp_create_nonce( 'pa-blog-widget-nonce' ),
				'upgrade_link' => Helper_Functions::get_campaign_link( 'https://premiumaddons.com/pro/', '', 'wp-editor', 'get-pro' ),
				// 'unused_nonce' => wp_create_nonce( 'pa-disable-unused' ),
			)
		);

		$data = array(
			'ajaxurl' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'nonce'   => wp_create_nonce( 'pa-editor' ),
		);

		wp_enqueue_script(
			'pa-editor-handler',
			PREMIUM_ADDONS_URL . 'assets/editor/js/editor-handler.js',
			array( 'elementor-editor' ),
			PREMIUM_ADDONS_VERSION,
			true
		);

		wp_localize_script( 'pa-editor-handler', 'paEditorSettings', $data );

	}

	/**
	 * Loads plugin icons font
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue_editor_styles() {

		$theme = Helper_Functions::get_elementor_ui_theme();

		wp_enqueue_style(
			'pa-editor',
			PREMIUM_ADDONS_URL . 'assets/editor/css/style.css',
			array(),
			PREMIUM_ADDONS_VERSION
		);

		// Enqueue required style for Elementor dark UI Theme.
		if ( 'dark' === $theme ) {

			wp_add_inline_style(
				'pa-editor',
				'.elementor-panel .elementor-control-section_pa_docs .elementor-panel-heading-title.elementor-panel-heading-title,
				.elementor-control-raw-html.editor-pa-doc a {
					color: #e0e1e3 !important;
				}
				[class^="pa-"]::after,
				[class*=" pa-"]::after {
					color: #aaa;
                    opacity: 1 !important;
				}
                .premium-promotion-dialog .premium-promotion-btn {
                    background-color: #202124 !important
                }
				.premium-promote-ctas .elementor-button.premium-promote-upgrade:hover {
					background-color: #999C9E !important;
				}
				.premium-promote-ctas .elementor-button.premium-promote-demo {
                    color: #BFC3C7 !important;
					background-color: #373C41 !important;
					border: 1px solid rgba(154, 157, 160, 0.5) !important;
                }

				.premium-promote-ctas .elementor-button.premium-promote-demo:hover {
					background-color: #2B2E32 !important;
                }'
			);

		}

		$badge_text = Helper_Functions::get_badge();

		$dynamic_css = sprintf( '.elementor-element-wrapper:not(.elementor-element--promotion) [class^="pa-"]::after, .elementor-element-wrapper:not(.elementor-element--promotion) [class*="  pa-"]::after { content: "%s"; }', $badge_text );

		wp_add_inline_style( 'pa-editor', $dynamic_css );
	}

	/**
	 * Register Old Scripts
	 *
	 * @since 4.9.0
	 * @access public
	 *
	 * @param string $directory script directory.
	 * @param string $suffix file suffix.
	 */
	public function register_old_scripts( $directory, $suffix ) {

		wp_register_script(
			'premium-addons',
			PREMIUM_ADDONS_URL . 'assets/frontend/' . $directory . '/premium-addons' . $suffix . '.js',
			array( 'jquery' ),
			PREMIUM_ADDONS_VERSION,
			true
		);
	}

	/**
	 * Enqueue Preview CSS files
	 *
	 * @since 2.9.0
	 * @access public
	 */
	public function enqueue_preview_styles() {

		wp_enqueue_style(
			'pa-preview',
			PREMIUM_ADDONS_URL . 'assets/editor/templates/css/preview.css',
			array(),
			PREMIUM_ADDONS_VERSION,
			'all'
		);

		wp_enqueue_style( 'pa-prettyphoto' );

		wp_enqueue_style( 'premium-addons' );

		wp_enqueue_style( 'pa-slick' );
	}

	/**
	 * Register Widgets Category
	 *
	 * Register a new category for Premium Addons widgets
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param object $elements_manager elements manager.
	 */
	public function register_widgets_category( $elements_manager ) {

		$elements_manager->add_category(
			'premium-elements',
			array(
				'title' => Helper_Functions::get_category(),
			),
			1
		);
	}

	/**
	 * Load widgets require function
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_widgets( $widgets_manager ) {

		$enabled_elements = Helper_Functions::get_enabled_widgets();

		foreach ( $enabled_elements as $key ) {

			$class = Helper_Functions::get_widget_class_name( $key );

			if( ! $class ) {
				continue;
			}

			if( 'PremiumAddons\Widgets\Premium_Contactform' === $class && ! function_exists( 'wpcf7' ) ) {
				continue;
			}

			$this->load_widget_files( $class );

			$widgets_manager->register( new $class() );

		}
	}

	public function load_widget_files( $class ) {

		if ( 'PremiumAddons\Widgets\Premium_Videobox' === $class || 'PremiumAddons\Widgets\Premium_Weather' === $class ) {
			require_once PREMIUM_ADDONS_PATH . 'widgets/dep/urlopen.php';
		}

		if ( 'PremiumAddons\Widgets\Premium_Weather' === $class ) {
			require_once PREMIUM_ADDONS_PATH . 'widgets/dep/pa-weather-handler.php';
		}

		if ( in_array( $class, array( 'PremiumAddons\Widgets\Premium_Pinterest_Feed', 'PremiumAddons\Widgets\Premium_Tiktok_Feed' ), true ) ) {

			if ( 'PremiumAddons\Widgets\Premium_Pinterest_Feed' == $class ) {
				require_once PREMIUM_ADDONS_PATH . 'widgets/dep/pa-pins-handler.php';
			}

			if ( 'PremiumAddons\Widgets\Premium_Tiktok_Feed' == $class ) {
				require_once PREMIUM_ADDONS_PATH . 'widgets/dep/pa-tiktok-handler.php';
			}
		}
	}

	/**
	 * Enqueue editor scripts
	 *
	 * @since 3.2.5
	 * @access public
	 */
	public function before_enqueue_scripts() {

		wp_enqueue_script(
			'pa-editor-behavior',
			PREMIUM_ADDONS_URL . 'assets/editor/js/pa-editor-behavior.min.js',
			array( 'elementor-editor', 'jquery' ),
			PREMIUM_ADDONS_VERSION,
			true
		);

		$data = array(
			'ajaxurl' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'nonce'   => wp_create_nonce( 'pa-disable-unused' ),
			'disable_unused_link' => add_query_arg(
				array(
					'page'      => 'premium-addons',
					'pa-action' => 'unused',
					'#tab'      => 'elements',
				),
				esc_url( admin_url( 'admin.php' ) )
			)

		);

		wp_localize_script( 'pa-editor-behavior', 'paEditorBehaviorSettings', $data );

		$map_enabled = isset( self::$modules['premium-maps'] ) ? self::$modules['premium-maps'] : 1;

		if ( $map_enabled ) {

			$premium_maps_api = self::$integrations['premium-map-api'];

			$locale = self::$integrations['premium-map-locale'] ?? 'en';

			$disable_api = self::$integrations['premium-map-disable-api'];

			if ( $disable_api && '1' !== $premium_maps_api ) {

				$api = sprintf( 'https://maps.googleapis.com/maps/api/js?key=%1$s&libraries=marker&language=%2$s&loading=async', $premium_maps_api, $locale );
				wp_enqueue_script(
					'pa-maps-api',
					$api,
					array(),
					PREMIUM_ADDONS_VERSION,
					false
				);

			}

			wp_enqueue_script(
				'pa-maps-finder',
				PREMIUM_ADDONS_URL . 'assets/editor/js/pa-maps-finder.js',
				array( 'jquery' ),
				PREMIUM_ADDONS_VERSION,
				true
			);

		}
	}

	/**
	 * Get Pinterest account token for Pinterest Feed widget
	 *
	 * @since 4.10.2
	 * @access public
	 *
	 * @return void
	 */
	public function get_pinterest_token() {

		check_ajax_referer( 'pa-editor', 'security' );

		$api_url = 'https://appfb.premiumaddons.com/wp-json/fbapp/v2/pinterest';

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'   => 15,
				'sslverify' => true,
			)
		);

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		wp_send_json_success( $body );
	}

	/**
	 * Get Pinterest account token for Pinterest Feed widget
	 *
	 * @since 4.10.2
	 * @access public
	 *
	 * @return void
	 */
	public function get_pinterest_boards() {

		check_ajax_referer( 'pa-blog-widget-nonce', 'nonce' );

		if ( ! isset( $_GET['token'] ) ) {
			wp_send_json_error();
		}

		$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );

		$transient_name = 'pa_pinterest_boards_' . substr( $token, 0, 15 );

		$body = get_transient( $transient_name );

		if ( false === $body ) {

			$api_url = 'https://api.pinterest.com/v5/boards?page_size=60';

			$response = wp_remote_get(
				$api_url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
					),
				)
			);

			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );

			set_transient( $transient_name, $body, 30 * MINUTE_IN_SECONDS );

		}

		$boards = array();

		foreach ( $body['items'] as $index => $board ) {
			$boards[ $board['id'] ] = $board['name'];
		}

		wp_send_json_success( wp_json_encode( $boards ) );
	}

	/**
	 * Get Pinterest account token for Pinterest Feed widget
	 *
	 * @since 4.10.2
	 * @access public
	 *
	 * @return void
	 */
	public function get_tiktok_token() {

		check_ajax_referer( 'pa-editor', 'security' );

		$api_url = 'https://appfb.premiumaddons.com/wp-json/fbapp/v2/tiktok';

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		wp_send_json_success( $body );
	}

	/**
	 * Get Template Content
	 *
	 * Get Elementor template HTML content.
	 *
	 * @since 3.2.6
	 * @access public
	 */
	public function get_template_content() {

		$template = isset( $_GET['templateID'] ) ? sanitize_text_field( wp_unslash( $_GET['templateID'] ) ) : '';
		$is_ID    = isset( $_GET['is_id'] ) ? filter_var( $_GET['is_id'], FILTER_VALIDATE_BOOLEAN ) : false;

		if ( empty( $template ) ) {
			wp_send_json_error( 'Empty Template ID' );
		}

		// Get the post object to check status and author
		$post = get_post( $template );

		if ( ! $post ) {
			wp_send_json_error( 'Invalid Template ID' );
		}

		// Check if post is published or user has permission to view
		if ( 'publish' !== $post->post_status ) {
			$current_user_id = get_current_user_id();
			$is_author = ( $current_user_id === (int) $post->post_author );
			$is_admin = current_user_can( 'manage_options' );

			if ( ! $is_admin && ! $is_author ) {
				wp_send_json_error( 'Permission denied' );
			}
		}

		$template_content = Helper_Functions::render_elementor_template( $template, $is_ID );

		if ( empty( $template_content ) || ! isset( $template_content ) ) {
			wp_send_json_error( 'Empty Content' );
		}

		$data = array(
			'template_content' => $template_content,
		);

		wp_send_json_success( $data );
	}

	/**
	 * Registers Premium Addons Custom Controls.
	 *
	 * @since 4.2.5
	 * @access public
	 *
	 * @return void
	 */
	public function register_controls() {

		$control_manager = \Elementor\Plugin::instance();

		if ( self::$modules['premium-equal-height'] || self::$modules['premium-pinterest-feed'] ) {

			require_once PREMIUM_ADDONS_PATH . 'includes/controls/premium-select.php';
			$premium_select = __NAMESPACE__ . '\Controls\Premium_Select';
			$control_manager->controls_manager->register( new $premium_select() );

		}

		require_once PREMIUM_ADDONS_PATH . 'includes/controls/premium-post-filter.php';

		$premium_post_filter = __NAMESPACE__ . '\Controls\Premium_Post_Filter';

		$control_manager->controls_manager->register( new $premium_post_filter() );

		if ( self::$modules['premium-blog'] || self::$modules['premium-smart-post-listing'] || self::$modules['premium-tcloud'] ) {

			require_once PREMIUM_ADDONS_PATH . 'includes/controls/premium-tax-filter.php';

			$premium_tax_filter = __NAMESPACE__ . '\Controls\Premium_Tax_Filter';

			$control_manager->controls_manager->register( new $premium_tax_filter() );
		}

		if ( self::$modules['pa-display-conditions'] ) {

			require_once PREMIUM_ADDONS_PATH . 'includes/controls/premium-acf-selector.php';
			$premium_acf_selector = __NAMESPACE__ . '\Controls\Premium_Acf_Selector';
			$control_manager->controls_manager->register( new $premium_acf_selector() );

		}

		if ( self::$modules['premium-shape-divider'] ) {

			require_once PREMIUM_ADDONS_PATH . 'includes/controls/pa-image-choose.php';
			$premium_image_choose = __NAMESPACE__ . '\Controls\Premium_Image_Choose';
			$control_manager->controls_manager->register( new $premium_image_choose() );

		}

		$premium_background = __NAMESPACE__ . '\Controls\Premium_Background';

		$control_manager->controls_manager->add_group_control( 'pa-background', new $premium_background() );
	}

	/**
	 * Load PA Extensions
	 *
	 * @since 4.7.0
	 * @access public
	 */
	public function load_extensions() {

		Extras\Live_Editor::get_instance();

		if ( self::$modules['premium-equal-height'] ) {
			Equal_Height::get_instance();
		}

		if ( self::$modules['premium-glassmorphism'] ) {
			Glassmorphism::get_instance();
		}

		if ( self::$modules['pa-display-conditions'] ) {
			require_once PREMIUM_ADDONS_PATH . 'widgets/dep/urlopen.php';
			$has_acf = class_exists( 'ACF' );
			$has_woo = class_exists( 'woocommerce' );
			Display_Conditions::get_instance( $has_acf, $has_woo );
		}

		if ( self::$modules['premium-floating-effects'] ) {
			Floating_Effects::get_instance();
		}

		if ( class_exists( 'woocommerce' ) && ( self::$modules['woo-products'] || self::$modules['woo-categories'] || self::$modules['mini-cart'] || self::$modules['woo-cta'] ) ) {
			Woocommerce::get_instance();
		}

		if ( self::$modules['premium-global-tooltips'] ) {
			GlobalTooltips::get_instance();
		}

		if ( self::$modules['premium-shape-divider'] ) {
			Shape_Divider::get_instance();
		}

		if ( self::$modules['premium-wrapper-link'] ) {
			Wrapper_Link::get_instance();
		}

		if ( self::$modules['premium-cross-domain'] ) {
			Extras\Cross_Copy_Paste::get_instance();
		}

		if ( self::$modules['premium-contactform'] && function_exists( 'wpcf7' ) ) {
			Extras\CF7_Inserter::get_instance();
		}

		if ( ! Helper_Functions::check_papro_version() ) {
			Helpers\PAPRO_Promotion::get_instance();
		}

	}

	/**
	 * Add PAPRO Elements
	 *
	 * @since 4.10.90
	 * @access public
	 *
	 * @param array $config Elementor Config
	 */
	public function add_papro_elements( $config ) {

		$is_papro_active = Helper_Functions::check_papro_version();

		if ( $is_papro_active ) {
			return $config;
		}

		$promotion_widgets = array();

		if ( isset( $config['promotionWidgets'] ) ) {
			$promotion_widgets = $config['promotionWidgets'];
		}

		$pro_elements = Admin_Helper::get_pro_elements();

		$pro_elements = array_merge( $promotion_widgets, $pro_elements );

		$config['promotionWidgets'] = $pro_elements;

		// Fix promotion box not showing when Elementor Pro is active.
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$config['promotion']['elements']['action_button'] = array(
				'text' => 'Connect & Activate',
				'url'  => 'https://go.elementor.com/',
			);
		}

		return $config;
	}


	/**
	 *
	 * Creates and returns an instance of the class
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;
	}
}

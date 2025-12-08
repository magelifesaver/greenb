<?php
namespace Barn2\Plugin\WC_Fast_Cart\Integration;

use Barn2\Plugin\WC_Fast_Cart\Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Conditional;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Rest\Rest_Server;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Util as Lib_Util;

/**
 * Adds theme-specific integration
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Themes implements Registerable, Premium_Service {

	private $settings;
	private $theme;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = Util::get_settings();
		$this->theme    = strtolower( get_template() );

		// needs to be in constructor because 'register' is too late
		$this->register_theme_hooks();
	}

	public function register() {
		add_filter( 'wfc_should_load_front_end_assets', [ $this, 'elementor_compatibility' ], 20 );
	}

	/**
	 * Filter to disable WFC front-end assets on Elementor pages
	 *
	 * @param boolean $should_load
	 * @return boolean
	 */
	public function elementor_compatibility( $should_load ) {

		if ( ! class_exists( 'Elementor\Plugin' ) ) {
			return $should_load;
		}

		// the last condition should be unncessary but is a valuable fallbacks in some instances
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode()
			|| \Elementor\Plugin::$instance->preview->is_preview_mode()
			// phpcs:ignore WordPress.Security.NonceVerification
			|| isset( $_GET['elementor-preview'] ) ) {
			return false;
		}

		return $should_load;
	}

	/**
	 * Provides theme specific integration for various themes
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function register_theme_hooks() {
		switch ( $this->theme ) {

			case 'atelier':
				add_action(
					'wfc_checkout_before_content',
					function () {
						echo '<div id="container" style="padding-left:15px;padding-right:15px">';
						echo '<div id="main-container" class="clearfix">';
						echo '<div class="container>';
						echo '<div class="inner-page-wrap has-no-sidebar clearfix">';
						echo '<div class="clearfix">';
						echo '<div class="page-content hfeed clearfix">';
						echo '<div class="clearfix">';
					}
				);

				add_action(
					'wfc_checkout_after_content',
					function () {
						echo '</div>';
						echo '</div>';
						echo '</div>';
						echo '</div>';
						echo '</div>';
						echo '</div>';
						echo '</div>';
					}
				);

				add_action(
					'wp',
					function () {
						remove_action( 'wfc_checkout_before_content', [ 'Barn2\Plugin\WC_Fast_Cart\Frontend_Templates', 'open_checkout_wrapper' ], 10 );
						remove_action( 'wfc_checkout_after_content', [ 'Barn2\Plugin\WC_Fast_Cart\Frontend_Templates', 'close_checkout_wrapper' ], 10 );
					}
				);

				break;

			case 'avada':
				add_action(
					'wfc_pre_wp_head',
					function () {
						\Fusion::$instance->dynamic_js->init();
					}
				);

				add_filter(
					'wfc_default_button_classes',
					function () {
						return 'fusion-button-default button checkout-button';
					}
				);

				add_action(
					'wfc_checkout_before_content',
					function () {
						echo '<div id="boxed-wrapper" style="padding-left:15px;padding-right:15px">';
						echo '<div id="wrapper" class="fusion-wrapper">';
						echo '<main id="main" class="clearfix width-100" style="padding:0!important">';
						echo '<div class="fusion-row" style="max-width:100%;">';
						echo '<section id="content" style="width:100%">';
						echo '<div id="post-' . esc_attr( get_the_ID() ) . '" ' . esc_attr( implode( ' ', get_post_class() ) ) . '>';
						echo '<div class="post-content">';
					}
				);

				add_action(
					'wfc_checkout_after_content',
					function () {
						echo '</div>';
						echo '</div>';
						echo '</section>';
						echo '</div>';
						echo '</main>';
						echo '</div>';
						echo '</div>';
					}
				);

				add_action(
					'wp',
					function () {
						remove_action( 'wfc_checkout_before_content', [ 'Barn2\Plugin\WC_Fast_Cart\Frontend_Templates', 'open_checkout_wrapper' ], 10 );
						remove_action( 'wfc_checkout_after_content', [ 'Barn2\Plugin\WC_Fast_Cart\Frontend_Templates', 'close_checkout_wrapper' ], 10 );
					}
				);

				break;

			case 'bridge':
				add_action(
					'wp_loaded',
					function () {
						global $bridge_qode_options;
						$bridge_qode_options['smooth_scroll'] = 'no';
					}
				);
				add_filter(
					'option_qode_options_proya',
					function ( $options ) {
						if ( empty( $options ) ) {
							$options = [];
						}
						$options['smooth_scroll'] = 'no';
						return $options;
					},
					11
				);
				break;

			case 'buddyboss-theme':
				add_filter(
					'wfc_script_params',
					function ( $params ) {
						$params['selectors']['allowClickEventsOn'] = '.quantity-button';
						return $params;
					},
					1
				);

				break;

			case 'divi':
				add_action( 'wfc_checkout_before_template', [ $this, 'maybe_load_divi_builder' ] );
				break;

			case 'enfold':
				add_action(
					'wp',
					function () {
						remove_action( 'wfc_checkout_before_content', [ 'Barn2\Plugin\WC_Fast_Cart\Frontend_Templates', 'open_checkout_wrapper' ], 10 );
						remove_action( 'wfc_checkout_after_content', [ 'Barn2\Plugin\WC_Fast_Cart\Frontend_Templates', 'close_checkout_wrapper' ], 10 );
					}
				);

				add_action(
					'wfc_checkout_before_content',
					function () {

						$post_class = 'post-entry-' . avia_get_the_id();

						?>

					<div id="wrap_all" style="padding-left:15px;padding-right:15px">
					<div id="main" class="all_colors">
					<div class='container_wrap container_wrap_first main_color <?php avia_layout_class( 'main' ); ?>'>

					<main class='template-page content units' 
						<?php
						avia_markup_helper(
							[
								'context'   => 'content',
								'post_type' => 'page',
							]
						);
						?>
					>

					<article class='post-entry post-entry-type-page <?php echo esc_attr( $post_class ); ?>' <?php avia_markup_helper( [ 'context' => 'entry' ] ); ?>>
					<div class="entry-content-wrapper clearfix">

						<?php
					}
				);

				add_action(
					'wfc_checkout_after_content',
					function () {
						echo '</div>';
						echo '</article>';
						echo '</main>';

						echo '</div>';
						echo '</div>';
						echo '</div>';
					}
				);

				add_action(
					'wfc_checkout_body_tag',
					function () {
						echo ' id="top" ';
					}
				);

				break;

			case 'flatsome':
				add_action(
					'wp',
					function () {
						remove_action( 'wfc_checkout_before_content', [ 'Barn2\Plugin\WC_Fast_Cart\Frontend_Templates', 'open_checkout_wrapper' ], 10 );
						remove_action( 'wfc_checkout_after_content', [ 'Barn2\Plugin\WC_Fast_Cart\Frontend_Templates', 'close_checkout_wrapper' ], 10 );
					}
				);

				add_action(
					'wfc_checkout_before_content',
					function () {

						?>

					<div id="wrapper" style="padding-left:15px;padding-right:15px">
					<main id="main" class="<?php flatsome_main_classes(); ?>">

						<?php
					}
				);

				add_action(
					'wfc_checkout_after_content',
					function () {

						echo '</main>';
						echo '</div>';
					}
				);

				break;

			case 'jupiterx':
				add_action(
					'wfc_before_cart',
					function () {
						remove_action( 'woocommerce_proceed_to_checkout', 'jupiterx_wc_continue_shopping_button', 5 );
						remove_action( 'woocommerce_review_order_after_submit', 'jupiterx_wc_continue_shopping_button' );
					}
				);

				break;

			case 'genesis':
				add_action(
					'wfc_pre_wp_head',
					function () {
						do_action( 'genesis_doctype' );
						do_action( 'genesis_title' );
						do_action( 'genesis_meta' );
					}
				);

				break;

			case 'thrive-theme':
				add_action(
					'wfc_checkout_before_content',
					function () {
						if ( function_exists( 'thrive_template' ) ) {
							thrive_template()->render();
						}
					}
				);
				break;

		}
	}

	public function maybe_load_divi_builder() {

		if ( ! function_exists( 'et_theme_builder_overrides_layout' ) || ! defined( 'ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE' ) ) {
			return;
		}

		$override_body = et_theme_builder_overrides_layout( ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE );
		if ( ! $override_body ) {
			return;
		}

		remove_action( 'wfc_checkout_the_content', 'the_content', 10 );

		add_action( 'wfc_checkout_the_content', [ $this, 'load_divi_builder_content' ] );

	}

	public function load_divi_builder_content() {

		$layouts = et_theme_builder_get_template_layouts();

		et_theme_builder_frontend_render_body(
			$layouts[ ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ]['id'],
			$layouts[ ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ]['enabled'],
			$layouts[ ET_THEME_BUILDER_TEMPLATE_POST_TYPE ]
		);

	}
}

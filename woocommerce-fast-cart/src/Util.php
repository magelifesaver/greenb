<?php
namespace Barn2\Plugin\WC_Fast_Cart;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Admin\Settings_Util;
use function Barn2\Plugin\WC_Fast_Cart\wfc;

/**
 * Utility functions for WooCommerce Fast Cart
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Util {

	const OPTION_NAME = 'wc_fast_cart_settings';

	private static $settings = null;

	/**
	 * Returns the absolute value of a sanitized floating point number
	 *
	 * @since   v0.1
	 * @param   mixed   $val
	 * @return  float
	 */
	public static function absfloat( $val ) {
		return abs( floatval( $val ) );
	}


	/**
	 * Returns the combination of current user settings and default values
	 *
	 * @since   v0.1
	 * @param   bool  $force_refresh Setting to false will avoid using settings already in memory
	 * @return  array
	 */
	public static function get_settings( $force_refresh = false ) {
		if ( $force_refresh || is_null( self::$settings ) ) {
			$option         = get_option( self::OPTION_NAME, [] );
			self::$settings = self::convert_settings_from_wc_format( wp_parse_args( $option, self::get_default_settings() ) );

			/**
			 * Filter Fast Cart setting values.
			 *
			 * Provides the ability change user settings before they are used by Fast Cart.
			 *
			 * @since 1.0.0
			 *
			 * @param array $var Current settings to be returned by function, with default values already assigned.
			 * @param array $var Original user-defined settings found in options table.
			 */
			self::$settings = apply_filters( 'wfc_settings', self::$settings, $option );
		}
		return self::$settings;
	}


	/**
	 * Returns the default setting values
	 *
	 * @since   v0.1
	 * @return  array
	 */
	public static function get_default_settings() {

		return [
			'enable_cart_button'      => true,
			'cart_icon_position'      => 'bottom',
			'enable_auto_open'        => true,
			'replace_cart_page'       => false,
			'replace_checkout_page'   => false,
			'fast_cart_mode'          => 'side',
			'enable_fast_checkout'    => false,
			'enable_direct_checkout'  => false,
			'enable_autocomplete'     => false,
			'maps_api'                => '',
			'cart_show_headings'      => false,
			'cart_show_item_price'    => true,
			'cart_show_item_subtotal' => true,
			'cart_show_item_images'   => true,
			'cart_show_item_qty'      => true,
			'cart_show_item_del'      => true,
			'cart_show_coupons'       => true,
			'cart_show_cross_sells'   => true,
			'cart_show_cart_subtotal' => true,
			'cart_show_cart_shipping' => true,
			'cart_show_keep_shopping' => true,
			'cart_button_radius'      => '20',
			'cart_button_style'       => 'icon',
			'cart_button_text'        => '',
			'cart_icon_fill'          => '#FFFFFF',
			'cart_icon_bg'            => '#03A0C7',
			'cart_count_color'        => '#FFFFFF',
			'cart_count_bg'           => '#25b354',
			'checkout_btn_color'      => '#FFFFFF',
			'checkout_btn_bg'         => '#03A0C7',

		];
	}

	/**
	 * Converts 'yes'/'no' string values into boolean true/false
	 *
	 * @since   v0.1
	 * @param   array   $settings
	 * @return  array
	 */
	public static function convert_settings_from_wc_format( $settings ) {
		if ( empty( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $key => $setting ) {
			if ( 'yes' === $setting ) {
				$settings[ $key ] = true;
			} elseif ( 'no' === $setting ) {
				$settings[ $key ] = false;
			}
		}

		return $settings;
	}

	/**
	 * Converts boolean true/false into 'yes'/'no' string values
	 *
	 * @since   v0.1
	 * @param   array   $settings
	 * @return  array
	 */
	public static function convert_settings_to_wc_format( $settings ) {
		if ( empty( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $key => $setting ) {
			if ( true === $setting ) {
				$settings[ $key ] = 'yes';
			} elseif ( false === $setting ) {
				$settings[ $key ] = 'no';
			}
		}

		return $settings;
	}

	/**
	 * Retreives the absolute url of an asset contained in the plugin's assets folder
	 *
	 * @since   v0.1
	 * @param   string  $path   Default: '' path of asset relative to assets folder
	 * @return  string
	 */
	public static function get_asset_url( $path = '' ) {
		return plugins_url( 'assets/' . ltrim( $path, '/' ), wfc()->get_file() );
	}

	/**
	 * Retreives the absolute disk path of an asset contained in the plugin's assets folder
	 *
	 * @since   v0.1
	 * @param   string  $path   Default: '' path of asset relative to assets folder
	 * @return  string
	 */
	public static function get_asset_path( $path = '' ) {
		return trailingslashit( dirname( wfc()->get_file() ) ) . 'assets/' . ltrim( $path, '/' );
	}

	/**
	 * Adds plugin name to error string
	 *
	 * @since   v0.1
	 * @param   string  $error_message  original error message
	 * @return  string
	 */
	public static function format_rest_error( $error_message ) {
		return sprintf( '%s: %s', wfc()->get_name(), $error_message );
	}

	/**
	 * Searches for a user defined template file in the theme/wfc, plugin/templates folders, with that priority
	 *
	 * @since   v0.1
	 * @param   string  $template_name  name of template file, including .php extension
	 * @return  string  the absolute path of the template file
	 */
	public static function get_template_path( $template_name ) {

		return wc_locate_template( $template_name, WC()->template_path() . 'wfc/', wfc()->get_dir_path() . 'templates/' );
	}

	/**
	 * Outputs a user defined template file in the theme/wfc, plugin/templates folders, with that priority
	 *
	 * @since   v0.1
	 * @param   string  $template_name  name of template file, including .php extension
	 * @param   array   $args           pass arbitrary arguments to the template file
	 */
	public static function load_template( $template_name, $args = [] ) {
		wc_get_template( $template_name, $args, WC()->template_path() . 'wfc/', wfc()->get_dir_path() . 'templates/' );
	}

	/**
	 * Wrapper for wc_get_notices to account for various return formats
	 *
	 * @since   v0.1
	 * @param   string  $notice_type    type of notice to return
	 * @return  array
	 */
	public static function get_wc_notices( $notice_type ) {
		$notices = wc_get_notices( $notice_type );
		wc_clear_notices();

		// WC > 3.8 uses nested arrays for each notice.
		if ( ! empty( $notices ) && isset( $notices[0]['notice'] ) ) {
			$notices = wp_list_pluck( $notices, 'notice' );
		}

		return array_filter( $notices );
	}

	/**
	 * Returns the hexadecimal color code for either white or black, based on which has the highest contrast with a given color
	 *
	 * @since   v0.1
	 * @param   string  $hexcolor   a hexadecimal color code to compare contrast levels with, must include '#' at beginning of string
	 * @return  string
	 */
	public static function color_yiq( $hexcolor ) {

		if ( strlen( $hexcolor ) !== 7 || substr( $hexcolor, 0, 1 ) !== '#' ) {
			return '#000000';
		}

		$r = hexdec( substr( $hexcolor, 1, 2 ) );
		$g = hexdec( substr( $hexcolor, 3, 2 ) );
		$b = hexdec( substr( $hexcolor, 5, 2 ) );

		if ( $r === false || $r < 0 || $r > 255 || $g === false || $g < 0 || $g > 255 || $b === false || $b < 0 || $b > 255 ) {
			return '#000000';
		}

		$yiq = ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000;
		return ( $yiq >= 128 ) ? '#000000' : '#FFFFFF';
	}

	/**
	 * Get WooCommerce compatible list of settings
	 *
	 * @param [type] $plugin
	 * @return array
	 */
	public static function get_settings_list( $plugin ) {

		$wc_shop       = wc_get_page_id( 'shop' );
		$shop_page_url = $wc_shop ? get_permalink( $wc_shop ) : trailingslashit( get_bloginfo( 'url' ) ) . 'shop/';

		$plugin_settings = [
			[
				'id'    => 'fast-cart_settings_start',
				'type'  => 'settings_start',
				'class' => 'fast-cart-settings',
			],
			// License key settings.
			[
				'title' => __( 'Fast Cart', 'wc-fast-cart' ),
				'type'  => 'title',
				'id'    => 'fast_cart_license_section',
				'desc'  => '<p>' . __( 'The following options control the WooCommerce Fast Cart extension.', 'wc-fast-cart' ) . '<p>'
				. '<p>'
				. Settings_Util::get_help_links( $plugin )
				. '</p>',
			],
			$plugin->get_license_setting()->get_license_key_setting(),
			$plugin->get_license_setting()->get_license_override_setting(),
			[
				'type' => 'sectionend',
				'id'   => 'fast_cart_license_section',
			],
			[
				'title' => __( 'Content', 'wc-fast-cart' ),
				'type'  => 'title',
				'id'    => 'fast_cart_content_section',
			],
			[
				'title'   => __( 'Layout', 'wc-fast-cart' ),
				'type'    => 'radio',
				'id'      => self::OPTION_NAME . '[fast_cart_mode]',
				'options' => [
					'side'  => __( 'Side cart', 'wc-fast-cart' ),
					'modal' => __( 'Centered popup', 'wc-fast-cart' ),
				],
				'default' => 'side',
			],
			[
				'title'   => __( 'Auto open', 'wc-fast-cart' ),
				'type'    => 'checkbox',
				'id'      => self::OPTION_NAME . '[enable_auto_open]',
				'desc'    => __( 'Open after adding a product to the cart', 'wc-fast-cart' ),
				'default' => 'yes',
			],
			[
				'type'    => 'hidden',
				'id'      => self::OPTION_NAME . '[enable_fast_cart]',
				'default' => 'yes',
			],
			[
				'title'    => __( 'Allow fast checkout', 'wc-fast-cart' ),
				'type'     => 'checkbox',
				'id'       => self::OPTION_NAME . '[enable_fast_checkout]',
				'desc'     => __( 'Allow customers to check out in the fast cart popup', 'wc-fast-cart' ),
				'default'  => 'no',
				'desc_tip' => __( 'May not work with all themes -', 'wc-fast-cart' )
					. sprintf( ' <a target="_blank" href="%s">', add_query_arg( '_wfc-preview', 'wfc-checkout', $shop_page_url ) )
					. __( 'try it with yours', 'wc-fast-cart' )
					. '</a>',
			],
			[
				'title'   => __( 'Direct checkout', 'wc-fast-cart' ),
				'type'    => 'checkbox',
				'id'      => self::OPTION_NAME . '[enable_direct_checkout]',
				'desc'    => __( 'Skip the cart and show the checkout immediately', 'wc-fast-cart' ),
				'default' => 'no',
			],
			[
				'title'   => __( 'Autocomplete', 'wc-fast-cart' ),
				'type'    => 'checkbox',
				'id'      => self::OPTION_NAME . '[enable_autocomplete]',
				'desc'    => __( "Auto-fill the customer's address details in the fast checkout", 'wc-fast-cart' ),
				'default' => 'no',
			],
			[
				'title'       => __( 'Google API key', 'wc-fast-cart' ),
				'type'        => 'text',
				'id'          => self::OPTION_NAME . '[maps_api]',
				'placeholder' => 'Please enter an API key',
				'desc'        =>
					__( 'Enter your Google API key to use address autocomplete', 'wc-fast-cart' )
					. sprintf( '. <a target="_blank" href="%s">', 'https://barn2.com/kb/checkout-autocomplete/' )
					. __( 'Read more', 'wc-fast-cart' )
					. '</a>',
				'default'     => '',
			],
			[
				'type'    => 'hidden',
				'id'      => self::OPTION_NAME . '[maps_api_status]',
				'default' => '',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_item_images]',
				'desc'          => __( 'Show product images', 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => 'start',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_item_subtotal]',
				'desc'          => __( 'Show product subtotals', 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => '',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_item_price]',
				'desc'          => __( 'Show product prices', 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => '',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_item_qty]',
				'desc'          => __( 'Show quantity pickers', 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => '',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_item_del]',
				'desc'          => __( 'Show delete buttons', 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => '',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_coupons]',
				'desc'          => __( 'Show coupons', 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => '',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_cart_subtotal]',
				'desc'          => __( 'Show order subtotal', 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => '',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_cart_shipping]',
				'desc'          => __( 'Show shipping information', 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => '',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_cross_sells]',
				'desc'          => __( 'Show cross-sells', 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => '',
			],
			[
				'title'         => __( 'Cart contents', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[cart_show_keep_shopping]',
				'desc'          => __( "Show 'Keep Shopping' button", 'wc-fast-cart' ),
				'default'       => 'yes',
				'checkboxgroup' => 'end',
			],
			[
				'type' => 'sectionend',
				'id'   => 'fast_cart_content_section',
			],
			[
				'title' => __( 'Cart icon', 'wc-fast-cart' ),
				'type'  => 'title',
				'id'    => 'fast_cart_triggers_section',
			],
			[
				'type'    => 'radio',
				'id'      => self::OPTION_NAME . '[cart_icon_position]',
				'title'   => __( 'Position', 'wc-fast-cart' ),
				'default' => 'bottom',
				'options' => [
					'top'    => __( 'Top', 'wc-fast-cart' ),
					'center' => __( 'Center', 'wc-fast-cart' ),
					'bottom' => __( 'Bottom', 'wc-fast-cart' ),
					'none'   => __( 'Hidden', 'wc-fast-cart' ),
				],
			],
			[
				'type'    => 'select',
				'id'      => self::OPTION_NAME . '[cart_button_style]',
				'title'   => __( 'Button style', 'wc-fast-cart' ),
				'default' => 'icon',
				'options' => [
					'icon'      => __( 'Icon only', 'wc-fast-cart' ),
					'text'      => __( 'Text only', 'wc-fast-cart' ),
					'text_icon' => __( 'Icon and text', 'wc-fast-cart' ),
				],
			],
			[
				'type'    => 'text',
				'id'      => self::OPTION_NAME . '[cart_button_text]',
				'title'   => __( 'Button text', 'wc-fast-cart' ),
				'default' => 'Cart',
			],
			[
				'type' => 'sectionend',
				'id'   => 'fast_cart_triggers_section',
			],
			[
				'title' => __( 'Replace pages', 'wc-fast-cart' ),
				'type'  => 'title',
				'id'    => 'fast_cart_replace_section',
			],
			[
				'title'   => __( 'Replace cart page', 'wc-fast-cart' ),
				'type'    => 'checkbox',
				'id'      => self::OPTION_NAME . '[replace_cart_page]',
				'desc'    => __( 'Open when customers try to access the default cart page', 'wc-fast-cart' ),
				'default' => 'no',
			],
			[
				'title'   => __( 'Replace checkout page', 'wc-fast-cart' ),
				'type'    => 'checkbox',
				'id'      => self::OPTION_NAME . '[replace_checkout_page]',
				'desc'    => __( 'Open when customers try to access the default checkout page', 'wc-fast-cart' ),
				'default' => 'no',
			],
			[
				'type' => 'sectionend',
				'id'   => 'fast_cart_replace_section',
			],
			[
				'title' => __( 'Design', 'wc-fast-cart' ),
				'type'  => 'title',
				'id'    => 'fast_cart_design_section',
			],
			[
				'title'       => __( 'Cart icon color', 'wc-fast-cart' ),
				'type'        => 'color',
				'id'          => self::OPTION_NAME . '[cart_icon_fill]',
				'desc_tip'    => __( 'The primary color used for the floating cart button icon.', 'wc-fast-cart' ),
				'placeholder' => __( 'Color', 'wc-fast-cart' ),
				'class'       => 'custom-style ',
				'default'     => '#FFFFFF',
				'css'         => 'width:6.7em;margin-top:1px',
			],
			[
				'title'       => __( 'Cart button background', 'wc-fast-cart' ),
				'type'        => 'color',
				'id'          => self::OPTION_NAME . '[cart_icon_bg]',
				'desc_tip'    => __( 'The background color used for the floating cart button.', 'wc-fast-cart' ),
				'placeholder' => __( 'Color', 'wc-fast-cart' ),
				'class'       => 'custom-style ',
				'default'     => '#03A0C7',
				'css'         => 'width:6.7em;margin-top:1px',
			],
			[
				'title'       => __( 'Cart count text color', 'wc-fast-cart' ),
				'type'        => 'color',
				'id'          => self::OPTION_NAME . '[cart_count_color]',
				'desc_tip'    => __( 'The text color used for the number of items count.', 'wc-fast-cart' ),
				'placeholder' => __( 'Color', 'wc-fast-cart' ),
				'class'       => 'custom-style ',
				'default'     => '#FFFFFF',
				'css'         => 'width:6.7em;margin-top:1px',
			],
			[
				'title'       => __( 'Cart count background', 'wc-fast-cart' ),
				'type'        => 'color',
				'id'          => self::OPTION_NAME . '[cart_count_bg]',
				'desc_tip'    => __( 'The background color used for the number of items count.', 'wc-fast-cart' ),
				'placeholder' => __( 'Color', 'wc-fast-cart' ),
				'class'       => 'custom-style ',
				'default'     => '#25b354',
				'css'         => 'width:6.7em;margin-top:1px',
			],
			[
				'title'       => __( 'Cart button border radius', 'wc-fast-cart' ),
				'type'        => 'number',
				'id'          => self::OPTION_NAME . '[cart_button_radius]',
				'desc_tip'    => __( 'The border radius for the fast cart button in pixel.', 'wc-fast-cart' ),
				'placeholder' => __( 'in pixel', 'wc-fast-cart' ),
				'class'       => 'custom-style ',
				'default'     => '20',
				'css'         => 'width:6.7em;margin-top:1px',
			],
			[
				'title'       => __( 'Checkout button text color', 'wc-fast-cart' ),
				'type'        => 'color',
				'id'          => self::OPTION_NAME . '[checkout_btn_color]',
				'desc_tip'    => __( 'The text color used for the checkout button inside Fast Cart.', 'wc-fast-cart' ),
				'placeholder' => __( 'Color', 'wc-fast-cart' ),
				'class'       => 'custom-style ',
				'default'     => '#FFFFFF',
				'css'         => 'width:6.7em;margin-top:1px',
			],
			[
				'title'       => __( 'Checkout button background', 'wc-fast-cart' ),
				'type'        => 'color',
				'id'          => self::OPTION_NAME . '[checkout_btn_bg]',
				'desc_tip'    => __( 'The background color used for the checkout button inside Fast Cart.', 'wc-fast-cart' ),
				'placeholder' => __( 'Color', 'wc-fast-cart' ),
				'class'       => 'custom-style ',
				'default'     => '#03A0C7',
				'css'         => 'width:6.7em;margin-top:1px',
			],
			[
				'type' => 'sectionend',
				'id'   => 'fast_cart_design_section',
			],
			/*
			[
				'title' => __( 'Advanced', 'wc-fast-cart' ),
				'type'  => 'title',
				'id'    => 'fast_cart_advanced_section',
			],
			[
				'title'         => __( 'Send CORS Headers With AJAX Requests', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[send_cors_headers]',
				'desc'          => __( 'Not needed for most websites. Only enable if your cart has trouble updating after items are added.', 'wc-fast-cart' ),
				'default'       => 'no',
			],
			[
				'type' => 'sectionend',
				'id'   => 'fast_cart_advanced_section',
			],*/

			[
				'title' => __( 'Uninstalling ' . $plugin->get_name(), 'wc-fast-cart' ),
				'type'  => 'title',
				'id'    => 'quick_view_uninstall_section',
			],
			[
				'title'         => __( 'Delete data on uninstall', 'wc-fast-cart' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_NAME . '[delete_data]',
				'desc'          => __( 'Permanently delete all ' . $plugin->get_name() . ' settings and data when uninstalling the plugin', 'wc-fast-cart' ),
				'default'       => 'no',
				'checkboxgroup' => 'start',
			],
			[
				'type' => 'sectionend',
				'id'   => 'quick_view_uninstall_section',
			],
			[
				'id'   => 'fast_cart_settings_end',
				'type' => 'settings_end',
			],

		];

		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$plugin_settings = apply_filters( 'woocommerce_get_settings_fast-cart', $plugin_settings, '' );

		return $plugin_settings;
	}

	/**
	 * Reformats values generated by setup wizard into WC compatible values.
	 *
	 * @param [type] $values
	 * @return array
	 */
	public static function prepare_wizard_fields_for_wc( $values ) {

		$return = [];
		foreach ( $values as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( strpos( $key, '[]' ) !== false ) {
					$return[ $key ] = [];
					foreach ( $value as $optval ) {
						$return[ $key ][] = $optval;
					}
				} else {
					$first          = current( $value );
					$return[ $key ] = $first['key'];
				}
			} elseif ( $value === 'false' ) {
				$return[ $key ] = 'no';
			} elseif ( $value === 'true' || $value === '1' ) {
				$return[ $key ] = 'yes';
			} else {
				$return[ $key ] = $value;
			}
		}

		return $return;
	}
}

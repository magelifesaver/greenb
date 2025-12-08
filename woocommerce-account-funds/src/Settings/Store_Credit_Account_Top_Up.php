<?php
/**
 * Account Funds for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Account_Funds\Settings;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Setting;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Settings_Group;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Stores\Option;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Amount;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Boolean;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Text;

/**
 * Settings group for the "My Account" top-up feature.
 *
 * @since 4.0.0
 */
final class Store_Credit_Account_Top_Up extends Settings_Group {

	/** @var string */
	protected const SETTING_NAME = 'my_account_top_up_settings';

	/** @var string */
	private const ENABLED_SETTING_NAME = 'top_up_enabled';

	/** @var string */
	private const MINIMUM_TOP_UP_SETTING_NAME = 'minimum_top_up';

	/** @var string */
	private const MAXIMUM_TOP_UP_SETTING_NAME = 'maximum_top_up';

	/** @var string */
	private const ALLOW_TOP_UP_REWARDS_SETTING_NAME = 'allow_top_up_rewards';

	/** @var string */
	private const TOP_UP_IMAGE_TYPE_SETTING_NAME = 'top_up_image_type';

	/** @var string */
	private const TOP_UP_IMAGE_URL_SETTING_NAME = 'top_up_image_id';

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 */
	protected function __construct() {

		parent::__construct(
			new Option( self::plugin()->key( self::SETTING_NAME ), false ),
			[
				self::ENABLED_SETTING_NAME              => new Setting( [
					'name'        => self::ENABLED_SETTING_NAME,
					'title'       => __( 'Enable "My Account" top-up', 'woocommerce-account-funds' ),
					'description' => __( 'Allow customers to top up their store credit via their account page', 'woocommerce-account-funds' ),
					'default'     => false,
					'type'        => new Boolean(),
				] ),
				self::ALLOW_TOP_UP_REWARDS_SETTING_NAME => new Setting( [
					'name'         => self::ALLOW_TOP_UP_REWARDS_SETTING_NAME,
					'title'        => __( 'Allow rewards', 'woocommerce-account-funds' ),
					'description'  => __( 'Top-up orders may trigger eligible rewards, when applicable', 'woocommerce-account-funds' ),
					'instructions' => __( 'For example, disabling this will prevent store credit cashback to be awarded to customers that top-up.', 'woocommerce-account-funds' ),
					'default'      => false,
					'type'         => new Boolean(),
				] ),
				self::MINIMUM_TOP_UP_SETTING_NAME       => new Setting( [
					'name'         => self::MINIMUM_TOP_UP_SETTING_NAME,
					'title'        => __( 'Minimum top-up', 'woocommerce-account-funds' ),
					'instructions' => __( 'The minimum amount of store credit a customer is allowed to top up from their account page.', 'woocommerce-account-funds' ),
					'default'      => wc_format_decimal( 1 ),
					'type'         => new Amount(),
				] ),
				self::MAXIMUM_TOP_UP_SETTING_NAME       => new Setting( [
					'name'         => self::MAXIMUM_TOP_UP_SETTING_NAME,
					'title'        => __( 'Maximum top-up', 'woocommerce-account-funds' ),
					'instructions' => __( 'The maximum amount of store credit a customer is allowed to top up from their account page.', 'woocommerce-account-funds' ),
					'default'      => '',
					'type'         => new Amount(),
				] ),
				self::TOP_UP_IMAGE_TYPE_SETTING_NAME    => new Setting( [
					'name'         => self::TOP_UP_IMAGE_TYPE_SETTING_NAME,
					'title'        => __( 'Top-up image type', 'woocommerce-account-funds' ),
					'instructions' => __( 'The type of image to display for the top-up button.', 'woocommerce-account-funds' ),
					'default'      => '',
					'type'         => new Text( [
						'field'   => Field::SELECT,
						'choices' => [
							''       => __( 'Use default image', 'woocommerce-account-funds' ),
							'custom' => __( 'Use a custom image', 'woocommerce-account-funds' ),
						],
					] ),
				] ),
				self::TOP_UP_IMAGE_URL_SETTING_NAME     => new Setting( [
					'name'         => self::TOP_UP_IMAGE_URL_SETTING_NAME,
					'title'        => __( 'Top-up image', 'woocommerce-account-funds' ),
					'description'  => $this->get_top_up_image_preview(),
					'instructions' => __( 'Choose or upload an image from the media gallery to use as the image shown in cart when the customer adds store credit for top-up.', 'woocommerce-account-funds' ),
					'type'         => new Text(),
				] ),
			]
		);
	}

	/**
	 * Returns the HTML for the top-up image preview.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	private function get_top_up_image_preview() : string {

		if ( isset( $_POST['top_up_image_id'] ) && is_numeric( $_POST['top_up_image_id'] ) ) {
			$image_id = (int) sanitize_text_field( wp_unslash( $_POST['top_up_image_id'] ) );
		} else {
			$option   = get_option( self::plugin()->key( self::SETTING_NAME ), [] );
			$image_id = isset( $option['top_up_image_id'] ) && is_numeric( $option['top_up_image_id'] ) ? (int) $option['top_up_image_id'] : 0;
		}

		$default_url = self::default_image_url();
		$image_url   = $image_id && $image_id > 0 ? wp_get_attachment_image_url( $image_id ) : $default_url;

		ob_start();

		?>
		<span id="top-up-image-container">
			<img src="<?php echo esc_url( (string) $image_url ); ?>" id="top-up-image-preview" alt="<?php esc_attr_e( 'Top-up image used in the cart', 'woocommerce-account-funds' ); ?>" />
			<a href="#" id="top-up-image-select" class="button"><?php esc_html_e( 'Select image', 'woocommerce-account-funds' ); ?></a>
			<span class="clear"></span>
		</span>
		<?php

		return ob_get_clean();
	}

	/**
	 * Determines if the top-up feature is enabled.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public static function enabled() : bool {

		return wc_string_to_bool( self::instance()->get_setting( self::ENABLED_SETTING_NAME )->get_value() );
	}

	/**
	 * Returns the minimum top-up amount.
	 *
	 * @since 4.0.0
	 *
	 * @return float|null
	 */
	public static function minimum_top_up() : ?float {

		$value = self::instance()->get_setting( self::MINIMUM_TOP_UP_SETTING_NAME )->format();

		if ( empty( $value ) ) {
			return null;
		}

		return (float) str_replace( ',', wc_get_price_decimal_separator(), (string) $value ) ?: null;
	}

	/**
	 * Returns the maximum top-up amount.
	 *
	 * @since 4.0.0
	 *
	 * @return float|null
	 */
	public static function maximum_top_up() : ?float {

		$value = self::instance()->get_setting( self::MAXIMUM_TOP_UP_SETTING_NAME )->format();

		if ( empty( $value ) ) {
			return null;
		}

		return (float) str_replace( ',', wc_get_price_decimal_separator(), (string) $value ) ?: null;
	}

	/**
	 * Determines if top-ups are excluded from rewards.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public static function exclude_top_up_from_rewards() : bool {

		return false === wc_string_to_bool( self::instance()->get_setting( self::ALLOW_TOP_UP_REWARDS_SETTING_NAME )->get_value() );
	}

	/**
	 * Returns the custom image ID when set.
	 *
	 * @since 4.0.0
	 *
	 * @return int
	 */
	public static function image_id() : ?int {

		$image_id = self::instance()->get_setting( self::TOP_UP_IMAGE_URL_SETTING_NAME )->get_value();

		return is_numeric( $image_id ) ? (int) $image_id : null;
	}

	/**
	 * Returns the URL of the top-up image.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public static function image_url() : string {

		$default_url = self::default_image_url();
		$image_id    = self::image_id();
		$image_url   = $image_id ? wp_get_attachment_image_url( $image_id ) : $default_url;

		return $image_url ?: $default_url;
	}

	/**
	 * Returns the HTML for the top-up image.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public static function image_html() : string {

		$account_funds_url = wc_get_account_endpoint_url( 'account-funds' );

		if ( $id = self::image_id() ) {
			$img = wp_get_attachment_image( $id );
		} else {
			$img = '<img src="' . esc_url( self::default_image_url() ) . '">';
		}

		// @NOTE <a></a> included, as some themes set widths based on the link being present
		return '<a href="' . esc_url( $account_funds_url ) . '">' . $img . '</a>';
	}

	/**
	 * Returns image data for the top-up product.
	 *
	 * @since 4.0.0
	 *
	 * @return array<int, object{
	 *     id: int,
	 *     src: string,
	 *     thumbnail: string,
	 *     srcset: string,
	 *     sizes: string,
	 *     name: string,
	 *     alt: string
	 * }>
	 */
	public static function image_data() : array {

		if ( $image_id = self::image_id() ) { // custom image

			return [
				(object) [
					'id'        => $image_id,
					'src'       => self::image_url(),
					'thumbnail' => wp_get_attachment_thumb_url( $image_id ),
					'srcset'    => wp_get_attachment_image_srcset( $image_id ),
					'sizes'     => wp_get_attachment_image_sizes( $image_id ),
					'name'      => get_the_title( $image_id ),
					'alt'       => wp_get_attachment_caption( $image_id ),
				],
			];

		} else { // default image

			/* translators: Placeholder: %s - Label used to represent store credit (e.g. "Store credit top-up") */
			$name      = sprintf( __( '%s top-up', 'woocommerce-account-funds' ), Store_Credit_Label::plural()->to_string() );
			$image_url = self::image_url();
			$width     = 800; // our image has a fixed width of 800 px
			$srcset    = sprintf( '%s %dw', esc_url( $image_url ), $width );
			$sizes     = sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $width );

			return [
				(object) [
					'id'        => 0,
					'src'       => $image_url,
					'thumbnail' => $image_url,
					'srcset'    => $srcset,
					'sizes'     => $sizes,
					'name'      => $name,
					'alt'       => $name,
				],
			];
		}
	}

	/**
	 * Returns the default cart image URL for the top-up feature.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	private static function default_image_url() : string {

		return self::plugin()->assets_url( 'img/store-credit-top-up.png' );
	}

}

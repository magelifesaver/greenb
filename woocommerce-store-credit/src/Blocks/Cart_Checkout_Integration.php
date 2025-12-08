<?php
/**
 * Kestrel Store Credit for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2012-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Store_Credit\Blocks;

defined( 'ABSPATH' ) or exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;

/**
 * WooCommerce Cart & Checkout blocks integration for Store Credit.
 *
 * @since 5.1.0
 */
final class Cart_Checkout_Integration implements IntegrationInterface {
	use Has_Plugin_Instance;

	/**
	 * Initializes the integration.
	 *
	 * @since 5.1.0
	 *
	 * @return void
	 */
	public function initialize() {
	}

	/**
	 * Returns the name of the integration.
	 *
	 * @since 5.1.0
	 *
	 * @return string
	 */
	public function get_name() {

		return 'kestrel/store-credit';
	}

	/**
	 * Returns the label for the integration.
	 *
	 * @since 5.1.0
	 *
	 * @return string
	 */
	public function get_label() {

		return __( 'Store credit', 'woocommerce-store-credit' );
	}

	/**
	 * Returns the list of script handles utilized by the integration in the frontend.
	 *
	 * @since 5.1.0
	 *
	 * @return string[]
	 */
	public function get_script_handles() {

		return [
			self::plugin()->handle( 'store-credit-cart-checkout-blocks' ),
		];
	}

	/**
	 * Returns the list of script handles utilized by the integration in the editor.
	 *
	 * @since 5.1.0
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {

		return [];
	}

	/**
	 * Returns data to be passed from the server to the frontend script.
	 *
	 * @si nce 5.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_script_data() {

		$coupons     = wc_store_credit_get_customer_coupons( get_current_user_id() ); // @phpstan-ignore-line
		$notice_text = get_option( 'wc_store_credit_cart_notice_text', '' );
		$notice_text = is_string( $notice_text ) ? preg_replace( '/\[link].*?\[\/link]/', '', $notice_text ) : '';

		if ( ! is_string( $notice_text ) || '' === trim( $notice_text ) ) {
			$notice_text = __( 'You have store credit coupons available!', 'woocommerce-store-credit' );
		}

		// Ensure $coupons is always an array to prevent array_map() errors
		if ( ! is_array( $coupons ) ) {
			$coupons = [];
		}

		return [
			'nonce'                  => wp_create_nonce( 'wc_store_credit_cart_checkout_blocks' ),
			'nonce_action'           => 'wc_store_credit_cart_checkout_blocks',
			'should_display_coupons' => wc_bool_to_string( get_option( 'wc_store_credit_show_cart_notice', 'no' ) ),
			'coupons_notice_text'    => esc_html( $notice_text ),
			'coupons_data'           => array_map(
				static function( $coupon ) {
					return [
						'code'    => $coupon->get_code(),
						'label'   => $coupon->get_description(),
						'amount'  => self::convert_coupon_amount_to_cents( $coupon->get_amount() ),
						'expires' => $coupon->get_date_expires()
							/* translators: Placeholder: %s - Store credit coupon expiration date */
							? sprintf( __( 'Expires on %s', 'woocommerce-store-credit' ), $coupon->get_date_expires()->date_i18n( wc_date_format() ) )
							: __( 'Never expires', 'woocommerce-store-credit' ),
					];
				},
				$coupons
			),
		];
	}

	/**
	 * Converts a coupon amount to cents.
	 *
	 * @since 5.1.0
	 *
	 * @param float|int|numeric-string|string|null $amount
	 * @return int
	 */
	private static function convert_coupon_amount_to_cents( $amount ) : int {

		if ( ! is_numeric( $amount ) ) {
			return 0;
		}

		$decimals   = wc_get_price_decimals();
		$multiplier = pow( 10, $decimals );

		return (int) round( floatval( $amount ) * $multiplier );
	}

}

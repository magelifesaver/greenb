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

defined( 'ABSPATH' ) or exit;

/**
 * Store credit coupon template.
 *
 * @since 3.0.0
 * @version 4.2.0
 *
 * @var WC_Coupon $coupon coupon object
 */
?>
<div class="wc-store-credit-cart-coupon" data-coupon-code="<?php echo esc_attr( $coupon->get_code() ); ?>">
	<div class="wc-store-credit-cart-coupon-inner">
		<div class="coupon-amount"><?php echo wp_kses_post( wc_price( $coupon->get_amount() ) ); ?></div>
		<div class="coupon-code"><?php echo esc_html( $coupon->get_code() ); ?></div>
		<div class="coupon-date-expires">
		<?php
		$expiration_date = $coupon->get_date_expires();

		if ( $expiration_date ) :
			/* translators: %s: coupon date expires */
			echo wp_kses_post( sprintf( __( 'Expires on %s', 'woocommerce-store-credit' ), '<span class="date-expires">' . wc_format_datetime( $expiration_date ) . '</span>' ) );
		else :
			esc_html_e( 'Never expires', 'woocommerce-store-credit' );
		endif;
		?>
		</div>
	</div>
</div>
<?php

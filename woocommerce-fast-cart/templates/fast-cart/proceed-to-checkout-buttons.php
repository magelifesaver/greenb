<?php
/**
 * Proceed to checkout button
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/wfc/fast-cart/proceed-to-checkout-buttons.php.
 *
 * Based on WooCommerce\Templates\proceed-to-checkout-button.php version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'wfc_before_proceed_to_checkout_buttons' );

?>
<div class="wfc-checkout-buttons">
	<?php do_action( 'wfc_proceed_to_checkout_buttons_open' ); ?>

	<a href="#close-modal" class="wfc-button wfc-exit">
		<?php esc_html_e( 'Keep Shopping', 'wc-fast-cart' ); ?>
	</a>
	<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="wfc-checkout-button wfc-button">
		<?php esc_html_e( 'Checkout', 'wc-fast-cart' ); ?>
	</a>

	<?php do_action( 'wfc_proceed_to_checkout_buttons_close' );

	?>
</div>
<?php
/**
 * Necessary for PayPal Payments integration to work.
 *
 * @hooked [ 'Barn2\Plugin\WC_Fast_Cart\Integration\Paypal', 'echo_paypal_button_cart_container' ]
 */

do_action( 'wfc_after_proceed_to_checkout_buttons' );

?>

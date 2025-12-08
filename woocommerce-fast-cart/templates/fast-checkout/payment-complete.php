<?php
/**
 * WFC checkout  template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/wfc/fast-checkout/payment-complete.php.
 */
?>
<?php

	$order_id = $_GET['_redirect_to_payment'] ?? null;
	if ( empty( $order_id ) || empty( $_GET['_redirect_hash'] ) ) {
		header( 'HTTP/1.0 403 Forbidden' );
		echo 'Nothing to do.';
		exit;
	}

	$order = wc_get_order( $order_id );
	$url   = $order->get_meta( '_payment_redirect' );

	$verified = wp_verify_nonce( $_GET['_redirect_hash'], $url );
	if ( ! $verified ) {
		header( 'HTTP/1.0 403 Forbidden' );
		echo 'Could not verify.';
		exit;
	}

	if ( $url ) {
		$order->delete_meta_data( '_payment_redirect' );
		$order->save();
	}

?>
<!doctype html>
<html <?php language_attributes(); ?> style="background:white">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class( 'wfc-checkout--payment' ); ?>>

<?php wp_body_open(); ?>

<div class="wfc-checkout__inner-contents">

	<p><?php esc_html_e( 'Your order has been submitted! Please wait while we redirect you to payment.', 'wc-fast-cart' ); ?></p>
	<script>
		if ( window.parent ) {
			document.addEventListener( 'DOMContentLoaded', function () {
				window.parent.FastCart.completeCheckout( '<?php echo esc_url( $url, null, 'js' ); ?>' );
			} );
			window.scrollTo( 0, 0 );
			window.top.scrollTo( 0, 0 );
			window.parent.scrollTo( 0, 0 );
			window.parent.document.querySelector( '.wc-fast-cart__page-overlay' ).scrollTo( 0, 0 );
		} else {
			window.location.href = '<?php echo esc_url( $url, null, 'js' ); ?>';
		}
	</script>

</div>

<?php wp_footer(); ?>

</body>
</html>

<?php
/**
 * WFC checkout completed template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/wfc/fast-checkout/checkout-complete.php.
 */
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

<body <?php body_class( 'wfc-checkout--receipt' ); ?>>

<?php wp_body_open(); ?>

<div class="wfc-checkout__inner-contents">

	<p><?php echo esc_html( apply_filters( 'wfc_checkout_complete_message', __( 'Your order has been submitted! Please wait while we redirect you to the order receipt.', 'wc-fast-cart' ) ) ); ?></p>
	<script>
		window.scrollTo( 0, 0 );
		window.top.scrollTo( 0, 0 );
		window.parent.scrollTo( 0, 0 );
		window.parent.document.querySelector( '.wc-fast-cart__page-overlay' ).scrollTo( 0, 0 );
	</script>

</div>

<?php wp_footer(); ?>

</body>
</html>

<?php
/**
 * WFC cart button template
 *
 * This template can be overridden by copying it to yourtheme/wfc/open-cart-button.php.
 */


$count = WC()->cart->cart_contents_count;

$wc_cart  = wc_get_page_id( 'cart' );
$cart_url = $wc_cart ? get_permalink( $wc_cart ) : trailingslashit( get_bloginfo( 'url' ) ) . 'cart/';
$button_classes = [
	'wfc-open-cart-button',
	'at-' .esc_attr( $position )
];
if ( $button_style !== 'icon' ) {
	$button_classes[] = 'has-text';
}
if ( $button_style !== 'text' ) {
	$button_classes[] = 'has-icon';
}

?>
<a href="<?php echo $cart_url; ?>" aria-hidden="<?php echo esc_attr( $hidden ); ?>" id="wfc-open-cart-button" class="<?php echo implode( ' ', $button_classes ) ?>" style="background-color:<?php echo esc_attr( $fill_bg ); ?>;color:<?php echo esc_attr( $fill_color ); ?>; border-radius:<?php echo esc_attr( $border_radius ); ?>px;">
	<?php
	if ( $button_style !== 'text' ) {
		?>
		<svg class="wfc-open-cart-button__icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="transform:scale(-1,1) translate(-2px,0)"><path d="M21.822,7.431C21.635,7.161,21.328,7,21,7H7.333L6.179,4.23C5.867,3.482,5.143,3,4.333,3H2v2h2.333l4.744,11.385 C9.232,16.757,9.596,17,10,17h8c0.417,0,0.79-0.259,0.937-0.648l3-8C22.052,8.044,22.009,7.7,21.822,7.431z M17.307,15h-6.64 l-2.5-6h11.39L17.307,15z"/><circle cx="10.5" cy="19.5" r="1.5"/><circle cx="17.5" cy="19.5" r="1.5"/></svg>
		<?php
	}
	echo $button_text;
	?>
	<span class="wfc-open-cart-button__count<?php echo $count > 99 ? ' is-over-99' : ''; ?>" style="background-color:<?php echo esc_attr( $count_bg ); ?>;color:<?php echo esc_attr( $count_color ); ?>"><?php echo esc_html( $count ); ?></span>
</a>

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
 * Customer send store credit email (HTML) template.
 *
 * @since 3.0.0
 * @version 5.0.0
 *
 * @var WC_Store_Credit_Email_Send_Credit $email email object
 * @var string $email_heading email heading
 * @var string $additional_content additional content
 * @var WC_Coupon $coupon coupon object
 */

do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore

if ( $coupon->get_description() ) :
	echo wp_kses_post( wpautop( wptexturize( $coupon->get_description() ) ) );
endif;

?>
<p><?php esc_html_e( 'To redeem your store credit use the following code during checkout:', 'woocommerce-store-credit' ); ?></p>

<div class="store-credit-wrapper text-center">
	<span class="store-credit-code"><?php echo esc_html( $coupon->get_code() ); ?></span>
</div>

<div class="store-credit-wrapper text-center">
	<?php printf( '<a class="store-credit-cta-button" href="%1$s" target="_blank">%2$s</a>', esc_url( wc_store_credit_get_redeem_url( $coupon ) ), esc_html( $email->get_button_text() ) ); ?>
</div>

<?php

if ( $date_expires = $coupon->get_date_expires() ) :

	echo '<p class="text-center">';
	/* translators: Placeholder: %s - expiration date */
	echo wp_kses_post( sprintf( __( 'This credit can be redeemed until %s.', 'woocommerce-store-credit' ), '<strong>' . esc_html( wc_format_datetime( $date_expires ) ) . '</strong>' ) );
	echo '</p>';

endif;

?>

<div class="store-credit-wrapper store-credit-restrictions">
	<?php

	$product_ids = $coupon->get_product_ids();

	if ( ! empty( $product_ids ) ) :

		$product_names = array_filter( array_map(
			function( $id ) {
				$product = wc_get_product( $id );
				return $product ? '"' . $product->get_name() . '"' : '';
			},
			$product_ids
		) );

		if ( ! empty( $product_names ) ) :
			/* translators: Placeholder: %s - Comma-separated list of product names */
			echo '<p>' . wp_kses_post( sprintf( _n( 'This credit is valid for the following product: %s.', 'This credit is valid for the following products: %s.', count( $product_names ), 'woocommerce-store-credit' ), implode( ', ', $product_names ) ) ) . '</p>';
		endif;

	endif;

	$category_ids = $coupon->get_product_categories();

	if ( ! empty( $category_ids ) ) :

		$category_names = array_filter( array_map(
			function( $id ) {
				$term = get_term( $id, 'product_cat' );
				return $term ? '"' . $term->name . '"' : '';
			},
			$category_ids
		) );

		if ( ! empty( $category_names ) ) :
			/* translators: Placeholder: %s - Comma-separated list of product category names */
			echo '<p>' . wp_kses_post( sprintf( _n( 'This credit is valid for the following product category: %s.', 'This credit is valid for the following product categories: %s.', count( $category_names ), 'woocommerce-store-credit' ), implode( ', ', $category_names ) ) ) . '</p>';
		endif;

	endif;

	?>
</div>
<?php

if ( $additional_content ) :
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
endif;

do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore

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
 * Customer send store credit email (plain text) template.
 *
 * @since 3.0.0
 * @version 5.0.0
 *
 * @var WC_Store_Credit_Email_Send_Credit $email email object
 * @var string $email_heading email heading
 * @var string $additional_content additional content
 * @var WC_Coupon $coupon coupon object
 */

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( $coupon->get_description() ) :
	echo esc_html( wp_strip_all_tags( wptexturize( $coupon->get_description() ) ) ) . "\n\n";
endif;

esc_html_e( 'To redeem your store credit use the following code during checkout:', 'woocommerce-store-credit' ) . "\n";

echo "\n----------------------------------------\n\n";

echo esc_html( $coupon->get_code() ) . "\n";

echo "\n----------------------------------------\n\n";

echo esc_html( $email->get_button_text() ) . "\n";

echo esc_url( wc_store_credit_get_redeem_url( $coupon ) ) . "\n";


if ( $date_expires = $coupon->get_date_expires() ) :

	echo "\n----------------------------------------\n\n";

	/* translators: Placeholder: %s - expiration date */
	printf( esc_html__( 'This credit can be redeemed until %s.', 'woocommerce-store-credit' ) . "\n", esc_html( wc_format_datetime( $date_expires ) ) );

endif;

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

		echo "\n----------------------------------------\n";

		/* translators: Placeholder: %s - Comma-separated list of product names */
		echo "\n" . sprintf( esc_html( _n( 'This credit is valid for the following product: %s.', 'This credit is valid for the following products: %s.', count( $product_names ), 'woocommerce-store-credit' ) ), implode( ', ', $product_names ) ); // phpcs:ignore

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

		if ( empty( $product_ids ) ) :
			echo "\n----------------------------------------\n";
		endif;

		/* translators: Placeholder: %s - Comma-separated list of product category names */
		echo "\n" . sprintf( esc_html( _n( 'This credit is valid for the following category: %s.', 'This credit is valid for the following categories: %s.', count( $category_names ), 'woocommerce-store-credit' ) ), implode( ', ', $category_names ) ); // phpcs:ignore

		endif;

endif;

echo "\n----------------------------------------\n\n";


if ( $additional_content ) :
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
endif;

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

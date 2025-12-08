<?php
/**
 * Product rating stars template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/wfc/fast-cart/cross-sells-product-rating.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! wc_review_ratings_enabled() ) {
	return;
}

$rating = $product->get_average_rating();

$html = '';

if ( 0 < $rating ) {
	/* translators: %s: rating */
	$label = sprintf( __( 'Rated %s out of 5', 'wc-fast-cart' ), $rating );
	$html  = '<div class="wfc-product-rating" role="img" title="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '">%s</div>';

	$stars = '';
	for ( $s = 1; $s <= $rating; $s ++ ) {
		$stars .= '<span class="wfc-product-rating__star wfc-product-rating__star--full"></span>';
	}
	if ( floor( $rating ) != $rating ) {
		$stars .= '<span class="wfc-product-rating__star wfc-product-rating__star--half"></span>';
		$s++;
	}
	for ( $e = $s; $s <= 5; $s ++ ) {
		$stars .= '<span class="wfc-product-rating__star wfc-product-rating__star--empty"></span>';
	}
	$html = sprintf( $html, $stars );
}

echo apply_filters( 'wfc_product_get_rating_html', $html, $rating );

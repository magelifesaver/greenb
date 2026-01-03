<?php
/**
 * Frontend display for promo banners.  This module hooks into the product
 * loop and outputs the uploaded banner image for promo products.  The banner
 * renders before any other product content (title/price) and respects
 * categories, brands and other taxonomies – meaning a promo assigned to a
 * category will only display in that category's archive.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
    /**
     * Output the promo banner image at the start of the product loop item.
     */
    add_action( 'woocommerce_before_shop_loop_item', function () {
        global $product;
        if ( ! is_object( $product ) || 'promo' !== $product->get_type() ) {
            return;
        }
        // Retrieve image ID and URL.
        $image_id  = get_post_meta( $product->get_id(), '_promo_banner_image', true );
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
        if ( ! $image_url ) {
            return;
        }
        // Retrieve optional link URL.
        $link_url = get_post_meta( $product->get_id(), '_promo_banner_link', true );
        // Build the image HTML.
        $img_html = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr__( 'Promo Banner', 'aaa' ) . '" />';
        // If a link is set, wrap the image in an anchor.
        if ( $link_url ) {
            $img_html = '<a href="' . esc_url( $link_url ) . '" class="aaa-promo-link">' . $img_html . '</a>';
        }
        echo '<div class="aaa-promo-banner">' . $img_html . '</div>';
    }, 5 );
} );

/**
 * Inline CSS to hide leftover details for promo products on the frontend.  We
 * hide product details (title, price, rating) so only the banner displays.  If
 * you wish to override this styling, copy the CSS to your theme and remove
 * this action.
 */
add_action( 'wp_head', function () {
    ?>
    <style>
        /* Hide most product details for promo products so only the banner shows. */
        /* Older versions of this plugin relied on the theme adding a .product-details wrapper.  */
        /* To maintain backwards compatibility, hide that container if present. */
        .product-type-promo .product-details,
        .product-type-promo .my-custom-image-container {
            display: none !important;
        }
        /* Hide common WooCommerce loop elements for promo products (title, price, rating, buttons, short description). */
        .woocommerce ul.products li.product.product-type-promo .woocommerce-loop-product__title,
        .woocommerce ul.products li.product.product-type-promo .price,
        .woocommerce ul.products li.product.product-type-promo .star-rating,
        .woocommerce ul.products li.product.product-type-promo .button,
        .woocommerce ul.products li.product.product-type-promo .woocommerce-product-details__short-description {
            display: none !important;
        }
        /* Ensure promo products render as a block item in list/grid views. */
        .woocommerce ul.products.products-list-view li.product.virtual.product-type-promo {
            display: block !important;
        }
        /* Make sure the banner spans the full product tile. */
        .aaa-promo-banner img {
            width: 100%;
            height: auto;
            display: block;
        }
    </style>
    <?php
} );

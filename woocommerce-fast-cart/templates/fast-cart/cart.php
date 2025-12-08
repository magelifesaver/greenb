<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/wfc/fast-cart/cart.php
 *
 * Based on WooCommerce\Templates\Cart Page version 3.8.0
 */

defined( 'ABSPATH' ) || exit;

$count = WC()->cart->cart_contents_count;

wc_nocache_headers();

/*
* @hooked woocommerce_output_all_notices - 10
*/
do_action( 'wfc_before_cart' );

if ( $count === 0 ) {

	echo '<div class="wfc-cart-empty">';

	/*
	* @hooked wc_empty_cart_message - 10
	*/
	do_action( 'wfc_cart_is_empty' );

	echo '</div>';

} else {


	?>

<form class="wfc-cart-form wfc" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post" data-count="<?php echo esc_attr( $count ); ?>">
	<?php do_action( 'wfc_before_cart_table' ); ?>

	<table class="wfc-cart-table wfc-cart-form__contents has-background" cellspacing="0" cellpadding="0">
		<thead>
			<tr>
				<th class="product-thumbnail"><?php esc_html_e( 'Image', 'wc-fast-cart' ); ?></th>
				<th class="product-name"><?php esc_html_e( 'Product', 'wc-fast-cart' ); ?></th>
				<th class="product-price"><?php esc_html_e( 'Price', 'wc-fast-cart' ); ?></th>
				<th class="product-quantity"><?php esc_html_e( 'Quantity', 'wc-fast-cart' ); ?></th>
				<th class="product-subtotal"><?php esc_html_e( 'Total', 'wc-fast-cart' ); ?></th>
				<th class="product-remove">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php do_action( 'wfc_before_cart_contents' ); ?>
			
			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					?>
					<tr class="wfc-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

						<td class="product-thumbnail">
						<?php
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

						if ( ! $product_permalink ) {
							echo $thumbnail; // PHPCS: XSS ok.
						} else {
							printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // PHPCS: XSS ok.
						}
						?>
						</td>

						<td class="product-name" data-title="<?php esc_attr_e( 'Product', 'wc-fast-cart' ); ?>">
						<?php
						if ( ! $product_permalink ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' );
						} else {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
						}

						do_action( 'wfc_after_cart_item_name', $cart_item, $cart_item_key );

						// Meta data.
						echo wc_get_formatted_cart_item_data( $cart_item ); // PHPCS: XSS ok.

						// Backorder notification.
						if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
							echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'wc-fast-cart' ) . '</p>', $product_id ) );
						}
						?>
						</td>

						<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'wc-fast-cart' ); ?>">
							<?php
								echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
							?>
						</td>

						<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'wc-fast-cart' ); ?>">
						<?php
						if ( $_product->is_sold_individually() ) {
							$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
						} else {
							$product_quantity = woocommerce_quantity_input(
								[
									'input_name'   => "cart[{$cart_item_key}][qty]",
									'input_value'  => $cart_item['quantity'],
									'max_value'    => $_product->get_max_purchase_quantity(),
									'min_value'    => '0',
									'product_name' => $_product->get_name(),
								],
								$_product,
								false
							);
						}

						echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // PHPCS: XSS ok.
						?>
						</td>

						<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'wc-fast-cart' ); ?>">
							<?php
								echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
							?>
						</td>

						<td class="product-remove">
							<?php
								echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									'woocommerce_cart_item_remove_link',
									sprintf(
										'<a href="%s" class="wfc-cart__remove" data-product_id="%s" data-product_sku="%s"><span class="wfc-sr-text">%s</span></a>',
										esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
										esc_attr( $product_id ),
										esc_attr( $_product->get_sku() ),
										esc_html__( 'Remove this item', 'wc-fast-cart' )
									),
									$cart_item_key
								);
							?>
						</td>
					</tr>
					<?php
				}
			}
			?>

			<?php do_action( 'wfc_cart_contents' ); ?>

			<tr class="wfc-cart-table__actions">
				<td colspan="6">

					<?php do_action( 'wfc_cart_actions' ); ?>

					<button type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'wc-fast-cart' ); ?>"><?php esc_html_e( 'Update cart', 'wc-fast-cart' ); ?></button>

					<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
				</td>
			</tr>

			<?php do_action( 'wfc_after_cart_contents' ); ?>
		</tbody>
	</table>
	<?php do_action( 'wfc_after_cart_table' ); ?>
</form>

	<?php do_action( 'wfc_before_cart_collaterals' ); ?>

<div class="wfc-cart-collaterals">
	<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action( 'wfc_cart_collaterals' );
	?>
</div>

	<?php

} // if count === 0 ... else

do_action( 'wfc_after_cart' );

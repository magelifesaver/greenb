<?php
/**
 * View for the delivery item rows within the Edit Delivery modal's table
 *
 * @since 0.9.3
 *
 * @var \AtumPO\Deliveries\Models\Delivery             $delivery
 * @var \AtumPO\Deliveries\Items\DeliveryItemProduct[] $delivery_items
 * @var \Atum\PurchaseOrders\Items\POItemProduct[]     $po_items
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\Helpers;
use Atum\Inc\Helpers as AtumHelpers;

foreach ( $po_items as $po_item ) :

	// Allow bypassing an item externally.
	if ( ! apply_filters( 'atum/purchase_orders_pro/delivery_modal/show_item', TRUE, $po_item ) ) :
		continue;
	endif;

	$po_item_id            = $po_item->get_id();
	$product               = AtumHelpers::get_atum_product( $po_item->get_product() );
	$current_delivery_item = NULL;
	$delivery_item_id      = '';

	// Find the associated delivery item (if any).
	foreach ( $delivery_items as $delivery_item ) :
		if ( $delivery_item->get_po_item_id() === $po_item_id ) :
			$current_delivery_item = $delivery_item;
			$delivery_item_id      = $current_delivery_item->get_id();
			break;
		endif;
	endforeach;

	$expected   = $po_item->get_quantity();
	$delivered  = $current_delivery_item ? $current_delivery_item->get_quantity() : 0; // Delivered in this delivery.
	$already_in = (float) Helpers::get_po_item_already_in_total( $po_item_id, FALSE ) - $delivered; // The total already in, also counts the current delivery, so we need to subsctract the delivered.
	$pending    = $expected - $already_in;

	if ( $pending > 0 || ! is_null( $current_delivery_item ) ) : ?>

		<tr class="<?php echo esc_attr( apply_filters( 'atum/purchase_orders_pro/delivery_item_css_class', 'delivery-item', $po_item, $product ) ) ?>"
			data-id="<?php echo esc_attr( $delivery_item_id ) ?>" data-product_id="<?php echo esc_attr( $po_item->get_product_id() ) ?>"
		>
			<td class="thumb">
				<?php $thumbnail = $product instanceof \WC_Product ? $product->get_image( [ 40, 40 ], [ 'title' => '' ], FALSE ) : ''; ?>
				<div class="atum-order-item-thumbnail"><?php echo wp_kses_post( $thumbnail ) ?></div>
			</td>
			<td class="name">
				<div class="product-name">
					<?php echo esc_html( $product instanceof \WC_Product ? $product->get_name() : $po_item->get_name() ) ?>
				</div>

				<?php if ( $product instanceof \WC_Product ) : ?>
					<div class="item-meta">

						<?php $sku = $product->get_sku() ?>

						<?php if ( $sku ) : ?>
							<div>
								<?php /* translators: the product SKU */
								printf( esc_html__( 'SKU: %s', ATUM_PO_TEXT_DOMAIN ), $sku ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php endif; ?>

						<?php $supplier_sku = $product->get_supplier_sku() ?>

						<?php if ( $supplier_sku ) : ?>
							<div>
								<?php /* translators: the sipplier's SKU */
								printf( esc_html__( 'Supplier SKU: %s', ATUM_PO_TEXT_DOMAIN ), $supplier_sku ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php endif; ?>

					</div>
				<?php endif; ?>
			</td>
			<td class="expected center">
				<?php echo esc_html( $expected ); ?>
			</td>
			<td class="already-in center">
				<?php echo esc_html( $already_in ); ?>
			</td>
			<td class="qty center">
				<input type="number" name="delivery_qty" data-id="<?php echo esc_attr( $po_item_id ) ?>" value="<?php echo esc_attr( $delivered ) ?>"
					min="0" max="<?php echo esc_attr( $pending ) ?>" step="any" data-original-value="<?php echo esc_attr( $delivered ) ?>"
					data-expected="<?php echo esc_attr( $expected ) ?>"<?php disabled( $pending <= 0 ) ?> onfocus="this.select();"
				>
			</td>
		</tr>

		<?php do_action( 'atum/purchase_orders_pro/after_edit_delivery_modal_item', $po_item, $product, $delivery ) ?>

	<?php endif;

endforeach;

<?php
/**
 * View for the delivery item rows within the delivery items table
 *
 * @since 0.9.3
 *
 * @var \AtumPO\Models\POExtended          $po
 * @var \AtumPO\Deliveries\Models\Delivery $delivery
 * @var DeliveryItemProduct[]              $delivery_items
 * @var array                              $expected_qtys
 * @var array                              $already_in_qtys
 * @var array                              $delivered_qtys
 * @var array                              $pending_qtys
 * @var int|float                          $already_in_items_total
 * @var int|float                          $delivered_items_total
 * @var int|float                          $pending_items_total
 * @var bool                               $is_editable
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use AtumPO\Deliveries\Items\DeliveryItemProduct;

foreach ( $delivery_items as $delivery_item ) :

	/**
	 * Variable definition
	 *
	 * @var DeliveryItemProduct $delivery_item
	 */
	$po_item_id       = $delivery_item->get_po_item_id();
	$po_item          = $po->get_item( $po_item_id );
	$product_link     = admin_url( 'post.php?post=' . $delivery_item->get_product_id() . '&action=edit' );
	$product          = $delivery_item->get_product();
	$delivery_item_id = $delivery_item->get_id();
	$is_stock_changed = 'yes' === $delivery_item->get_stock_changed();
	$expected_qty     = $expected_qtys[ $po_item_id ][ $delivery_item_id ] ?? 0;
	$delivered        = $delivered_qtys[ $po_item_id ][ $delivery_item_id ] ?? 0;
	$already_in       = $already_in_qtys[ $po_item_id ][ $delivery_item_id ] ?? 0;
	$pending          = $pending_qtys[ $po_item_id ][ $delivery_item_id ] ?? 0;

	$already_in_items_total += $already_in;
	$delivered_items_total  += $delivered;
	$pending_items_total    += $pending;

	$row_classes = 'delivery-item';

	// Check if the related PO item still exists.
	if ( ! $po_item ) :
		$row_classes .= ' missing-item atum-tooltip';
	endif;

	if ( $is_stock_changed ) :
		$row_classes .= ' stock-changed';
	endif;
	?>
	<tr class="<?php echo esc_attr( apply_filters( 'atum/purchase_orders_pro/delivery_item_css_class', $row_classes, $po_item, $product ) ) ?>"
		data-po-item="<?php echo esc_attr( $po_item_id ) ?>" data-delivery-item="<?php echo esc_attr( $delivery_item_id ) ?>"
		<?php echo ! $po_item ? ' title="' . esc_attr__( 'The PO item related to this delivery item is missing', ATUM_PO_TEXT_DOMAIN ) . '"' : '' ?>
	>
		<td class="thumb">
			<?php $thumbnail = $product instanceof \WC_Product ? $product->get_image( [ 40, 40 ], array( 'title' => '' ), FALSE ) : ''; ?>
			<div class="atum-order-item-thumbnail"><?php echo wp_kses_post( $thumbnail ) ?></div>
		</td>
		<td class="name">
			<?php if ( $product instanceof \WC_Product ) : ?>
			<a href="<?php echo esc_url( $product_link ) ?>" class="atum-order-item-name atum-tooltip" title="<?php esc_attr_e( 'View Product', ATUM_PO_TEXT_DOMAIN ); ?>" target="_blank">
				<?php echo esc_html( $delivery_item->get_name() ) ?>
			</a>
			<?php else: ?>
			<span class="atum-order-item-name deleted" title="<?php esc_attr_e( 'This product does not exist', ATUM_PO_TEXT_DOMAIN ); ?>">
				<?php echo esc_html( $delivery_item->get_name() ) ?>
			</span>
			<?php endif; ?>

			<?php if ( $product instanceof \WC_Product ) : ?>
				<div class="item-meta">

					<?php if ( $product->get_sku() ) : ?>
						<div class="atum-order-item-sku"><strong><?php esc_html_e( 'SKU:', ATUM_PO_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $product->get_sku() ) ?></div>
					<?php endif;

					if ( AtumCapabilities::current_user_can( 'read_suppliers' ) ) :
						$supplier_sku = $product->get_supplier_sku();

						if ( $supplier_sku ) : ?>
							<div class="atum-order-item-sku"><strong><?php esc_html_e( 'Supplier SKU:', ATUM_PO_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $supplier_sku ) ?></div>
						<?php endif;
					endif; ?>

				</div>
			<?php endif; ?>

			<div class="order-item-icons">
				<?php if ( apply_filters( 'atum/purchase_orders_pro/delivery_item_unmanaged_stock_warning', $product instanceof \WC_Product && ( ! $product->managing_stock() || 'parent' === $product->managing_stock() ), $product ) ) : ?>
					<i class="atum-icon atmi-warning color-warning tips" data-tip="<?php esc_attr_e( "This item's stock is not managed by WooCommerce at product level, so it won't be updated when adding this delivery to stock", ATUM_PO_TEXT_DOMAIN ) ?>"></i>
				<?php endif; ?>

				<?php if ( $is_stock_changed ) : ?>
					<i class="atum-icon atmi-highlight color-warning tips" data-tip="<?php esc_attr_e( 'This item was already added to stock on this delivery', ATUM_PO_TEXT_DOMAIN ) ?>"></i>
				<?php endif; ?>

				<?php do_action( 'atum/purchase_orders_pro/after_delivery_item_icons', $po_item_id, $po_item, $product ) ?>
			</div>
		</td>
		<td class="already-in center"><?php echo esc_html( wc_stock_amount( $already_in ) ) ?></td>
		<td class="delivered center"><?php echo esc_html( wc_stock_amount( $delivered ) ) ?></td>
		<td class="pending center"><?php echo esc_html( wc_stock_amount( $pending ) ) ?></td>

		<?php if ( $is_editable ) : ?>
		<td class="actions center">
			<i class="show-actions atum-icon atmi-options"></i>
		</td>
		<?php endif; ?>
	</tr>

	<?php do_action( 'atum/purchase_orders_pro/after_delivery_item', $po_item, $product, $delivery, $delivery_item ) ?>

<?php endforeach;

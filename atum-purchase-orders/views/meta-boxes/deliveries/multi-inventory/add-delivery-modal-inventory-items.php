<?php
/**
 * View for the delivery inventory items within the Add Delivery Modal
 *
 * @since 0.9.3
 * @package Multi-Inventory
 *
 * @var \Atum\PurchaseOrders\Items\POItemProduct $po_item
 * @var \WC_Product                              $product
 * @var array                                    $po_inventory_order_items
 * @var array                                    $delivery_inventory_items
 */

defined( 'ABSPATH' ) || die;

use AtumMultiInventory\Inc\Helpers as MIHelpers;
?>

<?php foreach ( $po_inventory_order_items as $inventory_order_item ) : ?>

	<?php
	$inventory                         = MIHelpers::get_inventory( $inventory_order_item->inventory_id );
	$filtered_delivery_inventory_items = wp_list_filter( $delivery_inventory_items, array( 'inventory_id' => $inventory->id ) );
	$already_in                        = ! empty( $filtered_delivery_inventory_items ) ? array_sum( wp_list_pluck( $filtered_delivery_inventory_items, 'qty' ) ) : 0;
	$pending                           = $inventory_order_item->qty - $already_in;
	?>

	<tr class="is-inventory" data-product_id="<?php echo esc_attr( $inventory->product_id ) ?>">
		<td class="name" colspan="2">
			<div>
				<span>
					<i class="atum-icon atmi-arrow-child"></i>
					<i class="atum-icon atmi-multi-inventory"></i>
				</span>
				<span class="inventory-name">
					<?php echo esc_html( $inventory->name ) ?>

					<?php if ( $inventory->sku || $inventory->supplier_sku ) : ?>
					<div class="item-meta">
						<?php if ( $inventory->sku ) : ?>
							<div class="atum-order-item-sku"><?php esc_html_e( 'SKU:', ATUM_PO_TEXT_DOMAIN ) ?> <?php echo esc_html( $inventory->sku ) ?></div>
						<?php endif; ?>

						<?php if ( $inventory->supplier_sku ) : ?>
							<div class="atum-order-item-sku"><?php esc_html_e( 'Supplier SKU:', ATUM_PO_TEXT_DOMAIN ) ?> <?php echo esc_html( $inventory->supplier_sku ) ?></div>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</span>
			</div>
		</td>

		<td class="expected center"><?php echo esc_html( $inventory_order_item->qty ) ?></td>
		<td class="already-in center"><?php echo esc_html( $already_in ) ?></td>
		<td class="qty center">
			<input type="number" name="delivery_inventory_qty" data-id="<?php echo esc_attr( $po_item->get_id() ) ?>"
				data-inventory_id="<?php echo esc_attr( $inventory->id ) ?>" value="0"
				min="0" max="<?php echo esc_attr( $pending ) ?>" step="any"<?php disabled( $pending <= 0 ) ?>
			>
		</td>
	</tr>
<?php endforeach;

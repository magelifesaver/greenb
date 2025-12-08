<?php
/**
 * View for the delivery inventory items within the Edit Delivery Modal
 *
 * @since 0.9.3
 * @package Multi-Inventory
 *
 * @var \Atum\PurchaseOrders\Items\POItemProduct                $po_item
 * @var \WC_Product                                             $product
 * @var array                                                   $po_inventory_order_items
 * @var \AtumPO\Deliveries\Items\DeliveryItemProductInventory[] $delivery_inventory_item_objs
 * @var \AtumPO\Deliveries\Models\Delivery                      $delivery
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Integrations\MultiInventory as POMultiInventory;
use AtumMultiInventory\Inc\Helpers as MIHelpers;

?>

<?php foreach ( $po_inventory_order_items as $po_inventory_order_item ) :

	$inventory_id = (int) $po_inventory_order_item->inventory_id;
	$inventory    = MIHelpers::get_inventory( $inventory_id );
	$po_item_id   = (int) $po_inventory_order_item->order_item_id;

	// Get the delivery inventory item associated with this inventory.
	$current_delivery_inventory_item = NULL;
	foreach ( $delivery_inventory_item_objs as $delivery_inventory_item_obj ) :
		if ( $inventory_id === $delivery_inventory_item_obj->get_inventory_id() ) :
			$current_delivery_inventory_item = $delivery_inventory_item_obj;
			break;
		endif;
	endforeach;

	$expected   = (float) $po_inventory_order_item->qty;
	$delivered  = $current_delivery_inventory_item ? $current_delivery_inventory_item->get_quantity() : 0; // Delivered in this delivery.
	$already_in = (float) POMultiInventory::get_po_item_mi_already_in_total( $po_item_id, $inventory_id, FALSE ) - $delivered; // The total already in, also counts the current delivery, so we need to subsctract the delivered.
	$pending    = $expected - $already_in;
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
		<td class="expected center">
			<?php echo esc_html( $expected ); ?>
		</td>
		<td class="already-in center">
			<?php echo esc_html( $already_in ); ?>
		</td>
		<td class="qty center">
			<?php $fully_added = $pending <= 0 && is_null( $current_delivery_inventory_item ); // If there are no more pending items and this inventory wasn't part of this delivery. ?>
			<input type="number" name="delivery_inventory_qty" data-id="<?php echo esc_attr( $po_item_id ) ?>"
				data-inventory_id="<?php echo esc_attr( $inventory_id ) ?>"
				value="<?php echo esc_attr( $delivered ) ?>" min="0" max="<?php echo esc_attr( $pending ) ?>" step="any"
				data-original-value="<?php echo esc_attr( $delivered ) ?>" data-expected="<?php echo esc_attr( $expected ) ?>"
				data-delivered="<?php echo esc_attr( $delivered ) ?>"
				<?php disabled( $fully_added ); ?>
				<?php echo $fully_added ? ' class="atum-tooltip" title="' . esc_html__( 'This inventory was fully added to another delivery', ATUM_PO_TEXT_DOMAIN ) . '"' : '' ?>
			>
		</td>
	</tr>

<?php endforeach;

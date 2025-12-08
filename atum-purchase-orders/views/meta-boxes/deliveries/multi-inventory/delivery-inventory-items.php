<?php
/**
 * View for the delivery inventory items
 *
 * @since 0.9.3
 * @package Multi-Inventory
 *
 * @var \AtumPO\Models\POExtended                               $po
 * @var \AtumPO\Deliveries\Models\Delivery                      $delivery
 * @var \Atum\PurchaseOrders\Items\POItemProduct                $po_item
 * @var \WC_Product                                             $product
 * @var array                                                   $delivery_inventory_items
 * @var \AtumPO\Deliveries\Items\DeliveryItemProductInventory[] $delivery_inventory_item_objs
 * @var object[]                                                $po_inventory_order_items
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Deliveries\Models\Delivery;
use AtumMultiInventory\Inc\Helpers as MIHelpers;

if ( empty( $delivery_inventory_items ) ) :
	return;
endif;

/**
 * Variables definition
 *
 * @var array $expected_qtys
 * @var array $already_in_qtys
 * @var array $delivered_qtys
 * @var array $pending_qtys
 * @var int   $delivered_total
 * @var int   $already_in_total
 * @var int   $pending_total
 */
extract( Delivery::calculate_delivery_items_qtys( $delivery_inventory_item_objs, $po ) );

// Get only the delivery item inventories.
$expected_qtys   = $expected_qtys['delivery_item_inventory'] ?? [];
$already_in_qtys = $already_in_qtys['delivery_item_inventory'] ?? [];
$delivered_qtys  = $delivered_qtys['delivery_item_inventory'] ?? [];
$pending_qtys    = $pending_qtys['delivery_item_inventory'] ?? [];
?>

<?php foreach ( $delivery_inventory_items as $delivery_inventory_item ) : ?>

	<?php
	// If the delivery item inventory doesn't belong to the PO item or to this delivery, continue.
	if (
		( $po_item && $po_item->get_id() !== absint( $delivery_inventory_item->po_item_id ) ) ||
		! array_key_exists( $delivery_inventory_item->order_item_id, $delivery_inventory_item_objs )
	) :
		continue;
	endif;

	$inventory        = MIHelpers::get_inventory( $delivery_inventory_item->inventory_id );
	$po_item_id       = (int) $delivery_inventory_item->po_item_id;
	$delivery_item_id = (int) $delivery_inventory_item->order_item_id;
	$delivered        = $delivered_qtys[ $po_item_id ][ $delivery_item_id ] ?? 0;
	$already_in       = $already_in_qtys[ $po_item_id ][ $delivery_item_id ] ?? 0;
	$pending          = $pending_qtys[ $po_item_id ][ $delivery_item_id ] ?? 0;

	$delivery_inventory_item_obj = $delivery_inventory_item_objs[ $delivery_item_id ] ?? NULL;
	$is_stock_changed            = $delivery_inventory_item_obj && 'yes' === $delivery_inventory_item_obj->get_stock_changed();
	$is_missing                  = ! $po_item || empty( wp_list_filter( $po_inventory_order_items, [ 'inventory_id' => $inventory->id ] ) )
	?>

	<tr class="is-inventory<?php echo $is_stock_changed ? ' stock-changed' : '' ?><?php echo $is_missing ? ' missing-item atum-tooltip' : '' ?>"
		data-po-item="<?php echo esc_attr( $po_item_id ) ?>" data-delivery-item="<?php echo esc_attr( $delivery_item_id ) ?>"
		data-inventory-id="<?php echo esc_attr( $inventory->id ) ?>"
		<?php echo $is_missing ? ' title="' . esc_attr__( 'The PO inventory item related to this delivery inventory item is missing', ATUM_PO_TEXT_DOMAIN ) . '"' : '' ?>
	>
		<td class="name" colspan="2">
			<div>
				<span>
					<i class="atum-icon atmi-arrow-child"></i>
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

				<div class="order-item-icons">
					<?php if ( $is_stock_changed ) : ?>
						<i class="atum-icon atmi-highlight color-warning tips" data-tip="<?php esc_attr_e( 'This inventory item was already added to stock on this delivery', ATUM_PO_TEXT_DOMAIN ) ?>"></i>
					<?php endif; ?>

					<?php do_action( 'atum/purchase_orders_pro/delivery_inventory_icons', $po_item, $inventory ) ?>
				</div>


			</div>
		</td>

		<td class="already-in center"><?php echo esc_html( wc_stock_amount( $already_in ) ) ?></td>
		<td class="delivered center"><?php echo esc_html( wc_stock_amount( $delivered ) ) ?></td>
		<td class="pending center"><?php echo esc_html( wc_stock_amount( $pending ) ) ?></td>

		<?php if ( $po->is_editable() && ! $po->is_due() ) : ?>
		<td class="actions center">
			<i class="show-actions atum-icon atmi-options"></i>
		</td>
		<?php endif; ?>
	</tr>

	<?php do_action( 'atum/purchase_orders_pro/after_delivery_inventory_item', $delivery_inventory_item_obj, $product, $delivery, $delivery_inventory_item, $inventory ) ?>

<?php endforeach;

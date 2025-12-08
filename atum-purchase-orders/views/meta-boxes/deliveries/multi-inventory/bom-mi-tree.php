<?php
/**
 * View for the BOM tree UI within the Delivery Inventory Order Items
 *
 * @since 0.9.24
 *
 * @var Inventory                         $inventory
 * @var object                            $order_item_inventory
 * @var int                               $order_type_id
 * @var POItemProduct|DeliveryItemProduct $order_item
 * @var array                             $bom_order_items
 * @var array                             $unsaved_bom_order_items
 * @var \WC_Product                       $item_product
 * @var int|float                         $order_item_qty
 * @var POExtended|Delivery               $order
 * @var boolean                           $is_completed
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Deliveries\Models\Delivery;
use AtumPO\Models\POExtended;
use Atum\PurchaseOrders\Items\POItemProduct;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumMultiInventory\Models\Inventory;

$order_item_qty = $order_item_qty ?? $order_item->get_quantity();
$nesting_level  = 1;
$accumulated[]  = $order_item_qty;
$is_editable    = TRUE;

if ( $order instanceof POExtended ) :
	$is_editable = $order->is_editable() && ! $order->is_due() && 'yes' !== $order_item->get_stock_changed();
elseif ( $order instanceof Delivery ) :
	$po          = AtumHelpers::get_atum_order_model( $order->po, TRUE, PurchaseOrders::POST_TYPE );
	$is_editable = $po->is_editable() && ! $po->is_due() && 'yes' !== $order_item->get_stock_changed();
endif;
?>
<tr class="order-item-bom-tree-panel" data-sort-ignore="true" data-atum_order_item_id="<?php echo esc_attr( $order_item->get_id() ) ?>">
	<td colspan="6" class="delivery-item-bom-tree bom-tree-wrapper">

		<div class="bom-tree-field" style="display: none">

			<div class="bom-tree-field-actions">
				<a href="#" class="open-nodes"><?php esc_html_e( 'Expand all', ATUM_PO_TEXT_DOMAIN ) ?></a> |
				<a href="#" class="close-nodes"><?php esc_html_e( 'Collapse all', ATUM_PO_TEXT_DOMAIN ) ?></a>
			</div>

			<div class="atum-bom-tree<?php if ( ! $is_editable ) echo ' read-only' ?>" data-qty="<?php echo esc_attr( $order_item_qty ) ?>">
				<ul>
					<li class="isExpanded isFolder" data-uiicon="<?php echo esc_attr( AtumHelpers::get_atum_icon_type( $item_product ) ) ?>">
						<?php echo esc_html( $item_product->get_name() ) ?> (<?php echo esc_html( apply_filters( 'atum/product_levels/order_item/bom_tree_qty', $order_item_qty, $item_product ) ) ?>)

						<?php
						AtumHelpers::load_view(
							ATUM_LEVELS_PATH . 'views/meta-boxes/order-items/multi-inventory/bom-mi-subtree',
							compact( 'inventory', 'order_item_inventory', 'order_type_id', 'order_item', 'order_item_qty', 'bom_order_items', 'unsaved_bom_order_items', 'item_product', 'accumulated', 'nesting_level', 'is_completed' )
						);
						?>
					</li>
				</ul>
			</div>

		</div>

	</td>
</tr>

<?php
/**
 * View for the BOM tree UI within the Delivery items with MI disabled
 *
 * @since 0.9.24
 *
 * @var int                               $order_type_id
 * @var POItemProduct|DeliveryItemProduct $order_item
 * @var array                             $bom_order_items
 * @var array                             $unsaved_bom_order_items
 * @var \WC_Product                       $item_product
 * @var bool                              $is_editable
 * @var bool                              $transient_created
 * @var int|float                         $order_item_qty
 * @var POExtended|Delivery               $order
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Models\POExtended;
use AtumPO\Deliveries\Models\Delivery;
use Atum\PurchaseOrders\Items\POItemProduct;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use Atum\Addons\Addons;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders;

$order_item_qty = isset( $order_item_qty ) ? $order_item_qty : $order_item->get_quantity();
$nesting_level  = 1;
$accumulated[]  = $order_item_qty;

if ( $order instanceof POExtended ) :
	$is_editable = $order->is_editable() && ! $order->is_due() && 'yes' !== $order_item->get_stock_changed();
elseif ( $order_item instanceof Delivery ) :
	$po          = AtumHelpers::get_atum_order_model( $order->po, TRUE, PurchaseOrders::POST_TYPE );
	$is_editable = $po->is_editable() && ! $po->is_due() && 'yes' !== $order_item->get_stock_changed();
endif;

?>
<tr class="order-item-bom-tree-panel" data-sort-ignore="true" data-transient="<?php echo $transient_created ? 'yes' : 'no' ?>"
	data-atum_order_item_id="<?php echo esc_attr( $order_item->get_id() ) ?>"
>
	<td colspan="6" class="delivery-item-bom-tree bom-tree-wrapper">

		<div class="bom-tree-field" style="display: none">

			<div class="bom-tree-field-actions">
				<a href="#" class="open-nodes"><?php esc_html_e( 'Expand all', ATUM_PO_TEXT_DOMAIN ) ?></a> |
				<a href="#" class="close-nodes"><?php esc_html_e( 'Collapse all', ATUM_PO_TEXT_DOMAIN ) ?></a>
			</div>

			<div class="atum-bom-tree<?php if ( ! $is_editable ) echo ' read-only' ?>" data-qty="<?php echo esc_attr( $order_item_qty ) ?>">

				<ul>
					<li class="isExpanded isFolder" data-uiicon="<?php echo esc_attr( AtumHelpers::get_atum_icon_type( $item_product ) ) ?>">
						<?php echo esc_html( $item_product->get_name() ) ?> (<?php echo esc_html( apply_filters( 'atum/product_levels/order_item/bom_tree_qty', $accumulated[0], $item_product ) ) ?>)

						<?php
						$view = Addons::is_addon_active( 'multi_inventory' ) ? 'views/meta-boxes/order-items/multi-inventory/bom-mi-subtree' : 'views/meta-boxes/order-items/bom-subtree';
						AtumHelpers::load_view(
							ATUM_LEVELS_PATH . $view,
							compact( 'bom_order_items', 'unsaved_bom_order_items', 'item_product', 'order_item', 'accumulated', 'order_type_id', 'nesting_level' )
						);
						?>
					</li>
				</ul>

			</div>
		</div>

	</td>
</tr>

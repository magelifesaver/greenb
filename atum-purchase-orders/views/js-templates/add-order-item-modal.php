<?php
/**
 * View for the Add Order Item modal for purchase order items
 *
 * @since 0.7.9
 *
 * @var POExtended $atum_order
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Models\POExtended;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders;

// TODO: THE DELIVERY MODALS SHOULD BE UPDATED AUTOMATICALLY AFTER CHANGING ANY PO ITEM.

// Check whether we should exclude some products.
$order_items = $atum_order->get_items();
$excluded    = $included = array();

if ( ! empty( $order_items ) ) {

	foreach ( $order_items as $order_item ) {
		/**
		 * Variable definition
		 *
		 * @var POItemProduct $order_item
		 */
		$excluded[] = $order_item->get_variation_id() ?: $order_item->get_product_id();
	}

	$excluded = apply_filters( 'atum/purchase_orders_pro/add_item_search_excluded', array_unique( $excluded ), $order_items );

}

$excluded_data = apply_filters( 'atum/purchase_orders_pro/add_item_search_excluded_data', 'data-exclude="' . implode( ',', $excluded ) . '"', $excluded, $order_items );

if ( $atum_order->is_returning() ) {

	$original_po = AtumHelpers::get_atum_order_model( $atum_order->related_po, TRUE, PurchaseOrders::POST_TYPE );

	if ( $original_po->exists() ) {

		$original_po_items = $original_po->get_items();

		foreach ( $original_po_items as $original_po_item ) {
			/**
			 * Variable definition
			 *
			 * @var POItemProduct $original_po_item
			 */
			$id = $original_po_item->get_variation_id() ?: $original_po_item->get_product_id();

			if ( in_array( $id, $excluded ) ) {
				continue;
			}

			$included[] = $id;
		}

	}

	$included = apply_filters( 'atum/purchase_orders_pro/add_item_search_included', array_unique( $included ), $order_items );

}

$included_data = apply_filters( 'atum/purchase_orders_pro/add_item_search_included_data', 'data-include="' . implode( ',', $included ) . '"', $included, $order_items );

?>
<script type="text/template" id="add-order-item-modal">
	<div class="atum-modal-content">

		<div class="note">
			<?php if ( ! $atum_order->is_returning() ) : ?>
				<?php esc_html_e( 'Select the items you want to add to the purchase order.', ATUM_PO_TEXT_DOMAIN ) ?>
			<?php else : ?>
				<?php esc_html_e( 'Select the items you want to add to the returning purchase order.', ATUM_PO_TEXT_DOMAIN ) ?>
			<?php endif; ?>
			<br>
		</div>

		<hr>

		<?php if ( $atum_order->is_returning() ) : ?>
			<div class="alert alert-primary">
				<i class="atum-icon atmi-info"></i>
				<p><?php esc_html_e( 'Only the items delivered on the original PO can be added here.', ATUM_PO_TEXT_DOMAIN ) ?></p>
			</div>
		<?php endif; ?>

		<div class="add-order-item-modal__search">
			<select multiple="multiple" id="search-po-products" data-post-id="<?php echo absint( $atum_order->get_id() ) ?>"
				data-placeholder="<?php esc_attr_e( 'Search products&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"
				data-action="atum_po_json_search_products" style="width:100%" data-nonce="<?php echo esc_attr( wp_create_nonce( 'search-products' ) ) ?>"
				<?php echo $excluded_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $included_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			></select>
		</div>

		<div class="add-order-item-modal__items no-items">

			<div class="add-products-block">
				<span class="no-items-text"><?php esc_html_e( 'No items selected yet', ATUM_PO_TEXT_DOMAIN ); ?></span>
				<span class="no-items-text no-items-supplier-text"><?php esc_html_e( 'Search for products manually or use the automatic filters below:', ATUM_PO_TEXT_DOMAIN ); ?></span>
			</div>

			<form id="add-items-results">
				<table>
					<thead>
						<tr>
							<th class="item"><?php esc_attr_e( 'Item', ATUM_PO_TEXT_DOMAIN ); ?></th>
							<th class="cost"><?php esc_attr_e( 'Cost', ATUM_PO_TEXT_DOMAIN ); ?></th>
							<th class="qty"><?php esc_attr_e( 'Qty', ATUM_PO_TEXT_DOMAIN ); ?></th>
							<th class="actions"></th>
						</tr>
					</thead>
					<tbody></tbody>
					<tfoot></tfoot>
				</table>
			</form>

			<?php AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/add-supplier-items' ); ?>

		</div>

		<div class="add-order-item-modal__total">
			<strong><?php esc_html_e( 'Total items:', ATUM_PO_TEXT_DOMAIN ); ?> <span class="total-items">0</span></strong>
		</div>
	</div>
</script>

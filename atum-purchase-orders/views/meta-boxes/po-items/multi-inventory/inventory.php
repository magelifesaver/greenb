<?php
/**
 * View for the Inventories' Header UI within the Order items
 *
 * @since 0.7.4
 *
 * @var POExtended                               $order
 * @var object                                   $order_item_inventory
 * @var int                                      $order_type_id // Inherited.
 * @var \Atum\PurchaseOrders\Items\POItemProduct $item
 * @var \WC_Product                              $product
 * @var int                                      $item_id
 * @var string                                   $currency
 * @var string                                   $currency_template
 * @var int|float                                $step
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumStockDecimals;
use AtumMultiInventory\Inc\Helpers as MIHelpers;
use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use AtumPO\Inc\Helpers;
use AtumPO\Models\POExtended;
use AtumPO\Inc\ReturningPOs;

$reduced_stock   = 0;
$has_multi_price = MIHelpers::has_multi_price( $product );
$is_editable     = $order->is_editable();
$display_fields  = AtumHelpers::get_option( 'po_display_extra_fields', [] );
$display_fields  = ! empty( $display_fields['options'] ) && is_array( $display_fields['options'] ) ? $display_fields['options'] : [];

if ( empty( $order_item_inventory ) ) :
	$total        = $subtotal = $cost = 0;
	$qty          = isset( $product ) && $product instanceof \WC_Product ? $product->get_min_purchase_quantity() : 1;
	$inventory    = MIHelpers::get_inventory( 0 );
	$item_id      = '{{item_id}}';
	$inventory_id = '{{inventory_id}}';
	$extra_data   = array(
		'name'         => $inventory->name,
		'sku'          => $inventory->sku,
		'supplier_sku' => $inventory->supplier_sku,
	);
else :
	// WC product bundles compatibility.
	$is_bundle_item = class_exists( '\WC_Product_Bundle' ) && wc_pb_is_bundled_order_item( $item ) ? TRUE : FALSE;
	$inventory_id   = absint( $order_item_inventory->inventory_id );
	$total          = ! $is_bundle_item || ! empty( floatval( $item->get_total() ) ) ? $order_item_inventory->total : 0;
	$subtotal       = ! $is_bundle_item || ! empty( floatval( $item->get_subtotal() ) ) ? $order_item_inventory->subtotal : 0;
	$qty            = wc_format_decimal( floatval( $order_item_inventory->qty ), AtumStockDecimals::get_stock_decimals(), TRUE );
	$cost           = floatval( $subtotal ) / $qty;
	$inventory      = MIHelpers::get_inventory( $inventory_id );
	$reduced_stock  = (int) $order_item_inventory->reduced_stock;
	$extra_data     = maybe_unserialize( $order_item_inventory->extra_data );
endif;

if ( $inventory->managing_stock() ) :
	$stock_available = wc_format_decimal( floatval( $inventory->stock_quantity ), AtumStockDecimals::get_stock_decimals(), TRUE );
else :
	$stock_available = 'instock' === $inventory->stock_status ? '&infin;' : '--';
endif;

$supplier_data = $inventory->supplier_id ? ' data-supplier="' . $inventory->supplier_id . '"' : '';
$max_qty       = NULL;
$min_qty       = Helpers::get_minimum_quantity_to_add();

?>
<tr class="order-item-inventory collapsed" data-inventory_id="<?php echo esc_attr( $inventory_id ) ?>"
	data-atum_order_item_id="<?php echo esc_attr( $item_id ) ?>"<?php echo $supplier_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>

	<td class="thumb"></td>

	<td class="inventory-name">
		<span>
			<i class="atum-icon atmi-arrow-child text-success"></i>
		</span>
		<span class="mi-text">
			<?php echo esc_html( $extra_data['name'] ) ?>

			<?php if ( isset( $extra_data['sku'] ) && $extra_data['sku'] ) : ?>
				(<?php echo esc_html( $extra_data['sku'] ) ?>)
			<?php endif; ?>

			<?php if ( $inventory->is_main() ) : ?>
				<i class="atum-icon atmi-store tips" data-tip="<?php esc_attr_e( 'This is the Main inventory', ATUM_PO_TEXT_DOMAIN ); ?>"></i>
			<?php endif; ?>

			<?php if ( ! $inventory->managing_stock() ) : ?>
				<i class="atum-icon atmi-warning color-warning atum-tooltip" title="<?php esc_attr_e( "This inventory's stock is not managed at inventory level, so it won't be updated when processing this PO", ATUM_PO_TEXT_DOMAIN ) ?>"></i>
			<?php endif; ?>

			<button class="toggle-indicator tips" data-tip="<?php esc_attr_e( 'Toggle info', ATUM_PO_TEXT_DOMAIN ) ?>"></button>

			<?php if ( isset( $extra_data['supplier_sku'] ) && $extra_data['supplier_sku'] ) : ?>
				<div class="atum-inventory-supplier-sku"><strong><?php esc_html_e( 'Supplier SKU:', ATUM_PO_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $extra_data['supplier_sku'] ) ?></div>
			<?php endif; ?>
		</span>
	</td>

	<?php // Available Stock. ?>
	<?php if ( ! array_key_exists( 'stock', $display_fields ) || 'yes' === $display_fields['stock'] ) : ?>
	<td class="available_stock center" style="width: 1%">
		<div class="view">
			<?php echo esc_html( $stock_available ); ?>
		</div>
	</td>
	<?php endif; ?>

	<?php // Last Week Sales. ?>
	<?php if ( ! array_key_exists( 'last_week_sales', $display_fields ) || 'yes' === $display_fields['last_week_sales'] ) : ?>
	<td class="last_week_sales center" style="width: 1%"></td>
	<?php endif; ?>

	<?php // Inbound Stock. ?>
	<?php if ( ! array_key_exists( 'inbound_stock', $display_fields ) || 'yes' === $display_fields['inbound_stock'] ) : ?>
	<td class="inbound_stock center" style="width: 1%"></td>
	<?php endif; ?>

	<?php // Recommended Order Quantity. ?>
	<?php if ( ! array_key_exists( 'recommended_quantity', $display_fields ) || 'yes' === $display_fields['recommended_quantity'] ) : ?>
	<td class="roq center" style="width: 1%"></td>
	<?php endif; ?>

	<?php // Cost. ?>
	<td class="item_cost center" style="width: 1%">
		<?php if ( $has_multi_price ) : ?>
			<div class="atum-edit-field__wrapper">

				<div class="<?php echo $is_editable ? 'editable atum-edit-field' : '' ?>" data-content-id="edit-cost-<?php echo esc_attr( "$item_id-$inventory_id" ) ?>">
					<span class="field-label currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
						data-decimal-separator="<?php echo esc_attr( $order->price_decimal_sep ) ?>"
						data-none="<?php echo esc_attr( str_replace( '%value%', '0.00', $currency_template ) ) ?>"
						data-value="<?php echo esc_attr( $cost ) ?>" data-decimals-number="2"
					>
						<?php echo $order->format_price( $cost ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				</div>

				<?php // We can't use a <script> here as template because in some cases this view is already within a template. ?>
				<template id="edit-cost-<?php echo esc_attr( "$item_id-$inventory_id" ) ?>">
					<input type="number" step="<?php echo esc_attr( $step ) ?>" min="0" autocomplete="off"
						value="<?php echo esc_attr( $cost ) ?>" class="meta-value" name="meta-value-cost">
				</template>

				<input type="hidden" name="oi_inventory_cost[<?php echo esc_attr( $item_id ) ?>][<?php echo esc_attr( $inventory_id ) ?>]" value="<?php echo esc_attr( $cost ) ?>">

			</div>
		<?php endif; ?>
	</td>

	<?php // Discount.
	$item_discount = wc_format_decimal( $qty ? ( (float) $subtotal - (float) $total ) / (float) $qty : 0, '' ); ?>
	<td class="discount center" style="width: 1%">
		<?php if ( $has_multi_price ) : ?>
			<div class="atum-edit-field__wrapper">

				<div class="<?php echo $is_editable ? 'editable atum-edit-field' : '' ?>" data-content-id="edit-discount-<?php echo esc_attr( "$item_id-$inventory_id" ) ?>">
					<span class="field-label currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
						data-decimal-separator="<?php echo esc_attr( $order->price_decimal_sep ) ?>" data-none="–"
						data-value="<?php echo esc_attr( $item_discount ) ?>" data-decimals-number="2"
					>
						<?php if ( floatval( $item_discount ) ) : ?>
							<?php echo $order->format_price( $item_discount ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							&ndash;
						<?php endif; ?>
					</span>
				</div>

				<template id="edit-discount-<?php echo esc_attr( "$item_id-$inventory_id" ) ?>">
					<div class="edit-fields-group">
						<div class="input-group number-mask">

							<input type="number" step="any" min="0" autocomplete="off"
								value="<?php echo esc_attr( $item_discount ) ?>" class="meta-value" name="meta-value-discount"
							>

							<span class="input-group-append" title="<?php esc_html_e( 'Click to switch behaviour', ATUM_PO_TEXT_DOMAIN ); ?>">
								<span class="input-group-text" data-value="percentage">%</span>
								<span class="input-group-text active" data-value="amount"><?php echo esc_html( get_woocommerce_currency_symbol( $currency ) ) ?></span>
								<input type="hidden" name="type" value="amount">
							</span>

						</div>

						<a href="#" class="set-default-value" data-type="percentage" data-meta_value_discount="<?php echo esc_attr( $order->supplier_discount ) ?>"><?php esc_html_e( 'Set default discount', ATUM_PO_TEXT_DOMAIN ); ?></a>
					</div>
				</template>

				<?php
				$discount_config_atts = $discount_config = '';

				if ( ! empty( $order_item_inventory ) ) :
					$extra_data = maybe_unserialize( $order_item_inventory->extra_data );

					if (
						$extra_data && is_array( $extra_data ) && ! empty( $extra_data['discount_config'] )
						&& ! empty( $extra_data['discount_config']['fieldValue'] ) && ! empty( $extra_data['discount_config']['type'] )
					) :
						$discount_config      = $extra_data['discount_config'];
						$discount_config_atts = ' data-field-value="' . $discount_config['fieldValue'] . '" data-type="' . $discount_config['type'] . '"';
					endif;
				endif;
				?>
				<input type="hidden" name="oi_inventory_discount[<?php echo esc_attr( $item_id ) ?>][<?php echo esc_attr( $inventory_id ) ?>]"
					value="<?php echo esc_attr( $item_discount ) ?>"<?php echo $discount_config_atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

			</div>

			<input type="hidden" name="oi_inventory_discount_config[<?php echo absint( $item_id ) ?>]" value='<?php echo wp_json_encode( $discount_config ) ?>'>
		<?php endif; ?>
	</td>

	<?php // Returned Quantity.
	if ( $order->is_returning() ) : ?>
		<?php
		$returned_qty = $delivered_qty = 0;

		// For Returning POs, restrict the maximum units.
		if ( $order->is_returning() && $order->related_po ) :
			$returned_po_items  = ReturningPOs::get_returned_po_items( $order->related_po );
			$delivered_po_items = Helpers::get_delivered_po_items( $order->related_po );
			$returned_qty       = $returned_po_items[ "$inventory->product_id:$inventory_id" ] ?? 0;
			$delivered_qty      = $delivered_po_items[ "$inventory->product_id:$inventory_id" ] ?? 0;
			$max_qty            = max( $delivered_qty - ( $returned_qty - $qty ), 0 );
		endif;
		?>
		<td class="returned-ratio center<?php echo $returned_qty > $delivered_qty ? ' color-danger' : '' ?>" style="width: 1%" data-returned="<?php echo esc_attr( $returned_qty ); ?>" data-delivered="<?php echo esc_attr( $delivered_qty ); ?>">
			<?php echo esc_html( $returned_qty ); ?>/<?php echo esc_html( $delivered_qty ); ?>
		</td>
	<?php endif; ?>

	<?php // Quantity. ?>
	<td class="quantity center" style="width: 1%">
		<div class="atum-edit-field__wrapper">

			<div class="<?php echo $is_editable ? 'editable atum-edit-field' : '' ?>" data-content-id="edit-quantity-<?php echo esc_attr( "$item_id-$inventory_id" ) ?>">
				<span class="field-label" data-template="&times;%value%" data-none="x0"
					<?php echo AtumStockDecimals::get_stock_decimals() ? ' data-decimal-separator="' . esc_attr( $order->price_decimal_sep ) . '" data-decimals-number="' . esc_attr( AtumGlobals::get_stock_decimals() ) . '" data-strip-zeros="yes"' : ''; ?>
				>
					&times;<?php echo esc_html( $qty ); ?>
				</span>
			</div>

			<template id="edit-quantity-<?php echo esc_attr( "$item_id-$inventory_id" ) ?>">
				<input type="number" step="<?php echo esc_attr( apply_filters( 'atum/atum_order/quantity_input_step', $step, $inventory ) ) ?>"
					min="<?php echo esc_attr( $min_qty ); ?>" autocomplete="off" value="<?php echo esc_attr( $qty ) ?>" class="meta-value" name="meta-value-qty"
					<?php echo ! is_null( $max_qty ) ? ' max="' . esc_attr( $max_qty ) . '"' : '' ?>>
			</template>

			<input type="hidden" name="oi_inventory_qty[<?php echo esc_attr( $item_id ) ?>][<?php echo esc_attr( $inventory_id ) ?>]" value="<?php echo esc_attr( $qty ) ?>" class="quantity-input">

		</div>
	</td>

	<?php // Total. ?>
	<td class="line_total center" style="width: 1%">
		<?php if ( $has_multi_price ) : ?>

			<span class="field-label currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
				data-decimal-separator="<?php echo esc_attr( $order->price_decimal_sep ) ?>"
				data-value="<?php echo esc_attr( $total ) ?>" data-decimals-number="2"
			>
				<?php echo $order->format_price( $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>

			<input type="hidden" class="line_total" name="oi_inventory_total[<?php echo esc_attr( $item_id ) ?>][<?php echo esc_attr( $inventory_id ) ?>]" value="<?php echo esc_attr( $total ) ?>">

		<?php endif; ?>
	</td>

	<?php if ( Helpers::may_use_po_taxes( $order ) ) : ?>
	<td class="line_tax center" style="width: 1%"></td>
	<?php endif; ?>

	<?php // Actions. ?>
	<?php if ( $is_editable ) : ?>
	<td class="actions center" style="width: 1%">
		<i class="show-actions atum-icon atmi-options"></i>
	</td>
	<?php endif; ?>

	<?php do_action( 'atum/multi_inventory/after_order_item_inventory', $item, $inventory ); ?>

</tr>
<tr class="inventory-info" data-inventory_id="<?php echo esc_attr( $inventory_id ) ?>" data-atum_order_item_id="<?php echo esc_attr( $item_id ) ?>">
	<td class="thumb"></td>
	<td colspan="11">
		<div class="inventory-info-wrapper" style="display: none">
			<?php require ATUM_MULTINV_PATH . 'views/meta-boxes/order-items/inventory-info.php' ?>
		</div>
	</td>
</tr>

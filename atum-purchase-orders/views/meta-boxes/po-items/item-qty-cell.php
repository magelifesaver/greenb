<?php
/**
 * View for the PO's item and invoice item qty columns
 *
 * @since 0.9.6
 *
 * @var \AtumPO\Models\POExtended                              $atum_order
 * @var int                                                    $item_id
 * @var \Atum\Components\AtumOrders\Items\AtumOrderItemProduct $item
 * @var WC_Product                                             $product
 * @var int|float                                              $step
 * @var string                                                 $field_name_prefix
 * @var bool                                                   $disable_edit      Optional.
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumStockDecimals;
use AtumPO\Inc\Helpers;
use AtumPO\Inc\ReturningPOs;
use Atum\Inc\Globals as AtumGlobals;

$item_qty = $item->get_quantity();
$min_qty  = Helpers::get_minimum_quantity_to_add();

// For Returning POs, restrict the maximum units.
$max_qty = NULL;
if ( $atum_order->is_returning() && $atum_order->related_po ) :

	$returned_po_items  = ReturningPOs::get_returned_po_items( $atum_order->related_po );
	$delivered_po_items = Helpers::get_delivered_po_items( $atum_order->related_po );
	$product_id         = $product instanceof \WC_Product ? $product->get_id() : $item->get_product_id();

	if ( array_key_exists( $product_id, $returned_po_items ) && array_key_exists( $product_id, $delivered_po_items ) ) :
		$max_qty = max( $delivered_po_items[ $product_id ] - ( $returned_po_items[ $product_id ] - $item_qty ), 0 );
	endif;
endif;
?>
<td class="quantity center" style="width: 1%">
	<div class="atum-edit-field__wrapper">

		<div class="<?php echo ( $atum_order->is_editable() && ( ! isset( $disable_edit ) || ! $disable_edit ) ) ? 'editable atum-edit-field' : '' ?>" data-content-id="edit-quantity-<?php echo esc_attr( $item_id ) ?>">
			<span class="field-label" data-template="&times;%value%" data-none="x0"
				<?php echo AtumStockDecimals::get_stock_decimals() ? ' data-decimal-separator="' . esc_attr( $atum_order->price_decimal_sep ) . '" data-decimals-number="' . esc_attr( AtumGlobals::get_stock_decimals() ) . '" data-strip-zeros="yes"' : ''; ?>
			>
				&times;<?php echo esc_html( $item_qty ); ?>
			</span>
		</div>

		<template id="edit-quantity-<?php echo esc_attr( $item_id ) ?>">
			<input type="number"
				step="<?php echo esc_attr( apply_filters( 'atum/atum_order/quantity_input_step', $step, $product ) ) ?>"
				min="<?php echo esc_attr( $min_qty ); ?>" autocomplete="off" value="<?php echo esc_attr( $item_qty ) ?>" class="meta-value"
				name="meta-value-qty"<?php echo ! is_null( $max_qty ) ? ' max="' . esc_attr( $max_qty ) . '"' : '' ?>
			>
		</template>

		<input type="hidden" name="<?php echo esc_attr( $field_name_prefix ) ?>qty[<?php echo absint( $item_id ) ?>]"
			value="<?php echo esc_attr( $item_qty ) ?>" class="quantity-input"
		>

	</div>
</td>

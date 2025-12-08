<?php
/**
 * View for the PO's shipping items
 *
 * @since 0.4.0
 *
 * @var \Atum\Components\AtumOrders\Items\AtumOrderItemShipping $item
 * @var \Atum\Components\AtumOrders\Models\AtumOrderModel       $atum_order
 * @var int                                                     $item_id
 * @var array                                                   $shipping_methods
 * @var string                                                  $currency
 * @var string                                                  $currency_template
 * @var string                                                  $field_name_prefix
 * @var array                                                   $display_fields
 */

defined( 'ABSPATH' ) || die;

$is_editable = $atum_order->is_editable();

do_action( 'atum/atum_order/before_item_shipping_html', $item, $atum_order );

$colspan = $atum_order->is_returning() ? 8 : 7;

$display_columns = [
	'stock',
	'last_week_sales',
	'inbound_stock',
	'recommended_quantity',
];

foreach ( $display_columns as $display_col ) :
	if ( isset( $display_fields[ $display_col ] ) && 'no' === $display_fields[ $display_col ] ) :
		$colspan--;
	endif;
endforeach;

?>
<tr class="shipping <?php echo esc_attr( ! empty( $class ) ? $class : '' ) ?>" data-atum_order_item_id="<?php echo absint( $item_id ) ?>">
	<td class="thumb">
		<div class="atum-order-item-thumbnail"></div>
	</td>

	<?php // Shipping name.
	$shipping_name = $item->get_name() ?: __( 'Shipping', ATUM_PO_TEXT_DOMAIN ); ?>
	<td class="name">
		<div class="atum-edit-field__wrapper">

			<div class="<?php echo $is_editable ? 'editable atum-edit-field' : '' ?>" data-content-id="edit-name-<?php echo esc_attr( $item_id ) ?>">
				<span class="field-label">
					<?php echo esc_html( $shipping_name ); ?>
				</span>
			</div>

			<template id="edit-name-<?php echo esc_attr( $item_id ) ?>">
				<input type="text" autocomplete="off" value="<?php echo esc_attr( $shipping_name ) ?>" class="meta-value">
			</template>

			<input type="hidden" name="atum_order_item_name[<?php echo absint( $item_id ) ?>]" value="<?php echo esc_attr( $shipping_name ) ?>">

		</div>
	</td>

	<?php do_action( 'atum/atum_order/shipping_item_values', NULL, $item, $item_id ); ?>

	<td colspan="<?php echo esc_attr( $colspan ); ?>">
		&nbsp;
		<input type="hidden" class="atum_order_item_id" name="atum_order_shipping_id[]" value="<?php echo absint( $item_id ) ?>">
	</td>

	<?php // Shipping Cost.
	$item_cost = wc_format_decimal( $item->get_total() );
	require 'item-cost-cell.php';
	?>

	<?php require 'item-tax-cell.php'; // Tax. ?>

	<?php // Actions. ?>
	<?php if ( $is_editable ) : ?>
	<td class="actions center" style="width: 1%">
		<i class="show-actions atum-icon atmi-options"></i>
	</td>
	<?php endif; ?>
</tr>
<?php

do_action( 'atum/atum_order/after_item_shipping_html', $item, $atum_order );

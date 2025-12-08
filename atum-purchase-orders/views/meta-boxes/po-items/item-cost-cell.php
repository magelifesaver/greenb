<?php
/**
 * View for the PO's item and invoice item cost columns
 *
 * @since 0.7.0
 *
 * @var \AtumPO\Models\POExtended $atum_order
 * @var int|float                 $item_cost
 * @var int                       $item_id
 * @var int|float                 $step
 * @var string                    $currency
 * @var string                    $currency_template
 * @var string                    $field_name_prefix
 * @var bool                      $disable_edit      Optional.
 */

defined( 'ABSPATH' ) || die;

?>
<td class="item_cost center" style="width: 1%" data-sort-value="<?php echo esc_attr( $item_cost ); ?>">
	<div class="atum-edit-field__wrapper">

		<div class="<?php echo ( $atum_order->is_editable() && ( ! isset( $disable_edit ) || ! $disable_edit ) ) ? 'editable atum-edit-field' : '' ?>"
			data-content-id="edit-cost-<?php echo esc_attr( $item_id ) ?>"
		>
			<span class="field-label currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
				data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>"
				data-none="<?php echo esc_attr( str_replace( '%value%', '0.00', $currency_template ) ) ?>"
				data-value="<?php echo esc_attr( $item_cost ) ?>" data-decimals-number="2"
			>
				<?php echo $atum_order->format_price( $item_cost ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
		</div>

		<template id="edit-cost-<?php echo esc_attr( $item_id ) ?>">
			<input type="number" step="<?php echo esc_attr( $step ) ?>" min="0" autocomplete="off"
				value="<?php echo esc_attr( $item_cost ) ?>" class="meta-value" name="meta-value-cost">
		</template>

		<input type="hidden" name="<?php echo esc_attr( $field_name_prefix ) ?>cost[<?php echo absint( $item_id ) ?>]" value="<?php echo esc_attr( $item_cost ) ?>">

	</div>
</td>

<?php
/**
 * View for the PO's item and invoice item discount columns
 *
 * @since 0.9.6
 *
 * @var \AtumPO\Models\POExtended                              $atum_order
 * @var int|float                                              $item_discount
 * @var int                                                    $item_id
 * @var \Atum\Components\AtumOrders\Items\AtumOrderItemProduct $item
 * @var WC_Product                                             $product
 * @var int|float                                              $step
 * @var string                                                 $currency
 * @var string                                                 $currency_template
 * @var string                                                 $field_name_prefix
 * @var bool                                                   $disable_edit      Optional.
 */

defined( 'ABSPATH' ) || die;

?>
<td class="discount center" data-sort-value="<?php echo esc_attr( $item_discount ) ?>" style="width: 1%">
	<div class="atum-edit-field__wrapper">

		<div class="<?php echo ( $atum_order->is_editable() && ( ! isset( $disable_edit ) || ! $disable_edit ) ) ? 'editable atum-edit-field' : '' ?>" data-content-id="edit-discount-<?php echo esc_attr( $item_id ) ?>">
			<span class="field-label currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
				data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>" data-none="–"
				data-value="<?php echo esc_attr( $item_discount ) ?>" data-decimals-number="2"
			>

				<?php if ( floatval( $item_discount ) ) : ?>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $atum_order->format_price( $item_discount );  ?>
				<?php else : ?>
					&ndash;
				<?php endif; ?>

			</span>
		</div>

		<template id="edit-discount-<?php echo esc_attr( $item_id ) ?>">
			<div class="edit-fields-group">
				<div class="input-group number-mask">

					<input type="number" step="<?php echo esc_attr( apply_filters( 'atum/atum_order/discount_input_step', $step, $product ) ) ?>"
						min="0" autocomplete="off" value="<?php echo esc_attr( $item_discount ) ?>" class="meta-value" name="meta-value-discount">

					<span class="input-group-append" title="<?php esc_html_e( 'Click to switch behaviour', ATUM_PO_TEXT_DOMAIN ); ?>">
						<span class="input-group-text" data-value="percentage">%</span>
						<span class="input-group-text active" data-value="amount"><?php echo esc_html( get_woocommerce_currency_symbol( $currency ) ) ?></span>
						<input type="hidden" name="type" value="amount">
					</span>

				</div>

				<a href="#" class="set-default-value" data-type="percentage" data-meta_value_discount="<?php echo esc_attr( $atum_order ? $atum_order->supplier_discount : 0 ) ?>"><?php esc_html_e( 'Set default discount', ATUM_PO_TEXT_DOMAIN ); ?></a>
			</div>
		</template>

		<?php
		$discount_config      = maybe_unserialize( $item->get_meta( '_discount_config' ) );
		$discount_config_atts = ( $discount_config && is_array( $discount_config ) ) ? ' data-field-value="' . ( $discount_config['fieldValue'] ?? '' ) . '" data-type="' . ( $discount_config['type'] ?? '' ) . '"' : '';
		?>
		<input type="hidden" name="<?php echo esc_attr( $field_name_prefix ) ?>discount[<?php echo absint( $item_id ) ?>]"
			value="<?php echo esc_attr( $item_discount ) ?>"<?php echo $discount_config_atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	</div>

	<input type="hidden" name="<?php echo esc_attr( $field_name_prefix ) ?>discount_config[<?php echo absint( $item_id ) ?>]" value='<?php echo wp_json_encode( $discount_config ) ?>'>
</td>

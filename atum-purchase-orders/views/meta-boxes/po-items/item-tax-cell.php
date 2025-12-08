<?php
/**
 * View for the PO's item and invoice item tax columns
 *
 * @since 0.7.0
 *
 * @var POExtended                                         $atum_order
 * @var \Atum\Components\AtumOrders\Items\AtumOrderItemFee $item
 * @var int                                                $item_id
 * @var string                                             $currency
 * @var string                                             $currency_template
 * @var int|float                                          $step
 * @var string                                             $field_name_prefix
 * @var bool                                               $disable_edit      Optional.
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\Helpers;
use AtumPO\Models\POExtended;

if ( ( $tax_data = $item->get_taxes() ) && Helpers::may_use_po_taxes( $atum_order ) ) :

	$tax_item_total    = ! empty( $tax_data['total'] ) ? current( $tax_data['total'] ) : '';
	$tax_item_subtotal = ! empty( $tax_data['subtotal'] ) ? current( $tax_data['subtotal'] ) : '';
	?>
	<td class="line_tax center" style="width: 1%">
		<div class="atum-edit-field__wrapper">

			<div class="<?php echo ( $atum_order->is_editable() && ( ! isset( $disable_edit ) || ! $disable_edit ) ) ? 'editable atum-edit-field' : '' ?>"
				data-content-id="edit-tax-<?php echo esc_attr( $item_id ) ?>" data-bs-placement="left"
			>
				<span class="field-label currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
					data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>" data-none="–"
					data-value="<?php echo esc_attr( $tax_item_total ) ?>" data-decimals-number="2"
				>
					<?php echo '' !== $tax_item_total ? $atum_order->format_price( wc_round_tax_total( $tax_item_total ) ) : '&ndash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</div>

			<template id="edit-tax-<?php echo esc_attr( $item_id ) ?>">
				<div class="edit-fields-group">
					<div class="input-group number-mask">

						<input type="number" step="<?php echo esc_attr( $step ) ?>"
							min="0" autocomplete="off" value="<?php echo esc_attr( $tax_item_total ) ?>" class="meta-value" name="meta-value-tax">

						<span class="input-group-append" title="<?php esc_html_e( 'Click to switch behaviour', ATUM_PO_TEXT_DOMAIN ); ?>">
							<span class="input-group-text" data-value="percentage">%</span>
							<span class="input-group-text active" data-value="amount"><?php echo esc_html( get_woocommerce_currency_symbol( $currency ) ) ?></span>
							<input type="hidden" name="type" value="amount">
						</span>

					</div>

					<a href="#" class="set-default-value" data-type="percentage" data-meta_value_tax="<?php echo esc_attr( $atum_order ? $atum_order->supplier_tax_rate : 0 ) ?>"><?php esc_html_e( 'Set default tax', ATUM_PO_TEXT_DOMAIN ); ?></a>
				</div>
			</template>

			<?php
			$tax_config      = maybe_unserialize( $item->get_meta( '_tax_config' ) );
			$tax_config_atts = ( $tax_config && is_array( $tax_config ) && isset( $tax_config['fieldValue'], $tax_config['type'] ) ) ? ' data-field-value="' . $tax_config['fieldValue'] . '" data-type="' . $tax_config['type'] . '"' : '';
			?>
			<input type="hidden" name="<?php echo esc_attr( $field_name_prefix ) ?>tax[<?php echo absint( $item_id ) ?>]"
				value="<?php echo esc_attr( $tax_item_total ) ?>"<?php echo $tax_config_atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

		</div>

		<input type="hidden" name="<?php echo esc_attr( $field_name_prefix ) ?>tax_config[<?php echo absint( $item_id ) ?>]" value='<?php echo wp_json_encode( $tax_config ) ?>'>
	</td>
<?php endif;

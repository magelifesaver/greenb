<?php
/**
 * View for the PO's shipping items
 *
 * @since 0.9.17
 *
 * @var \AtumPO\Models\POExtended                  $po
 * @var \AtumPO\Invoices\Items\InvoiceItemShipping $invoice_item
 * @var \AtumPO\Invoices\Models\Invoice            $invoice
 * @var int                                        $item_id
 * @var string                                     $currency
 * @var string                                     $currency_template
 * @var int|float                                  $step
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\Helpers;
use Atum\Inc\Helpers as AtumHelpers;

$field_name_prefix = 'invoice_item_';

// Check if the related PO item still exists.
$missing_po_item = ! $po->get_item( $invoice_item->get_po_item_id(), 'shipping' );
?>
<tr class="invoice-item-row shipping<?php echo $missing_po_item ? ' missing-item atum-tooltip' : '' ?>" data-invoice="<?php echo esc_attr( $invoice->get_id() ) ?>"
	data-invoice-item="<?php echo esc_attr( $item_id ) ?>" data-item-type="<?php echo esc_attr( $invoice_item->get_type() ) ?>"
	data-po-item="<?php echo esc_attr( $invoice_item->get_po_item_id() ) ?>"
	<?php echo $missing_po_item ? ' title="' . esc_attr__( 'The PO item related to this invoice item is missing', ATUM_PO_TEXT_DOMAIN ) . '"' : '' ?>
>
	<td colspan="3">
		&nbsp;
	</td>

	<td class="invoice-item-info">
		<span class="thumb"></span>

		<?php // Shipping name.
		$shipping_name = $invoice_item->get_name() ?: __( 'Shipping', ATUM_PO_TEXT_DOMAIN ); ?>
		<span class="name">

			<div data-content-id="edit-name-<?php echo esc_attr( $item_id ) ?>">
				<span class="field-label">
					<?php echo esc_html( $shipping_name ); ?>
				</span>
			</div>

			<template id="edit-name-<?php echo esc_attr( $item_id ) ?>">
				<input type="text" autocomplete="off" value="<?php echo esc_attr( $shipping_name ) ?>" class="meta-value">
			</template>

			<input type="hidden" name="atum_order_item_name[<?php echo absint( $item_id ) ?>]" value="<?php echo esc_attr( $shipping_name ) ?>">
		</span>
	</td>

	<td colspan="3">
		&nbsp;
		<input type="hidden" class="atum_order_item_id" name="atum_order_shipping_id[]" value="<?php echo absint( $item_id ) ?>">
	</td>

	<?php // Cost.
	$cost_args = array(
		'item_cost'         => wc_format_decimal( $invoice_item->get_total() ),
		'item_id'           => $item_id,
		'step'              => $step,
		'currency'          => $currency,
		'currency_template' => $currency_template,
		'field_name_prefix' => $field_name_prefix,
		'atum_order'        => $po,
	);
	AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/po-items/item-cost-cell', $cost_args );
	?>

	<?php if ( Helpers::may_use_po_taxes( $po ) ) : ?>

		<?php // Tax.
		$tax_args = array(
			'item'              => $invoice_item,
			'item_id'           => $item_id,
			'currency'          => $currency,
			'currency_template' => $currency_template,
			'step'              => $step,
			'field_name_prefix' => $field_name_prefix,
			'atum_order'        => $po,
		);
		AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/po-items/item-tax-cell', $tax_args ); ?>

	<?php endif; ?>

	<?php // Actions. ?>
	<td class="actions center" style="width: 1%">
		<i class="show-actions atum-icon atmi-options"></i>
	</td>
</tr>

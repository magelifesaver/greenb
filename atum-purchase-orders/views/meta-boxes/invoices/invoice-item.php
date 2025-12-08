<?php
/**
 * View for the invoice items
 *
 * @since 0.9.6
 *
 * @var \AtumPO\Models\POExtended                 $po
 * @var \AtumPO\Invoices\Models\Invoice           $invoice
 * @var \AtumPO\Invoices\Items\InvoiceItemProduct $invoice_item
 * @var string                                    $currency
 * @var string                                    $currency_template
 * @var int|float                                 $step
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\Helpers;
use Atum\Components\AtumCapabilities;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Items\POItemProduct;

$invoice_item_id   = $invoice_item->get_id();
$invoice_item_type = $invoice_item->get_type();
$product           = $invoice_item->get_product();
$product_id        = $product instanceof \WC_Product ? $product->get_id() : 0;
$product_link      = $product_id ? admin_url( 'post.php?post=' . ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) . '&action=edit' ) : '';
$thumbnail         = $product_id ? $product->get_image( [ 40, 40 ], [ 'title' => '' ], FALSE ) : '';
$field_name_prefix = 'invoice_item_';

// Check if the related PO item still exists.
$missing_po_item = ! $po->get_item( $invoice_item->get_po_item_id() );
?>
<tr class="invoice-item-row<?php echo $missing_po_item ? ' missing-item atum-tooltip' : '' ?>" data-invoice="<?php echo esc_attr( $invoice->get_id() ) ?>"
	data-invoice-item="<?php echo esc_attr( $invoice_item_id ) ?>"
	data-item-type="<?php echo esc_attr( $invoice_item_type ) ?>" data-po-item="<?php echo esc_attr( $invoice_item->get_po_item_id() ) ?>"
	<?php echo $missing_po_item ? ' title="' . esc_attr__( 'The PO item related to this invoice item is missing', ATUM_PO_TEXT_DOMAIN ) . '"' : '' ?>
>
	<td colspan="3">
		<input type="hidden" class="atum_order_item_id" name="atum_order_item_id[]" value="<?php echo esc_attr( $invoice_item_id ); ?>">
	</td>
	<td class="items">
		<span class="invoice-item-info">
			<span class="thumb">
				<?php echo '<div class="atum-order-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>'; ?>
			</span>
			<span class="name">

				<?php if ( $product instanceof \WC_Product ): ?>
				<a href="<?php echo esc_url( $product_link ) ?>" class="invoice-item-name atum-tooltip" target="_blank" title="<?php esc_attr_e( 'View Product', ATUM_PO_TEXT_DOMAIN ) ?>">
					<?php echo esc_html( $invoice_item->get_name() ) ?>
				</a>
				<?php else: ?>
				<span class="deleted" title="<?php esc_attr_e( 'This product does not exist', ATUM_PO_TEXT_DOMAIN ); ?>">
					<?php echo esc_html( $invoice_item->get_name() ) ?>
				</span>
				<?php endif; ?>

				<?php if ( $product_id && $product instanceof \WC_Product && $product->get_sku() ) : ?>
					<div class="invoice-item-sku"><strong><?php esc_html_e( 'SKU:', ATUM_PO_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $product->get_sku() ) ?></div>
				<?php endif;

				if ( $product_id && AtumCapabilities::current_user_can( 'read_suppliers' ) ) :
					$supplier_sku = $product instanceof \WC_Product ? $product->get_supplier_sku() : FALSE;

					if ( $supplier_sku ) : ?>
						<div class="invoice-item-sku"><strong><?php esc_html_e( 'Supplier SKU:', ATUM_PO_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $supplier_sku ) ?></div>
					<?php endif;
				endif;

				if ( $invoice_item->get_variation_id() ) : ?>
					<div class="invoice-item-variation"><strong><?php esc_html_e( 'Variation ID:', ATUM_PO_TEXT_DOMAIN ) ?></strong>

						<?php if ( 'product_variation' === get_post_type( $invoice_item->get_variation_id() ) ) :
							echo esc_html( $invoice_item->get_variation_id() );
						else :
							/* translators: the variation ID */
							printf( esc_html__( '%s (No longer exists)', ATUM_PO_TEXT_DOMAIN ), esc_attr( $invoice_item->get_variation_id() ) );
						endif; ?>

					</div>
				<?php endif; ?>

				<?php
				// TODO: STUDY HOW TO MAKE THE ACCUMULATED WORKING WHEN ADDING A NEW INVOICE.
				global $atum_po_accumulated_invoice_items;
				$po_item_id         = $invoice_item->get_po_item_id();
				$po_item            = $po->get_item( $po_item_id );
				$po_item_qty        = $po_item ? $po_item->get_quantity() : 0;
				$qty                = $invoice_item->get_quantity();
				$total_invoiced_qty = isset( $atum_po_accumulated_invoice_items[ $po_item_id ] ) ? $atum_po_accumulated_invoice_items[ $po_item_id ] + $qty : $qty;
				?>
				<div class="invoice-item-invoiced<?php if ( $total_invoiced_qty >= $po_item_qty ) echo ' completed' ?>">
					<i class="atum-icon atmi-file-empty"></i>
					<?php
					$invoiced_qty_text = isset( $atum_po_accumulated_invoice_items[ $po_item_id ] ) ? "$total_invoiced_qty <small>({$atum_po_accumulated_invoice_items[ $po_item_id ]} + $qty)</small>" : $qty;

					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					printf(
						/* translators: first is the invoiced items and second is the total items */
						esc_html( _n( '%1$s of %2$s item invoiced', '%1$s of %2$s items invoiced', $po_item_qty, ATUM_PO_TEXT_DOMAIN ) ),
						'<span class="invoice-item-invoiced-qty" data-value="' . esc_attr( $total_invoiced_qty ) . '">' . $invoiced_qty_text . '</span>',
						'<span class="invoice-item-ordered-qty" data-value="' . esc_attr( $po_item_qty ) . '">' . $po_item_qty . '</span>'
					);
					// phpcs:enable
					?>
				</div>
			</span>
		</span>
	</td>

	<?php // Cost.
	$cost_args = array(
		'item_cost'         => wc_format_decimal( $invoice->get_item_subtotal( $invoice_item, FALSE, TRUE ) ),
		'item_id'           => $invoice_item_id,
		'step'              => $step,
		'currency'          => $currency,
		'currency_template' => $currency_template,
		'field_name_prefix' => $field_name_prefix,
		'atum_order'        => $po,
		'disable_edit'      => $missing_po_item,
	);
	AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/po-items/item-cost-cell', $cost_args );
	?>

	<?php // Discount.
	$discount_args = array(
		'item_discount'     => $invoice_item->get_quantity() ? wc_format_decimal( ( floatval( $invoice_item->get_subtotal() ) - floatval( $invoice_item->get_total() ) ) / floatval( $invoice_item->get_quantity() ), '' ) : 0,
		'item_id'           => $invoice_item_id,
		'item'              => $invoice_item,
		'product'           => $product,
		'step'              => $step,
		'currency'          => $currency,
		'currency_template' => $currency_template,
		'field_name_prefix' => $field_name_prefix,
		'atum_order'        => $po,
		'disable_edit'      => $missing_po_item,
	);
	AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/po-items/item-discount-cell', $discount_args );
	?>

	<?php // Quantity.
	$qty_args = array(
		'item_id'           => $invoice_item_id,
		'item'              => $invoice_item,
		'product'           => $product,
		'step'              => $step,
		'field_name_prefix' => $field_name_prefix,
		'atum_order'        => $po,
		'disable_edit'      => $missing_po_item,
	);
	AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/po-items/item-qty-cell', $qty_args );
	?>

	<?php
	// Total.
	$invoice_item_total = $invoice_item->get_total();
	?>
	<td class="total center" style="width: 1%" data-sort-value="<?php echo esc_attr( $invoice_item_total ); ?>">
		<span class="field-label currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
			data-value="<?php echo esc_attr( $invoice_item_total ) ?>" data-decimals-number="2"
			data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ) ?>"
		>
			<?php echo $po->format_price( $invoice_item_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>

		<input type="hidden" class="line_total" name="<?php echo esc_attr( $field_name_prefix ) ?>total[<?php echo absint( $invoice_item_id ) ?>]" value="<?php echo esc_attr( $invoice_item_total ) ?>">
	</td>

	<?php if ( Helpers::may_use_po_taxes( $po ) ) : ?>

		<?php // Tax.
		$tax_args = array(
			'item'              => $invoice_item,
			'item_id'           => $invoice_item_id,
			'currency'          => $currency,
			'currency_template' => $currency_template,
			'step'              => $step,
			'field_name_prefix' => $field_name_prefix,
			'atum_order'        => $po,
			'disable_edit'      => $missing_po_item,
		);
		AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/po-items/item-tax-cell', $tax_args ); ?>

	<?php endif; ?>

	<?php if ( $po->is_editable() && ! $po->is_due() ) : ?>
	<td class="actions center" style="width: 1%">
		<i class="show-actions atum-icon atmi-options"></i>
	</td>
	<?php endif; ?>
</tr>

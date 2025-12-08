<?php
/**
 * View for the Purchase Order's Invoices meta box
 *
 * @since 0.9.0
 *
 * @var \AtumPO\Models\POExtended $po
 * @var Invoice[]                 $invoices
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumStockDecimals;
use AtumPO\Inc\Helpers;
use AtumPO\Invoices\Models\Invoice;
use Atum\Inc\Helpers as AtumHelpers;

$currency          = $po->currency ?: get_woocommerce_currency();
$currency_symbol   = get_woocommerce_currency_symbol( $currency );
$currency_template = sprintf( $po->get_price_format(), $currency_symbol, '%value%' );
$step              = AtumStockDecimals::get_input_step();
$is_editable       = $po->is_editable() && ! $po->is_due();

?>
<div class="atum-meta-box po-invoices<?php echo empty( $invoices ) ? ' no-items' : '' ?>">
	<div class="invoices">

		<div class="items-blocker unblocked">
			<?php esc_html_e( 'No Invoices Added', ATUM_PO_TEXT_DOMAIN ); ?>
		</div>

		<div class="invoices__table-wrapper">
			<table class="atum-items-table invoices__table">
				<thead>
					<tr>
						<th class="invoice_number sortable" data-sort="string-ins">
							<?php esc_html_e( 'Invoice', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>

						<th class="file center" style="width: 1%">
							<?php echo esc_html_x( 'File', 'PO Invoice', ATUM_PO_TEXT_DOMAIN ) ?>
						</th>

						<th class="date sortable" data-sort="string-ins">
							<?php echo esc_html_x( 'Date', 'PO Invoice', ATUM_PO_TEXT_DOMAIN ) ?>
						</th>

						<th class="items">
							<?php echo esc_html_x( 'Items', 'PO Invoice', ATUM_PO_TEXT_DOMAIN ) ?>
						</th>

						<th class="cost sortable center" data-sort="float">
							<?php echo esc_html_x( 'Cost', 'PO Invoice', ATUM_PO_TEXT_DOMAIN ) ?>
						</th>

						<th class="discount sortable center" data-sort="float">
							<?php echo esc_html_x( 'Discount', 'PO Invoice', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>

						<th class="qty sortable center" data-sort="float">
							<?php echo esc_html_x( 'Qty', 'PO Invoice', ATUM_PO_TEXT_DOMAIN ) ?>
						</th>

						<th class="total sortable center" data-sort="float">
							<?php echo esc_html_x( 'Total', 'PO Invoice', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>

						<?php if ( Helpers::may_use_po_taxes( $po ) ) : ?>
							<th class="tax sortable center" data-sort="float">
								<?php echo esc_html_x( 'Tax', 'PO Invoice', ATUM_PO_TEXT_DOMAIN ); ?>
							</th>
						<?php endif; ?>

						<?php if ( $is_editable ) : ?>
						<th class="actions center" style="width: 1%">
							<?php echo esc_html_x( 'Actions', 'PO Invoice', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>
						<?php endif; ?>
					</tr>
				</thead>

				<tbody>
					<?php
					$tax_total = $total_discount = $subtotal = $total = $total_qty = $ordered_total = $missing_total = 0;

					global $atum_po_accumulated_invoice_items;
					$atum_po_accumulated_invoice_items = [];

					$po_items = $po->get_items( [ 'line_item', 'fee', 'shipping' ] );

					foreach ( $po_items as $po_item ) :
						$ordered_total += $po_item->get_quantity();
					endforeach;

					foreach ( $invoices as $invoice ) :

						$invoice_items = $invoice->get_items( [ 'invoice_item', 'fee', 'shipping' ] );
						AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/invoices/invoice', compact( 'invoice', 'invoice_items', 'po' ) );

						if ( Helpers::may_use_po_taxes( $po ) ) :
							$tax_total += (float) $invoice->total_tax;
							$subtotal  += number_format( $invoice->get_subtotal(), 2 );
						endif;

						$total          += (float) $invoice->total;
						$total_discount += (float) $invoice->discount_total;

						foreach ( $invoice_items as $invoice_item ) :

							if ( array_key_exists( $invoice_item->get_po_item_id(), $po_items ) ) :
								$total_qty += $invoice_item->get_quantity();
							else :
								$missing_total += $invoice_item->get_quantity();
							endif;

						endforeach;

					endforeach;
					?>
				</tbody>
			</table>
		</div>

	</div>

	<div class="invoices__totals">

		<div class="items-totals">
			<table id="invoiced-totals">
				<tr>
					<td class="label"><?php esc_html_e( 'Ordered', ATUM_PO_TEXT_DOMAIN ); ?></td>
					<td class="total" id="invoices_ordered"><?php echo esc_html( wc_format_decimal( floatval( $ordered_total ), AtumStockDecimals::get_stock_decimals(), TRUE ) ); ?></td>
				</tr>
				<tr>
					<td class="label"><?php esc_html_e( 'Invoiced', ATUM_PO_TEXT_DOMAIN ); ?></td>
					<td class="total" id="invoices_invoiced"><?php echo esc_html( wc_format_decimal( floatval( $total_qty ), AtumStockDecimals::get_stock_decimals(), TRUE ) ); ?></td>
				</tr>
				<?php if ( $missing_total > 0 ) : ?>
				<tr class="missing-total__row">
					<td class="label">
						<span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( 'Invoice items linked to missing PO items', ATUM_PO_TEXT_DOMAIN ) ?>"></span>
						<?php esc_html_e( 'Missing', ATUM_PO_TEXT_DOMAIN ); ?>
					</td>
					<td class="total" id="invoices_missing"><?php echo esc_html( wc_format_decimal( floatval( $missing_total ), AtumStockDecimals::get_stock_decimals(), TRUE ) ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr class="grand-total">
					<td class="label"><?php esc_html_e( 'Pending', ATUM_PO_TEXT_DOMAIN ); ?></td>
					<td class="total">
						<?php $pending_total = $ordered_total - $total_qty ?>
						<span class="badge <?php echo esc_attr( $pending_total ? 'badge-danger' : 'badge-success' ) ?>" id="invoices_pending">
							<?php echo esc_html( wc_format_decimal( floatval( $pending_total ), AtumStockDecimals::get_stock_decimals(), TRUE ) ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<small class="currency-label"><?php esc_html_e( 'Items', ATUM_PO_TEXT_DOMAIN ); ?></small>
					</td>
				</tr>
			</table>
		</div>

		<div class="items-totals">
			<table id="invoices-totals">

				<?php if ( Helpers::may_use_po_taxes( $po ) ) : ?>

					<tr class="invoice-subtotal">
						<td class="label"><?php esc_html_e( 'Subtotal', ATUM_PO_TEXT_DOMAIN ) ?></td>
						<td class="total currency" id="invoices_subtotal" data-template="<?php echo esc_attr( $currency_template ) ?>"
							data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ) ?>"
							data-decimals-number="2" data-total="<?php echo esc_attr( $subtotal ) ?>"
						>
							<?php echo $po->format_price( $subtotal ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>

					<tr class="invovices-tax">
						<td class="label"><?php echo esc_html_e( 'Tax', ATUM_PO_TEXT_DOMAIN ) ?></td>
						<td class="total currency" id="invoices_tax_total" data-template="<?php echo esc_attr( $currency_template ) ?>"
							data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ) ?>"
							data-decimals-number="2" data-total="<?php echo esc_attr( $tax_total ?: 0 ) ?>"
						>
							<?php echo $po->format_price( $tax_total ?: 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>

				<?php endif; ?>

				<tr class="invoice-discount">
					<td class="label">
						<?php esc_html_e( 'Discount', ATUM_PO_TEXT_DOMAIN ); ?>
					</td>
					<td class="total currency" id="invoices_discount_total" data-template="<?php echo esc_attr( $currency_template ) ?>"
						data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ) ?>" data-decimals-number="2"
						data-total="<?php echo esc_attr( $total_discount ? -$total_discount : 0 ) ?>"
					>
						<?php echo $po->format_price( $total_discount ? -$total_discount : 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>

				<tr class="invoices-total grand-total">
					<td class="label">
						<?php esc_html_e( 'Total Invoiced', ATUM_PO_TEXT_DOMAIN ) ?>
					</td>
					<td>
						<span id="invoices_total" class="badge total currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
							data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ) ?>" data-decimals-number="2"
							data-total="<?php echo esc_attr( $total ) ?>"
						>
							<?php echo $po->format_price( $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
					</td>
				</tr>

				<?php $currencies = get_woocommerce_currencies() ?>
				<?php if ( ! empty( $currencies[ $currency ] ) ) : ?>
					<tr>
						<td colspan="2">
							<small class="currency-label"><?php echo $currencies[ $currency ] . " ($currency_symbol)"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small>
						</td>
					</tr>
				<?php endif; ?>
			</table>
		</div>

	</div>

	<?php $disable_adding = 0 >= $pending_total || ! $po->status || 'auto-draft' === $po->status || $po->is_due(); ?>
	<div class="atum-meta-box__footer<?php echo $disable_adding ? ' disable-adding' : '' ?>">
		<?php if ( $disable_adding && $po->is_due() ) : ?>
			<span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( "You can't add invoices while the PO is in a due status", ATUM_PO_TEXT_DOMAIN ) ?>"></span>
		<?php endif; ?>

		<?php if ( $is_editable ) : ?>

			<button type="button" class="btn btn-primary add-invoice"<?php disabled( $disable_adding ) ?>>
				<?php esc_html_e( 'Add Invoice', ATUM_PO_TEXT_DOMAIN ); ?>
			</button>

			<button type="button" class="btn btn-success save-invoices" disabled <?php echo empty( $invoices ) ? ' style="display:none"' : '' ?>>
				<?php esc_html_e( 'Save Invoices', ATUM_PO_TEXT_DOMAIN ); ?>
			</button>

			<?php AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/add-invoice-modal', compact( 'po' ) ); ?>

		<?php elseif ( ! empty( $invoices ) ) : ?>
			<span class="description">
				<span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( 'To edit the PO invoices change the PO status to any other that allows editing', ATUM_PO_TEXT_DOMAIN ) ?>"></span> <?php esc_html_e( 'These invoices are no longer editable.', ATUM_PO_TEXT_DOMAIN ); ?>
			</span>
		<?php endif; ?>
	</div>

</div>

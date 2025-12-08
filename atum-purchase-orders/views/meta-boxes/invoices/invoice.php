<?php
/**
 * View for the single invoices within the Invoices meta box
 *
 * @since 0.9.6
 *
 * @var \AtumPO\Invoices\Models\Invoice                             $invoice
 * @var InvoiceItemProduct[]|InvoiceItemShipping[]|InvoiceItemFee[] $invoice_items
 * @var \AtumPO\Models\POExtended                                   $po
 * @var array                                                       $accumulated_qtys
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumStockDecimals;
use Atum\Inc\Globals as AtumGlobals;
use AtumPO\Invoices\Items\InvoiceItemProduct;
use AtumPO\Invoices\Items\InvoiceItemFee;
use AtumPO\Invoices\Items\InvoiceItemShipping;
use AtumPO\Inc\Helpers;

global $wpdb;

$currency          = $po->currency ?: get_woocommerce_currency();
$currency_symbol   = get_woocommerce_currency_symbol( $currency );
$currency_template = sprintf( $po->get_price_format(), $currency_symbol, '%value%' );
$step              = AtumStockDecimals::get_input_step();

$date_created = $invoice->date_created;
$datetime     = new DateTime( $date_created, new DateTimeZone( 'GMT' ) );
$datetime->setTimeZone( new DateTimeZone( wp_timezone_string() ) );
$date_created = $datetime->format( 'Y-m-d H:i:s' );

$invoice_qty = 0;
foreach ( $invoice_items as $invoice_item ) :
	$invoice_qty += $invoice_item->get_quantity();
endforeach;
?>
<tr class="invoice-row" data-invoice="<?php echo esc_attr( $invoice->get_id() ) ?>">
	<td class="invoice_number">
		<?php echo esc_html( $invoice->document_number ) ?>
	</td>
	<td class="file center" style="width: 1%">
		<?php
		$invoice_files = $invoice->files;
		if ( ! empty( $invoice_files ) || ! is_array( $invoice_files ) ) : ?>
			<a href="<?php echo esc_url( wp_get_attachment_url( $invoice_files[0] ) ) ?>" target="_blank"
				data-file="<?php echo esc_attr( $invoice_files[0] ) ?>" data-file-name="<?php echo esc_attr( basename( get_attached_file( $invoice_files[0] ) ) ) ?>"
			>
				<i class="atum-icon atmi-pdf"></i>
			</a>
		<?php else : ?>
			&ndash;
		<?php endif; ?>
	</td>
	<td class="date">
		<?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $date_created ) ) ) ?>
	</td>
	<td class="items">
		x<?php echo count( $invoice_items ) ?>
		<a href="#" class="toggle-invoice-items" data-alt-text="<?php esc_attr_e( 'Show Items', ATUM_PO_TEXT_DOMAIN ) ?>">
			<?php esc_html_e( 'Hide Items', ATUM_PO_TEXT_DOMAIN ); ?>
		</a>
	</td>
	<?php $invoice_subtotal = $invoice->get_subtotal() ?>
	<td class="cost currency center" data-value="<?php echo esc_attr( $invoice_subtotal ) ?>"
		data-template="<?php echo esc_attr( $currency_template ) ?>" data-decimals-number="2"
		data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ) ?>"
	>
		<?php echo $po->format_price( $invoice_subtotal ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</td>
	<?php $invoice_discount = $invoice->get_total_discount() ?>
	<td class="discount currency center" data-value="<?php echo esc_attr( $invoice_discount ) ?>"
		data-template="<?php echo esc_attr( $currency_template ) ?>" data-decimals-number="2"
		data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ) ?>"
	>
		<?php echo $po->format_price( $invoice_discount ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</td>
	<td class="quantity center" data-template="&times;%value%" data-none="0"
		<?php echo AtumStockDecimals::get_stock_decimals() ? ' data-decimal-separator="' . esc_attr( $po->price_decimal_sep ) . '" data-decimals-number="' . esc_attr( AtumGlobals::get_stock_decimals() ) . '"' : ''; ?>
	>
		<?php echo esc_html( $invoice_qty ) ?>
	</td>
	<td class="total currency center" data-value="<?php echo esc_attr( $invoice->get_subtotal() - $invoice->get_total_discount() ) ?>"
		data-template="<?php echo esc_attr( $currency_template ) ?>" data-decimals-number="2"
		data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ) ?>"
	>
		<?php echo $po->format_price( $invoice->get_subtotal() - $invoice->get_total_discount() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</td>

	<?php if ( Helpers::may_use_po_taxes( $po ) ) : ?>
		<td class="tax currency center" data-value="<?php echo esc_attr( $invoice->total_tax ) ?>"
			data-template="<?php echo esc_attr( $currency_template ) ?>" data-decimals-number="2"
			data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ) ?>"
		>
			<?php echo $po->format_price( $invoice->total_tax ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</td>
	<?php endif; ?>

	<?php if ( $po->is_editable() && ! $po->is_due() ) : ?>
	<td class="actions center" style="width: 1%">
		<i class="show-actions atum-icon atmi-options"></i>
	</td>
	<?php endif; ?>
</tr>
<?php
$unit_totals = 0;

global $atum_po_accumulated_invoice_items;

foreach ( $invoice_items as $item_id => $invoice_item ) :

	/**
	 * Variable definition
	 *
	 * @var InvoiceItemProduct|InvoiceItemFee|InvoiceItemShipping $invoice_item
	 */
	$unit_totals += $invoice_item->get_quantity();

	if ( $invoice_item instanceof InvoiceItemProduct ) :
		include 'invoice-item.php';
	elseif ( $invoice_item instanceof InvoiceItemFee ) :
		include 'invoice-item-fee.php';
	elseif ( $invoice_item instanceof InvoiceItemShipping ) :
		include 'invoice-item-shipping.php';
	endif;

	$po_item_id = $invoice_item->get_po_item_id();

	if ( is_array( $atum_po_accumulated_invoice_items ) && array_key_exists( $po_item_id, $atum_po_accumulated_invoice_items ) ) :
		$atum_po_accumulated_invoice_items[ $po_item_id ] += $invoice_item->get_quantity();
	else :
		$atum_po_accumulated_invoice_items[ $po_item_id ] = $invoice_item->get_quantity();
	endif;

endforeach;

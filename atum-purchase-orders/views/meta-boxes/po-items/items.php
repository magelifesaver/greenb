<?php
/**
 * View for the Purchase Order items meta box
 *
 * @since 0.2.0
 *
 * @var \AtumPO\Models\POExtended $atum_order
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumStockDecimals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Items\POItemProduct;
use AtumPO\Inc\Helpers;

global $wpdb;

// Get line items.
$line_items          = $atum_order->get_items( apply_filters( 'atum/atum_order/item_types', 'line_item' ) ); // Using original ATUM hook.
$line_items_fee      = $atum_order->get_items( 'fee' );
$line_items_shipping = $atum_order->get_items( 'shipping' );
$no_items            = empty( $line_items ) && empty( $line_items_fee ) && empty( $line_items_shipping );

if ( Helpers::may_use_po_taxes( $atum_order ) ) {
	$taxes            = $atum_order->get_taxes();
	$tax_classes      = WC_Tax::get_tax_classes();
	$classes_options  = wc_get_product_tax_class_options();
	$show_tax_columns = 1 === count( $taxes );
}

$post_type         = get_post_type_object( get_post_type( $atum_order->get_id() ) );
$currency          = $atum_order->supplier_currency ?: get_woocommerce_currency();
$currency_symbol   = get_woocommerce_currency_symbol( $currency );
$currency_template = sprintf( $atum_order->get_price_format(), $currency_symbol, '%value%' );
$step              = AtumStockDecimals::get_input_step();
$supplier          = $atum_order->get_supplier();
$field_name_prefix = 'atum_order_item_';
$is_editable       = $atum_order->is_editable();
$display_fields    = AtumHelpers::get_option( 'po_display_extra_fields', [] );
$display_fields    = ! empty( $display_fields['options'] ) && is_array( $display_fields['options'] ) ? $display_fields['options'] : [];
$added_products    = array();
?>

<?php if ( 'atum_returning' === $atum_order->get_status() ) : ?>
	<div class="alert alert-warning">
		<i class="atum-icon atmi-warning"></i>
		<?php esc_html_e( 'All the items added to this Returning PO that were previously added to stock on the original PO, will be discounted automatically when this order is marked as "Returned". Please, only leave here the items and quantities that you want to return.', ATUM_PO_TEXT_DOMAIN ); ?>
	</div>
<?php endif; ?>

<div class="atum-meta-box <?php echo esc_attr( $post_type->name ) ?>_items<?php echo $no_items ? ' no-items' : '' ?>">

	<?php do_action( 'atum/atum_order/before_items_meta_box', $atum_order ); // Using original ATUM hook. ?>

	<div class="items-blocker unblocked">
		<?php esc_html_e( 'No Items Added', ATUM_PO_TEXT_DOMAIN ); ?>
	</div>

	<div class="atum_order_items_wrapper">
		<div class="atum_order_items__table-wrapper">

			<table class="atum_order_items">

				<thead>
					<tr>
						<th class="item sortable" colspan="2" data-sort="string-ins">
							<?php esc_html_e( 'Item', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>

						<?php do_action( 'atum/atum_order/item_headers', $atum_order ); // Using original ATUM hook. ?>

						<?php if ( ! array_key_exists( 'stock', $display_fields ) || 'yes' === $display_fields['stock'] ) : ?>
						<th class="available_stock sortable center" data-sort="float">
							<span class="atum-tooltip" title="<?php esc_attr_e( 'Available Stock', ATUM_PO_TEXT_DOMAIN ) ?>">
								<?php echo esc_html_x( 'Stock', 'Purchase Order', ATUM_PO_TEXT_DOMAIN ) ?>
							</span>
						</th>
						<?php endif; ?>

						<?php if ( ! array_key_exists( 'last_week_sales', $display_fields ) || 'yes' === $display_fields['last_week_sales'] ) : ?>
						<th class="last_week_sales sortable center" data-sort="float">
							<span class="atum-tooltip" title="<?php esc_attr_e( 'Last Week Sales', ATUM_PO_TEXT_DOMAIN ) ?>">
								<?php echo esc_html_x( 'LWS', 'Purchase Order', ATUM_PO_TEXT_DOMAIN ) ?>
							</span>
						</th>
						<?php endif; ?>

						<?php if ( ! array_key_exists( 'inbound_stock', $display_fields ) || 'yes' === $display_fields['inbound_stock'] ) : ?>
						<th class="inbound_stock sortable center" data-sort="float">
							<span class="atum-tooltip" title="<?php esc_attr_e( 'Inbound Stock', ATUM_PO_TEXT_DOMAIN ) ?>">
								<?php echo esc_html_x( 'Inbound', 'Purchase Order', ATUM_PO_TEXT_DOMAIN ) ?>
							</span>
						</th>
						<?php endif; ?>

						<?php if ( ! array_key_exists( 'recommended_quantity', $display_fields ) || 'yes' === $display_fields['recommended_quantity'] ) : ?>
						<th class="roq sortable center" data-sort="float">
							<span class="atum-tooltip" title="<?php esc_attr_e( 'Recommended Order Quantity', ATUM_PO_TEXT_DOMAIN ) ?>">
								<?php esc_html_e( 'ROQ', ATUM_PO_TEXT_DOMAIN ) ?>
							</span>
						</th>
						<?php endif; ?>

						<th class="item_cost sortable center" data-sort="float">
							<?php esc_html_e( 'Cost', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>

						<th class="item_discount sortable center" data-sort="float">
							<?php esc_html_e( 'Discount', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>

						<?php if ( $atum_order->is_returning() ) : ?>
							<th class="returned-ratio sortable center" data-sort="int">
								<span class="atum-tooltip" title="<?php esc_attr_e( 'Returned Items Ratio', ATUM_PO_TEXT_DOMAIN ) ?>">
									<?php
									if ( 'atum_returned' === $atum_order->get_status() ):
										esc_html_e( 'Returned', ATUM_PO_TEXT_DOMAIN );
									else:
										esc_html_e( 'Returning', ATUM_PO_TEXT_DOMAIN );
									endif;
									?>
								</span>
							</th>
						<?php endif; ?>

						<th class="quantity sortable center" data-sort="int">
							<?php esc_html_e( 'Qty', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>

						<th class="line_total sortable center" data-sort="float">
							<?php esc_html_e( 'Total', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>

						<?php if ( Helpers::may_use_po_taxes( $atum_order ) ) : ?>
						<th class="tax sortable center" data-sort="float">
							<?php esc_html_e( 'Tax', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>
						<?php endif; ?>

						<?php if ( $is_editable ) : ?>
						<th class="actions center">
							<?php esc_html_e( 'Actions', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>
						<?php endif; ?>
					</tr>
				</thead>

				<tbody id="atum_order_line_items">
					<?php
					$unit_totals = 0;

					foreach ( $line_items as $item_id => $item ) :

						/**
						 * Variable definition
						 *
						 * @var POItemProduct $item
						 */
						$unit_totals += $item->get_quantity();

						do_action( 'atum/atum_order/before_item_' . $item->get_type() . '_html', $item_id, $item, $atum_order ); // Using original ATUM hook.
						AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/po-items/item', compact( 'atum_order', 'item', 'item_id', 'currency', 'currency_template', 'step', 'supplier', 'field_name_prefix', 'display_fields' ) );
						$added_products[] = $item->get_variation_id() ?: $item->get_product_id();
						do_action( 'atum/atum_order/after_item_' . $item->get_type() . '_html', $item_id, $item, $atum_order ); // Using original ATUM hook.

					endforeach;

					do_action( 'atum/atum_order/after_line_items', $atum_order->get_id() ); // Using original ATUM hook.
					?>
				</tbody>

				<tbody id="atum_order_shipping_line_items">
					<?php
					$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();
					foreach ( $line_items_shipping as $item_id => $item ) :
						include 'item-shipping.php';
					endforeach;

					do_action( 'atum/atum_order/after_shipping', $atum_order->get_id() ); // Using original ATUM hook.
					?>
				</tbody>

				<tbody id="atum_order_fee_line_items">
					<?php
					foreach ( $line_items_fee as $item_id => $item ) :
						include 'item-fee.php';
					endforeach;

					do_action( 'atum/atum_order/after_fees', $atum_order->get_id() ); // Using original ATUM hook.
					?>
				</tbody>

			</table>

		</div>

		<div class="atum-order-data-row atum-order-totals-items">

			<table class="atum-order-totals items-totals">

				<tr class="po-units">
					<td class="label">
						<?php esc_html_e( 'Units Total', ATUM_PO_TEXT_DOMAIN ); ?>
					</td>
					<td class="total" id="units_total"
						data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>"
						data-decimals-number="<?php echo esc_attr( AtumStockDecimals::get_stock_decimals() ) ?>"
					>
						<?php echo esc_html( $unit_totals ); ?>
					</td>
				</tr>

				<tr class="po-fees hideable"<?php if ( empty( $line_items_fee ) ) echo ' style="display:none"' ?>>
					<td class="label">
						<?php esc_html_e( 'Fees', ATUM_PO_TEXT_DOMAIN ); ?>
					</td>
					<?php $fees_total = $atum_order->get_total_fees() ?>
					<td class="total currency" id="fees_total" data-template="<?php echo esc_attr( $currency_template ) ?>"
						data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>"
						data-total="<?php echo esc_attr( $fees_total ) ?>" data-decimals-number="2"
					>
						<?php echo $atum_order->format_price( $fees_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>

				<tr class="po-shipping hideable"<?php if ( empty( $line_items_shipping ) ) echo ' style="display:none"' ?>>
					<td class="label">
						<?php esc_html_e( 'Shipping', ATUM_PO_TEXT_DOMAIN ); ?>
					</td>
					<td class="total currency" id="shiping_total" data-template="<?php echo esc_attr( $currency_template ) ?>"
						data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>"
						data-total="<?php echo esc_attr( $atum_order->shipping_total ) ?>" data-decimals-number="2"
					>
						<?php echo $atum_order->format_price( $atum_order->shipping_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>

				<?php do_action( 'atum/atum_order/totals_after_shipping', $atum_order->get_id() ); // Using original ATUM hook. ?>

				<?php if ( Helpers::may_use_po_taxes( $atum_order ) ) :

					$tax_total = $atum_order->get_tax_totals();
					$subtotal  = $atum_order->get_formatted_total( '', TRUE );
					?>

					<tr class="po-subtotal hideable"<?php if ( empty( $tax_total ) ) echo ' style="display:none"' ?>>
						<td class="label"><?php esc_html_e( 'Items Subtotal', ATUM_PO_TEXT_DOMAIN ) ?></td>
						<td class="total currency" id="po_subtotal" data-template="<?php echo esc_attr( $currency_template ) ?>"
							data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>"
							data-total="<?php echo esc_attr( $atum_order->get_subtotal() ) ?>" data-decimals-number="2"
						>
							<?php echo $subtotal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>

					<tr class="po-tax hideable"<?php if ( empty( $tax_total ) ) echo ' style="display:none"' ?>>
						<td class="label"><?php echo esc_html_e( 'Tax', ATUM_PO_TEXT_DOMAIN ) ?></td>
						<td class="total currency" id="tax_total" data-template="<?php echo esc_attr( $currency_template ) ?>"
							data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>"
							data-total="<?php echo esc_attr( $tax_total ) ?>" data-decimals-number="2"
						>
							<?php echo $tax_total ? $atum_order->format_price( $tax_total ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>

					<?php do_action( 'atum/atum_order/totals_after_tax', $atum_order->get_id() ); // Using original ATUM hook.

				endif; ?>

				<?php $total_discount = $atum_order->get_total_discount(); ?>
				<tr class="po-discount hideable"<?php if ( ! $total_discount ) echo ' style="display: none"' ?>>
					<td class="label">
						<?php esc_html_e( 'Discount', ATUM_PO_TEXT_DOMAIN ); ?>
					</td>
					<td class="total currency" id="discount_total" data-template="<?php echo esc_attr( $currency_template ) ?>"
						data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>"
						data-total="<?php echo esc_attr( $total_discount ? -$total_discount : 0 ) ?>"
						data-decimals-number="2"
					>
						<?php echo $total_discount ? $atum_order->format_price( -$total_discount ) : 0; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>

				<?php do_action( 'atum/atum_order/totals_after_discount', $atum_order->get_id() ); // Using original ATUM hook. ?>

				<tr class="po-total grand-total">
					<td class="label">
						<?php esc_html_e( 'PO TOTAL', ATUM_PO_TEXT_DOMAIN ) ?>
					</td>
					<td>
						<span id="po_total" class="badge total currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
							data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>"
							data-total="<?php echo esc_attr( $atum_order->total ) ?>"
							data-decimals-number="2"
						>
							<?php echo $atum_order->get_formatted_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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

				<?php do_action( 'atum/atum_order/totals_after_total', $atum_order->get_id() ); // Using original ATUM hook. ?>

			</table>

			<div class="clear"></div>
		</div>

		<?php if ( ! $is_editable ) : ?>
			<div class="atum-order-data-row atum-order-bulk-actions">
				<p class="add-items">

					<span class="description">
						<span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( 'To edit the purchase order items change the PO status to any other that allows editing', ATUM_PO_TEXT_DOMAIN ) ?>"></span> <?php esc_html_e( 'These purchase order items are no longer editable.', ATUM_PO_TEXT_DOMAIN ); ?>
					</span>
					<?php
					// Allow adding custom buttons.
					do_action( 'atum/atum_order/add_action_buttons', $atum_order ); // Using original ATUM hook. ?>

				</p>
			</div>
		<?php else : ?>
			<div class="atum-order-data-row atum-order-add-item">
				<button type="button" class="btn btn-primary add-po-item"><?php esc_html_e( 'Add item(s)', ATUM_PO_TEXT_DOMAIN ); ?></button>
				<button type="button" class="btn btn-primary add-po-fee"><?php esc_html_e( 'Add fee', ATUM_PO_TEXT_DOMAIN ); ?></button>
				<button type="button" class="btn btn-primary add-po-shipping"><?php esc_html_e( 'Add shipping cost', ATUM_PO_TEXT_DOMAIN ); ?></button>

				<?php
				// Allow adding custom buttons.
				do_action( 'atum/atum_order/add_line_buttons', $atum_order ); // Using original ATUM hook. ?>

				<button type="button" class="btn btn-success save-po-items"><?php esc_html_e( 'Save Items', ATUM_PO_TEXT_DOMAIN ); ?></button>
			</div>
		<?php endif; ?>

		<script type="text/template" id="tmpl-atum-modal-add-products">
			<div class="wc-backbone-modal">
				<div class="wc-backbone-modal-content">
					<section class="wc-backbone-modal-main" role="main">

						<header class="wc-backbone-modal-header">
							<h1><?php esc_html_e( 'Add Items', ATUM_PO_TEXT_DOMAIN ); ?></h1>
							<button class="modal-close modal-close-link">
								<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', ATUM_PO_TEXT_DOMAIN ) ?></span>
							</button>
						</header>

						<article>
							<?php do_action( 'atum/atum_order/before_product_search_modal', $atum_order ); // Using original ATUM hook. ?>
							<form action="" method="post">
								<select class="wc-product-search atum-enhanced-select" multiple="multiple" style="width: 50%;"
									id="add_item_id" name="add_atum_order_items[]"
									data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"
									data-action="atum_json_search_products"
									data-exclude="<?php echo esc_attr( implode( ',', $added_products ) ); ?>"
								></select>
							</form>
						</article>

						<footer>
							<div class="inner">
								<button id="btn-ok" class="button button-primary button-large"><?php esc_html_e( 'Add', ATUM_PO_TEXT_DOMAIN ); ?></button>
							</div>
						</footer>

					</section>
				</div>
			</div>

			<div class="wc-backbone-modal-backdrop modal-close"></div>
		</script>

		<?php if ( Helpers::may_use_po_taxes( $atum_order ) ) : // TODO: ALLOW ADDING MULTIPLE TAXES. ?>
			<script type="text/template" id="tmpl-atum-modal-add-tax">
				<div class="wc-backbone-modal">
					<div class="wc-backbone-modal-content">
						<section class="wc-backbone-modal-main" role="main">

							<header class="wc-backbone-modal-header">
								<h1><?php esc_html_e( 'Add tax', ATUM_PO_TEXT_DOMAIN ); ?></h1>
								<button class="modal-close modal-close-link">
									<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', ATUM_PO_TEXT_DOMAIN ) ?></span>
								</button>
							</header>

							<article>
								<form action="" method="post">
									<table class="widefat">
										<thead>
										<tr>
											<th>&nbsp;</th>
											<th><?php esc_html_e( 'Rate name', ATUM_PO_TEXT_DOMAIN ); ?></th>
											<th><?php esc_html_e( 'Tax class', ATUM_PO_TEXT_DOMAIN ); ?></th>
											<th><?php esc_html_e( 'Rate code', ATUM_PO_TEXT_DOMAIN ); ?></th>
											<th><?php esc_html_e( 'Rate %', ATUM_PO_TEXT_DOMAIN ); ?></th>
										</tr>
										</thead>
										<?php
										$rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name LIMIT 100" );

										foreach ( $rates as $rate ) : ?>
											<tr>
												<td><input type="radio" id="add_atum_order_tax_<?php echo absint( $rate->tax_rate_id ) ?>" name="add_atum_order_tax" value="<?php echo absint( $rate->tax_rate_id ) ?>" /></td>
												<td><label for="add_atum_order_tax_<?php echo absint( $rate->tax_rate_id ) ?>"><?php echo esc_html( WC_Tax::get_rate_label( $rate ) ) ?></label></td>
												<td><?php echo esc_html( $classes_options[ $rate->tax_rate_class ] ?? '-' ) ?></td>
												<td><?php echo esc_html( WC_Tax::get_rate_code( $rate ) ) ?></td>
												<td><?php echo esc_html( WC_Tax::get_rate_percent( $rate ) ) ?></td>
											</tr>
										<?php endforeach; ?>
									</table>

									<?php if ( absint( $wpdb->get_var( "SELECT COUNT(tax_rate_id) FROM {$wpdb->prefix}woocommerce_tax_rates;" ) ) > 100 ) : ?>
										<p>
											<label for="manual_tax_rate_id"><?php esc_html_e( 'Or, enter tax rate ID:', ATUM_PO_TEXT_DOMAIN ); ?></label><br/>
											<input type="number" name="manual_tax_rate_id" id="manual_tax_rate_id" step="1" placeholder="<?php esc_attr_e( 'Optional', ATUM_PO_TEXT_DOMAIN ); ?>" />
										</p>
									<?php endif; ?>
								</form>
							</article>

							<footer>
								<div class="inner">
									<button id="btn-ok" class="button button-primary button-large"><?php esc_html_e( 'Add', ATUM_PO_TEXT_DOMAIN ); ?></button>
								</div>
							</footer>

						</section>
					</div>
				</div>

				<div class="wc-backbone-modal-backdrop modal-close"></div>
			</script>
		<?php endif; ?>

	</div>

	<?php do_action( 'atum/atum_order/after_items_meta_box', $atum_order ); // Using original ATUM hook. ?>
	<?php do_action( 'atum/purchase_orders_pro/after_items_meta_box', $atum_order ); ?>

</div>

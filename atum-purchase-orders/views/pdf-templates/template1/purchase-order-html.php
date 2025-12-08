<?php
/**
 * View for the Purchase Orders' PDF (Template 1)
 *
 * @since 0.9.7
 *
 * @var \AtumPO\Exports\POExtendedExport $po
 * @var int                              $desc_percent
 * @var float                            $discount
 * @var int                              $total_text_colspan
 * @var string                           $currency
 * @var array                            $line_items_shipping
 * @var bool                             $requires_requisition
 * @var array                            $template_fields
 * @var string                           $template_color
 * @var bool                             $display_thumbnails
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Components\AtumColors;
use AtumPO\Inc\Helpers;

if ( $template_color ) :
	$template_color_rgb = AtumColors::hex_to_rgb( $template_color );
	?>
	<style>
		.po-title {
			color: <?php echo esc_html( $template_color ) ?>;
		}

		.content-lines .po-li-head, .content-header, .content-address__info, .content-address__extra-info, .po-total td {
			background-color: <?php echo esc_html( $template_color ) ?> !important;
		}

		td.total, .po-lines tr:nth-child(even) td {
			background-color: rgba(<?php echo esc_html( $template_color_rgb ) ?>, .1);
		}

		.content-totals .subtotal td, .po-total .label, .po-total .total {
			border-top-color: <?php echo esc_html( $template_color ) ?>;
		}
	</style>
<?php endif; ?>

<?php $company_logo = AtumHelpers::get_option( 'po_default_pdf_template_logo', '' ); ?>

<div class="logo__wrapper<?php echo $po->get_debug_mode() ? ' is-debug' : '' ?>">
	<div class="logo">

		<?php if ( $company_logo ) : ?>
			<?php echo wp_get_attachment_image( $company_logo, 'full', false, [ 'style' => 'max-width:300px;height:auto;' ] ) ?>
		<?php endif; ?>

		<h3 class="po-title"><?php ! $po->is_returning() ? esc_html_e( 'Purchase Order', ATUM_PO_TEXT_DOMAIN ) : esc_html_e( 'Returning PO', ATUM_PO_TEXT_DOMAIN ) ?></h3>
		<div class="content-header-po-data">
			<div class="row">
				<span class="label"><?php esc_html_e( 'Date:', ATUM_PO_TEXT_DOMAIN ) ?>&nbsp;&nbsp;</span>
				<span class="field"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $po->date_created ) ) ) ?></span>
			</div>
			<div class="row">
				<span class="label"><?php esc_html_e( 'P.O. #:', ATUM_PO_TEXT_DOMAIN ) ?>&nbsp;&nbsp;</span>
				<span class="field"><?php echo esc_html( $po->number ) ?></span>
			</div>

			<?php do_action( 'atum/purchase_orders_pro/po_report/after_header', $po ); ?>
		</div>
	</div>
</div>

<div class="main-page<?php echo $po->get_debug_mode() ? ' is-debug' : '' ?>">

	<?php $company_data = $po->get_company_data(); ?>

	<h1><?php echo esc_html( $company_data['company'] ) ?>&nbsp;</h1>

	<div class="content-header">
		<div class="company-address">
			<?php $company_data = $po->get_company_data() ?>

			<?php if ( ! empty( $company_data['address_1'] ) ) : ?>
				<?php echo esc_html( $company_data['address_1'] ) ?>
			<?php endif; ?>
			<br>
			<?php if ( ! empty( $company_data['postcode'] ) ) : ?>
				<?php echo esc_html( $company_data['postcode'] ) ?>
			<?php endif; ?>

			<?php if ( ! empty( $company_data['city'] ) ) : ?>
				<?php echo esc_html( $company_data['city'] ) ?>
			<?php endif; ?>

			<?php if ( ! empty( $company_data['state'] ) ) : ?>
				<?php $states = WC()->countries->get_states( $company_data['country'] ) ?>
				<?php echo ! empty( $states[ $company_data['state'] ] ) ? '- ' . esc_html( $states[ $company_data['state'] ] ) : '' ?>
			<?php endif; ?>

			<?php if ( ! empty( $company_data['country'] ) ) : ?>
				<?php $countries = WC()->countries->get_countries() ?>
				<?php echo ! empty( $countries[ $company_data['country'] ] ) ? '(' . esc_html( $countries[ $company_data['country'] ] ) . ')' : '' ?>
			<?php endif; ?>

			<?php $vat_number = $po->get_tax_number() ?>

			<?php if ( $vat_number ) : ?>
				<br>
				<?php
				/* translators: the VAT number */
				printf( esc_html__( 'Tax/VAT number: %s', ATUM_PO_TEXT_DOMAIN ), $vat_number ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="content-address__titles">
		<div class="content-address__wrapper">
			<div class="float-left">
				<h4><?php esc_html_e( 'Supplier', ATUM_PO_TEXT_DOMAIN ) ?></h4>
			</div>

			<?php if ( ! $po->is_returning() ) : ?>
				<div class="float-left">
					<h4><?php esc_html_e( 'Ship To', ATUM_PO_TEXT_DOMAIN ) ?></h4>
				</div>
			<?php endif; ?>
			<div class="spacer" style="clear: both;"></div>
		</div>
	</div>

	<div class="content-address__info">
		<div class="content-address__wrapper">
			<div class="float-left">
				<p class="address">
					<?php echo wp_kses_post( $po->get_supplier_address() ) ?>&nbsp;
				</p>
			</div>

			<?php if ( ! $po->is_returning() ) : ?>
				<div class="float-left">
					<p class="address">
						<?php echo wp_kses_post( $po->get_shipping_address() ) ?>
					</p>
				</div>
			<?php endif; ?>
			<div class="spacer" style="clear: both;"></div>
		</div>
	</div>

	<?php if (
		(
			( array_key_exists( 'requisitioner', $template_fields ) && 'yes' === $template_fields['requisitioner'] ) ||
			( array_key_exists( 'delivery_terms', $template_fields ) && 'yes' === $template_fields['delivery_terms'] )
		) && ( ( $requires_requisition && $po->requisitioner ) || $po->delivery_terms )
	) : ?>
		<div class="content-address__extra-info-titles">
			<div class="content-address__wrapper">

				<?php
				if (
					array_key_exists( 'requisitioner', $template_fields ) && 'yes' === $template_fields['requisitioner'] &&
					$requires_requisition && $po->requisitioner
				) : ?>
					<div class="float-left">
						<h5><?php esc_html_e( 'Requisitioner', ATUM_PO_TEXT_DOMAIN ) ?></h5>
					</div>
				<?php endif; ?>

				<?php if ( array_key_exists( 'delivery_terms', $template_fields ) && 'yes' === $template_fields['delivery_terms'] && $po->delivery_terms ) : ?>
					<div class="float-left">
						<h5><?php esc_html_e( 'Delivery Terms', ATUM_PO_TEXT_DOMAIN ) ?></h5>
					</div>
				<?php endif; ?>

				<div class="spacer" style="clear: both;"></div>
			</div>
		</div>

		<div class="content-address__extra-info">
			<div class="content-address__wrapper">

				<?php
				if (
					array_key_exists( 'requisitioner', $template_fields ) && 'yes' === $template_fields['requisitioner'] &&
					$requires_requisition && $po->requisitioner
				) : ?>
					<div class="float-left">
						<p class="address">
							<?php
							$requisitioner = get_user_by( 'id', $po->requisitioner );
							echo esc_html( $requisitioner->display_name )
							?>
						</p>
					</div>
				<?php endif; ?>

				<?php if ( array_key_exists( 'delivery_terms', $template_fields ) && 'yes' === $template_fields['delivery_terms'] && $po->delivery_terms ) : ?>
					<div class="float-left">
						<p class="address">
							<?php echo wp_kses_post( html_entity_decode( $po->delivery_terms, ENT_COMPAT, 'UTF-8' ) ) ?>
						</p>
					</div>
				<?php endif; ?>

				<div class="spacer" style="clear: both;"></div>
			</div>
		</div>

	<?php else : // Just add an empty block. ?>
		<div class="content-address__extra-info-titles">
			<div class="content-address__wrapper">&nbsp;</div>
		</div>
	<?php endif; ?>

	<div class="po-wrapper content-lines">
		<table>
			<thead>
				<tr class="po-li-head">
					<th class="description"<?php echo $display_thumbnails ? ' colspan="2"' : '' ?> style="width:<?php echo esc_attr( $desc_percent ) ?>%"><?php esc_html_e( 'Item', ATUM_PO_TEXT_DOMAIN ) ?></th>
					<th class="qty"><?php esc_html_e( 'Qty', ATUM_PO_TEXT_DOMAIN ) ?></th>
					<th class="price"><?php esc_html_e( 'Unit Price', ATUM_PO_TEXT_DOMAIN ) ?></th>

					<?php if ( $discount ) : ?>
						<th class="discount"><?php esc_html_e( 'Discount', ATUM_PO_TEXT_DOMAIN ) ?></th>
					<?php endif; ?>

					<?php if ( Helpers::may_use_po_taxes( $po ) ) : ?>
						<th class="tax">
							<?php esc_attr_e( 'Tax', ATUM_PO_TEXT_DOMAIN ); ?>
						</th>
					<?php endif; ?>
					<th class="total"><?php esc_html_e( 'Total', ATUM_PO_TEXT_DOMAIN ) ?></th>
				</tr>
			</thead>

			<tbody class="po-lines">
				<?php foreach ( $po->get_items() as $item ) :
					$product = AtumHelpers::get_atum_product( $item->get_product() );

					if ( $display_thumbnails ) {
						$product_id = $product instanceof \WC_Product ? $product->get_id() : 0;
						$thumbnail  = $product_id ? $product->get_image( [ 38, 38 ], [ 'title' => '' ], FALSE ) : '';
					}

					/**
					 * Variable definition
					 *
					 * @var \WC_Order_Item_Product $item
					 */
					?>
					<tr class="po-line">
						<?php if ( $display_thumbnails ) : ?>
						<td class="thumb">
							<?php if ( $thumbnail ) :
								echo wp_kses_post( $thumbnail );
							else : ?>
								<img src="<?php echo esc_url( ATUM_PO_URL . 'views/pdf-templates/icons/picture.jpg' ) ?>" class="size-38x38" loading="lazy" width="38" height="38">
							<?php endif; ?>
						</td>
						<?php endif; ?>
						<td class="description"><?php echo wp_kses_post( $item->get_name() ) ?>

							<?php
							if ( $product instanceof \WC_Product && AtumCapabilities::current_user_can( 'read_suppliers' ) ) :

								$supplier_sku = array_filter( (array) apply_filters( 'atum/atum_order/po_report/supplier_sku', [ $product->get_supplier_sku() ], $item ) ); // Using original ATUM hook name.

								if ( ! empty( $supplier_sku ) ) : ?>
									<div class="atum-order-item-sku">
										<?php echo esc_html( _n( 'Supplier SKU:', 'Supplier SKUs:', count( $supplier_sku ), ATUM_PO_TEXT_DOMAIN ) . ' ' . implode( ', ', $supplier_sku ) ) ?>
									</div>
								<?php endif;

								$sku = array_filter( (array) apply_filters( 'atum/atum_order/po_report/sku', [ $product->get_sku() ], $item ) ); // Using original ATUM hook name.

								if ( ! empty( $sku ) ) : ?>
									<div class="atum-order-item-sku">
										<?php echo esc_html( _n( 'SKU:', 'SKUs:', count( $sku ), ATUM_PO_TEXT_DOMAIN ) . ' ' . implode( ', ', $sku ) ) ?>
									</div>
								<?php endif;

								do_action( 'atum/purchase_orders_pro/po_report/after_item_product', $item->get_id(), $item, $item->get_order() );

							endif;

							// Show the custom meta.
							$hidden_item_meta = apply_filters( 'atum/purchase_orders_pro/po_report/hidden_item_meta', array(
								'_qty',
								'_tax_class',
								'_product_id',
								'_variation_id',
								'_line_subtotal',
								'_line_subtotal_tax',
								'_line_total',
								'_line_tax',
								'_line_tax_data',
								'_method_id',
								'_cost',
								'_total_tax',
								'_taxes',
								'_stock_changed',
								'_tax_config',
								'_discount_config',
							) );

							$meta_data = $item->get_formatted_meta_data( '' ); ?>

							<?php foreach ( $meta_data as $meta_id => $meta ) :

								if ( in_array( $meta->key, $hidden_item_meta, TRUE ) ) :
									continue;
								endif;

								$meta_label = $meta->display_key;

								if ( '_order_id' === $meta->display_key ) :
									$meta_label = esc_html__( 'Order ID', ATUM_PO_TEXT_DOMAIN );
								endif;
								?>
								<br>
								<span class="atum-order-item-<?php echo esc_attr( $meta->display_key ) ?>" style="color: #888; font-size: 12px;">
									<?php echo esc_html( $meta_label ) ?>: <?php echo esc_html( wp_strip_all_tags( $meta->display_value ) ) ?>
								</span>

							<?php endforeach; ?>

						</td>
						<td class="qty"><?php echo esc_html( $item->get_quantity() ) ?></td>
						<td class="price"><?php echo $po->format_price( $po->get_item_subtotal( $item, FALSE, FALSE ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<?php if ( $discount ) : ?>
							<td class="discount">
								<?php if ( $item->get_subtotal() != $item->get_total() ) : // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison ?>
									-<?php echo $po->format_price( wc_format_decimal( (float) $item->get_subtotal() - (float) $item->get_total(), '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
							</td>
						<?php endif; ?>

						<?php $tax_item_total = 0; ?>
						<?php if ( Helpers::may_use_po_taxes( $po ) ) : ?>
							<td class="tax">
								<?php if ( ( $tax_data = $item->get_taxes() ) && Helpers::may_use_po_taxes( $po ) ) :
									$tax_item_total = (float) ! empty( $tax_data['total'] ) ? current( $tax_data['total'] ) : '';

									if ( '' !== $tax_item_total ) :
										echo $po->format_price( wc_round_tax_total( $tax_item_total ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									else :
										echo '&ndash;';
									endif;
								else :
									echo '&ndash;';
								endif; ?>
							</td>
						<?php endif; ?>

						<td class="total"><?php echo $po->format_price( (float) $item->get_total() + $tax_item_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				<?php endforeach; ?>

				<?php if ( ! empty( $line_items_shipping ) ) : ?>

					<?php foreach ( $line_items_shipping as $item_id => $item ) : ?>
						<tr class="po-line content-shipping">
							<?php if ( $display_thumbnails ) : ?>
								<td class="thumb">
									<img src="<?php echo esc_url( ATUM_PO_URL . 'views/pdf-templates/icons/shipping.jpg' ) ?>" class="size-38x38" loading="lazy" width="38" height="38">
								</td>
							<?php endif; ?>
							<td class="description"><?php echo esc_html( $item->get_name() ?: __( 'Shipping', ATUM_PO_TEXT_DOMAIN ) ); ?></td>
							<td class="qty">&nbsp;</td>
							<td class="price"><?php echo $po->format_price( $item->get_total() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<?php $shipping_total = (float) $item->get_total(); ?>

							<?php if ( $discount ) : ?>
								<td class="discount">&nbsp;</td>
							<?php endif; ?>

							<?php if ( Helpers::may_use_po_taxes( $po ) ) : ?>
								<td class="tax">
									<?php if ( ( $tax_data = $item->get_taxes() ) && Helpers::may_use_po_taxes( $po ) ) :
										$tax_item_total = (float) ! empty( $tax_data['total'] ) ? current( $tax_data['total'] ) : '';

										if ( '' !== $tax_item_total ) :
											echo $po->format_price( wc_round_tax_total( $tax_item_total ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											$shipping_total += $tax_item_total;
										else :
											echo '&ndash;';
										endif;
									else :
										echo '&ndash;';
									endif; ?>
								</td>
							<?php endif; ?>
							<td class="total"><?php echo $po->format_price( $shipping_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						</tr>
					<?php endforeach; ?>

				<?php endif; ?>

				<?php if ( ! empty( $line_items_fee ) ) : ?>

					<?php foreach ( $line_items_fee as $item_id => $item ) : ?>
						<tr class="po-line content-fees">
							<?php if ( $display_thumbnails ) : ?>
								<td class="thumb">
									<img src="<?php echo esc_url( ATUM_PO_URL . 'views/pdf-templates/icons/plus-circle.jpg' ) ?>" class="size-38x38" loading="lazy" width="38" height="38">
								</td>
							<?php endif; ?>
							<td class="description"><?php echo esc_html( $item->get_name() ?: __( 'Fee', ATUM_PO_TEXT_DOMAIN ) ); ?></td>
							<td class="qty">&nbsp;</td>
							<td class="price"><?php echo $po->format_price( $item->get_total() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<?php $fee_total = (float) $item->get_total(); ?>

							<?php if ( $discount ) : ?>
								<td class="discount">&nbsp;</td>
							<?php endif; ?>

							<?php if ( Helpers::may_use_po_taxes( $po ) ) : ?>
								<td class="tax">
									<?php if ( ( $tax_data = $item->get_taxes() ) && Helpers::may_use_po_taxes( $po ) ) :
										$tax_item_total = (float) ! empty( $tax_data['total'] ) ? current( $tax_data['total'] ) : '';

										if ( '' !== $tax_item_total ) :
											echo $po->format_price( wc_round_tax_total( $tax_item_total ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											$fee_total += $tax_item_total;
										else :
											echo '&ndash;';
										endif;
									else :
										echo '&ndash;';
									endif; ?>
								</td>
							<?php endif; ?>
							<td class="total"><?php echo $po->format_price( $fee_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						</tr>
					<?php endforeach; ?>

				<?php endif; ?>
			</tbody>

			<tbody class="content-totals">

				<tr class="subtotal">
					<td class="label" colspan="<?php echo esc_attr( $total_text_colspan ) ?>">
						<?php esc_html_e( 'Subtotal', ATUM_PO_TEXT_DOMAIN ) ?>:
					</td>
					<td class="total">
						<?php echo $po->get_formatted_total( '', TRUE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>

				<?php if ( $discount ) : ?>
					<tr>
						<td class="label" colspan="<?php echo esc_attr( $total_text_colspan ) ?>">
							<?php esc_html_e( 'Discount', ATUM_PO_TEXT_DOMAIN ) ?>:
						</td>
						<td class="total">
							-<?php echo $po->format_price( $po->discount_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( $line_items_shipping ) : ?>
					<tr>
						<td class="label" colspan="<?php echo esc_attr( $total_text_colspan ) ?>">
							<?php esc_html_e( 'Shipping', ATUM_PO_TEXT_DOMAIN ) ?>:
						</td>
						<td class="total">
							<?php echo $po->format_price( $po->shipping_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( $line_items_fee ) : ?>
					<tr>
						<td class="label" colspan="<?php echo esc_attr( $total_text_colspan ) ?>">
							<?php esc_html_e( 'Fees', ATUM_PO_TEXT_DOMAIN ) ?>:
						</td>
						<td class="total">
							<?php echo $po->format_price( $po->get_total_fees() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( Helpers::may_use_po_taxes( $po ) ) :

					$tax_total = $po->get_tax_totals();

					if ( ! empty( $tax_total ) ) : ?>

						<tr>
							<td class="label" colspan="<?php echo esc_attr( $total_text_colspan ) ?>">
								<?php esc_html_e( 'Taxes', ATUM_PO_TEXT_DOMAIN ); ?>:
							</td>
							<td class="total">
								<?php echo $po->format_price( $tax_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</td>
						</tr>

					<?php endif;

				endif; ?>

				<tr class="po-total">
					<td colspan="<?php echo esc_attr( $total_text_colspan - 2 ) ?>"></td>
					<td class="label" colspan="2">
						<?php esc_html_e( 'Total', ATUM_PO_TEXT_DOMAIN ) ?>:
					</td>
					<td class="total">
						<?php echo $po->get_formatted_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>

			</tbody>
		</table>
	</div>

	<?php $description = $po->get_description() ?>
	<?php if ( array_key_exists( 'description', $template_fields ) && 'yes' === $template_fields['description'] && $description ) : ?>
		<footer<?php echo $po->get_debug_mode() ? ' class="is-debug"' : '' ?>>
			<h6><?php esc_html_e( 'Notes', ATUM_PO_TEXT_DOMAIN ) ?></h6>
			<?php echo wp_kses_post( html_entity_decode( apply_filters( 'the_content', $description ), ENT_COMPAT, 'UTF-8' ) ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
		</footer>
	<?php endif; ?>

</div>

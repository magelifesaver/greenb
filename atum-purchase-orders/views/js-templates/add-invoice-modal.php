<?php
/**
 * View for the Add PO Invoice modal's JS template
 *
 * @since 0.9.6
 *
 * @var \AtumPO\Models\POExtended $po
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;

?>
<script type="text/template" id="add-invoice-modal">
	<div class="atum-modal-content">

		<div class="note">
			<?php esc_html_e( 'Add an invoice to this purchase order.', ATUM_PO_TEXT_DOMAIN ) ?><br>
		</div>

		<hr>

		<div class="po-modal__details">
			<h4><?php esc_html_e( 'Invoice Details', ATUM_PO_TEXT_DOMAIN ); ?></h4>

			<div class="po-modal__details-field pr">
				<label for=""><?php esc_html_e( 'Invoice Number', ATUM_PO_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
				<input type="text" name="invoice_number" value="" placeholder="<?php esc_html_e( 'Add document number', ATUM_PO_TEXT_DOMAIN ); ?>" required>
			</div>

			<div class="po-modal__details-field">
				<label for=""><?php esc_html_e( 'Date', ATUM_PO_TEXT_DOMAIN ); ?></label>

				<span class="date-field with-icon">
					<input type="text" name="invoice_date" class="atum-datepicker"
						placeholder="<?php esc_attr_e( 'Will be current date if empty', ATUM_PO_TEXT_DOMAIN ); ?>"
						data-date-format="YYYY-MM-DD" data-min-date="false" autocomplete="off"
					>
				</span>
			</div>

		</div>

		<hr>

		<div class="po-modal__details">
			<h4><?php esc_html_e( 'Invoice File', ATUM_PO_TEXT_DOMAIN ); ?></h4>

			<div class="po-modal__details-field link-invoice-files">
				<label for=""><?php esc_html_e( 'Link or upload file (optional)', ATUM_PO_TEXT_DOMAIN ); ?></label>

				<div class="link-invoice-files__fields">

					<select name="supplier_invoice_file">
						<option value=""><?php esc_html_e( 'Select a supplier file...', ATUM_PO_TEXT_DOMAIN ); ?></option>
						<?php $files = $po->files; ?>
						<?php if ( ! empty( $files ) ) : ?>
							<?php
							foreach ( $files as $file ) :

								if ( ! array_key_exists( 'supplier', $file ) || 'yes' !== $file['supplier'] ) continue;
								?>
								<option value="<?php echo esc_attr( $file['id'] ) ?>"><?php echo esc_html( basename( get_attached_file( $file['id'] ) ) ) ?></option>

							<?php endforeach; ?>
						<?php endif; ?>
					</select>

					<span><?php esc_html_e( 'or', ATUM_PO_TEXT_DOMAIN ); ?></span>

					<button type="button" class="add-invoice-file atum-file-uploader btn btn-outline-primary"><?php esc_html_e( 'Upload File', ATUM_PO_TEXT_DOMAIN ); ?></button>
				</div>

				<div class="uploaded-invoice-files"></div>

			</div>

		</div>

		<hr>

		<div class="po-modal__search">

			<h4><?php esc_html_e( 'Invoice Items', ATUM_PO_TEXT_DOMAIN ); ?></h4>

			<?php
			$po_items    = $po->get_items();
			$shown_items = 0;
			?>

			<?php if ( empty( $po_items ) ) : ?>

				<div class="alert alert-primary">
					<i class="atum-icon atmi-question-circle"></i>
					<?php esc_html_e( 'Please, first add items to the PO and save', ATUM_PO_TEXT_DOMAIN ); ?>
				</div>

			<?php else : ?>

				<div class="po-modal__search-input">
					<input type="text" id="search-invoice-products" placeholder="<?php esc_attr_e( 'Search for products&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>" />
				</div>

				<form id="add-invoice-items-form">
					<table class="atum-items-table po-modal__items">
						<thead>
						<tr>
							<th colspan="2"><?php esc_html_e( 'Item', ATUM_PO_TEXT_DOMAIN ); ?></th>
							<th class="center" style="width:10%"><?php esc_html_e( 'Qty', ATUM_PO_TEXT_DOMAIN ); ?></th>
						</tr>
						</thead>
						<tbody>
							<?php foreach ( $po_items as $po_item ) :

								$po_item_id = $po_item->get_id();
								$product    = AtumHelpers::get_atum_product( $po_item->get_product() );

								$shown_items++;
								?>
								<tr class="invoice-item" data-po-item="<?php echo esc_attr( $po_item_id ) ?>" data-item-type="<?php echo esc_attr( $po_item->get_type() ) ?>">
									<td class="thumb">
										<div class="atum-order-item-thumbnail">
											<?php $thumbnail = $product instanceof \WC_Product ? $product->get_image( [ 40, 40 ], array( 'title' => '' ), FALSE ) : ''; ?>
											<?php echo wp_kses_post( $thumbnail ) ?>
										</div>
									</td>
									<td class="name<?php echo $product instanceof \WC_Product ? '' : esc_attr( ' deleted' ); ?>">
										<div class="product-name" title="<?php echo $product instanceof \WC_Product ? '' : esc_attr( 'This product does not exist' ); ?>">
											<?php echo esc_html( $product instanceof \WC_Product ? $product->get_name() : $po_item->get_name() ) ?>
										</div>

										<?php if ( $product instanceof \WC_Product ) : ?>
										<div class="item-meta">

											<?php $sku = $product->get_sku() ?>

											<?php if ( $sku ) : ?>
												<div>
													<?php /* translators: the product SKU */
													printf( esc_html__( 'SKU: %s', ATUM_PO_TEXT_DOMAIN ), $sku ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												</div>
											<?php endif; ?>

											<?php $supplier_sku = $product->get_supplier_sku() ?>

											<?php if ( $supplier_sku ) : ?>
												<div>
													<?php /* translators: the sipplier's SKU */
													printf( esc_html__( 'Supplier SKU: %s', ATUM_PO_TEXT_DOMAIN ), $supplier_sku ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												</div>
											<?php endif; ?>

										</div>
										<?php endif; ?>
									</td>
									<td class="qty center">
										<?php if ( $product instanceof \WC_Product ) : ?>
										<input type="number" name="invoice_qty" data-id="<?php echo esc_attr( $po_item_id ) ?>"
											value="0" min="0" max="<?php echo esc_attr( $po_item->get_quantity() ) ?>"
											step="any" onfocus="this.select();"
										>
										<?php
										else :
											echo esc_attr_e( 'N/A', ATUM_PO_TEXT_DOMAIN );
										endif;
										?>
									</td>
								</tr>

								<?php do_action( 'atum/purchase_orders_pro/after_add_invoice_modal_item', $po_item, $product ) ?>

							<?php endforeach; ?>

							<?php foreach ( [ 'fee', 'shipping' ] as $type ) :

								$items = $po->get_items( $type );

								foreach ( $items as $item ) :

									$item_id = $item->get_id();
									$shown_items++;
									?>
									<tr class="invoice-item <?php echo esc_attr( $type ) ?>"
										data-po-item="<?php echo absint( $item_id ) ?>"
										data-item-type="<?php echo esc_attr( $type ) ?>"
									>
										<td class="thumb">
											<div class="atum-order-item-thumbnail"></div>
										</td>

										<?php // Fee name.
										$name = $item->get_name() ?: __( 'Fee', ATUM_PO_TEXT_DOMAIN ); ?>
										<td class="name">

											<div class="product-name">
												<?php echo esc_html( $name ) ?>
											</div>
											<div class="item-meta">
												<?php $amount = wc_format_decimal( $item->get_total() ); ?>
												<?php echo esc_html( 'fee' === $type ? __( 'Fee Cost', ATUM_PO_TEXT_DOMAIN ) : __( 'Shipping Cost', ATUM_PO_TEXT_DOMAIN ) ) ?>:
												<?php echo $po->format_price( $amount ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</div>

										</td>

										<td class="qty center">
											<input type="checkbox" value="yes" name="invoice_item_check" data-id="<?php echo esc_attr( $item_id ) ?>">
										</td>
									</tr>
									<?php
								endforeach;

							endforeach; ?>
						</tbody>
					</table>
				</form>

			<?php endif; ?>
		</div>

		<div class="po-modal__total">
			<strong><?php esc_html_e( 'Total items:', ATUM_PO_TEXT_DOMAIN ); ?> <span class="total-items"><?php echo esc_html( $shown_items ) ?></span></strong>
		</div>
	</div>
</script>

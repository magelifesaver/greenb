<?php
/**
 * View for every single delivery displayed on the Deliveries meta box
 *
 * @since 0.9.1
 *
 * @var \AtumPO\Models\POExtended          $po
 * @var \AtumPO\Deliveries\Models\Delivery $delivery
 * @var DeliveryItemProduct[]              $delivery_items
 * @var array                              $expected_qtys
 * @var array                              $already_in_qtys
 * @var array                              $delivered_qtys
 * @var array                              $pending_qtys
 */

defined( 'ABSPATH' ) || die;

$is_editable = $po->is_editable() && ! $po->is_due();
$delivery_id = $delivery->get_id();

use AtumPO\Deliveries\Items\DeliveryItemProduct;

$date_created = $delivery->date_created;
$datetime     = new DateTime( $date_created, new DateTimeZone( wp_timezone_string() ) );
$date_created = $datetime->format( 'Y-m-d H:i:s' );

?>
<div class="delivery" data-id="<?php echo esc_attr( $delivery_id ) ?>">

	<div class="delivery__data">

		<h2>
			<span class="delivery__data-name"><?php echo esc_html( $delivery->get_title() ) ?></span>

			<?php if ( $is_editable ) : ?>
			<i class="show-actions atum-icon atmi-options"></i>
			<?php endif; ?>
		</h2>

		<div class="delivery__data-field delivery-date__field">
			<label for="delivery_date_created_<?php echo esc_attr( $delivery_id ) ?>"><?php esc_html_e( 'Date', ATUM_PO_TEXT_DOMAIN ); ?></label>

			<span class="date-field with-icon<?php echo esc_attr( ! $is_editable ? ' disabled' : '' ) ?>">
				<input type="text" class="atum-datepicker" name="delivery[<?php echo esc_attr( $delivery_id ) ?>][date_created]"
					id="delivery_date_created_<?php echo esc_attr( $delivery_id ) ?>" maxlength="10"
					value="<?php echo esc_attr( $date_created ) ?>" autocomplete="off" data-date-format="YYYY-MM-DD HH:mm"
					<?php disabled( ! $is_editable ) ?>
				>
			</span>
		</div>

		<div class="delivery__data-field delivery-number__field">
			<label for="delivery_number_<?php echo esc_attr( $delivery_id ) ?>"><?php esc_html_e( 'Document Number', ATUM_PO_TEXT_DOMAIN ); ?></label>
			<input type="text" name="delivery[<?php echo esc_attr( $delivery_id ) ?>][document_number]"
				id="delivery_number_<?php echo esc_attr( $delivery_id ) ?>" value="<?php echo esc_attr( $delivery->document_number ) ?>"
				<?php disabled( ! $is_editable ) ?>
			>
		</div>

		<div class="delivery__data-field delivery-files__field">
			<label for="delivery_files_<?php echo esc_attr( $delivery_id ) ?>"><?php esc_html_e( 'Files', ATUM_PO_TEXT_DOMAIN ); ?></label>

			<ul class="delivery-files-list">
				<?php if ( ! empty( $files = $delivery->files ) ) : ?>

					<?php foreach ( $delivery->files as $file_id ) : ?>
						<li data-id="<?php echo esc_attr( $file_id ) ?>">
							<div class="delivery-files-list__name"><?php echo esc_html( basename( get_attached_file( $file_id ) ) ) ?></div>

							<div class="delivery-files-list__actions">
								<a href="<?php echo esc_url( wp_get_attachment_url( $file_id ) ) ?>" target="_blank" class="view-file btn btn-primary atum-tooltip" title="<?php esc_attr_e( 'View file', ATUM_PO_TEXT_DOMAIN ); ?>">
									<i class="atum-icon atmi-eye"></i>
								</a>

								<?php if ( $is_editable ) : ?>
								<button type="button" class="delete-file btn btn-danger atum-tooltip" title="<?php esc_attr_e( 'Delete file', ATUM_PO_TEXT_DOMAIN ); ?>">
									<i class="atum-icon atmi-trash"></i>
								</button>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>

				<?php else : ?>
					<li class="no-file">
						<div class="delivery-files-list__name"><?php esc_html_e( 'No file added', ATUM_PO_TEXT_DOMAIN ) ?></div>
					</li>
				<?php endif; ?>
			</ul>

			<?php if ( $is_editable ) : ?>
			<button type="button" class="add-file atum-file-uploader btn btn-outline-primary"><?php esc_html_e( 'Add File', ATUM_PO_TEXT_DOMAIN ); ?></button>
			<?php endif; ?>
		</div>

	</div>

	<div class="delivery__items">

		<div class="atum-items-table__wrapper">
			<table class="atum-items-table po-delivery-items">
				<thead>
					<tr>
						<th class="text-left" colspan="2"><?php esc_html_e( 'Product', ATUM_PO_TEXT_DOMAIN ); ?></th>
						<th class="already-in"><?php esc_html_e( 'Already In', ATUM_PO_TEXT_DOMAIN ); ?></th>
						<th class="delivered"><?php esc_html_e( 'Delivered', ATUM_PO_TEXT_DOMAIN ); ?></th>
						<th class="pending"><?php esc_html_e( 'Pending', ATUM_PO_TEXT_DOMAIN ); ?></th>
						<?php if ( $is_editable ) : ?>
						<th class="actions"><?php esc_html_e( 'Actions', ATUM_PO_TEXT_DOMAIN ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>

				<tbody>
					<?php
					$already_in_items_total = $delivered_items_total = $pending_items_total = 0;
					require 'delivery-items.php'; ?>
				</tbody>

				<tfoot>
					<tr>
						<td colspan="2"><?php esc_html_e( 'Totals', ATUM_PO_TEXT_DOMAIN ); ?></td>
						<td class="already-in-total center"><span class="badge"><?php echo esc_html( $already_in_items_total ) ?></span></td>
						<td class="delivered-total center"><span class="badge"><?php echo esc_html( $delivered_items_total ) ?></span></td>
						<td class="pending-total center"><span class="badge"><?php echo esc_html( $pending_items_total ) ?></span></td>
						<?php if ( $is_editable ) : ?>
						<td></td>
						<?php endif; ?>
					</tr>
				</tfoot>
			</table>
		</div>

		<?php if ( $is_editable ) : ?>
		<div class="atum-items-table__actions">
			<button type="button" class="add-all-to-stock btn btn-outline-primary"><?php esc_html_e( 'Add All Into Stock', ATUM_PO_TEXT_DOMAIN ); ?></button>
		</div>
		<?php endif; ?>

	</div>

</div>

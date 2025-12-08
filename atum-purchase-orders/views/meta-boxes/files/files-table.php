<?php
/**
 * View for the PO files table
 *
 * @since 0.9.7
 *
 * @var array                     $files
 * @var \AtumPO\Models\POExtended $po
 */

$is_editable = $po->is_editable();
?>
<div class="atum-items-table__wrapper">
	<table class="atum-items-table po-files__table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', ATUM_PO_TEXT_DOMAIN ); ?></th>
				<th><?php esc_html_e( 'Description', ATUM_PO_TEXT_DOMAIN ); ?></th>

				<?php if ( $is_editable ) : ?>
				<th class="center" style="width: 1%"><?php esc_html_e( 'Actions', ATUM_PO_TEXT_DOMAIN ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>
			<tr class="no-items-message">
				<td colspan="3">
					<div class="items-blocker unblocked">
						<?php esc_html_e( 'No Files Added', ATUM_PO_TEXT_DOMAIN ); ?>
					</div>
				</td>
			</tr>
			<?php foreach ( $files as $file ) : ?>
				<tr data-id="<?php echo esc_attr( $file['id'] ) ?>">
					<td>
						<a href="<?php echo esc_url( wp_get_attachment_url( $file['id'] ) ) ?>" target="_blank">
							<?php echo esc_html( basename( get_attached_file( $file['id'] ) ) ) ?>
						</a>
					</td>
					<td class="file-description">
						<div class="description"><?php echo wp_kses_post( $file['desc'] ) ?></div>

						<?php if ( $is_editable ) : ?>
						<div class="edit-file-description">
							<textarea rows="3"><?php echo esc_textarea( $file['desc'] ) ?></textarea>
							<button type="button" class="btn btn-primary btn-sm"><?php esc_html_e( 'Save', ATUM_PO_TEXT_DOMAIN ); ?></button>
						</div>
						<?php endif; ?>
					</td>

					<?php if ( $is_editable ) : ?>
					<td class="center"><i class="show-actions atum-icon atmi-options"></i></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="3">
					<?php if ( $is_editable ) : ?>
					<button type="button" class="add-po-file btn btn-primary"><?php esc_html_e( 'Add File', ATUM_PO_TEXT_DOMAIN ); ?></button>
					<?php endif; ?>
				</td>
			</tr>
		</tfoot>
	</table>
</div>

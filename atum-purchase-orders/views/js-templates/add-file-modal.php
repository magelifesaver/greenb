<?php
/**
 * View for the Add PO File modal's JS template
 *
 * @since 0.9.7
 */

defined( 'ABSPATH' ) || die;

?>
<script type="text/template" id="add-file-modal">
	<div class="atum-modal-content">

		<div class="note">
			<?php esc_html_e( 'Add a file to this purchase order.', ATUM_PO_TEXT_DOMAIN ) ?><br>
		</div>

		<hr>

		<div class="po-modal__details">

			<div class="po-modal__details-field full-width">
				<label><?php esc_html_e( 'File', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<div class="file-info">
					<span class="file-name no-file"><?php esc_html_e( 'No file added', ATUM_PO_TEXT_DOMAIN ); ?></span>
					<button type="button" class="btn btn-outline-primary btn-sm atum-file-uploader"><?php esc_html_e( 'Upload', ATUM_PO_TEXT_DOMAIN ); ?></button>
					<input type="hidden" name="file" value="">
					<input type="hidden" name="file_url" value="">
				</div>
			</div>

			<div class="po-modal__details-field full-width">
				<label><?php esc_html_e( 'Description (optional)', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<textarea name="file_description" rows="5"></textarea>
			</div>

		</div>

	</div>
</script>

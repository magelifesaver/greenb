<?php
/**
 * View for the PO notes' meta box
 *
 * @since 0.3.0
 *
 * @var bool $is_editable
 */

defined( 'ABSPATH' ) || die;

?>
<script type="text/template" id="tmpl-atum-comments-actions">
	<div class="po-comments-row-actions hide">
		<div class="po-comments-actions-wrapper">

			<?php if ( $is_editable ) : ?>
			<div class="bulk-actions-wrapper">
				<select id="po-comments-bulk-actions">
					<option value="invalid"><?php esc_html_e( 'Bulk Actions', ATUM_PO_TEXT_DOMAIN ); ?></option>
					<option value="remove"><?php esc_html_e( 'Remove comments', ATUM_PO_TEXT_DOMAIN ); ?></option>
					<option value="mark_read" style="display: none;"><?php esc_html_e( 'Mark as Read', ATUM_PO_TEXT_DOMAIN ); ?></option>
				</select>
				<span id="po-comments-bulk-actions-btn" class="btn btn-sm btn-warning"><?php esc_html_e( 'APPLY', ATUM_PO_TEXT_DOMAIN ); ?></span>
			</div>
			<?php endif; ?>

			<div class="search-wrapper">
				<p class="search-box">
					<label class="screen-reader-text" for="post-search-input"><?php esc_html_e( 'Search POs:', ATUM_PO_TEXT_DOMAIN ); ?></label>
					<input type="search" id="post-search-input" name="s" value="" placeholder="<?php esc_attr_e( 'Search...', ATUM_PO_TEXT_DOMAIN ); ?>">
					<input type="button" id="po-search-comments" class="btn btn-warning" value="<?php esc_attr_e( 'Search', ATUM_PO_TEXT_DOMAIN ); ?>">
				</p>
			</div>

		</div>
	</div>

</script>

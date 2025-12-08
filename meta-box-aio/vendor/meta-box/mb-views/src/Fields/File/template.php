<div class="mbv-modal hidden" data-modal="field-file" data-type="file">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'File settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-field-file-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-file-output">
					<option value="tag"><?php esc_html_e( 'File link tag', 'mb-views' ) ?></option>
					<option value="url"><?php esc_html_e( 'File URL', 'mb-views' ) ?></option>
					<option value="title"><?php esc_html_e( 'File title', 'mb-views' ) ?></option>
					<option value="name"><?php esc_html_e( 'File name', 'mb-views' ) ?></option>
				</select>
			</div>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>

<div class="mbv-modal hidden" data-modal="field-background" data-type="background">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'Background settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-field-background-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-background-output">
					<option value="css"><?php esc_html_e( 'CSS', 'mb-views' ) ?></option>
					<option value="color"><?php esc_html_e( 'Color', 'mb-views' ) ?></option>
					<option value="image"><?php esc_html_e( 'Image', 'mb-views' ) ?></option>
					<option value="position"><?php esc_html_e( 'Position', 'mb-views' ) ?></option>
					<option value="attachment"><?php esc_html_e( 'Attachment', 'mb-views' ) ?></option>
					<option value="size"><?php esc_html_e( 'Size', 'mb-views' ) ?></option>
					<option value="repeat"><?php esc_html_e( 'Repeat', 'mb-views' ) ?></option>
				</select>
			</div>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>

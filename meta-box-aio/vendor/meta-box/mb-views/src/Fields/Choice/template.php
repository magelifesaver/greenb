<div class="mbv-modal hidden" data-modal="field-choice" data-type="choice">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'Choice settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-field-choice-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-choice-output">
					<option value="value"><?php esc_html_e( 'Value', 'mb-views' ) ?></option>
					<option value="label"><?php esc_html_e( 'Label', 'mb-views' ) ?></option>
				</select>
			</div>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>

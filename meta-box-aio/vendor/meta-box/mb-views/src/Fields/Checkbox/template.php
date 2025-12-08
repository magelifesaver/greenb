<div class="mbv-modal hidden" data-modal="field-checkbox" data-type="checkbox">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'Checkbox settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-field-checkbox-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-checkbox-output">
					<option value="raw"><?php esc_html_e( 'Raw value (1 = checked, 0 = unchecked)', 'mb-views' ) ?></option>
					<option value="custom"><?php esc_html_e( 'Custom', 'mb-views' ) ?></option>
				</select>
			</div>
			<div class="mbv-control mbv-field-checkbox-checked">
				<label class="mbv-control__label" for=field-checkbox-checked><?php esc_html_e( 'Text for checked status', 'mb-views' ) ?></label>
				<input class="mbv-control__input" type="text" id="mbv-field-checkbox-checked" value="<?php esc_attr_e( 'Yes', 'mb-views' ) ?>">
			</div>
			<div class="mbv-control mbv-field-checkbox-unchecked">
				<label class="mbv-control__label" for="mbv-field-checkbox-unchecked"><?php esc_html_e( 'Text for unchecked status', 'mb-views' ) ?></label>
				<input class="mbv-control__input" type="text" id="mbv-field-checkbox-unchecked" value="<?php esc_attr_e( 'No', 'mb-views' ) ?>">
			</div>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>

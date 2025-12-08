<div class="mbv-modal hidden" data-modal="field-oembed" data-type="oembed">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'Oembed settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-field-oembed-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-oembed-output">
					<option value="rendered"><?php esc_html_e( 'Rendered embeded media', 'mb-views' ) ?></option>
					<option value="url"><?php esc_html_e( 'URL', 'mb-views' ) ?></option>
				</select>
			</div>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>

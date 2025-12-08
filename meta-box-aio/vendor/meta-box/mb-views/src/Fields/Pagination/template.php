<div class="mbv-modal hidden" data-modal="field-pagination" data-type="pagination">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'Pagination settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control mbv-field-pagination-type">
				<label class="mbv-control__label" for="mbv-field-pagination-type"><?php esc_html_e( 'Type', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-pagination-type">
					<option value="numeric"><?php esc_html_e( 'Numeric', 'mb-views' ) ?></option>
					<option value="next_previous"><?php esc_html_e( 'Next/Previous', 'mb-views' ) ?></option>
				</select>
			</div>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>

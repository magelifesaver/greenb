<div class="mbv-modal hidden" data-modal="field-taxonomy" data-type="taxonomy">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'Taxonomy settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-field-taxonomy-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-taxonomy-output">
					<option value="tag"><?php esc_html_e( 'Term link tag', 'mb-views' ) ?></option>
					<option value="id"><?php esc_html_e( 'Term ID', 'mb-views' ) ?></option>
					<option value="name"><?php esc_html_e( 'Term name', 'mb-views' ) ?></option>
					<option value="slug"><?php esc_html_e( 'Term slug', 'mb-views' ) ?></option>
					<option value="url"><?php esc_html_e( 'Term URL', 'mb-views' ) ?></option>
					<option value="description"><?php esc_html_e( 'Term description', 'mb-views' ) ?></option>
				</select>
			</div>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>

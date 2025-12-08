<div class="mbv-modal hidden" data-modal="field-video" data-type="video">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'Video settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control mbv-field-video-output">
				<label class="mbv-control__label" for="mbv-field-video-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-video-output">
					<option value="rendered"><?php esc_html_e( 'Video player', 'mb-views' ) ?></option>
					<option value="ID"><?php esc_html_e( 'Video ID', 'mb-views' ) ?></option>
					<option value="src"><?php esc_html_e( 'Video source URL', 'mb-views' ) ?></option>
					<option value="title"><?php esc_html_e( 'Video title', 'mb-views' ) ?></option>
					<option value="caption"><?php esc_html_e( 'Video caption', 'mb-views' ) ?></option>
					<option value="description"><?php esc_html_e( 'Video description', 'mb-views' ) ?></option>
					<option value="dimensions.width"><?php esc_html_e( 'Video width', 'mb-views' ) ?></option>
					<option value="dimensions.height"><?php esc_html_e( 'Video height', 'mb-views' ) ?></option>
					<option value="image.src"><?php esc_html_e( 'Video thumbnail URL', 'mb-views' ) ?></option>
					<option value="image.width"><?php esc_html_e( 'Video thumbnail width', 'mb-views' ) ?></option>
					<option value="image.height"><?php esc_html_e( 'Video thumbnail height', 'mb-views' ) ?></option>
				</select>
			</div>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>

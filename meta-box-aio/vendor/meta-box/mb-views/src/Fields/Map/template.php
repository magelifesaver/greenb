<div class="mbv-modal hidden" data-modal="field-map" data-type="map">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'Map settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-field-map-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-map-output">
					<option value="map_default"><?php esc_html_e( 'Map (default style)', 'mb-views' ) ?></option>
					<option value="map_custom"><?php esc_html_e( 'Map (custom style)', 'mb-views' ) ?></option>
					<option value="latitude"><?php esc_html_e( 'Latitude', 'mb-views' ) ?></option>
					<option value="longitude"><?php esc_html_e( 'Longitude', 'mb-views' ) ?></option>
				</select>
			</div>
			<div class="mbv-control mbv-field-map-width">
				<label class="mbv-control__label" for="mbv-field-map-width"><?php esc_html_e( 'Width', 'mb-views' ) ?></label>
				<input class="mbv-control__input" type="text" id="mbv-field-map-width" value="100%">
			</div>
			<div class="mbv-control mbv-field-map-height">
				<label class="mbv-control__label" for="mbv-field-map-height"><?php esc_html_e( 'Height', 'mb-views' ) ?></label>
				<input class="mbv-control__input" type="text" id="mbv-field-map-height" value="480px">
			</div>
			<div class="mbv-control mbv-field-map-zoom">
				<label class="mbv-control__label" for="mbv-field-map-zoom"><?php esc_html_e( 'Zoom level', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-map-zoom">
					<?php foreach ( range( 0, 19 ) as $number ) : ?>
						<option value="<?= esc_attr( $number ) ?>"<?php selected( $number, 14, true ) ?>><?= esc_html( $number ) ?></option>
					<?php endforeach ?>
				</select>
			</div>
			<div class="mbv-control mbv-field-map-marker-icon">
				<label class="mbv-control__label" for="mbv-field-map-marker-icon"><?php esc_html_e( 'Marker icon', 'mb-views' ) ?></label>
				<input class="mbv-control__input" type="url" id="mbv-field-map-marker-icon" placeholder="https://">
			</div>
			<div class="mbv-control mbv-field-map-marker-title">
				<label class="mbv-control__label" for="mbv-field-map-marker-title"><?php esc_html_e( 'Marker title', 'mb-views' ) ?></label>
				<input class="mbv-control__input" type="text" id="mbv-field-map-marker-title">
				<div class="mbv-control__description"><?php esc_html_e( 'Displayed when hover the marker.', 'mb-views' ) ?></div>
			</div>
			<div class="mbv-control mbv-field-map-info-window">
				<label class="mbv-control__label" for="mbv-field-map-info-window"><?php esc_html_e( 'Info window', 'mb-views' ) ?></label>
				<textarea class="mbv-control__input" id="mbv-field-map-info-window" rows="2"></textarea>
				<div class="mbv-control__description"><?php esc_html_e( 'Displayed when click the marker. HTML allowed. No quotes.', 'mb-views' ) ?></div>
			</div>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>

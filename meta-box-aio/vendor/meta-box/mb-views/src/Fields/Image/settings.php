<div class="mbv-control <?= esc_attr( $prefix ) ?>-size">
	<label class="mbv-control__label" for="<?= esc_attr( $prefix ) ?>-size"><?php esc_html_e( 'Image size', 'mb-views' ) ?></label>
	<?php $sizes = $this->get_image_sizes(); ?>
	<select class="mbv-control__input" id="<?= esc_attr( $prefix ) ?>-size">
		<?php foreach ( $sizes as $size => $dimensions ) : ?>
			<?php $name = ucwords( str_replace( [ '_', '-' ], ' ', $size ) ) ?>
			<option value="<?= esc_attr( $size ) ?>"><?= esc_html( "{$name} ({$dimensions[0]}x{$dimensions[1]})" ) ?></option>
		<?php endforeach ?>
		<option value="full"><?php esc_html_e( 'Full size', 'mb-views' ) ?></option>
	</select>
</div>
<div class="mbv-control <?= esc_attr( $prefix ) ?>-output">
	<label class="mbv-control__label" for="<?= esc_attr( $prefix ) ?>-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
	<select class="mbv-control__input" id="<?= esc_attr( $prefix ) ?>-output">
		<option value="tag"><?php esc_html_e( 'Image tag', 'mb-views' ) ?></option>
		<option value="ID"><?php esc_html_e( 'Image ID', 'mb-views' ) ?></option>
		<option value="url"><?php esc_html_e( 'Image URL', 'mb-views' ) ?></option>
		<option value="title"><?php esc_html_e( 'Image title', 'mb-views' ) ?></option>
		<option value="alt"><?php esc_html_e( 'Image alt text', 'mb-views' ) ?></option>
		<option value="caption"><?php esc_html_e( 'Image caption', 'mb-views' ) ?></option>
		<option value="description"><?php esc_html_e( 'Image description', 'mb-views' ) ?></option>
		<option value="width"><?php esc_html_e( 'Image width', 'mb-views' ) ?></option>
		<option value="height"><?php esc_html_e( 'Image height', 'mb-views' ) ?></option>
		<option value="name"><?php esc_html_e( 'Image file name', 'mb-views' ) ?></option>
	</select>
</div>

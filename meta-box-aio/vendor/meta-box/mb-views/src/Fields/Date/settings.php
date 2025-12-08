<div class="mbv-control <?= esc_attr( $prefix ) ?>-format">
	<label class="mbv-control__label" for="<?= esc_attr( $prefix ) ?>-format"><?php esc_html_e( 'Format', 'mb-views' ) ?></label>
	<select class="mbv-control__input" id="<?= esc_attr( $prefix ) ?>-format">
		<option value="<?= esc_attr( get_option( 'date_format' ) ) ?>" selected><?php esc_html_e( 'From WordPress settings', 'mb-views' ) ?></option>
		<option value="M j, Y">Feb 13, 2020</option>
		<option value="F j, Y">February 13, 2020</option>
		<option value="m/d/Y">02/13/2020</option>
		<option value="d-m-Y">13-02-2020</option>
		<option value="d M Y">13 Feb 2020</option>
		<option value="d F Y">13 February 2020</option>
		<option value="Y-m-d">2020-02-13</option>
		<option value="Y-m-d H:i">2020-02-13 16:20</option>
		<option value="M j, Y h:i A">Feb 13, 2020 04:20 PM</option>
		<option value="H:i">16:20</option>
		<option value="h:i A">04:20 PM</option>
		<option value="custom"><?php esc_html_e( 'Custom', 'mb-views' ) ?></option>
	</select>
</div>
<div class="mbv-control <?= esc_attr( $prefix ) ?>-custom-format">
	<label class="mbv-control__label" for="<?= esc_attr( $prefix ) ?>-custom-format"><?php esc_html_e( 'Custom format string', 'mb-views' ) ?></label>
	<input class="mbv-control__input" id="<?= esc_attr( $prefix ) ?>-custom-format" type="text">
	<?php // Translators: %s - URL to php.net date function. ?>
	<div class="mbv-control__description"><?= wp_kses_post( sprintf( __( 'See <a href="%s" target="_blank">references</a>.', 'mb-views' ), 'https://www.php.net/manual/en/function.date.php' ) ) ?></div>
</div>

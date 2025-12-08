<?php
/**
 * View for the Barcodes PRO's fields within the ATUM's Product Data meta box
 *
 * @since 0.1.1
 *
 * @var string $field_visibility
 * @var string $product_type
 * @var string $product_barcode_type
 */

defined( 'ABSPATH' ) || die;

use AtumBarcodes\Inc\Globals;

?>
<div class="options_group <?php echo esc_attr( $field_visibility ) ?>">

	<h4 class="atum-section-title"><?php esc_html_e( 'Barcodes Settings', ATUM_BARCODES_TEXT_DOMAIN ) ?></h4>

	<p class="form-field _barcode_type_field">
		<label for="barcode_type"><?php esc_attr_e( 'Barcode Type', ATUM_BARCODES_TEXT_DOMAIN ) ?></label>

		<select name="barcode_type" id="barcode_type">
			<option value=""<?php selected( $product_barcode_type, 'global' ) ?>>-- <?php esc_html_e( 'Global', ATUM_BARCODES_TEXT_DOMAIN ); ?> --</option>
			<?php foreach ( Globals::get_allowed_barcodes() as $barcode_type => $label ) : ?>
				<option value="<?php echo esc_attr( $barcode_type ) ?>"<?php selected( $product_barcode_type, $barcode_type ) ?>><?php echo esc_html( $label ) ?></option>
			<?php endforeach; ?>
		</select>

		<?php echo wc_help_tip( esc_attr__( 'Choose a different barcode type for this product if you want to override the global setting.', ATUM_BARCODES_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>

</div>

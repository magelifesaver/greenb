<?php
/**
 * ATUM barcode input field view
 *
 * @var string $barcode
 * @var int    $order_id
 */

?>
<div class="atum-barcodes__input">
	<input
		type="text"
		value="<?php echo esc_attr( $barcode !== (string) $order_id ? $barcode : '' ) ?>"
		id="atum_barcode" name="atum_barcode"
		placeholder="<?php esc_attr_e( 'Enter a manual barcode...', ATUM_BARCODES_TEXT_DOMAIN ); ?>"
	>
</div>

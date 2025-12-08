<?php
/**
 * ATUM barcodes meta box view
 *
 * @var string          $default_barcode
 * @var string          $barcode
 * @var string|WP_Error $barcode_img
 * @var WP_Post         $post
 */

use Atum\PurchaseOrders\PurchaseOrders;
use Atum\InventoryLogs\InventoryLogs;


$is_variable = FALSE;

if ( ! empty( $post ) && $post instanceof WP_Post && 'product' === $post->post_type ) :
	$product     = wc_get_product( $post );
	$is_variable = $product->is_type( 'variable' );
endif;
?>

<div class="atum-barcodes">

	<?php if ( $is_variable ) : ?>

		<div class="atum-barcodes__variations">

			<a class="load-variations" href="#">
				<?php esc_html_e( 'Load variations first to see their barcodes', ATUM_BARCODES_TEXT_DOMAIN ); ?>
			</a>

			<select name="atum-barcode-variation" id="atum-barcode-variation" style="display: none">
				<option value=""><?php esc_html_e( 'Select a variation...', ATUM_BARCODES_TEXT_DOMAIN ); ?></option>
			</select>

			<div class="atum-barcodes__variations-barcode" style="display: none"></div>
		</div>

	<?php elseif ( is_wp_error( $barcode_img ) ) : ?>

		<div class="alert alert-danger"><?php echo esc_html( $barcode_img->get_error_message() ?: __( 'Error generating the barcode. Please check if it was entered correctly', ATUM_BARCODES_TEXT_DOMAIN ) ) ?></div>

	<?php elseif ( ! empty( $barcode_img ) ) : ?>

		<?php echo $barcode_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php if ( ! empty( $default_barcode ) ) : ?>
			<?php if ( $post instanceof WP_Post && in_array( $post->post_type, [ PurchaseOrders::POST_TYPE, InventoryLogs::POST_TYPE ] ) ): ?>
				<span class="atum-help-tip atum-tooltip" data-tip="<?php esc_attr_e( 'This barcode was created automatically. If you want to add your own barcode, please use the input field below.', ATUM_TEXT_DOMAIN ) ?>"></span>
			<?php else: ?>
				<?php echo wc_help_tip( __( 'This barcode was created automatically. If you want to add your own barcode, please use the input field below.', ATUM_BARCODES_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		<?php endif; ?>

	<?php else : ?>

		<div class="alert alert-primary"><?php esc_html_e( 'No barcode available.', ATUM_BARCODES_TEXT_DOMAIN ); ?></div>

	<?php endif; ?>

	<?php do_action( 'atum/barcodes_pro/after_barcodes_meta_box', $barcode, $post ); ?>
</div>

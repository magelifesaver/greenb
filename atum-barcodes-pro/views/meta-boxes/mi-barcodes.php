<?php
/**
 * ATUM barcodes meta box view for products with MI enabled
 *
 * @var string          $barcode
 * @var string|WP_Error $barcode_img
 * @var WP_Post         $post
 */

use AtumBarcodes\Inc\Helpers;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Inc\Helpers as MIHelpers;

$product      = AtumHelpers::get_atum_product( $post->ID );
$barcode_type = $product->get_barcode_type();
$inventories  = MIHelpers::get_product_inventories_sorted( $post->ID );
$inv_barcodes = [];

foreach ( $inventories as $inventory ) :
	$inv_barcode_img = Helpers::generate_barcode( $inventory->barcode, [ 'type' => $barcode_type ] );

	if ( $inv_barcode_img && ! is_wp_error( $inv_barcode_img ) ) :
		$inv_barcodes[ $inventory->id ] = array(
			'name'        => $inventory->name,
			'barcode_img' => $inv_barcode_img,
		);
	endif;
endforeach;
?>

<div class="atum-barcodes">

	<?php if ( ! empty( $inv_barcodes ) ) : ?>

		<div class="atum-barcodes__inventories">
			<?php foreach ( $inv_barcodes as $inventory_id => $inv_barcode ) : ?>
				<div data-id="<?php echo esc_attr( $inventory_id ) ?>">

					<strong><?php echo esc_html( $inv_barcode['name'] ); ?></strong>

					<?php if ( is_wp_error( $inv_barcode['barcode_img'] ) ) : ?>
						<div class="alert alert-danger"><?php echo esc_html( $inv_barcode['barcode_img']->get_error_message() ?: __( 'Error generating the barcode. Please check if it was entered correctly', ATUM_BARCODES_TEXT_DOMAIN ) ) ?></div>
					<?php else : ?>
						<?php echo $inv_barcode['barcode_img']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>

				</div>
			<?php endforeach; ?>
		</div>

	<?php else : ?>

		<div class="alert alert-primary"><?php esc_html_e( 'No barcodes available.', ATUM_BARCODES_TEXT_DOMAIN ); ?></div>

	<?php endif; ?>

	<?php do_action( 'atum/barcodes_pro/after_mi_barcodes_meta_box', $barcode ); ?>
</div>

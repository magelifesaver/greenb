<?php
/**
 * View for the Add to PO modal's items
 *
 * @since 0.9.10
 *
 * @var \WC_Product $product
 * @var array       $suppliers
 */

use Atum\Suppliers\Supplier;
use AtumPO\Inc\Helpers;
use Atum\Inc\Helpers as AtumHelpers;

$product_id = $product->get_id();
$thumbnail  = $product_id ? $product->get_image( 'thumbnail', array( 'title' => '' ), FALSE ) : '';
$value      = $product->get_stock_quantity() < 0 ? -$product->get_stock_quantity() + 1 : 1;
$min_stock  = Helpers::get_minimum_quantity_to_add();

$item_stock = $product->get_stock_quantity();
$backorders = '&#45;';

if ( 0 > $item_stock ) {
	$backorders = $item_stock;
	$item_stock = 0;
}
?>
<tr class="add-item__product<?php echo esc_attr( apply_filters( 'atum/purchase_orders_pro/add_to_po/item_row_classes', '', $product_id ) ) ?>"
	data-id="<?php echo esc_attr( $product_id ) ?>">

	<td class="add-item__name">
		<span class="add-item__name-wrapper">
			<?php echo $thumbnail ?: AtumHelpers::get_product_image_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php echo esc_html( $product->get_name() ) ?>
		</span>
	</td>
	<?php
	$supplier_id = $product->get_supplier_id();
	$suppliers[] = $supplier_id; // No matter it has a supplier ID or not, an empty supplier must be counted later.
	?>
	<td class="add-item__supplier" data-supplier-id="<?php echo esc_attr( $supplier_id ) ?>">
		<?php if ( $supplier_id ) : ?>
			<?php
			$supplier = new Supplier( $supplier_id );
			echo esc_html( $supplier->name );
			?>
		<?php else : ?>
			&ndash;
		<?php endif; ?>
	</td>
	<td class="add-item__stock">
		<?php echo esc_html( $item_stock ) ?>
	</td>
	<td class="add-item__backorders">
		<?php echo esc_html( $backorders ) ?>
	</td>
	<td class="add-item__qty">
		<input type="number" min="<?php echo esc_attr( $min_stock ); ?>" step="any" name="qty[<?php echo esc_attr( $product_id ) ?>]" value="<?php echo esc_attr( $value ) ?>">
	</td>
	<td class="add-item__actions">
		<i class="atum-icon atmi-cross-circle remove-item-result" title="<?php esc_attr_e( 'Remove item', ATUM_PO_TEXT_DOMAIN ); ?>"></i>
	</td>
</tr>

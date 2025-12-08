<?php
/**
 * View for the Add to PO modal's MI items
 *
 * @since 0.9.10
 *
 * @var Inventory $inventory
 * @var array     $suppliers
 */

use AtumMultiInventory\Models\Inventory;
use Atum\Suppliers\Supplier;
use AtumPO\Inc\Helpers;

$item_stock = $inventory->stock_quantity;
$backorders = '&#45;';
$value      = $item_stock < 0 ? -$item_stock + 1 : 1;
$min_stock  = Helpers::get_minimum_quantity_to_add();

if ( 0 > $item_stock ) {
	$backorders = $item_stock;
	$item_stock = 0;
}

?>
<tr class="add-item__product is-inventory" data-inventory-id="<?php echo esc_attr( $inventory->id ) ?>"
	data-product-id="<?php echo esc_attr( $inventory->product_id ) ?>">

	<td class="add-item__name">
		<div class="add-item-result inventory-result">
			<div class="add-item-result__info">

				<span>
					<i class="atum-icon atmi-arrow-child"></i>
					<i class="atum-icon atmi-multi-inventory"></i>
				</span>

				<div class="add-item-result__title"><?php echo esc_html( $inventory->name ) ?></div>

			</div>
		</div>

	</td>

	<?php
	$supplier_id = $inventory->supplier_id;
	?>
	<td class="add-item__supplier" data-supplier-id="<?php echo esc_attr( $supplier_id ) ?>">
		<?php if ( $supplier_id ) : ?>
			<?php
			$suppliers[] = $supplier_id;
			$supplier    = new Supplier( $supplier_id );
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
		<input type="number" min="<?php echo esc_attr( $min_stock ); ?>" step="any" name="qty[<?php echo esc_attr( $inventory->product_id . ':' . $inventory->id ) ?>]" value="<?php echo esc_attr( $value ) ?>">
	</td>
	<td class="add-item__actions">
		<i class="atum-icon atmi-cross-circle remove-item-result" title="<?php esc_attr_e( 'Remove item', ATUM_PO_TEXT_DOMAIN ); ?>"></i>
	</td>
</tr>

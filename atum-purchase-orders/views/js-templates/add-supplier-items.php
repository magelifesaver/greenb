<?php
/**
 * View for the Add supplier items panel.
 *
 * @since 1.0.4
 *
 * @var array $suppliers
 */

if ( ! empty( $suppliers ) ) {
	$tooltip_oost = _n(
		'Add all the products which status is "out of stock" or "on backorder" from the current supplier at once',
		'Add all the products which status is "out of stock" or "on backorder" from the current suppliers at once', count( $suppliers ),
		ATUM_PO_TEXT_DOMAIN
	);
	$tooltip_lst  = _n(
		'Add all the products that reached their low stock threshold from the current supplier at once',
		'Add all the products that reached their low stock threshold from the current suppliers at once', count( $suppliers ),
		ATUM_PO_TEXT_DOMAIN
	);
	$tooltip_rst  = _n(
		'Add all the products in restock status from the current supplier at once',
		'Add all the products in restock status from the current suppliers at once', count( $suppliers ),
		ATUM_PO_TEXT_DOMAIN
	);
}
else {
	$tooltip_oost = __( 'Add all the out-of-stock products at once', ATUM_PO_TEXT_DOMAIN );
	$tooltip_lst  = __( 'Add all the products that reached their low stock threshold at once', ATUM_PO_TEXT_DOMAIN );
	$tooltip_rst  = __( 'Add all the products in restock status at once', ATUM_PO_TEXT_DOMAIN );
}

?>

<div class="add-supplier-products-options no-items">
	<p class="add-supplier-products-options__text"><?php esc_html_e( 'Add all products with', ATUM_PO_TEXT_DOMAIN ) ?></p>

	<div class="add-options">
		<label for="out_of_stock_status">
			<input type="checkbox" id="out_of_stock_status" name="add_product_options[outofstock]" />
			<?php esc_html_e( 'Out of stock status', ATUM_PO_TEXT_DOMAIN ); ?>
			<span class="atum-help-tip atum-tooltip"
				title="<?php echo esc_attr( $tooltip_oost ); ?>"
			></span>
		</label>
		<label for="low_stock_threshold">
			<input type="checkbox" id="low_stock_threshold" name="add_product_options[lowstock]" />
			<?php esc_html_e( 'Low stock threshold', ATUM_PO_TEXT_DOMAIN ); ?>
			<span class="atum-help-tip atum-tooltip"
				title="<?php echo esc_attr( $tooltip_lst ); ?>"
			></span>
		</label>
		<label for="restock_status">
			<input type="checkbox" id="restock_status" name="add_product_options[restock]" />
			<?php esc_html_e( 'Restock status', ATUM_PO_TEXT_DOMAIN ); ?>
			<span class="atum-help-tip atum-tooltip"
				title="<?php echo esc_attr( $tooltip_rst ); ?>"
			></span>
		</label>
	</div>

	<button type="button" class="add-supplier-products btn btn-outline-primary" disabled><?php esc_html_e( 'Add Items', ATUM_PO_TEXT_DOMAIN ); ?></button>
</div>


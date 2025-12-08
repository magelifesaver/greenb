<?php
/**
 * View for the PO's product items
 *
 * @since 0.4.0
 *
 * @var \AtumPO\Models\POExtended                              $atum_order
 * @var \Atum\Components\AtumOrders\Items\AtumOrderItemProduct $item
 * @var int                                                    $item_id
 * @var string                                                 $class
 * @var string                                                 $currency
 * @var string                                                 $currency_template
 * @var int|float                                              $step
 * @var \Atum\Suppliers\Supplier                               $supplier
 * @var string                                                 $field_name_prefix
 * @var array                                                  $display_fields
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use Atum\Components\AtumCapabilities;
use AtumPO\Inc\Helpers;

do_action( 'atum/atum_order/before_item_product_html', $item, $atum_order ); // Using original ATUM hook.

$product       = AtumHelpers::get_atum_product( $item->get_product() );
$product_id    = $product instanceof \WC_Product ? $product->get_id() : 0;
$product_link  = $product_id ? admin_url( 'post.php?post=' . ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) . '&action=edit' ) : '';
$thumbnail     = $product_id ? $product->get_image( [ 40, 40 ], [ 'title' => '' ], FALSE ) : '';
$supplier_data = $product_id && $product->get_supplier_id() ? ' data-supplier="' . $product->get_supplier_id() . '"' : '';
?>
<tr class="item <?php echo esc_attr( apply_filters( 'atum/atum_order/item_class', ! empty( $class ) ? $class : '', $item, $atum_order ) ) ?>"
	data-product_id="<?php echo esc_attr( $product_id ); ?>" data-atum_order_item_id="<?php echo esc_attr( $item_id ); ?>"
	<?php echo $supplier_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>

	<?php // Thumbnail. ?>
	<td class="thumb">
		<?php echo '<div class="atum-order-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>'; ?>
	</td>

	<?php // Name. ?>
	<td class="name" data-sort-value="<?php echo esc_attr( $item->get_name() ) ?>">

		<?php
		$item_class = 'atum-order-item-name' . ( $product_id ? '' : ' deleted' );
		$item_title = $product_id ? '' : ' title="' . __( 'This product does not exist', ATUM_PO_TEXT_DOMAIN ) . '"';
		?>
		<div class="<?php echo esc_attr( $item_class ) ?>" <?php echo $item_title; ?>>
			<?php echo esc_html( $item->get_name() ) ?>

			<?php if ( $product_link ) : ?>
				<a href="<?php echo esc_url( $product_link ) ?>" class="atum-order-item-name atum-tooltip" target="_blank" title="<?php esc_attr_e( 'View Product', ATUM_PO_TEXT_DOMAIN ) ?>">
					<i class="atum-icon atmi-link"></i>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $product_id && $product->get_sku() ) : ?>
			<div class="atum-order-item-sku"><strong><?php esc_html_e( 'SKU:', ATUM_PO_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $product->get_sku() ) ?></div>
		<?php endif;

		if ( $product_id && AtumCapabilities::current_user_can( 'read_suppliers' ) ) :
			$supplier_sku = $product->get_supplier_sku();

			if ( $supplier_sku ) : ?>
				<div class="atum-order-item-sku"><strong><?php esc_html_e( 'Supplier SKU:', ATUM_PO_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $supplier_sku ) ?></div>
			<?php endif;
		endif;

		if ( $item->get_variation_id() ) : ?>
			<div class="atum-order-item-variation"><strong><?php esc_html_e( 'Variation ID:', ATUM_PO_TEXT_DOMAIN ) ?></strong>

				<?php if ( 'product_variation' === get_post_type( $item->get_variation_id() ) ) :
					echo esc_html( $item->get_variation_id() );
				else :
					/* translators: the variation ID */
					printf( esc_html__( '%s (No longer exists)', ATUM_PO_TEXT_DOMAIN ), esc_attr( $item->get_variation_id() ) );
				endif; ?>

			</div>
		<?php endif; ?>

		<input type="hidden" class="atum_order_item_id" name="<?php echo esc_attr( $field_name_prefix ) ?>id[]" value="<?php echo esc_attr( $item_id ) ?>">

		<?php do_action( 'atum/atum_order/before_item_meta', $item_id, $item, $product, $atum_order ) ?>
		<?php require 'item-meta.php'; ?>
		<?php do_action( 'atum/atum_order/after_item_meta', $item_id, $item, $product, $atum_order ) ?>

		<div class="order-item-icons">
			<?php // TODO: WHAT IF IS IT MANAGING AT PARENT LEVEL? WE SHOULD ADD THE STOCK TO THE PARENT INSTEAD... ?>
			<?php if ( $product_id && apply_filters( 'atum/purchase_orders_pro/item_unmanaged_stock_warning', $product_id && ( ! $product->managing_stock() || 'parent' === $product->managing_stock() ), $product ) ) : ?>
				<i class="atum-icon atmi-warning color-warning atum-tooltip" title="<?php esc_attr_e( "This item's stock is not managed by WooCommerce at product level, so it won't be updated when processing this PO", ATUM_PO_TEXT_DOMAIN ) ?>"></i>
			<?php endif; ?>

			<?php if ( 'yes' === $item->get_stock_changed() ) : ?>
				<i class="atum-icon atmi-highlight color-warning atum-tooltip" title="<?php esc_attr_e( "This item's stock was already changed within this PO", ATUM_PO_TEXT_DOMAIN ) ?>"></i>
			<?php endif; ?>

			<?php do_action( 'atum/atum_order/after_order_item_icons', $item_id, $item, $product, $atum_order ) ?>
		</div>
	</td>

	<?php do_action( 'atum/atum_order/item_values', $product, $item, absint( $item_id ) ); ?>

	<?php // Available Stock. ?>
	<?php if ( ! array_key_exists( 'stock', $display_fields ) || 'yes' === $display_fields['stock'] ) : ?>
	<td class="available_stock center" style="width: 1%">
		<div class="view">
			<?php
			if ( ! $product_id ) :
				$stock = '&ndash;';
			elseif ( ! $product->managing_stock() || 'parent' === $product->managing_stock() ) :
				$stock = 'instock' === $product->get_stock_status() ? '&infin;' : '&ndash;';
			else :
				$stock = esc_html( wc_stock_amount( $product->get_stock_quantity() ) );
			endif;

			echo apply_filters( 'atum/purchase_orders_pro/po_item/available_stock', $stock, $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</td>
	<?php endif; ?>

	<?php // Last Week Sales. ?>
	<?php if ( ! array_key_exists( 'last_week_sales', $display_fields ) || 'yes' === $display_fields['last_week_sales'] ) : ?>
	<td class="last_week_sales center" style="width: 1%">
		<div class="view">
			<?php echo $product_id ? esc_html( Helpers::get_product_last_week_sales( $product ) ) : '&ndash;'; ?>
		</div>
	</td>
	<?php endif; ?>

	<?php // Inbound Stock. ?>
	<?php if ( ! array_key_exists( 'inbound_stock', $display_fields ) || 'yes' === $display_fields['inbound_stock'] ) : ?>
	<td class="inbound_stock center" style="width: 1%">
		<div class="view">
			<?php echo $product_id ? esc_html( AtumHelpers::get_product_inbound_stock( $product ) ) : '&ndash;'; ?>
		</div>
	</td>
	<?php endif; ?>

	<?php // Recommended Order Quantity. ?>
	<?php if ( ! array_key_exists( 'recommended_quantity', $display_fields ) || 'yes' === $display_fields['recommended_quantity'] ) : ?>
	<td class="roq center" style="width: 1%">
		<div class="view">
			<?php echo $product_id ? esc_html( Helpers::get_product_roq( $product ) ) : '&ndash;'; ?>
		</div>
	</td>
	<?php endif; ?>

	<?php // Cost.
	$item_cost = wc_format_decimal( $atum_order->get_item_subtotal( $item, FALSE, TRUE ) );
	require 'item-cost-cell.php';
	?>

	<?php // Discount.
	$item_discount = $item->get_quantity() ? wc_format_decimal( ( floatval( $item->get_subtotal() ) - floatval( $item->get_total() ) ) / floatval( $item->get_quantity() ), '' ) : 0;
	require 'item-discount-cell.php';
	?>

	<?php // Returned Quantity.
	if ( $atum_order->is_returning() ) :
		require 'item-returned-ratio-cell.php';
	endif;
	?>

	<?php // Quantity.
	require 'item-qty-cell.php';
	?>

	<?php
	// Total.
	$item_total = $item->get_total();
	?>
	<td class="line_total center" style="width: 1%" data-sort-value="<?php echo esc_attr( $item_total ); ?>">
		<span class="field-label currency" data-template="<?php echo esc_attr( $currency_template ) ?>"
			data-decimal-separator="<?php echo esc_attr( $atum_order->price_decimal_sep ) ?>"
			data-value="<?php echo esc_attr( $item_total ) ?>" data-decimals-number="2"
		>
			<?php echo $atum_order->format_price( $item_total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>

		<input type="hidden" class="line_total" name="<?php echo esc_attr( $field_name_prefix ) ?>total[<?php echo absint( $item_id ) ?>]" value="<?php echo esc_attr( $item->get_total() ) ?>">
	</td>

	<?php // Tax.
	require 'item-tax-cell.php'; ?>

	<?php // Actions. ?>
	<?php if ( $atum_order->is_editable() ) : ?>
	<td class="actions center" style="width: 1%">
		<i class="show-actions atum-icon atmi-options"></i>

		<?php do_action( 'atum/atum_order/after_item_actions', $item, $atum_order ) ?>
	</td>
	<?php endif; ?>
</tr>
<?php

do_action( 'atum/atum_order/after_item_product_html', $item, $atum_order );

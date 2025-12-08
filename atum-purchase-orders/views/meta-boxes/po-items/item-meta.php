<?php
/**
 * View for the PO items' meta
 *
 * @since 0.4.0
 *
 * @var \WC_Order_Item            $item
 * @var int                       $item_id
 * @var \AtumPO\Models\POExtended $atum_order
 */

defined( 'ABSPATH' ) || die;

$hidden_item_meta = apply_filters( 'atum/atum_order/hidden_item_meta', array(
	'_qty',
	'_tax_class',
	'_product_id',
	'_variation_id',
	'_line_subtotal',
	'_line_subtotal_tax',
	'_line_total',
	'_line_tax',
	'_line_tax_data',
	'_method_id',
	'_cost',
	'_total_tax',
	'_taxes',
	'_stock_changed',
	'_discount_config',
	'_tax_config',
) );


$meta_data = $item->get_formatted_meta_data( '' );

if ( ! empty( $meta_data ) ) : ?>

	<div class="item-meta">
		<?php foreach ( $meta_data as $meta_id => $meta ) :

			if ( in_array( $meta->key, $hidden_item_meta, TRUE ) ) :
				continue;
			endif;

			if ( '_order_id' === $meta->display_key ) :
				$meta->display_key = __( 'Order ID', ATUM_PO_TEXT_DOMAIN );
			endif;
			?>
			<div class="item-meta__row">
				<?php echo wp_kses_post( $meta->display_key ) ?>: <?php echo wp_kses_post( $meta->value ); ?>
			</div>

		<?php endforeach; ?>
	</div>

<?php endif; ?>

<script type="text/template" id="add-meta-<?php echo esc_attr( $item_id ) ?>">
	<div class="atum-modal-content">

		<div class="note"></div>
		<hr>

		<div class="meta-table-wrapper">
			<table class="meta" cellspacing="0">
				<tbody class="meta_items">
					<?php if ( ! empty( $meta_data ) ) : ?>

						<?php foreach ( $meta_data as $meta_id => $meta ) :

							if ( in_array( $meta->key, $hidden_item_meta, TRUE ) ) :
								continue;
							endif;
							?>
							<tr data-meta_id="<?php echo esc_attr( $meta_id ) ?>">
								<td>
									<input type="text" placeholder="<?php esc_attr_e( 'Name (required)', ATUM_PO_TEXT_DOMAIN ) ?>" name="meta_key[<?php echo esc_attr( $item_id ) ?>][<?php echo esc_attr( $meta_id ) ?>]" value="<?php echo esc_attr( $meta->key ) ?>" />
								</td>
								<td>
									<input type="text" placeholder="<?php esc_attr_e( 'Value (required)', ATUM_PO_TEXT_DOMAIN ) ?>" name="meta_value[<?php echo esc_attr( $item_id ) ?>][<?php echo esc_attr( $meta_id ) ?>]" value="<?php echo esc_textarea( rawurldecode( $meta->value ) ) ?>" />
								</td>
								<td style="width: 1%">
									<button class="remove-atum-order-item-meta btn btn-danger" type="button"><i class="atum-icon atmi-trash"></i></button>
								</td>
							</tr>

						<?php endforeach; ?>

					<?php endif; ?>
				</tbody>
			</table>
		</div>

	</div>
</script>

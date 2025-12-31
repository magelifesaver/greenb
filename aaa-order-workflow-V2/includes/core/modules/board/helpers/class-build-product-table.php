<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/helpers/class-build-product-table.php
 * Purpose: Core/default neutral product table for Board cards (no module-specific logic).
 * Columns: Qty | Product | Unit Price | Total
 * Note: Signature preserved for backward-compatibility; extra parameters are ignored.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_Build_Product_Table' ) ) :

class AAA_Build_Product_Table {

	/**
	 * Render a simple product table from indexed items.
	 *
	 * @param array  $items
	 * @param string $currency
	 * @param string $picked_json  (unused)
	 * @param string $order_status (unused)
	 * @param string $status_extra (unused)
	 * @return string HTML
	 */
	public static function render( $items, $currency, $picked_json = '', $order_status = 'processing', $status_extra = 'unused' ) {
		$items = is_array( $items ) ? $items : [];
		ob_start();
		?>
		<table style="width:100%; border-collapse:collapse;">
			<thead>
				<tr>
					<th style="text-align:left;">Qty</th>
					<th style="text-align:left;">Product</th>
					<th style="text-align:right;">Unit Price</th>
					<th style="text-align:right;">Total</th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $items ) ) : ?>
				<tr><td colspan="4"><em><?php esc_html_e('No items','aaa-oc'); ?></em></td></tr>
			<?php else :
				foreach ( $items as $it ) :
					$name  = isset($it['name']) ? (string)$it['name'] : '';
					$brand = isset($it['brand']) ? (string)$it['brand'] : '';
					$qty   = (int)   ( $it['quantity'] ?? 0 );
					$sub   = (float) ( $it['subtotal'] ?? 0 ); // line subtotal used for unit calc
					$tot   = (float) ( $it['total']    ?? 0 );

					$unit  = $qty > 0 ? $sub / $qty : 0.0;
					$fmtU  = function_exists('wc_price') ? wc_price( $unit, [ 'currency' => $currency ] ) : number_format( $unit, 2 );
					$fmtT  = function_exists('wc_price') ? wc_price( $tot,  [ 'currency' => $currency ] ) : number_format( $tot, 2 );
					?>
					<tr style="border-top:1px solid #ddd;">
						<td style="padding:4px;"><?php echo (int)$qty; ?></td>
						<td style="padding:4px;">
							<?php
								echo esc_html($name);
								if ( $brand !== '' ) {
									echo '<br><em style="font-size:.85em;color:#555;">' . esc_html($brand) . '</em>';
								}
							?>
						</td>
						<td style="padding:4px; text-align:right;"><?php echo wp_kses_post($fmtU); ?></td>
						<td style="padding:4px; text-align:right;"><strong><?php echo wp_kses_post($fmtT); ?></strong></td>
					</tr>
				<?php endforeach;
			endif; ?>
			</tbody>
		</table>
		<?php
		return (string) ob_get_clean();
	}
}

endif;

<?php
/**
 * View for the Purchase Order's Deliveries meta box
 *
 * @since 0.9.0
 *
 * @var \AtumPO\Models\POExtended $po
 * @var Delivery[]                $deliveries
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumStockDecimals;
use AtumPO\Deliveries\Models\Delivery;
use Atum\Inc\Helpers as AtumHelpers;

$ordered_grandtotal   = $po->get_ordered_items_total();
$delivered_grandtotal = $missing_grandtotal = 0;
?>
<div class="atum-meta-box po-deliveries<?php echo empty( $deliveries ) ? ' no-items' : '' ?>">
	<div class="deliveries">

		<div class="items-blocker unblocked">
			<?php esc_html_e( 'No Deliveries Added', ATUM_PO_TEXT_DOMAIN ); ?>
		</div>

		<?php foreach ( $deliveries as $delivery ) : ?>

			<?php
			$delivery_items = $delivery->get_items();

			/**
			 * Variables definition
			 *
			 * @var array $expected_qtys
			 * @var array $already_in_qtys
			 * @var array $delivered_qtys
			 * @var array $pending_qtys
			 * @var array $missing_qtys
			 * @var int[] $delivered_total
			 * @var int[] $already_in_total
			 * @var int[] $pending_total
			 * @var int[] $missing_total
			 */
			extract( Delivery::calculate_delivery_items_qtys( $delivery_items, $po, $delivery->get_id() ) );

			// Get only the delivery items.
			$expected_qtys   = $expected_qtys['delivery_item'] ?? [];
			$already_in_qtys = $already_in_qtys['delivery_item'] ?? [];
			$delivered_qtys  = $delivered_qtys['delivery_item'] ?? [];
			$pending_qtys    = $pending_qtys['delivery_item'] ?? [];

			$delivered_grandtotal += $delivered_total['delivery_item'] ?? 0;
			$missing_grandtotal   += array_sum( $missing_total ?? [] );
			?>

			<?php require 'delivery.php' ?>

		<?php endforeach; ?>

	</div>

	<div class="delivery__totals">
		<table id="delivery-totals" class="items-totals"
			data-decimal-separator="<?php echo esc_attr( $po->price_decimal_sep ); ?>"
			data-decimals-number="<?php echo esc_attr( AtumStockDecimals::get_stock_decimals() ); ?>"
		>
			<tr>
				<td><?php esc_html_e( 'Ordered', ATUM_PO_TEXT_DOMAIN ); ?></td>
				<td class="total ordered-total"><?php echo esc_html( wc_format_decimal( floatval( $ordered_grandtotal ), AtumStockDecimals::get_stock_decimals(), TRUE ) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Delivered', ATUM_PO_TEXT_DOMAIN ); ?></td>
				<td class="total delivered-total"><?php echo esc_html( wc_format_decimal( floatval( $delivered_grandtotal ), AtumStockDecimals::get_stock_decimals(), TRUE ) ); ?></td>
			</tr>
			<?php if ( $missing_grandtotal > 0 ) : ?>
				<tr class="missing-total__row">
					<td>
						<span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( 'Delivery items linked to missing PO items', ATUM_PO_TEXT_DOMAIN ) ?>"></span>
						<?php esc_html_e( 'Missing', ATUM_PO_TEXT_DOMAIN ); ?>
					</td>
					<td class="total missing-total"><?php echo esc_html( wc_format_decimal( floatval( $missing_grandtotal ), AtumStockDecimals::get_stock_decimals(), TRUE ) ); ?></td>
				</tr>
			<?php endif; ?>
			<tr class="grand-total">
				<td><?php esc_html_e( 'Pending', ATUM_PO_TEXT_DOMAIN ); ?></td>
				<td class="pending-total">
					<?php $pending_grandtotal = $ordered_grandtotal - $delivered_grandtotal ?>
					<span class="badge <?php echo esc_attr( 0 < $pending_grandtotal ? 'badge-danger' : 'badge-success' ) ?>">
						<?php echo esc_html( wc_format_decimal( floatval( $pending_grandtotal ), AtumStockDecimals::get_stock_decimals(), TRUE ) ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<small class="currency-label"><?php esc_html_e( 'Items', ATUM_PO_TEXT_DOMAIN ); ?></small>
				</td>
			</tr>
		</table>
	</div>

	<?php if ( $po->is_editable() ) : ?>

		<?php $disable_adding = 0 >= $pending_grandtotal || ! $po->status || 'auto-draft' === $po->status || $po->is_due() ?>
		<div class="atum-meta-box__footer<?php echo $disable_adding ? ' disable-adding' : '' ?>">

			<?php if ( $disable_adding && $po->is_due() ) : ?>
				<span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( "You can't add deliveries while the PO is in a due status", ATUM_PO_TEXT_DOMAIN ) ?>"></span>
			<?php endif; ?>

			<button type="button" class="btn btn-primary add-delivery"<?php disabled( $disable_adding ) ?>>
				<?php esc_html_e( 'Add Delivery', ATUM_PO_TEXT_DOMAIN ); ?>
			</button>
		</div>

		<?php AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/add-delivery-modal', compact( 'po', 'deliveries' ) ); ?>
		<?php AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/edit-delivery-modal', compact( 'po' ) ); ?>

	<?php else : ?>
		<div class="atum-meta-box__footer">
			<span class="description">
				<span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( 'To edit the PO deliveries change the PO status to any other that allows editing', ATUM_PO_TEXT_DOMAIN ) ?>"></span> <?php esc_html_e( 'These deliveries are no longer editable.', ATUM_PO_TEXT_DOMAIN ); ?>
			</span>
		</div>
	<?php endif; ?>
</div>

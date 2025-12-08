<?php
/**
 * View for the Add to PO modal's JS template
 *
 * @since 0.9.9
 *
 * @var AtumProductInterface[]|WC_Product[] $items
 * @var bool                                $allow_adding
 */

defined( 'ABSPATH' ) || die;

use Atum\Models\Interfaces\AtumProductInterface;
use Atum\Inc\Helpers as AtumHelpers;

$suppliers      = [];
$allow_adding   = $allow_adding ?? FALSE;
$excluded_items = array();

ob_start();

foreach ( $items as $product ) :

	if ( ! $product instanceof \WC_Product ) :
		do_action_ref_array( 'atum/purchase_orders_pro/add-to-po-modal/print_item', [ $product, &$suppliers ] ); // Pass the $suppliers by reference.
		continue;
	endif;

	require 'add-to-po-item.php';
	$excluded_items[] = $product->get_id();

endforeach;

$suppliers     = array_unique( $suppliers );
$num_suppliers = count( $suppliers );
$table_rows    = ob_get_clean();

?>
<div class="atum-modal-content">

	<div class="note">
		<?php esc_html_e( "Enter the items' quantities to create the new PO(s).", ATUM_PO_TEXT_DOMAIN ) ?><br>
	</div>

	<hr>

	<?php if ( $allow_adding ) : ?>

		<div class="add-to-po-modal__search">
			<select multiple="multiple" id="search-po-products" data-post-id=""
				data-placeholder="<?php esc_attr_e( 'Add products&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"
				data-action="atum_po_json_search_products" style="width:100%" data-nonce="<?php echo esc_attr( wp_create_nonce( 'search-products' ) ) ?>"
				data-exclude="<?php echo implode( ',', $excluded_items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
			></select>
		</div>

	<?php endif; ?>

	<form id="add-items-results">
		<fieldset>
			<table>
				<thead>
				<tr>
					<th class="add-item__name">
						<?php esc_html_e( 'Name', ATUM_PO_TEXT_DOMAIN ) ?>
					</th>
					<th class="add-item__supplier">
						<?php esc_html_e( 'Supplier', ATUM_PO_TEXT_DOMAIN ) ?>
					</th>
					<th class="add-item__stock">
						<?php esc_html_e( 'Stock', ATUM_PO_TEXT_DOMAIN ) ?>
					</th>
					<th class="add-item__backorders">
						<?php esc_html_e( 'Backorders', ATUM_PO_TEXT_DOMAIN ) ?>
					</th>
					<th class="add-item__qty">
						<?php esc_html_e( 'Qty to Add', ATUM_PO_TEXT_DOMAIN ) ?>
					</th>
					<th class="actions"></th>
				</tr>
				</thead>
				<tbody>
					<?php echo $table_rows; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</tbody>
			</table>

		</fieldset>

		<fieldset class="add-to-po-modal__actions"<?php echo ! count( $suppliers ) && $num_suppliers <= 1 ? ' style="display:none"' : '' ?>>

			<div class="multiple-pos-switch form-check form-switch"<?php echo $num_suppliers <= 1 ? ' style="display:none"' : '' ?>>
				<label for="multiple_pos" class="form-check-label">
					<?php esc_html_e( 'Multiple POs', ATUM_PO_TEXT_DOMAIN ) ?>
					<span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( 'Create multiple POs (one per supplier)', ATUM_PO_TEXT_DOMAIN ); ?>"></span>
				</label>
				<input type="checkbox" id="multiple_pos" name="multiple_pos" value="yes" checked="checked" class="form-check-input atum-settings-input"<?php disabled( $num_suppliers <= 1 ) ?>>
			</div>

		</fieldset>
	</form>

	<div<?php echo ! count( $suppliers ) ? ' style="display:none"' : '' ?>>
		<?php AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/add-supplier-items', compact( 'suppliers' ) ); ?>
	</div>

	<div class="add-to-po-modal__total">
		<strong><?php esc_html_e( 'Total items:', ATUM_PO_TEXT_DOMAIN ); ?> <span class="total-items">0</span></strong>
	</div>

</div>

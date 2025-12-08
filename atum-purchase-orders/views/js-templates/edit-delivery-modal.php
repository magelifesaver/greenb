<?php
/**
 * View for the Edit PO Delivery modal's JS template
 *
 * @since 0.9.3
 *
 * @var \AtumPO\Models\POExtended $po
 */

defined( 'ABSPATH' ) || die;

?>
<script type="text/template" id="edit-delivery-modal">
	<div class="atum-modal-content">

		<div class="note">
			<span class="delivery-name"></span><br>
		</div>

		<hr>

		<div class="po-modal__search">

			<h4><?php esc_html_e( 'Delivery Items', ATUM_PO_TEXT_DOMAIN ); ?></h4>

			<?php
			$po_items    = $po->get_items();
			$shown_items = 0;
			?>

			<?php if ( empty( $po_items ) ) : ?>

				<div class="alert alert-primary">
					<i class="atum-icon atmi-question-circle"></i>
					<?php esc_html_e( 'Please, first add items to the PO and save', ATUM_PO_TEXT_DOMAIN ); ?>
				</div>

			<?php else : ?>

				<div class="po-modal__search-input">
					<input type="text" id="search-delivery-products" placeholder="<?php esc_attr_e( 'Search for products&hellip;', ATUM_PO_TEXT_DOMAIN ); ?>"/>
				</div>

				<form id="edit-delivery-items-form">
					<table class="atum-items-table po-modal__items">
						<thead>
						<tr>
							<th colspan="2"><?php esc_attr_e( 'Item', ATUM_PO_TEXT_DOMAIN ); ?></th>
							<th class="center"><?php esc_attr_e( 'Expected', ATUM_PO_TEXT_DOMAIN ); ?></th>
							<th class="center"><?php esc_attr_e( 'Already In', ATUM_PO_TEXT_DOMAIN ); ?></th>
							<th class="center"><?php esc_attr_e( 'Delivered', ATUM_PO_TEXT_DOMAIN ); ?></th>
						</tr>
						</thead>
						<tbody>
							<tr class="loading-items">
								<td colspan="5">
									<div class="atum-loading"></div>
								</td>
							</tr>
						</tbody>
					</table>
				</form>

			<?php endif; ?>
		</div>

		<div class="po-modal__total">
			<strong><?php esc_html_e( 'Total items:', ATUM_PO_TEXT_DOMAIN ); ?> <span class="total-items">0</span></strong>
		</div>
	</div>
</script>

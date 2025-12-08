<?php
/**
 * View for the Add Fee / Add Shipping modal for purchase order items
 *
 * @since 0.9.10
 *
 * @var \AtumPO\Models\POExtended $atum_order
 */

defined( 'ABSPATH' ) || die;

?>
<script type="text/template" id="add-fee-modal">
	<div class="atum-modal-content">

		<div class="note"></div>

		<hr>

		<form>
			<div class="modal-form-group">
				<label for="name"><?php esc_html_e( 'Name', ATUM_PO_TEXT_DOMAIN ); ?></label>
				<input type="text" value="" name="name">
			</div>

			<div class="modal-form-group">
				<label><?php esc_html_e( 'Amount', ATUM_PO_TEXT_DOMAIN ); ?></label>

				<div class="input-group number-mask">

					<input type="number" step="1" min="0" autocomplete="off" value="" class="meta-value" name="meta-value-tax">

					<span class="input-group-append" title="<?php esc_attr_e( 'Click to switch behaviour', ATUM_PO_TEXT_DOMAIN ); ?>">
						<span class="input-group-text" data-value="percentage">%</span>
						<span class="input-group-text active" data-value="amount"><?php echo esc_attr( get_woocommerce_currency_symbol( $atum_order->currency ) ) ?></span>
						<input type="hidden" name="type" value="amount">
					</span>

				</div>

			</div>
		</form>

	</div>
</script>

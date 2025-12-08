<?php
/**
 * View for the PO top bar
 *
 * @since 0.9.3
 *
 * @var \AtumPO\Models\POExtended $atum_order
 * @var string                    $po_status
 * @var string                    $status_label
 * @var string                    $status_color
 * @var array                     $statuses
 * @var array                     $status_colors
 */

use Atum\Inc\Helpers as AtumHelpers;


defined( 'ABSPATH' ) || die;

$po_number = $atum_order->number ?: $atum_order->get_id();
?>
<div class="po-data-header">

	<div class="po-title__wrapper">

		<h2>
			<?php if ( str_contains( $_SERVER['REQUEST_URI'], 'post-new.php' ) ) : ?>
				<span><?php esc_html_e( 'New Purchase Order', ATUM_PO_TEXT_DOMAIN ) ?></span>
				<input type="hidden" name="number" value="<?php echo esc_attr( $po_number ); ?>">
			<?php else : ?>

				<span>
					<?php echo ! $atum_order->is_returning() ? esc_html__( 'Purchase Order', ATUM_PO_TEXT_DOMAIN ) : esc_html__( 'Returning PO', ATUM_PO_TEXT_DOMAIN ); ?>
				</span>

				<span class="po-number">

					<span class="po-number__view">
						<span class="po-number__view-sufix">#</span>
						<span class="po-number__view-number">
							<?php
							if ( $atum_order->is_returning() && $atum_order->related_po ) : ?>

								<a href="<?php echo esc_url( get_edit_post_link( $atum_order->related_po, '' ) ) ?>"
								   class="atum-tooltip" title="<?php esc_attr_e( 'Open original PO', ATUM_PO_TEXT_DOMAIN ) ?>"
								   target="_blank"
								>
									<?php echo esc_html( $po_number ) ?>
								</a>

							<?php else :
								echo esc_html( $po_number );
							endif; ?>
						</span>

						<?php if ( array_key_exists( $po_status, $statuses ) && ! $atum_order->is_cancelled() && ! $atum_order->is_returning() && 'trash' !== $po_status ) : ?>
							<a href="#" class="po-number__edit atum-tooltip" title="<?php esc_attr_e( 'Edit PO number', ATUM_PO_TEXT_DOMAIN ); ?>">
								<i class="atum-icon atmi-pencil"></i>
							</a>
						<?php endif; ?>
					</span>

					<span class="input-group number-mask po-number__input" style="display: none">

						<input type="text" name="number" value="<?php echo esc_attr( $po_number ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'po-edit-po-number-nonce' ) ) ?>">

						<span class="input-group-append">
							<span class="set-po-number input-group-text active atum-tooltip" title="<?php esc_attr_e( 'Set PO number', ATUM_PO_TEXT_DOMAIN ); ?>">
								<i class="atum-icon atmi-save"></i>
							</span>

							<?php if ( 'custom' === AtumHelpers::get_option( 'po_numbering_system', 'ids' ) ) : ?>
							<span class="generate-po-number input-group-text active atum-tooltip" title="<?php esc_attr_e( 'Auto-generate next PO number', ATUM_PO_TEXT_DOMAIN ); ?>">
								<i class="atum-icon atmi-text-format"></i>
							</span>
							<?php endif; ?>
						</span>
					</span>

				</span>
			<?php endif; ?>
		</h2>

		<?php do_action( 'atum/purchase_orders_pro/after_po_number', $atum_order ) ?>

		<?php require 'po-status-dropdown.php'; ?>

	</div>

	<?php require 'po-actions.php' ?>

</div>

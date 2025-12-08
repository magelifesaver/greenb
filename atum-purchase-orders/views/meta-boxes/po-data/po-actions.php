<?php
/**
 * View for the PO actions
 *
 * @since 0.8.4
 *
 * @var \AtumPO\Models\POExtended $atum_order
 * @var string                    $po_status
 * @var array                     $statuses
 */

defined( 'ABSPATH' ) || die;

use AtumPO\Inc\DuplicatePO;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders;

$status_exists        = array_key_exists( $po_status, $statuses );
$requires_requisition = AtumHelpers::get_option( 'po_required_requisition', 'no' );

?>
<div class="po-actions">

	<div class="po-actions__group">

		<div class="po-actions__group__title">
			<?php esc_html_e( 'Actions', ATUM_PO_TEXT_DOMAIN ); ?>
		</div>

		<div class="po-actions__group__buttons action-buttons">

			<?php $duplicate_link = DuplicatePO::get_duplicate_link( $atum_order->get_id() ) ?>
			<?php if ( $status_exists && $duplicate_link && ! $atum_order->is_returning() ) : ?>
				<button type="button" data-href="<?php echo esc_url( $duplicate_link ) ?>"
					class="btn btn-primary btn-sm clone atum-tooltip"
					title="<?php esc_attr_e( 'Clone', ATUM_PO_TEXT_DOMAIN ) ?>"
				>
					<i class="atum-icon atmi-duplicate"></i>
				</button>
			<?php endif; ?>

			<?php if ( ! $atum_order->is_cancelled() ) : ?>

				<?php /* TODO: ADD THE IMPORT BUTTON WHEN ATUM EXPORT PRO IS ACTIVE AND THIS FEATURE INCLUDED.
				<button type="button" class="btn btn-primary btn-sm import atum-tooltip" title="<?php esc_attr_e( 'Import', ATUM_PO_TEXT_DOMAIN ) ?>">
					<i class="atum-icon atmi-enter-down"></i>
				</button>
                */ ?>

				<?php /* TODO: WHAT THE CONVERT BUTTON DOES?
				<button type="button" class="btn btn-primary btn-sm convert atum-tooltip" title="<?php esc_attr_e( 'Convert', ATUM_PO_TEXT_DOMAIN ) ?>">
					<i class="atum-icon atmi-sync"></i>
				</button>
				*/ ?>
			<?php endif; ?>

			<?php if ( $status_exists && ! $atum_order->is_cancelled() && ! $atum_order->is_returning() ) : ?>
				<button type="button" class="btn btn-primary btn-sm merge atum-tooltip" title="<?php esc_attr_e( 'Merge', ATUM_PO_TEXT_DOMAIN ) ?>">
					<i class="atum-icon atmi-magic-wand"></i>
				</button>
			<?php endif; ?>

			<button type="button" class="btn btn-danger btn-sm delete atum-tooltip"
				title="<?php esc_attr_e( 'Archive', ATUM_PO_TEXT_DOMAIN ) ?>"
				data-href="<?php echo esc_url( get_delete_post_link( $atum_order->get_id() ) ) ?>"
			>
				<i class="atum-icon atmi-trash"></i>
			</button>

			<?php if ( ! $atum_order->is_cancelled() && ! $atum_order->is_returning() && ! $atum_order->is_due() ) : ?>
				<button type="button" class="btn btn-warning btn-sm create-returning atum-tooltip" title="<?php esc_attr_e( 'Create Returning PO', ATUM_PO_TEXT_DOMAIN ) ?>">
					<i class="atum-icon atmi-undo"></i>
				</button>
			<?php endif; ?>

			<?php if ( 'atum_returning' === $po_status ) : // Only when returning and not when it was already returned. ?>
				<button type="button" class="btn btn-warning btn-sm return-items">
					<?php esc_attr_e( 'Return Items', ATUM_PO_TEXT_DOMAIN ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! $atum_order->is_returning() && ! $atum_order->is_cancelled() && 'trash' !== $po_status ) : ?>
	<div class="po-actions__group">

		<div class="po-actions__group__title">
			<?php esc_html_e( 'Save', ATUM_PO_TEXT_DOMAIN ); ?>
		</div>

		<div class="po-actions__group__buttons save-buttons">

			<?php // Draft POs. ?>
			<?php if ( 'atum_pending' === $po_status ) : ?>
				<button type="button" class="btn btn-outline-primary save-draft" data-action-name="saveDraft" data-action-icon="atmi-save"
					data-action-label="<?php esc_attr_e( 'Save Draft', ATUM_PO_TEXT_DOMAIN ) ?>">
					<?php esc_html_e( 'Save Draft', ATUM_PO_TEXT_DOMAIN ) ?>
				</button>

				<button type="button" class="btn btn-primary place" data-action-name="place"  data-action-icon="atmi-enter-down"
					data-action-label="<?php esc_attr_e( 'Place', ATUM_PO_TEXT_DOMAIN ) ?>">
					<?php esc_html_e( 'Place', ATUM_PO_TEXT_DOMAIN ) ?>
				</button>
			<?php endif; ?>

			<?php // POs that allow updating. ?>
			<?php if ( ! in_array( $po_status, [ 'atum_pending', 'atum_approval', 'atum_cancelled' ] ) ) : ?>
				<button type="button" class="btn btn-primary update" data-action-name="update" data-action-icon="atmi-save"
					data-action-label="<?php esc_attr_e( 'Update', ATUM_PO_TEXT_DOMAIN ) ?>">
					<?php esc_html_e( 'Update', ATUM_PO_TEXT_DOMAIN ) ?>
				</button>
			<?php endif; ?>

			<?php // POs that requires requisition. ?>
			<?php if ( 'yes' === $requires_requisition && 'atum_new' === $po_status && $atum_order->requisitioner ) : ?>
				<button type="button" class="btn btn-primary request-approval" data-action-name="requestApproval" data-action-icon="atmi-users"
					data-action-label="<?php esc_attr_e( 'Request Approval', ATUM_PO_TEXT_DOMAIN ) ?>">
					<?php esc_html_e( 'Request Approval', ATUM_PO_TEXT_DOMAIN ) ?>
				</button>
			<?php endif; ?>

			<?php // POs submitted for approval. NOTE: we leave this even when the requisition requirement is disabled in case some PO was in this status before disabling it. ?>
			<?php if ( 'atum_approval' === $po_status ) : ?>

				<?php if ( get_current_user_id() === absint( $atum_order->requisitioner ) ) : ?>
					<button type="button" class="btn btn-warning approve" data-action-name="approve" data-action-icon="atmi-thumbs-up"
						data-action-label="<?php esc_attr_e( 'Approve', ATUM_PO_TEXT_DOMAIN ) ?>">
						<?php esc_html_e( 'Approve', ATUM_PO_TEXT_DOMAIN ) ?>
					</button>
				<?php else : ?>
					<button type="button" class="btn btn-secondary pending-approval" disabled data-action-name="pendingApproval" data-action-icon="atmi-time"
						data-action-label="<?php esc_attr_e( 'Pending Approval', ATUM_PO_TEXT_DOMAIN ) ?>">
						<?php esc_html_e( 'Pending Approval', ATUM_PO_TEXT_DOMAIN ) ?>
					</button>
				<?php endif; ?>

			<?php endif; ?>

			<?php // Received by Supplier POs. ?>
			<?php /*if ( 'atum_vendor_received' === $po_status ) : ?>
				<button type="button" class="btn btn-primary mark-onthewayin" data-action-name="markOnTheWayIn" data-action-icon="atmi-select"
					data-action-label="<?php esc_attr_e( "Mark as 'ON THE WAY IN'", ATUM_PO_TEXT_DOMAIN ) ?>">
					<?php esc_html_e( "Mark as 'ON THE WAY IN'", ATUM_PO_TEXT_DOMAIN ) ?>
				</button>
			<?php endif;*/ ?>

			<?php // "On the way in" POs. ?>
			<?php /*if ( 'atum_onthewayin' === $po_status ) : ?>
				<button type="button" class="btn btn-primary mark-received" data-action-name="markSupplierReceived" data-action-icon="atmi-select"
					data-action-label="<?php esc_attr_e( "Back to 'RECEIVED BY VENDOR'", ATUM_PO_TEXT_DOMAIN ) ?>">
					<?php esc_html_e( "Back to 'RECEIVED BY VENDOR'", ATUM_PO_TEXT_DOMAIN ) ?>
				</button>
			<?php endif;*/ ?>

		</div>

	</div>
	<?php endif; ?>

	<div class="po-actions__group">

		<div class="po-actions__group__title">
			<?php esc_html_e( 'Communication', ATUM_PO_TEXT_DOMAIN ); ?>
		</div>

		<div class="po-actions__group__buttons communication-buttons">
			<?php
			$disabled_tooltip = __( 'Please, finish editing the PO and press the PLACE button first', ATUM_PO_TEXT_DOMAIN );

			$comm_disabled = ! $status_exists || in_array( $po_status, [ 'atum_pending', 'atum_new', 'atum_approval' ] );

			if ( 'atum_new' === $po_status ) :

				if ( ! $atum_order->requisitioner || 'no' === $requires_requisition ) :
					$comm_disabled = FALSE;
				else :
					$disabled_tooltip = __( 'This PO must be approved by your requisitioner', ATUM_PO_TEXT_DOMAIN );
				endif;

			elseif ( 'atum_approval' === $po_status ) :
				$disabled_tooltip = __( 'Awaiting approval from your requisitioner', ATUM_PO_TEXT_DOMAIN );
			endif;
			?>

			<?php if ( $comm_disabled ) : ?>
			<div class="atum-tooltip" title="<?php echo esc_attr( $disabled_tooltip ) ?>">
			<?php endif; ?>

				<?php
				$email_button_disabled = $comm_disabled;
				$email_button_text     = empty( get_post_meta( $atum_order->get_id(), 'po_email_sent', TRUE ) ) ? __( 'Email', ATUM_PO_TEXT_DOMAIN ) : __( 'Emailed', ATUM_PO_TEXT_DOMAIN );

				if ( FALSE !== in_array( $po_status, [ 'atum_pending', 'atum_new', 'atum_approval', 'atum_approved' ] ) ) :
					$email_button_class = 'btn-success';
				elseif ( $atum_order->is_cancelled() ) :
					$email_button_class    = 'btn-default';
					$email_button_disabled = TRUE;
				else :
					$email_button_class = 'btn-success';
				endif;
				?>
				<button type="button" class="btn <?php echo esc_attr( $email_button_class ) ?> send-email"
					<?php disabled( $email_button_disabled ) ?>
					data-email-sent="<?php echo esc_attr( get_post_meta( $atum_order->get_id(), 'po_email_sent', TRUE ) ? 'yes' : 'no' ) ?>"
					data-action-name="sendEmail" data-action-icon="atmi-envelope"
					data-action-label="<?php esc_attr_e( 'Email', ATUM_PO_TEXT_DOMAIN ) ?>">
					<?php echo esc_html( $email_button_text ) ?>
				</button>

				<button type="button" data-href="<?php echo PurchaseOrders::get_pdf_generation_link( $atum_order->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
					class="btn btn-success print"<?php disabled( $comm_disabled ) ?>
					data-action-name="print" data-action-label="<?php esc_attr_e( 'Print', ATUM_PO_TEXT_DOMAIN ) ?>"
					data-action-icon="atmi-printer"
				>
					<?php esc_html_e( 'Print', ATUM_PO_TEXT_DOMAIN ) ?>
				</button>

			<?php if ( $comm_disabled ) : ?>
			</div>
			<?php endif; ?>

		</div>
	</div>

	<div class="po-actions__menu"><i class="show-actions atum-icon atmi-options" data-bs-placement="bottom"></i></div>

</div>

<?php AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/email-modal', compact( 'atum_order' ) ); ?>

<?php AtumHelpers::load_view( ATUM_PO_PATH . 'views/js-templates/merge-modal', compact( 'atum_order' ) );

<?php
/**
 * View for the PO status dropdown
 *
 * @since 0.9.13
 *
 * @var \AtumPO\Models\POExtended $atum_order
 * @var string                    $po_status
 * @var string                    $status_label
 * @var string                    $status_color
 * @var array                     $statuses
 * @var array                     $status_colors
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use AtumPO\Inc\Globals;
use AtumPO\Inc\Helpers;


$po_status_flow_restiction = Helpers::get_po_status_flow_restriction( $atum_order->get_id() );
$status_flow               = Globals::get_status_flow();
$current_status            = array_search( $status_label, $statuses );
$current_status_flow       = $status_flow[ array_search( $status_label, $statuses ) ] ?? [];
$required_requisition      = AtumHelpers::get_option( 'po_required_requisition', 'no' ) === 'yes';
$requisitioner_statuses    = Globals::get_requisitioner_statuses();
$custom_label              = NULL;

// In the case the requisitioner was disabled after this PO was created.
if ( ! $required_requisition && in_array( $current_status, [ 'atum_approval', 'atum_approved' ] ) ) :
	$current_status = 'atum_new';
	$atum_order->set_status( $current_status );
	$atum_order->save_meta();
endif;

global $pagenow;
?>
<div class="po-status">

	<select name="po_status" class="atum-enhanced-select">

		<?php // Unknown statuses.
		if ( ! $current_status ) : ?>
		<option value="<?php echo esc_attr( $po_status ) ?>" data-color="rgba(255,72,72,.5)" selected><?php echo esc_html( $status_label ) ?></option>
		<?php endif; ?>

		<?php foreach ( $statuses as $status => $label ) : ?>

			<?php
			// Exclude some statuses depending on the requisitioner status.
			if ( ! $required_requisition && in_array( $status, $requisitioner_statuses ) ) :
				continue;
			elseif ( $required_requisition && 'atum_new' === $status ) :
				/* translators: the new status label */
				$custom_label = sprintf( __( '%s (requisition)', ATUM_PO_TEXT_DOMAIN ), $label );
			endif;

			// If requisition is enabled and the PO is awaiting for approval, only the requisitioner can change the status.
			if ( $required_requisition && 'atum_approval' === $current_status && get_current_user_id() !== $atum_order->requisitioner ) :
				$current_status_flow = [];
			endif;

			// The returning POs have their own statuses and don't share them with regular POs. Only trash is shared.
			$is_returning_po    = $atum_order->is_returning();
			$returning_statuses = [ 'atum_returning', 'atum_returned' ];
			if (
				( $is_returning_po && ! in_array( $status, $returning_statuses ) ) ||
				( ! $is_returning_po && in_array( $status, $returning_statuses ) )
			) :
				if ( 'trash' !== $status ) continue;
			endif;
			?>

			<option value="<?php echo esc_attr( $status ); ?>"<?php selected( $label, $status_label ) ?>
				data-color="<?php echo esc_attr( $status_colors[ $status ] ?? 'white' ) ?>"
				<?php disabled( 'no' !== $po_status_flow_restiction && ! empty( $status_flow ) && $current_status && $status !== $current_status && ( empty( $current_status_flow ) || ! in_array( $status, $current_status_flow ) ) ) ?>
			>
				<?php
				echo esc_html( $custom_label ?? $label );
				$custom_label = NULL;
				?>
			</option>

		<?php endforeach; ?>
	</select>

</div>

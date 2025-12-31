<?php
/**
 * File: /includes/payment/board/partials/bottom/right/dispatch/dispatch-notes.php
 * Purpose: Dispatch/Admin Notes block (read list + add new) — uses Payment module fields.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Expect: $order_id in scope */
$payment_data = AAA_OC_Payment_Fields::get_payment_fields( $order_id );
$notes        = trim( $payment_data['payment_admin_notes'] ?? '' );
?>

<!-- Dispatch Notes (read-only list) -->
<?php if ( $notes ) : ?>
  <div class="aaa-admin-notes"
       style="margin-top:.1rem; padding:0.75rem; border:1px solid #ddd; background:#f9f9f9;">
    <p class="aaa-admin-notes__heading" style="margin:0 0 .5em; font-size:1em; color:#333;">Dispatch Notes</p>
    <div class="aaa-admin-notes__content" style="max-height:100px; overflow-y:auto; padding-right:0.5em;">
      <?php echo nl2br( esc_html( $notes ) ); ?>
    </div>
  </div>
<?php endif; ?>

<!-- Add New Admin Note -->
<div class="aaa-admin-note-entry" style="margin-top:1rem;">
  <textarea name="new_admin_note" class="widefat" rows="3" style="width:100%;" placeholder="Add a new note…"></textarea>
  <button class="button-modern button-small aaa-add-admin-note"
          data-order-id="<?php echo esc_attr( $order_id ); ?>"
          style="margin-top:.5em;">Add Note</button>
</div>

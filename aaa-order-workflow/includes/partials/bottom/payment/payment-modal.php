<?php
/**
 * File: /plugins/aaa-order-workflow/includes/partials/bottom/payment/payment-modal.php
 * Purpose: Right column (Notes/Driver/Delivery) for the expanded card on the Workflow Board.
 * Notes:
 *  - Delivery inputs are rendered as <input type="date"> and <input type="time"> (HH:mm).
 *  - Values are derived from canonical metas or legacy metas and normalized to HH:mm here.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Helpers to normalize existing meta to HH:mm for input[type=time].
$__aaa_oc_to_24 = function( string $t12, DateTimeZone $tz ): string {
    $t12 = strtolower(trim($t12));
    if ($t12 === '') return '';
    $dt = DateTime::createFromFormat('g:i a', $t12, $tz);
    return ($dt instanceof DateTime) ? $dt->format('H:i') : '';
};

$__aaa_oc_parse_range_to_h24 = function( string $range, DateTimeZone $tz ) use ($__aaa_oc_to_24): array {
    $range = trim((string)$range);
    if ($range === '') return ['', ''];

    // Handle shapes:
    // "From 3:00 pm - To 4:05 pm"
    // "From 3:00 pm to 4:05 pm"
    // "3:00 pm|From 3:00 pm - To 4:05 pm"
    // "3:00 pm"
    if (strpos($range, '|') !== false) {
        $parts = explode('|', $range, 2);
        $range = trim($parts[1]);
    }

    $low = strtolower($range);
    $from12 = '';
    $to12   = '';

    if (preg_match('/from\s+([0-9:\s]+[ap]m)\s*(?:-|–|to)\s*(?:to\s+)?([0-9:\s]+[ap]m)/i', $low, $m)) {
        $from12 = trim($m[1]);
        $to12   = trim($m[2]);
    } elseif (preg_match('/^([0-9]{1,2}:[0-9]{2}\s*[ap]m)$/i', $low, $m)) {
        $from12 = trim($m[1]);
    }

    return [ $__aaa_oc_to_24($from12, $tz), $__aaa_oc_to_24($to12, $tz) ];
};

$order        = wc_get_order( $order_id );
$tz           = wp_timezone();

// Date (prefer canonical -> legacy -> timestamp)
$dateYmd = (string) $order->get_meta('delivery_date_formatted');
if ($dateYmd === '') {
    $ts = (int) $order->get_meta('delivery_date');
    $dateYmd = $ts ? gmdate('Y-m-d', $ts) : '';
}

// Time range → HH:mm
$timeRange = (string) $order->get_meta('delivery_time_range');
list($fromH24, $toH24) = $__aaa_oc_parse_range_to_h24($timeRange, $tz);

// Driver dropdown data
$options = [];
$drivers = get_users([
    'role'    => 'driver',
    'orderby' => 'display_name',
    'order'   => 'ASC',
]);
foreach ($drivers as $driver) {
    $options[$driver->ID] = $driver->display_name;
}
$selected_driver = get_post_meta( $order_id, 'lddfw_driverid', true );
?>

<!-- RIGHT COLUMN (Payment + Tools + Admin Notes) -->
<div class="aaa-payment-wrapper expanded-only"
     style="flex:1; border-left:1px solid #ccc; padding:0.5rem;"
     data-order-id="<?php echo esc_attr( $order_id ); ?>"
>

  <!-- Dispatch Notes (read-only list) -->
  <?php
  $payment_data = AAA_OC_Payment_Fields::get_payment_fields( $order_id );
  $notes        = trim( $payment_data['payment_admin_notes'] ?? '' );
  if ( $notes ) : ?>
	  <div class="aaa-admin-notes"
	       style="margin-top:.1rem; padding:0.75rem; border:1px solid #ddd; background:#f9f9f9;"
	  >
	    <p class="aaa-admin-notes__heading"
	        style="margin:0 0 .5em; font-size:1em; color:#333;"
	    >Dispatch Notes</p>
	    <div class="aaa-admin-notes__content"
	         style="max-height:100px; overflow-y:auto; padding-right:0.5em;"
	    >
	      <?php echo nl2br( esc_html( $notes ) ); ?>
	    </div>
	  </div>
	<?php endif; ?>

	    <!-- Add New Admin Note -->
	    <div class="aaa-admin-note-entry" style="margin-top:1rem;">
	        <textarea
	            name="new_admin_note"
	            class="widefat"
	            rows="3"
	            style="width:100%;"
	            placeholder="Add a new note…"
	        ></textarea>
	        <button
	            class="button-modern button-small aaa-add-admin-note"
	            data-order-id="<?php echo esc_attr( $order_id ); ?>"
	            style="margin-top:.5em;"
	        >Add Note</button>
	    </div>
			<!-- Driver Selection -->
			<?php
			$options = [];
			$drivers = get_users([
			    'role'    => 'driver',
			    'orderby' => 'display_name',
			    'order'   => 'ASC',
			    'meta_query' => [
			        [
			            'key'     => 'lddfw_driver_availability',
			            'value'   => '1',
			            'compare' => '=',
			        ],
			    ],
			]);

			$options = [];
			foreach ($drivers as $driver) {
			    $options[ $driver->ID ] = $driver->display_name;
			}
			$selected_driver = get_post_meta( $order_id, 'lddfw_driverid', true );

			?>
  <div class="aaa-driver-selection" style="margin-top:1rem;">
    <div style="display:flex; gap:1rem; align-items:flex-start;">

      <!-- LEFT: Driver -->
      <div style="flex:1 1 0;border:1px solid #ccc;border-radius:4px;padding:10px;background:#f9f9f9;min-width:0;min-height:163px;">
        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Driver</label>
        <select name="driver_id"
                class="aaa-driver-dropdown"
                style="width:100%; padding:6px; font-size:14px;"
                data-order-id="<?php echo esc_attr( $order_id ); ?>">
          <option value="">— Select driver —</option>
          <?php foreach ( $options as $id => $label ) : ?>
            <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $selected_driver, $id ); ?>>
              <?php echo esc_html( $label ); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="button-modern button-small aaa-save-driver"
                data-order-id="<?php echo esc_attr( $order_id ); ?>"
                style="margin-top:.5rem;">Save Driver</button>
      </div>

      <!-- RIGHT: Delivery (date + from/to) -->
      <div style="flex:1 1 0;border:1px solid #ccc;border-radius:4px;padding:10px;background:#f9f9f9;min-width:0;">
        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Delivery:</label>

        <small style="display:block; color:#555; margin-bottom:2px;">Delivery Date</small>
        <input type="date"
               name="aaa_delivery_date"
               class="aaa-delivery-date"
               value="<?php echo esc_attr( $dateYmd ); ?>"
               style="width:100%; padding:6px; margin-bottom:6px;">

        <small style="display:block; color:#555; margin-bottom:2px;">From</small>
        <input type="time"
               name="aaa_delivery_from"
               class="aaa-delivery-from"
               value="<?php echo esc_attr( $fromH24 ); ?>"
               style="width:100%; padding:6px; margin-bottom:6px;">

        <small style="display:block; color:#555; margin-bottom:2px;">To</small>
        <input type="time"
               name="aaa_delivery_to"
               class="aaa-delivery-to"
               value="<?php echo esc_attr( $toH24 ); ?>"
               style="width:100%; padding:6px;">

        <button class="button-modern button-small aaa-save-delivery"
                data-order-id="<?php echo esc_attr( $order_id ); ?>"
                style="margin-top:.5rem;">Save Delivery</button>
      </div>

    </div>
  </div>

  <!-- Print buttons -->
    <div class="aaa-oc-print-buttons"
         style="margin-top: 15px; padding:0.5rem; line-height: normal;"
    >
    <div>
      <?php
      if ( class_exists( 'AAA_OC_Printing' ) ) {
          echo AAA_OC_Printing::render_print_buttons( $order_id );
      }
      ?>
    </div>
  </div>

</div> <!-- closes .aaa-payment-wrapper -->
</div>
</div>
</div>

<!-- Payment Modal (outside layout structure) -->
<div class="aaa-payment-modal" 
     id="aaa-payment-modal-<?php echo esc_attr( $order_id ); ?>"
>
  <div class="aaa-payment-modal-inner">
    <button class="close-payment-modal">&times;</button>
    <div class="aaa-payment-modal-content">
      <?php
            include AAA_OC_PLUGIN_DIR
                . 'includes/partials/bottom/payment/modal/form/payment-form.php';
      ?>
    </div>
  </div>
</div>

/**
 * File: /assets/js/board-save-driver.js
 * Handles saving driver selection + delivery date/slot from the expanded order card.
 */
jQuery(document).ready(function ($) {

  // ---------------- Save Driver ----------------
    $(document).on('click', '.aaa-save-driver', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const $wrapper = $btn.closest('.aaa-payment-wrapper');
        const orderId = $btn.data('order-id');
        const driverId = $wrapper.find('select[name="driver_id"]').val();

        if (!orderId || !driverId) {
            alert('Please select a driver before saving.');
            return;
        }

        $btn.prop('disabled', true).text('Saving...');

        $.post(AAA_OC_Payment.ajaxUrl, {
            action: 'aaa_oc_save_driver',
            order_id: orderId,
            driver_id: driverId,
            nonce: AAA_OC_Payment.nonce || ''
        }, function (response) {
            $btn.prop('disabled', false).text('Save Driver');

            if (response.success) {
                alert('Driver saved successfully.');
            } else {
                alert('Error: ' + (response.data?.message || 'Unknown error'));
            }
        }).fail(function (xhr, status, error) {
            $btn.prop('disabled', false).text('Save Driver');
            alert('AJAX request failed: ' + error);
        });
    });

// ---------------- Save Delivery ----------------
$(document).on('click', '.aaa-save-delivery', function (e) {
  e.preventDefault();

  const $btn  = $(this);
  const $wrap = $btn.closest('.aaa-payment-wrapper');
  const orderId = $btn.data('order-id');

  // Get values from inputs
  const dateYmd = $wrap.find('input[name="aaa_delivery_date"]').val() || '';
  const fromVal = $wrap.find('input[name="aaa_delivery_from"]').val() || '';
  const toVal   = $wrap.find('input[name="aaa_delivery_to"]').val() || '';

  if (!orderId) {
    alert('Missing order id.');
    return;
  }
  if (!dateYmd || !fromVal || !toVal) {
    alert('Please set a delivery date and both From/To times.');
    return;
  }

  $btn.prop('disabled', true).text('Saving...');

  $.post(AAA_OC_Payment.ajaxUrl, {
    action: 'aaa_oc_save_delivery',
    order_id: orderId,
    date_ymd: dateYmd,
    from_val: fromVal,
    to_val: toVal,
    nonce: AAA_OC_Payment.nonce || ''
  })
  .done(function (response) {
    $btn.prop('disabled', false).text('Save Delivery');
    if (response.success) {
      alert('Delivery saved successfully.');
    } else {
      alert('Error: ' + (response.data?.message || 'Unknown error'));
    }
  })
  .fail(function (xhr, status, error) {
    $btn.prop('disabled', false).text('Save Delivery');
    alert('AJAX request failed: ' + error);
  });
});

});

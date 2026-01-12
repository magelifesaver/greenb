/**
 * File: /assets/js/board-save-driver.js
 * Handles saving driver selection + delivery date/slot from the expanded order card.
 */
jQuery(document).ready(function ($) {

  function refreshBoard() {
    $.post(AAA_OC_Payment.ajaxUrl, {
      action:      'aaa_oc_get_latest_orders',
      _ajax_nonce: AAA_OC_Payment.nonce,
      sortMode:    window.sortMode || 'published'
    }, function (res) {
      if (res && res.success) {
        $('#aaa-oc-board-columns').html(res.data.columns_html);
      } else {
        console.warn('[DRIVER][REFRESH] Failed:', res && res.data ? res.data : res);
      }
    }).fail(function (err) {
      console.error('[DRIVER][REFRESH] AJAX error:', err);
    });
  }

  function getCurrentStatusForOrder(orderId) {
    const $card =
      $('.aaa-oc-order-card[data-order-id="' + orderId + '"]').first()
      .add($('.aaa-oc-card[data-order-id="' + orderId + '"]').first());

    if ($card.length) {
      const ds = $card.data('status') || $card.attr('data-status') || '';
      return (ds || '').toString();
    }

    const $modalFields = $('#aaa-payment-modal-' + orderId).find('.aaa-payment-fields');
    if ($modalFields.length) {
      const s = $modalFields.data('current-status') || '';
      return (s || '').toString();
    }

    return '';
  }

  function isPackedAndReady(statusSlug) {
    return ['lkd-packed-ready', 'packed-ready', 'packed-and-ready'].includes((statusSlug || '').toString());
  }

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

    const originalLabel = $btn.text();
    $btn.prop('disabled', true).text('Saving...');

    $.post(AAA_OC_Payment.ajaxUrl, {
      action:    'aaa_oc_save_driver',
      order_id:  orderId,
      driver_id: driverId,
      nonce:     AAA_OC_Payment.nonce || ''
    }, function (response) {

      if (!response || !response.success) {
        $btn.prop('disabled', false).text(originalLabel);
        var msg = (response && response.data && response.data.message) ? response.data.message : 'Unknown error';
        alert('Error: ' + msg);
        return;
      }

      // Success UI (no dialog)
      $btn.text('Driver Saved');

      const currentStatus = getCurrentStatusForOrder(orderId);
      refreshBoard();

      if (isPackedAndReady(currentStatus) && typeof aaaOcChangeOrderStatus === 'function') {
        const ok = confirm('Driver saved. Move this order to Out for Delivery now?');
        if (ok) {
          aaaOcChangeOrderStatus(orderId, 'out-for-delivery');
          setTimeout(refreshBoard, 600);
        }
      }

      // Re-enable after a moment so it can be changed later if needed.
      setTimeout(function () {
        $btn.prop('disabled', false).text(originalLabel);
      }, 1200);

    }).fail(function (xhr, status, error) {
      $btn.prop('disabled', false).text(originalLabel);
      alert('AJAX request failed: ' + error);
    });
  });

  // ---------------- Save Delivery ----------------
  $(document).on('click', '.aaa-save-delivery', function (e) {
    e.preventDefault();

    const $btn  = $(this);
    const $wrap = $btn.closest('.aaa-payment-wrapper');
    const orderId = $btn.data('order-id');

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

    const originalLabel = $btn.text();
    $btn.prop('disabled', true).text('Saving...');

    $.post(AAA_OC_Payment.ajaxUrl, {
      action:   'aaa_oc_save_delivery',
      order_id: orderId,
      date_ymd: dateYmd,
      from_val: fromVal,
      to_val:   toVal,
      nonce:    AAA_OC_Payment.nonce || ''
    })
    .done(function (response) {
      if (response && response.success) {
        $btn.text('Delivery Saved');
        refreshBoard();
        setTimeout(function () {
          $btn.prop('disabled', false).text(originalLabel);
        }, 1200);
      } else {
        $btn.prop('disabled', false).text(originalLabel);
        var msg = (response && response.data && response.data.message) ? response.data.message : 'Unknown error';
        alert('Error: ' + msg);
      }
    })
    .fail(function (xhr, status, error) {
      $btn.prop('disabled', false).text(originalLabel);
      alert('AJAX request failed: ' + error);
    });
  });

});

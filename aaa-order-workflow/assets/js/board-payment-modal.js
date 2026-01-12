/**
 * File: /assets/js/board-payment-modal.js
 * Purpose: Open/close payment modal with detailed logging, safe reset (no [checked]/[selected] restore),
 *          and required-field highlighting based on blocker banner flags.
 */
jQuery(document).ready(function ($) {

    function stashOriginals($fields) {
        // Numbers + readonly
        $fields.find('input[type="number"], input[readonly]').each(function () {
            $(this).attr('data-orig', $(this).val() || '');
        });

        // Selects
        $fields.find('select').each(function () {
            $(this).attr('data-orig', $(this).val() || '');
        });

        // Checkboxes
        $fields.find('input[type="checkbox"]').each(function () {
            $(this).attr('data-orig', $(this).is(':checked') ? '1' : '0');
        });
    }

    function restoreOriginals($fields) {
        $fields.find('input[type="number"], input[readonly]').each(function () {
            const orig = $(this).attr('data-orig');
            if (orig !== undefined) {
                $(this).val(orig);
            }
        });

        $fields.find('select').each(function () {
            const orig = $(this).attr('data-orig');
            if (orig !== undefined) {
                $(this).val(orig);
            }
        });

        $fields.find('input[type="checkbox"]').each(function () {
            const orig = $(this).attr('data-orig');
            if (orig !== undefined) {
                $(this).prop('checked', orig === '1');
            }
        });

        // Recalculate after reset
        if (typeof recalcPaymentTotals === 'function') {
            recalcPaymentTotals($fields);
        }
    }

    function applyRequiredHighlights($modal, orderId) {
        // Always clear old highlights first
        $modal.find('.aaa-oc-required-border').removeClass('aaa-oc-required-border');

        // Banner could be inside modal OR in expanded card. Prefer modal, fallback to page.
        let $banner = $modal.find('.aaa-oc-blocker-banner');
        if (!$banner.length) {
            $banner = $('.aaa-oc-blocker-banner[data-order-id="' + orderId + '"]');
        }
        if (!$banner.length) {
            return;
        }

        const needDriver   = ($banner.data('require-driver') == 1);
        const needDelivery = ($banner.data('require-delivery') == 1);
        const needPayment  = ($banner.data('require-payment') == 1);
        const needFulfill  = ($banner.data('require-fulfillment') == 1);

        // Driver field
        if (needDriver) {
            $modal.find('select[name="driver_id"]').addClass('aaa-oc-required-border');
        }

        // Delivery fields
        if (needDelivery) {
            $modal.find('input[name="aaa_delivery_date"], input[name="aaa_delivery_from"], input[name="aaa_delivery_to"]')
                .addClass('aaa-oc-required-border');
        }

        // Payment fields (minimal)
        if (needPayment) {
            $modal.find('select[name="aaa_oc_payment_status"], input[name="envelope_outstanding"]')
                .addClass('aaa-oc-required-border');
        }

        // Fulfillment highlight (best effort: highlight product table area if present in modal)
        if (needFulfill) {
            $modal.find('.aaa-oc-items-table, .aaa-oc-order-items, .aaa-oc-products-table')
                .first()
                .addClass('aaa-oc-required-border');
        }
    }

    // Expose helper so save JS can call it after successful save (prevents checkbox revert on close)
    window.aaaOcPaymentModalSyncOriginals = function (orderId) {
        const $modal = $('#aaa-payment-modal-' + orderId);
        const $fields = $modal.find('.aaa-payment-fields');
        if ($fields.length) {
            stashOriginals($fields);
            console.log('[MODAL] Synced originals for order', orderId);
        }
    };

    // Open the modal when the button is clicked
    $(document).on('click', '.open-payment-modal', function (e) {
        e.preventDefault();

        const orderId = $(this).data('order-id');
        console.log('[MODAL] Button clicked. Order ID:', orderId);

        const $modal = $('#aaa-payment-modal-' + orderId);
        if ($modal.length) {
            console.log('[MODAL] Found modal for order ID:', orderId);

            $modal.fadeIn(200, function () {
                const $fields = $modal.find('.aaa-payment-fields');

                // Store originals based on current values (NOT attributes)
                stashOriginals($fields);

                // Apply required-field highlights (if any)
                applyRequiredHighlights($modal, orderId);

                // Log initial field values (kept from your current file)
                console.log('[MODAL] Initial payment data for order', orderId, {
                    orderTotal:      $fields.find('input[name="aaa_oc_order_total"]').val(),
                    cash:            $fields.find('input[name="aaa_oc_cash_amount"]').val(),
                    zelle:           $fields.find('input[name="aaa_oc_zelle_amount"]').val(),
                    venmo:           $fields.find('input[name="aaa_oc_venmo_amount"]').val(),
                    applePay:        $fields.find('input[name="aaa_oc_applepay_amount"]').val(),
                    creditCard:      $fields.find('input[name="aaa_oc_creditcard_amount"]').val(),
                    cashApp:         $fields.find('input[name="aaa_oc_cashapp_amount"]').val(),
                    epaymentTotal:   $fields.find('input[name="aaa_oc_epayment_total"]').val(),
                    webTip:          $fields.find('input[name="aaa_oc_tip_total"]').val(),
                    payrecTotal:     $fields.find('input[name="aaa_oc_payrec_total"]').val(),
                    orderBalance:    $fields.find('input[name="aaa_oc_order_balance"]').val(),
                    epaymentTip:     $fields.find('input[name="epayment_tip"]').val(),
                    totalOrderTip:   $fields.find('input[name="total_order_tip"]').val(),
                    paymentStatus:   $fields.find('select[name="aaa_oc_payment_status"]').val(),
                    driverId:        $fields.find('select[name="driver_id"]').val(),
                    cleared:         $fields.find('input[name="cleared"]').is(':checked'),
                    envelope_outstanding: $fields.find('input[name="envelope_outstanding"]').is(':checked')
                });
            });

        } else {
            console.warn('[MODAL] Modal not found for order ID:', orderId);
        }
    });

    // Close the modal when the close button is clicked
    $(document).on('click', '.close-payment-modal', function (e) {
        e.preventDefault();

        const $modal  = $(this).closest('.aaa-payment-modal');
        const $fields = $modal.find('.aaa-payment-fields');
        const orderId = $fields.data('order-id') || '';

        console.log('[MODAL] Restoring payment fields for order ID:', orderId);

        // Restore to original snapshot (NOT from HTML attributes)
        restoreOriginals($fields);

        // Clear highlights when closing
        $modal.find('.aaa-oc-required-border').removeClass('aaa-oc-required-border');

        $modal.hide();
    });

});

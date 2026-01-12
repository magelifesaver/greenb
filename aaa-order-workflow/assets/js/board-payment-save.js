// Filename: board-payment-save.js
// Version: 1.0.7
// Last updated: 2026-01-11
// Purpose: Save payment, enforce envelope/paid rules, optional status jump (Delivered/Complete).

jQuery(document).ready(function($) {
    "use strict";

    function getTargetStatusFromButton($btn) {
        if ($btn.hasClass('save-payment-delivered-button')) {
            return 'lkd-delivered';
        }
        if ($btn.hasClass('save-payment-complete-button')) {
            return 'completed';
        }
        return '';
    }

    function getButtonLabel($btn) {
        return $btn.hasClass('save-payment-button') ? 'Save Payment' :
               ($btn.hasClass('save-payment-delivered-button') ? 'Save + Mark Delivered' : 'Save + Complete');
    }

    function refreshBoard() {
        $.post(AAA_OC_Payment.ajaxUrl, {
            action:      'aaa_oc_get_latest_orders',
            _ajax_nonce: AAA_OC_Payment.nonce,
            sortMode:    window.sortMode || 'published'
        }, function(res) {
            if (res && res.success) {
                $('#aaa-oc-board-columns').html(res.data.columns_html);
            } else {
                console.warn('[WFPAY][REFRESH] Failed to refresh columns:', res && res.data ? res.data : res);
            }
        }).fail(function(err) {
            console.error('[WFPAY][REFRESH] AJAX error during board refresh:', err);
        });
    }

    function closePaymentModal() {
        $('.aaa-payment-modal').hide();
        if (typeof aaaOcCloseModal === 'function') {
            aaaOcCloseModal();
        }
    }

    // Unified handler for all save buttons
    $(document).on('click', '.save-payment-button, .save-payment-delivered-button, .save-payment-complete-button', function(e) {
        e.preventDefault();

        const $btn          = $(this);
        const $container    = $btn.closest('.aaa-payment-fields');
        const orderId       = $btn.data('order-id');
        const currentStatus = $container.data('current-status') || '';
        const btnLabel      = getButtonLabel($btn);

        if (!orderId) {
            alert('Missing Order ID');
            return;
        }

        const targetStatus = getTargetStatusFromButton($btn);

        // === Gather Input Field Values =========================
        const cash        = parseFloat($container.find('input[name="aaa_oc_cash_amount"]').val())        || 0;
        const zelle       = parseFloat($container.find('input[name="aaa_oc_zelle_amount"]').val())       || 0;
        const venmo       = parseFloat($container.find('input[name="aaa_oc_venmo_amount"]').val())       || 0;
        const applepay    = parseFloat($container.find('input[name="aaa_oc_applepay_amount"]').val())    || 0;
        const creditcard  = parseFloat($container.find('input[name="aaa_oc_creditcard_amount"]').val())  || 0;
        const cashapp     = parseFloat($container.find('input[name="aaa_oc_cashapp_amount"]').val())     || 0;
        const orderTotal  = parseFloat($container.find('input[name="aaa_oc_order_total"]').val())        || 0;
        const originalTip = parseFloat($container.find('input[name="aaa_oc_tip_total"]').val())          || 0;

        // === Compute Totals & Derived Fields ===================
        const epaymentTotal  = zelle + venmo + applepay + creditcard + cashapp;
        const payrecTotal    = cash + epaymentTotal;
        const epaymentTip    = Math.max(0, epaymentTotal - orderTotal);
        const totalOrderTip  = epaymentTip + originalTip;
        const balance        = Math.max(0, orderTotal - payrecTotal);

        // IMPORTANT: paid means fully covered. Partial never counts as paid.
        const status = payrecTotal === 0 ? 'unpaid' : (balance <= 0.01 ? 'paid' : 'partial');

        // === Enforce envelope rules ============================
        // If paid -> envelope MUST be off.
        // If not paid -> envelope MUST be on.
        let envelopeOutstanding = (status === 'paid') ? 0 : 1;
        $container.find('input[name="envelope_outstanding"]').prop('checked', envelopeOutstanding === 1);

        // NOTE: cleared should NEVER be auto-toggled by JS.
        const cleared = $container.find('input[name="cleared"]').is(':checked') ? 1 : 0;

        // === Sync Hidden Input Fields ==========================
        $container.find('input[name="aaa_oc_epayment_total"]').val(epaymentTotal.toFixed(2));
        $container.find('input[name="aaa_oc_payrec_total"]').val(payrecTotal.toFixed(2));
        $container.find('input[name="aaa_oc_order_balance"]').val(balance.toFixed(2));
        $container.find('input[name="epayment_tip"]').val(epaymentTip.toFixed(2));
        $container.find('input[name="total_order_tip"]').val(totalOrderTip.toFixed(2));
        $container.find('select[name="aaa_oc_payment_status"]').val(status);

        const data = {
            action:                    'aaa_oc_update_payment_index',
            order_id:                  orderId,
            aaa_oc_cash_amount:        cash.toFixed(2),
            aaa_oc_zelle_amount:       zelle.toFixed(2),
            aaa_oc_venmo_amount:       venmo.toFixed(2),
            aaa_oc_applepay_amount:    applepay.toFixed(2),
            aaa_oc_creditcard_amount:  creditcard.toFixed(2),
            aaa_oc_cashapp_amount:     cashapp.toFixed(2),
            aaa_oc_epayment_total:     epaymentTotal.toFixed(2),
            aaa_oc_tip_total:          originalTip.toFixed(2),
            aaa_oc_payrec_total:       payrecTotal.toFixed(2),
            aaa_oc_order_balance:      balance.toFixed(2),
            aaa_oc_payment_status:     status,
            epayment_tip:              epaymentTip.toFixed(2),
            total_order_tip:           totalOrderTip.toFixed(2),
            cleared:                   cleared,
            envelope_outstanding:      envelopeOutstanding,
            payment_admin_notes:       $container.find('textarea[name="payment_admin_notes"]').val(),
            processing_fee:            $container.find('input[name="processing_fee"]').val()
        };

        console.groupCollapsed(`[WFPAY][SAVE] Order #${orderId} (${currentStatus}) -> payment_status=${status}, envelope=${envelopeOutstanding}, cleared=${cleared}`);
        console.table(data);
        console.log('[WFPAY][SAVE] targetStatus:', targetStatus || '(none)');
        console.groupEnd();

        $btn.prop('disabled', true).text('Saving...');

        $.post(AAA_OC_Payment.ajaxUrl, data, function(response) {
            $btn.prop('disabled', false).text(btnLabel);

            if (!response || !response.success) {
                console.warn('[WFPAY][SAVE] ❌ Error saving payment:', response && response.data ? response.data : response);
                alert('Error saving payment: ' + (response && response.data ? response.data : 'Unknown error'));
                return;
            }

            console.log('[WFPAY][SAVE] ✅ Payment saved successfully');

            // Update "original" values BEFORE closing modal so checkboxes don't revert.
            if (typeof window.aaaOcPaymentModalSyncOriginals === 'function') {
                window.aaaOcPaymentModalSyncOriginals(orderId);
            }

            // Optional status jump after save
            if (targetStatus && typeof aaaOcChangeOrderStatus === 'function') {
                console.log('[WFPAY][SAVE] Attempting status change to:', targetStatus);
                aaaOcChangeOrderStatus(orderId, targetStatus);
            }

            closePaymentModal();
            refreshBoard();

        }).fail(function(xhr, statusText, error) {
            $btn.prop('disabled', false).text(btnLabel);
            console.error('[WFPAY][SAVE] ❌ AJAX failure:', statusText, error);
            alert('Failed to send payment data.');
        });
    });
});

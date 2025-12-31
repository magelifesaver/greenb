// Filename: board-payment-save.js
// Version: 1.0.5
// Last updated: 2025-06-29
// Purpose: Gathers all visible payment fields, computes tips + balance,
//          and triggers AJAX save to persist values to the backend.
// Enhancements: Adds verbose debug logging and dev-commented data flow steps.

jQuery(document).ready(function($) {
    "use strict";

    // Main Save Button Handler
    $(document).on('click', '.save-payment-button', function(e) {
        e.preventDefault();

        const $btn       = $(this);
        const $container = $btn.closest('.aaa-payment-fields');
        const orderId    = $btn.data('order-id');

        if (!orderId) {
            alert('Missing Order ID');
            return;
        }

        // === [STEP 1] Gather Input Field Values =========================
        const cash        = parseFloat($container.find('input[name="aaa_oc_cash_amount"]').val())        || 0;
        const zelle       = parseFloat($container.find('input[name="aaa_oc_zelle_amount"]').val())       || 0;
        const venmo       = parseFloat($container.find('input[name="aaa_oc_venmo_amount"]').val())       || 0;
        const applepay    = parseFloat($container.find('input[name="aaa_oc_applepay_amount"]').val())    || 0;
        const creditcard  = parseFloat($container.find('input[name="aaa_oc_creditcard_amount"]').val())  || 0;
        const cashapp     = parseFloat($container.find('input[name="aaa_oc_cashapp_amount"]').val())     || 0;
        const orderTotal  = parseFloat($container.find('input[name="aaa_oc_order_total"]').val())        || 0;
        const originalTip = parseFloat($container.find('input[name="aaa_oc_tip_total"]').val())          || 0;

        console.groupCollapsed(`[WFPAY][SAVE] Raw Inputs for Order #${orderId}`);
        console.table({ cash, zelle, venmo, applepay, creditcard, cashapp, orderTotal, originalTip });
        console.groupEnd();

        // === [STEP 2] Compute Totals & Derived Fields ===================
        const epaymentTotal  = zelle + venmo + applepay + creditcard + cashapp;
        const payrecTotal    = cash + epaymentTotal;
        const epaymentTip    = Math.max(0, epaymentTotal - orderTotal); // Tip is overage
        const totalOrderTip  = epaymentTip + originalTip;
        const balance        = Math.max(0, orderTotal - payrecTotal);
        const status         = payrecTotal === 0 ? 'unpaid' : (balance <= 0.01 ? 'paid' : 'partial');

        console.groupCollapsed(`[WFPAY][SAVE] Computed Values`);
        console.table({ epaymentTotal, payrecTotal, epaymentTip, totalOrderTip, balance, status });
        console.groupEnd();

        // === [STEP 3] Sync Hidden Input Fields for Submission ===========
        $container.find('input[name="aaa_oc_epayment_total"]').val(epaymentTotal.toFixed(2));
        $container.find('input[name="aaa_oc_payrec_total"]').val(payrecTotal.toFixed(2));
        $container.find('input[name="aaa_oc_order_balance"]').val(balance.toFixed(2));
        $container.find('input[name="epayment_tip"]').val(epaymentTip.toFixed(2));
        $container.find('input[name="total_order_tip"]').val(totalOrderTip.toFixed(2));
        $container.find('select[name="aaa_oc_payment_status"]').val(status);

        // === [STEP 4] Prepare Data Payload for AJAX Save ===============
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
            cleared:                   $container.find('input[name="cleared"]').is(':checked') ? 1 : 0,
            envelope_outstanding:      $container.find('input[name="envelope_outstanding"]').is(':checked') ? 1 : 0, // NEW
            payment_admin_notes:       $container.find('textarea[name="payment_admin_notes"]').val(),
            processing_fee:            $container.find('input[name="processing_fee"]').val()
        };

        console.groupCollapsed(`[WFPAY][SAVE] Final AJAX Payload`);
        console.table(data);
        console.groupEnd();

        // === [STEP 5] Send Save Request to Server =======================
        $btn.prop('disabled', true).text('Saving...');

        $.post( AAA_OC_Payment.ajaxUrl, data, function( response ) {
            $btn.prop('disabled', false).text('Save Payment');

            if ( response.success ) {
                console.log('[WFPAY][SAVE] ✅ Payment saved successfully');

                $('.aaa-payment-modal').hide();
                if ( typeof aaaOcCloseModal === 'function' ) {
                    aaaOcCloseModal();
                }

                // Trigger board refresh to update cards
                $.post( AAA_OC_Payment.ajaxUrl, {
                    action:      'aaa_oc_get_latest_orders',
                    _ajax_nonce: AAA_OC_Payment.nonce,
                    sortMode:    window.sortMode || 'published'
                }, function( res ) {
                    if ( res.success ) {
                        $('#aaa-oc-board-columns').html( res.data.columns_html );
                    } else {
                        console.warn('[WFPAY][REFRESH] Failed to refresh columns:', res.data);
                    }
                }).fail(function( err ) {
                    console.error('[WFPAY][REFRESH] AJAX error during board refresh:', err);
                });

            } else {
                console.warn('[WFPAY][SAVE] ❌ Error saving payment:', response.data);
                alert('Error saving payment: ' + response.data);
            }
        }).fail(function( xhr, status, error ) {
            $btn.prop('disabled', false).text('Save Payment');
            console.error('[WFPAY][SAVE] ❌ AJAX failure:', status, error);
            alert('Failed to send payment data.');
        });
    });
});

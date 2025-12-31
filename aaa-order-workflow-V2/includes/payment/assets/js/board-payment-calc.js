jQuery(document).ready(function ($) {
    "use strict";

    function recalcPaymentTotals($container) {
        // Get payment amounts
        let cash        = parseFloat($container.find('input[name="aaa_oc_cash_amount"]').val())        || 0;
        let zelle       = parseFloat($container.find('input[name="aaa_oc_zelle_amount"]').val())       || 0;
        let venmo       = parseFloat($container.find('input[name="aaa_oc_venmo_amount"]').val())       || 0;
        let applepay    = parseFloat($container.find('input[name="aaa_oc_applepay_amount"]').val()) || 0;
        let creditcard  = parseFloat($container.find('input[name="aaa_oc_creditcard_amount"]').val()) || 0;
        let cashapp     = parseFloat($container.find('input[name="aaa_oc_cashapp_amount"]').val())     || 0;

        console.log('[CALC] Raw Inputs - Cash:', cash, 'Zelle:', zelle, 'Venmo:', venmo, 'ApplePay:', applepay, 'CreditCard:', creditcard, 'CashApp:', cashapp);

        // Calculate totals
        let epaymentTotal = zelle + venmo + applepay + creditcard + cashapp;
        console.log('[CALC] Calculated ePayment Total:', epaymentTotal);

        let orderTotal    = parseFloat($container.find('input[name="aaa_oc_order_total"]').val()) || 0;
        let originalTip   = parseFloat($container.find('input[name="aaa_oc_tip_total"]').val())   || 0;
        console.log('[CALC] Order Total:', orderTotal, 'Original Tip:', originalTip);

        let epaymentTip   = Math.max(0, (cash + epaymentTotal) - orderTotal);
        let totalOrderTip = epaymentTip + originalTip;
        console.log('[CALC] ePayment Tip:', epaymentTip, 'Total Order Tip:', totalOrderTip);

        let payrecTotal   = cash + epaymentTotal;
        let balance       = Math.max(0, orderTotal - payrecTotal);
        console.log('[CALC] Payment Recorded Total:', payrecTotal, 'Balance:', balance);

        // Update styled totals
        let $totals = $container.find('.total-row .total-display');
        $totals.eq(0).text(epaymentTotal.toFixed(2));   // ePayment Total
        $totals.eq(1).text(payrecTotal.toFixed(2));     // Payment Total
        $totals.eq(2).text(totalOrderTip.toFixed(2));   // Total Order Tip

        // Update hidden inputs for AJAX save
        $container.find('input[name="aaa_oc_epayment_total"]').val(epaymentTotal.toFixed(2));
        $container.find('input[name="aaa_oc_payrec_total"]').val(payrecTotal.toFixed(2));
        $container.find('input[name="aaa_oc_order_balance"]').val(balance.toFixed(2));
        $container.find('input[name="epayment_tip"]').val(epaymentTip.toFixed(2));
        $container.find('input[name="total_order_tip"]').val(totalOrderTip.toFixed(2));

        // Update status pill
        let statusText;
        if (payrecTotal === 0) {
            statusText = 'Unpaid';
        } else if (balance <= 0.01) {
            statusText = 'Paid';
        } else {
            statusText = 'Partial';
        }
        let $pill = $container.find('.status-pill');
        $pill.text(statusText).attr('class', 'status-pill status-' + statusText.toLowerCase());
        console.log('[CALC] Final Status:', statusText);
    }

    // Recalculate on any number input change
    $(document).on('input', '.aaa-payment-fields input[type="number"]', function () {
        console.log('[CALC] Input changed on:', $(this).attr('name'));
        recalcPaymentTotals($(this).closest('.aaa-payment-fields'));
    });

    // Initial calculation when modal opens
    $('.aaa-payment-fields').each(function () {
        recalcPaymentTotals($(this));
    });

});

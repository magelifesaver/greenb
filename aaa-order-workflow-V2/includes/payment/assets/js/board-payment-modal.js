jQuery(document).ready(function ($) {
    // Open the modal when the button is clicked
    $(document).on('click', '.open-payment-modal', function (e) {
        e.preventDefault();

        const orderId = $(this).data('order-id');
        console.log('[MODAL] Button clicked. Order ID:', orderId);

        // Grab the modal, then MOVE it to <body> to escape any stacking/overflow contexts
        let $modal = $('#aaa-payment-modal-' + orderId);
        if ($modal.length) {
            // Only append to body if not already there
            if ($modal.parent()[0] !== document.body) {
                $modal.appendTo('body');
            }

            // A11y: expose the modal to assistive tech before any focus lands inside
            $modal.attr('aria-hidden', 'false');

            console.log('[MODAL] Found modal for order ID:', orderId);
            $modal.fadeIn(200, function () {
                const $fields = $modal.find('.aaa-payment-fields');
                console.log('[MODAL] Initial payment data for order', orderId, {
                    orderTotal:   $fields.find('input[name="aaa_oc_order_total"]').val(),
                    cash:         $fields.find('input[name="aaa_oc_cash_amount"]').val(),
                    zelle:        $fields.find('input[name="aaa_oc_zelle_amount"]').val(),
                    venmo:        $fields.find('input[name="aaa_oc_venmo_amount"]').val(),
                    applePay:     $fields.find('input[name="aaa_oc_applepay_amount"]').val(),
                    creditCard:   $fields.find('input[name="aaa_oc_creditcard_amount"]').val(),
                    cashApp:      $fields.find('input[name="aaa_oc_cashapp_amount"]').val(),
                    epaymentTotal:$fields.find('input[name="aaa_oc_epayment_total"]').val(),
                    webTip:       $fields.find('input[name="aaa_oc_tip_total"]').val(),
                    payrecTotal:  $fields.find('input[name="aaa_oc_payrec_total"]').val(),
                    orderBalance: $fields.find('input[name="aaa_oc_order_balance"]').val(),
                    epaymentTip:  $fields.find('input[name="epayment_tip"]').val(),
                    totalOrderTip:$fields.find('input[name="total_order_tip"]').val(),
                    paymentStatus:$fields.find('select[name="aaa_oc_payment_status"]').val(),
                    driverId:     $fields.find('select[name="driver_id"]').val(),
                    cleared:      $fields.find('input[name="cleared"]').is(':checked')
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

        console.log('[MODAL] Clearing payment fields for order ID:', $fields.data('order-id'));

        // Reset fields to their original values on close
        $fields.each(function () {
            const $form = $(this);
            $form.find('input[type="number"], input[readonly]').each(function(){
                const original = $(this).attr('value') || '';
                $(this).val(original);
            });
            $form.find('select').each(function(){
                const original = $(this).find('option[selected]').val() || 'unpaid';
                $(this).val(original);
            });
            $form.find('input[type="checkbox"]').each(function(){
                const origChecked = $(this).is('[checked]');
                $(this).prop('checked', origChecked);
            });

            if (typeof recalcPaymentTotals === 'function') {
                recalcPaymentTotals($form);
            }
        });

        // A11y: hide from assistive tech when closing
        $modal.attr('aria-hidden', 'true').hide();
    });

    // Optional nicety: click backdrop to close (only if click is ON the overlay, not inside)
    $(document).on('click', '.aaa-payment-modal', function (e) {
        if (e.target === e.currentTarget) {
            // A11y: hide from assistive tech when closing
            $(this).attr('aria-hidden', 'true').hide();
        }
    });

    // Optional nicety: ESC to close
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            // A11y: hide from assistive tech when closing
            const $open = $('.aaa-payment-modal:visible');
            $open.attr('aria-hidden', 'true').hide();
        }
    });
});

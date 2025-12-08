jQuery(document).ready(function ($) {

    // Open the modal when the button is clicked
    $(document).on('click', '.open-payment-modal', function (e) {
        e.preventDefault();

        const orderId = $(this).data('order-id');
        console.log('[MODAL] Button clicked. Order ID:', orderId);

        const $modal = $('#aaa-payment-modal-' + orderId);
        if ($modal.length) {
            console.log('[MODAL] Found modal for order ID:', orderId);
            // Show the modal, then log initial field values
            $modal.fadeIn(200, function() {
                const $fields = $modal.find('.aaa-payment-fields');
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
                    cleared:         $fields.find('input[name="cleared"]').is(':checked')
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
            // Restore from the value attributes set on load
            $form.find('input[type="number"], input[readonly]').each(function() {
                const original = $(this).attr('value') || '';
                $(this).val(original);
            });
            $form.find('select').each(function() {
                const original = $(this).find('option[selected]').val() || 'unpaid';
                $(this).val(original);
            });
            $form.find('input[type="checkbox"]').each(function() {
                const origChecked = $(this).is('[checked]');
                $(this).prop('checked', origChecked);
            });

            // Recalculate after reset
            if (typeof recalcPaymentTotals === 'function') {
                recalcPaymentTotals($form);
            }
        });

        $modal.hide();
    });

});

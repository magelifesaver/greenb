jQuery(document).ready(function ($) {
    // If user selects an Order, fetch details
    $('#order_id').on('change', function () {
        const orderId = $(this).val();
        if (orderId) {
            $('#order_total, #payment_method_1, #order_status, #customer_account_id, #customer_full_name').val('Loading...');
            $.ajax({
                url: paymentRecordsAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'fetch_order_details',
                    nonce: paymentRecordsAjax.nonce,
                    order_id: orderId,
                },
                success: function (response) {
                    if (response.success) {
                        $('#order_total').val(response.data.order_total);
                        $('#payment_method_1').val(response.data.payment_method);
                        $('#order_status').val(response.data.order_status);
                        $('#customer_account_id').val(response.data.customer_account_id);
                        $('#customer_full_name').val(response.data.customer_full_name);
                    } else {
                        alert(response.data.message || 'Error fetching order details.');
                        clearFields();
                    }
                },
                error: function () {
                    alert('Failed to fetch order details. Please try again.');
                    clearFields();
                },
            });
        } else {
            clearFields();
        }

        function clearFields() {
            $('#order_total, #payment_method_1, #order_status, #customer_account_id, #customer_full_name').val('');
        }
    });

    // Recalc Payment Total and Balance
    function recalcPaymentTotal() {
        const val1 = parseFloat($('#payment_amount_1').val()) || 0;
        const val2 = parseFloat($('#payment_amount_2').val()) || 0;
        const sum = val1 + val2;
        $('#payment_total').val(sum.toFixed(2));

        const orderTotal = parseFloat($('#order_total').val()) || 0;
        const balance = orderTotal - sum;
        $('#order_balance').val(balance.toFixed(2));
    }

    $('.payment-field').on('keyup change', function () {
        recalcPaymentTotal();
    });

    // On initial load, recalc
    recalcPaymentTotal();
});

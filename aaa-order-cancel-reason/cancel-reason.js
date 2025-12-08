jQuery(document).ready(function ($) {
    // Monitor changes to the order status dropdown
    $('#order_status').on('change', function () {
        if ($(this).val() === 'wc-cancelled') {
            let reason = prompt("Please provide a reason for canceling this order:");
            if (reason) {
                // Append the reason as a hidden input
                $('<input>').attr({
                    type: 'hidden',
                    name: 'cancel_reason',
                    value: reason
                }).appendTo('#post');
            } else {
                alert("Cancellation reason is required.");
                $(this).val($('#order_status').data('prev-status')); // Revert status change
            }
        } else {
            // Track previous status for reverting if needed
            $('#order_status').data('prev-status', $(this).val());
        }
    });
});

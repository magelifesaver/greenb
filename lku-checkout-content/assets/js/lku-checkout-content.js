jQuery(document).ready(function($) {
    // Function to toggle the discount code input visibility
    window.toggleDiscountCode = function() {
        $('#pmpro_discount_code_input').toggle();
    };

    // Attach event handlers within the jQuery ready function to ensure all elements are fully loaded
    $('#other_discount_code_toggle').on('click', function(e) {
        e.preventDefault();
        toggleDiscountCode();
    });

    // Function to apply the discount code using AJAX
    window.pmpro_apply_discount_code = function() {
        var discountCode = $('#pmpro_discount_code').val();

        $.ajax({
            url: lku_checkout_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'apply_discount_code', // This should match the WordPress AJAX action in PHP
                discount_code: discountCode,
                nonce: lku_checkout_params.nonce // Nonce for security
            },
            success: function(response) {
                if (response.success) {
                    alert('Discount code applied successfully.');
                } else {
                    alert('Failed to apply discount code: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while applying the discount code.');
            }
        });
    };

    // Link the button to the AJAX apply discount function
    $('#pmpro_discount_code_input input[type="button"]').on('click', function() {
        pmpro_apply_discount_code();
    });
});

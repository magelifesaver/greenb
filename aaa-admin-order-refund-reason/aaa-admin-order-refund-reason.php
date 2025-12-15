<?php
/**
 * Plugin Name: AAA Admin Order Refund Reason (live)(net)(addon)(XHV98)
 * Description: Forces admins to enter a refund reason before processing refunds in WooCommerce order edit screen.
 * Version: 1.0.0
 * Author: AAA Workflow
 * License: GPL2
 *
 * File Path: wp-content/plugins/aaa-admin-order-refund-reason.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Output inline JS on the order edit screen to enforce refund reason entry.
 */
function aaa_admin_order_refund_reason_script() {
    global $pagenow;

    // Only run on the order edit screen.
    if ( 'post.php' !== $pagenow ) {
        return;
    }

    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Function to check refund reason field.
        function checkRefundReason(e) {
            var reasonVal = $('#refund_reason').val().trim();
            if (reasonVal === '') {
                e.preventDefault();
                alert("Please provide a refund reason before processing the refund.");
                $('#refund_reason').focus();
                return false;
            }
            return true;
        }

        // Bind check on refund button clicks.
        $('.do-manual-refund, .do-account-funds-refund').on('click', function(e) {
            if (!checkRefundReason(e)) {
                return false;
            }
        });

        // Also, bind the check to the refund form submission (if present).
        $('#woocommerce-refund-form').on('submit', function(e) {
            if (!checkRefundReason(e)) {
                return false;
            }
        });
    });
    </script>
    <?php
}
add_action( 'admin_footer', 'aaa_admin_order_refund_reason_script' );

// END Refund Reason Enforcer

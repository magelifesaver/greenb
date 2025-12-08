<?php

function display_pmpro_membership_details_on_checkout() {
    // Check if the cart contains a membership product
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product_id = $cart_item['product_id'];

        // Define the mapping between your product IDs and membership levels
        $membership_map = array(
            '167' => 1,
            '168' => 2,
            '169' => 3
        );

        if ( array_key_exists( $product_id, $membership_map ) ) {
            // Get the membership level ID
            $level_id = $membership_map[$product_id];

            // Fetch the membership level details from Paid Memberships Pro
            $level = pmpro_getLevel($level_id);
            if ( !empty($level) ) {
                // Display the membership level details
                echo '<div id="pmpro_pricing_fields" class="pmpro_checkout">';
                echo '<h2><span class="pmpro_checkout-h2-name">Membership Level</span> <span class="pmpro_checkout-h2-msg"><a href="/membership-account/membership-levels/" aria-label="Select a different membership level">change</a></span></h2>';
                echo '<div class="pmpro_checkout-fields">';
                echo '<p class="pmpro_level_name_text">You have selected the <strong>' . esc_html($level->name) . '</strong> membership level.</p>';

                echo '<div id="pmpro_level_cost">';
                echo '<div class="pmpro_level_cost_text">';

                if ( $level->billing_amount > 0 ) {
                    echo '<p>The price for membership is <strong>' . pmpro_formatPrice($level->billing_amount) . '</strong> per ' . esc_html($level->cycle_period) . '.</p>';
                } else {
                    echo '<p>Membership is free.</p>';
                }
                echo '</div>'; // Close pmpro_level_cost_text
                echo '</div>'; // Close pmpro_level_cost
                echo '</div>'; // Close pmpro_checkout-fields
                echo '</div>'; // Close pmpro_pricing_fields
                
                // Add discount code section
            }
        }
    }
}
add_action('woocommerce_before_checkout_form', 'display_pmpro_membership_details_on_checkout');

<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    function WooGC_gpls_woo_rfqtk_hide_woocommerce_cart_total($price)
    {

        if (is_admin() || gpls_woo_rfq_plus_check_staff_mode() == "yes") return $price;

        $temp_price = $price;

        $items = WC()->cart->get_cart();
        $product_names = array();

        $in_role = true;
        $enable_roles = get_option('settings_gpls_woo_rfq_plus_role_based_visible', 'no');

        if ($enable_roles == "purchase") {
            $in_role = false;

            $user = wp_get_current_user();
            $user_roles = $user->roles;

            $eligible_roles = get_option('settings_gpls_woo_rfq_plus_visible_price_roles', 'no');

            $option_value_list = explode(',', $eligible_roles);

            foreach ($user_roles as $cat_id) {

                if (in_array(trim($cat_id), $option_value_list)) {
                    $in_role = true;
                }
            }

            $in_role=apply_filters('rfqtk_is_in_role',$in_role,$user);

        }

        foreach ($items as $item => $values) {
            
            switch_to_blog ( $values['blog_id'] );

            $_product = wc_get_product($values['product_id']);

            $hide_price = get_post_meta($_product->get_id(), '_gpls_woo_rfq_hide_price', true);


            if (gpls_woo_rfq_plus_check_staff_mode() == "yes") {
                $hide_price = "no";
            }


            $hide_all = get_option('settings_gpls_woo_rfq_limit_to_rfq_only_hide_prices', 'no');

            $rfq_enable = get_post_meta($_product->get_id(), '_gpls_woo_rfq_rfq_enable', true);
            $rfq_enable = apply_filters('gpls_rfq_enable', $rfq_enable, $_product->get_id());

            if ($hide_price == 'yes' || ($hide_all == 'yes' && $rfq_enable == 'yes') || $in_role == false) {

                $temp_price = '';
                break;
            }
            
            restore_current_blog();

        }

        return $temp_price;

    }
    
    
    function WooGC_gpls_woo_rfq_hide_subtotal_price_function($subtotal, $cart_item, $cart_item_key)
        {

            if (is_admin() || gpls_woo_rfq_plus_check_staff_mode() == "yes") return $subtotal;

            $temp_price = $subtotal;

            switch_to_blog ( $cart_item['blog_id'] );
            
            $_product = wc_get_product($cart_item['data']->get_id());


            $hide_price = get_post_meta($_product->get_id(), '_gpls_woo_rfq_hide_price', true);


            if (gpls_woo_rfq_plus_check_staff_mode() == "yes") {
                $hide_price = "no";
            }


            $hide_all = get_option('settings_gpls_woo_rfq_limit_to_rfq_only_hide_prices', 'no');

            $rfq_enable = get_post_meta($_product->get_id(), '_gpls_woo_rfq_rfq_enable', true);
            $rfq_enable = apply_filters('gpls_rfq_enable', $rfq_enable, $_product->get_id());

            $in_role = true;
            $enable_roles = get_option('settings_gpls_woo_rfq_plus_role_based_visible', 'no');

            if ($enable_roles == "purchase") {
                $in_role = false;

                $user = wp_get_current_user();
                $user_roles = $user->roles;

                $eligible_roles = get_option('settings_gpls_woo_rfq_plus_visible_price_roles', 'no');

                $option_value_list = explode(',', $eligible_roles);

                foreach ($user_roles as $cat_id) {

                    if (in_array(trim($cat_id), $option_value_list)) {
                        $in_role = true;
                    }
                }
                $in_role=apply_filters('rfqtk_is_in_role',$in_role,$user);

            }


            if ($hide_price == 'yes' || ($hide_all == 'yes' && $rfq_enable == 'yes') || $in_role == false) {
                $temp_price = '';
            }
            
            restore_current_blog();
            
            return $temp_price;
        }
    
    
    function WooGC_gpls_woo_rfqtk_hide_woocommerce_cart_subtotal($cart_subtotal, $compound, $cart)
        {

            if (is_admin() || gpls_woo_rfq_plus_check_staff_mode() == "yes") return $cart_subtotal;

            $temp_price = $cart_subtotal;

            $items = WC()->cart->get_cart();
            $product_names = array();

            $in_role = true;
            $enable_roles = get_option('settings_gpls_woo_rfq_plus_role_based_visible', 'no');

            if ($enable_roles == "purchase") {
                $in_role = false;

                $user = wp_get_current_user();
                $user_roles = $user->roles;

                $eligible_roles = get_option('settings_gpls_woo_rfq_plus_visible_price_roles', 'no');

                $option_value_list = explode(',', $eligible_roles);

                foreach ($user_roles as $cat_id) {

                    if (in_array(trim($cat_id), $option_value_list)) {
                        $in_role = true;
                    }
                }

                $in_role=apply_filters('rfqtk_is_in_role',$in_role,$user);

            }

            foreach ($items as $item => $values) {
                
                switch_to_blog ( $values['blog_id'] );

                $_product = wc_get_product($values['product_id']);

                $hide_price = get_post_meta($_product->get_id(), '_gpls_woo_rfq_hide_price', true);


                if (gpls_woo_rfq_plus_check_staff_mode() == "yes") {
                    $hide_price = "no";
                }


                $hide_all = get_option('settings_gpls_woo_rfq_limit_to_rfq_only_hide_prices', 'no');

                $rfq_enable = get_post_meta($_product->get_id(), '_gpls_woo_rfq_rfq_enable', true);
                $rfq_enable = apply_filters('gpls_rfq_enable', $rfq_enable, $_product->get_id());

                if ($hide_price == 'yes' || ($hide_all == 'yes' && $rfq_enable == 'yes') || $in_role == false) {

                    $temp_price = '';
                    break;
                }
                
                restore_current_blog();

            }

            return $temp_price;
        }




?>
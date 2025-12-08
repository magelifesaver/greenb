<?php

    class WooGC_WAD_Discount extends WAD_Discount 
        {
            
            function get_cart_item_html($price_html, $cart_item, $cart_item_key) {
                //Some plugins like woocommerce request a quote send an empty $cart_item which trigger a lot of issues.
                if (!empty($cart_item)) {
                    $product_id = $cart_item["product_id"];
                    if ($cart_item["variation_id"])
                        $product_id = $cart_item["variation_id"];
                    $product_obj = wc_get_product($product_id);
                    $original_price = wad_get_product_price($product_obj); //$product_obj->get_price();
                    //We check the price used for add to cart
                    $used_price = $this->get_regular_price($original_price, $product_obj);
                    //A discount is applied
                    if ($used_price != $cart_item['data']->get_price()) {
                        $old_price_html = wc_price($original_price);
                        $price_html = "<span class='wad-discount-price' style='text-decoration: line-through;'>$old_price_html</span>" . " $price_html";
                    }
                }
                return $price_html;
            }

            function get_cart_subtotal( $subtotal ){
                $n_subtotal = 0;
                $items = WC()->cart->get_cart_contents();
                if (!is_cart() && !is_checkout()){
                    foreach($items as $item => $values) {
                        
                        if ( isset  ( $values['blog_id'] ) )
                            switch_to_blog( $values['blog_id'] );
                        
                        $price = $this->get_cart_item_price($values['product_id']);
                        $price = $this->apply_quantity_based_discount_if_needed($values['data'], $price);
                        $quantity = $values['quantity'];
                        $n_subtotal += $price * $quantity;
                        
                        if ( isset  ( $values['blog_id'] ) )
                            restore_current_blog();
                        
                    }
                    $subtotal = wc_price($n_subtotal);
                }
                
                return $subtotal;
            }
            
            private function apply_quantity_based_discount_if_needed($product, $normal_price) {
                global $wad_cart_total_without_taxes;
                global $wad_cart_total_inc_taxes;
                global $woocommerce;
                //We check if there is a quantity based discount for this product
                $product_type=$product->get_type();
                $id_to_check = $product->get_id();
                
                
                
                if($product_type=="variation")
                {
                    $parent_product=$product->get_parent_id();
                    $quantity_pricing = get_post_meta($parent_product, "o-discount", true);
                }
                else
                {
                    $quantity_pricing = get_post_meta($id_to_check, "o-discount", true);            
                }

                
                $products_qties = $this->get_cart_item_quantities();
                $rules_type = get_proper_value($quantity_pricing, "rules-type", "intervals");
                $original_normal_price = $normal_price;
                
                if (!isset($products_qties[$id_to_check]) || empty($quantity_pricing) || !isset($quantity_pricing["enable"]))
                {
                    return $normal_price;
                }

                if (isset($quantity_pricing["rules"]) && $rules_type == "intervals") {
                    foreach ($quantity_pricing["rules"] as $rule) {
                        //if ($rule["min"] <= $products_qties[$id_to_check] && $products_qties[$id_to_check] <= $rule["max"]) {
                        if (
                                ($rule["min"] === "" && $products_qties[$id_to_check] <= $rule["max"]) || ($rule["min"] === "" && $rule["max"] === "") || ($rule["min"] <= $products_qties[$id_to_check] && $rule["max"] === "") || ($rule["min"] <= $products_qties[$id_to_check] && $products_qties[$id_to_check] <= $rule["max"])
                        ) {
                            if ($quantity_pricing["type"] == "fixed")
                                $normal_price-=$rule["discount"];
                            else if ($quantity_pricing["type"] == "percentage")
                                $normal_price-=($normal_price * $rule["discount"]) / 100;
                            break;
                        }
                    }
                } else if (isset($quantity_pricing["rules-by-step"]) && $rules_type == "steps") {

                    foreach ($quantity_pricing["rules-by-step"] as $rule) {
                        if ($products_qties[$id_to_check] % $rule["every"] == 0) {
                            if ($quantity_pricing["type"] == "fixed")
                                $normal_price-=$rule["discount"];
                            else if ($quantity_pricing["type"] == "percentage")
                                $normal_price-=($normal_price * $rule["discount"]) / 100;
                            break;
                        }
                    }
                }
                $wad_cart_total_without_taxes = $woocommerce->cart->subtotal_ex_tax;
                if( version_compare( WC()->version , "3.2.1", "<" ) )
                        $taxes=$woocommerce->cart->taxes;
                else
                    $taxes=$woocommerce->cart->get_cart_contents_taxes();
                $wad_cart_total_inc_taxes = $woocommerce->cart->subtotal_ex_tax + array_sum($taxes);
                if(isset($woocommerce->cart->tax_total) && $woocommerce->cart->tax_total>0 && empty($taxes))
                {
                    $wad_cart_total_inc_taxes+=$woocommerce->cart->tax_total;
                }
                return $normal_price;
            }
            

        }

?>
<?php

class WooGC_Enhanced_Ecommerce_Google_Analytics_Public extends Enhanced_Ecommerce_Google_Analytics_Public {
  
    
    /**
     * Check if tracking is disabled
     *
     * @access private
     * @param mixed $type
     * @return bool
     */
    private function disable_tracking($type) {
        if (is_admin() || (!$this->ga_id ) || "" == $type || current_user_can("manage_options")) {
            return true;
        }
    }
    
    
        /**
     * Enhanced E-commerce tracking checkout step 1
     *
     * @access public
     * @return void
     */
    public function checkout_step_1_tracking() {
        if ($this->disable_tracking($this->ga_eeT)) {
            return;
        }
        //call fn to make json
        $this->get_ordered_items();
        $code= '
                var items = [];
                gtag("set", {"currency": tvc_lc});
                for(var t_item in tvc_ch){
                    items.push({
                        "id": tvc_ch[t_item].tvc_i,
                        "name": tvc_ch[t_item].tvc_n,
                        "category": tvc_ch[t_item].tvc_c,
                        "attributes": tvc_ch[t_item].tvc_attr,
                        "price": tvc_ch[t_item].tvc_p,
                        "quantity": tvc_ch[t_item].tvc_q
                    });
                    }';

        $code_step_1 = $code . 'gtag("event", "begin_checkout", {"event_category":"Enhanced-Ecommerce",
                        "event_label":"checkout_step_1","items":items,"non_interaction": true });';

        //check woocommerce version and add code
        $this->wc_version_compare($code_step_1);
    }
    

    /**
     * Enhanced E-commerce tracking for remove from cart
     *
     * @access public
     * @return void
     */
    public function remove_cart_tracking() {
        if ($this->disable_tracking($this->ga_eeT)) {
            return;
        }
        global $woocommerce;
        $cartpage_prod_array_main = array();
        foreach ($woocommerce->cart->cart_contents as $key => $item) {
            
            switch_to_blog( $item['blog_id']);
            //Version compare
            if (version_compare($woocommerce->version, "2.7", "<")) {
                $prod_meta = get_product($item["product_id"]);
            } else {
                $prod_meta = wc_get_product($item["product_id"]);
            }
            if (version_compare($woocommerce->version, "3.3", "<")) {
                $cart_remove_link=html_entity_decode($woocommerce->cart->get_remove_url($key));
            } else {
                $cart_remove_link=html_entity_decode(wc_get_cart_remove_url($key));
            }
            $category = get_the_terms($item["product_id"], "product_cat");
            $categories = "";
            if ($category) {
                foreach ($category as $term) {
                    $categories.=$term->name . ",";
                }
            }
            //remove last comma(,) if multiple categories are there
            $categories = rtrim($categories, ",");
            if(version_compare($woocommerce->version, "2.7", "<")){
                $cartpage_prod_array_main[$cart_remove_link] =array(
                    "tvc_id" => esc_html($prod_meta->ID),
                    "tvc_i" => esc_html($prod_meta->get_sku() ? $prod_meta->get_sku() : $prod_meta->ID),
                    "tvc_n" => html_entity_decode($prod_meta->get_title()),
                    "tvc_p" => esc_html($prod_meta->get_price()),
                    "tvc_c" => esc_html($categories),
                    "tvc_q"=>$woocommerce->cart->cart_contents[$key]["quantity"]
                );
            }else{
                $cartpage_prod_array_main[$cart_remove_link] =array(
                    "tvc_id" => esc_html($prod_meta->get_id()),
                    "tvc_i" => esc_html($prod_meta->get_sku() ? $prod_meta->get_sku() : $prod_meta->get_id()),
                    "tvc_n" => html_entity_decode($prod_meta->get_title()),
                    "tvc_p" => esc_html($prod_meta->get_price()),
                    "tvc_c" => esc_html($categories),
                    "tvc_q"=>$woocommerce->cart->cart_contents[$key]["quantity"]
                );
            }
            
            restore_current_blog();
        }

        //Cart Page item Array to Json
        $this->wc_version_compare("tvc_cc=" . json_encode($cartpage_prod_array_main) . ";");

        $code = '
            //set local currencies
            gtag("set", {"currency": tvc_lc});
        $("a[href*=\"?remove_item\"]").click(function(){
            t_url=jQuery(this).attr("href");
                    gtag("event", "remove_from_cart", {
                        "event_category":"Enhanced-Ecommerce",
                        "event_label":"remove_from_cart_click",
                        "items": [{
                            "id":tvc_cc[t_url].tvc_i,
                            "name": tvc_cc[t_url].tvc_n,
                            "category":tvc_cc[t_url].tvc_c,
                            "price": tvc_cc[t_url].tvc_p,
                            "quantity": tvc_cc[t_url].tvc_q
                        }],
                        "non_interaction": true
                    });
              });
            ';
        //check woocommerce version
        $this->wc_version_compare($code);
    }
    
    
    
    /**
     * Get oredered Items for check out page.
     *
     * @access public
     * @return void
     */
    public function get_ordered_items() {
        global $woocommerce;
        $code = "";
        //get all items added into the cart
        foreach ($woocommerce->cart->cart_contents as $item) {
            
            switch_to_blog( $item['blog_id']);
            //Version Compare
            if ( version_compare($woocommerce->version, "2.7", "<")) {
                $p = get_product($item["product_id"]);
            } else {
                $p = wc_get_product($item["product_id"]);
            }

            $category = get_the_terms($item["product_id"], "product_cat");
            $categories = "";
            if ($category) {
                foreach ($category as $term) {
                    $categories.=$term->name . ",";
                }
            }
            //remove last comma(,) if multiple categories are there
            $categories = rtrim($categories, ",");
            if(version_compare($woocommerce->version, "2.7", "<")){
                $chkout_json[get_permalink($p->ID)] = array(
                    "tvc_id" => esc_html($p->ID),
                    "tvc_i" => esc_js($p->get_sku() ? $p->get_sku() : $p->ID),
                    "tvc_n" => html_entity_decode($p->get_title()),
                    "tvc_p" => esc_js($p->get_price()),
                    "tvc_c" => $categories,
                    "tvc_q" => esc_js($item["quantity"]),
                    "isfeatured"=>$p->is_featured()
                );
            }else{
                $chkout_json[get_permalink($p->get_id())] = array(
                    "tvc_id" => esc_html($p->get_id()),
                    "tvc_i" => esc_js($p->get_sku() ? $p->get_sku() : $p->get_id()),
                    "tvc_n" => html_entity_decode($p->get_title()),
                    "tvc_p" => esc_js($p->get_price()),
                    "tvc_c" => $categories,
                    "tvc_q" => esc_js($item["quantity"]),
                    "isfeatured"=>$p->is_featured()
                );
            }
            restore_current_blog();
        }
        //return $code;
        //make product data json on check out page
        $this->wc_version_compare("tvc_ch=" . json_encode($chkout_json) . ";");
    }

 
} 
<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:              WooCommerce Wholesale Prices Premium
    * Since Version:            1.25
    * Last Update on Version    1.25
    */
    
    class WooGC_WWPP_Wholesale_Price_Product_Category extends WWPP_Wholesale_Price_Product_Category
        {
                       
            public function __construct( $dependencies = array() ) 
                {
                    
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'wwp_filter_wholesale_price_shop', 'WWPP_Wholesale_Price_Product_Category', 'apply_product_category_level_wholesale_discount', 100 );
                    $WooGC->functions->remove_class_filter( 'wwp_filter_wholesale_price_cart', 'WWPP_Wholesale_Price_Product_Category', 'apply_product_category_level_wholesale_discount', 100 );
                    
                    add_filter('wwp_filter_wholesale_price_shop', array($this, 'apply_product_category_level_wholesale_discount'), 100, 5);
                    add_filter('wwp_filter_wholesale_price_cart', array($this, 'apply_product_category_level_wholesale_discount'), 100, 5);
                         
                }
                
                
            /**
             * Apply product category level wholesale discount.
             * Only applies when a product has no wholesale price set on per product level.
             * This logic came from 'class-wwpp-wholesale-prices.php' function 'applyProductCategoryWholesaleDiscount'.
             * Moved it here on this model as this is the correct place on where it should be.
             * Refactor codebase too to include category level pet qty based wholesale discount.
             * Support ignore role/cat level wholesale pricing feature.
             *
             * @since 1.16.0
             * @since 1.23.5 Display correct wholesale when woocommerce multilingual is enabled.
             * @access public
             *
             * @param array   $wholesale_price_arr Wholesale price array data.
             * @param int     $product_id          Product id.
             * @param array   $user_wholesale_role User wholesale role.
             * @param null|array   $cart_item      Cart item data. Null if this callback is executed by the 'wwp_filter_wholesale_price_shop' filter.
             * @param null|WC_Cart $cart_object    Cart object. Null if this callback is executed by the 'wwp_filter_wholesale_price_shop' filter.
             * @return array Filtered wholesale price array data.
             */
            public function apply_product_category_level_wholesale_discount($wholesale_price_arr, $product_id, $user_wholesale_role, $cart_item, $cart_object)
            {

                if (!empty($user_wholesale_role) && empty($wholesale_price_arr['wholesale_price'])) {
                    
                    if ( isset($cart_item['blog_id']))
                        switch_to_blog( $cart_item['blog_id'] );

                    $product = wc_get_product($product_id);
                    $post_id = (WWP_Helper_Functions::wwp_get_product_type($product) === 'variation') ? WWP_Helper_Functions::wwp_get_parent_variable_id($product) : $product_id;

                    // Ignore wholesale pricing set on cat level
                    $disregard_cat_level_discount = apply_filters('wwpp_disregard_cat_level_discount', get_post_meta($post_id, 'wwpp_ignore_cat_level_wholesale_discount', true));
                    if ($disregard_cat_level_discount === 'yes') {
                        
                        if ( isset($cart_item['blog_id']))
                            restore_current_blog();
                        
                        return $wholesale_price_arr;
                    }

                    $use_regular_price = get_option('wwpp_settings_explicitly_use_product_regular_price_on_discount_calc');

                    if ($product->is_on_sale() && $use_regular_price != 'yes') {
                        $product_price = $product->get_sale_price();
                    } else {
                        $product_price = $product->get_regular_price();
                    }

                    // WCML Compatibility
                    $product_price = WWPP_Helper_Functions::get_product_default_currency_price($product_price, $product);

                    if (!is_null($post_id) && $product_price) {

                        // Category discount
                        $base_term_id_and_discount = $this->get_base_term_id_and_wholesale_discount($post_id, $user_wholesale_role);

                        if (!empty($base_term_id_and_discount['discount'])) {

                            $discount = array('source' => 'product_category_level', 'discount' => $base_term_id_and_discount['discount']);

                            // Check if theres category quantity discount
                            if (get_term_meta($base_term_id_and_discount['term_id'], 'wwpp_enable_quantity_based_wholesale_discount', true) === 'yes' && !is_null($cart_item) && !is_null($cart_object)) {
                                $discount = $this->get_cat_level_per_order_quantity_wholesale_discount($discount, $base_term_id_and_discount['term_id'], $product_id, $user_wholesale_role, $cart_item, $cart_object);
                            }

                            if (!empty($discount['discount'])) {

                                $wholesale_price_arr['wholesale_price'] = round($product_price - ($product_price * ($discount['discount'] / 100)), 2);

                                if ($wholesale_price_arr['wholesale_price'] < 0) {
                                    $wholesale_price_arr['wholesale_price'] = 0;
                                }

                                $wholesale_price_arr['source'] = $discount['source'];

                                switch ($discount['source']) {

                                    case 'product_category_level_qty_based':
                                        $wholesale_price_arr['wholesale_price'] = round($product_price - (($discount['discount'] / 100) * $product_price), 2);
                                        break;

                                    case 'product_category_level':
                                        $wholesale_price_arr['wholesale_price'] = round($product_price - (($base_term_id_and_discount['discount'] / 100) * $product_price), 2);
                                        break;

                                }

                                $wholesale_price_arr['source'] = $discount['source'];

                            }

                        }

                    }
                    
                    if ( isset($cart_item['blog_id']))
                        restore_current_blog();

                }

                return $wholesale_price_arr;

            }       
            
            
        }

        
    $wwp_wholesale_roles  = WWP_Wholesale_Roles::getInstance();
    new WooGC_WWPP_Wholesale_Price_Product_Category( array( 'WWPP_Wholesale_Price_Product_Category' => $wwp_wholesale_roles ) );


    
    
    class WooGC_WWPP_Wholesale_Price_Wholesale_Role extends WWPP_Wholesale_Price_Wholesale_Role
        {
                       
            public function __construct( $dependencies = array() ) 
                {
                    
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'wwp_filter_wholesale_price_shop', 'WWPP_Wholesale_Price_Wholesale_Role', 'apply_wholesale_role_general_discount', 200 );
                    $WooGC->functions->remove_class_filter( 'wwp_filter_wholesale_price_cart', 'WWPP_Wholesale_Price_Wholesale_Role', 'apply_wholesale_role_general_discount', 200 );
                    
                    add_filter('wwp_filter_wholesale_price_shop', array($this, 'apply_wholesale_role_general_discount'), 200, 5);
                    add_filter('wwp_filter_wholesale_price_cart', array($this, 'apply_wholesale_role_general_discount'), 200, 5);

                      
                }
                
                
            /**
             * Apply wholesale role general discount to the product being purchased by this user.
             * Only applies if
             * General discount is set for this wholesale role
             * No category level discount is set
             * No wholesale price is set
             *
             * @since 1.2.0
             * @since 1.16.0
             * Now calculates price with wholesale role cart quantity based wholesale discount.
             * This function was previously named as 'applyWholesaleRoleGeneralDiscount' and was from class-wwpp-wholesale-prices.php.
             * Support ignore role/cat level wholesale pricing feature.
             * @since 1.23.5 Display correct wholesale when woocommerce multilingual is enabled.
             * @access public
             *
             * @param array        $wholesale_price_arr Wholesale price array data.
             * @param int          $product_id          Product id.
             * @param array        $user_wholesale_role User wholesale roles.
             * @param null|array   $cart_item           Cart item. Null if this callback is being called by the 'wwp_filter_wholesale_price_shop' filter.
             * @param null|WC_Cart $cart_object         Cart object. Null if this callback is being called by the 'wwp_filter_wholesale_price_shop' filter.
             * @return array Filtered wholesale price array data.
             */
            public function apply_wholesale_role_general_discount($wholesale_price_arr, $product_id, $user_wholesale_role, $cart_item, $cart_object) {

                if (!empty($user_wholesale_role) && empty($wholesale_price_arr['wholesale_price'])) {

                    if ( isset($cart_item['blog_id']))
                        switch_to_blog( $cart_item['blog_id'] );
                    
                    $product = wc_get_product($product_id);

                    if (WWP_Helper_Functions::wwp_get_product_type($product) === 'variable') {
                        
                        if ( isset($cart_item['blog_id']))
                            restore_current_blog();
                            
                        return $wholesale_price_arr;
                    }

                    $post_id = (WWP_Helper_Functions::wwp_get_product_type($product) === 'variation') ? WWP_Helper_Functions::wwp_get_parent_variable_id($product) : $product_id;

                    $disregard_role_level_discount = apply_filters('wwpp_disregard_role_level_discount', get_post_meta($post_id, 'wwpp_ignore_role_level_wholesale_discount', true));
                    if ($disregard_role_level_discount === 'yes') {
                        return $wholesale_price_arr;
                    }

                    // General discount
                    $wholesale_role_discount = get_option(WWPP_OPTION_WHOLESALE_ROLE_GENERAL_DISCOUNT_MAPPING, array());

                    // Per user mapping
                    $puwd = get_user_meta(get_current_user_id(), 'wwpp_wholesale_discount', true);

                    // Check if theres general quantity discount
                    $user_wholesale_discount = $this->get_user_wholesale_role_level_discount(get_current_user_id(), $user_wholesale_role[0], $cart_item, $cart_object);

                    if (is_numeric($user_wholesale_discount['discount']) && !empty($user_wholesale_discount['discount']) && (isset($wholesale_role_discount[$user_wholesale_role[0]]) || !empty($puwd))) {

                        $product = wc_get_product($product_id);
                        $use_regular_price = get_option('wwpp_settings_explicitly_use_product_regular_price_on_discount_calc');

                        if ($product->is_on_sale() && $use_regular_price != 'yes') {
                            $product_price = $product->get_sale_price();
                        } else {
                            $product_price = $product->get_regular_price();
                        }

                        // WCML Compatibility
                        $product_price = WWPP_Helper_Functions::get_product_default_currency_price($product_price, $product);

                        if (is_numeric($product_price) && $product_price) {

                            switch ($user_wholesale_discount['source']) {

                                case 'wholesale_role_level_qty_based':
                                    $wholesale_price_arr['wholesale_price'] = round($product_price - (($user_wholesale_discount['discount'] / 100) * $product_price), 2);
                                    break;

                                case 'wholesale_role_level':
                                    $wholesale_price_arr['wholesale_price'] = round($product_price - (($wholesale_role_discount[$user_wholesale_role[0]] / 100) * $product_price), 2);
                                    break;

                                case 'per_user_level_qty_based':
                                    $wholesale_price_arr['wholesale_price'] = round($product_price - (($user_wholesale_discount['discount'] / 100) * $product_price), 2);
                                    break;

                                case 'per_user_level':

                                    $wholesale_price_arr['wholesale_price'] = round($product_price - (($puwd / 100) * $product_price), 2);
                                    break;

                            }

                            $wholesale_price_arr['source'] = $user_wholesale_discount['source'];

                        }

                    }
                    
                    if ( isset($cart_item['blog_id']))
                        restore_current_blog();

                }

                return $wholesale_price_arr;

            }       
            
            
        }

        
    $wwp_wholesale_roles  = WWP_Wholesale_Roles::getInstance();
    new WooGC_WWPP_Wholesale_Price_Wholesale_Role( array( 'WWPP_Wholesale_Price_Wholesale_Role' => $wwp_wholesale_roles ) );

    
    
    class WooGC_WWPP_Wholesale_Price_Requirement extends WWPP_Wholesale_Price_Requirement
        {
                       
            public function __construct( $dependencies = array() ) 
                {
                    
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'wwp_apply_wholesale_price_cart_level', 'WWPP_Wholesale_Price_Requirement', 'filter_if_apply_wholesale_price_cart_level', 10 );
                    
                    add_filter( 'wwp_apply_wholesale_price_cart_level' ,               array( $this , 'filter_if_apply_wholesale_price_cart_level' )                 , 10 , 5 );

                      
                }
                
                
                
            
            /**
             * Filter if apply wholesale price per cart level. Validate if cart level requirements are meet or not.
             *
             * * Important Note: This does not use the raw cart total, this calculate the cart total by using the wholesale price
             * * of each product on the cart. The idea is that so even after the cart is applied with wholesale price, it will
             * * still meet the minimum order price.
             *
             * * Important Note: We are retrieving the raw wholesale price, not wholesale price with applied tax. Just the raw
             * * wholesale price of the product.
             *
             * * Important Note: Minimum order price is purely based on product price. It does not include tax and shipping costs.
             * * Just the total product price on the cart using wholesale price.
             * 
             * @since 1.15.0
             * @since 1.16.0 Support per wholesale user settings.
             * @since 1.16.4 Add compatibility with "Minimum sub-total amount" with Aelia currency switcher plugin
             * @access public
             *
             * @param boolean $apply_wholesale_price Boolean flag that determines either to apply or not wholesale pricing per cart level.
             * @param WC_Cart $cart_object           WC_Cart instance.
             * @param array   $user_wholesale_role   Current user wholesale roles.
             * @return array|boolean Array of error notices on if current cart item fails cart requirements, boolean true if passed and should apply wholesale pricing.
             */
            public function filter_if_apply_wholesale_price_cart_level( $apply_wholesale_price , $cart_total , $cart_items , $cart_object , $user_wholesale_role ) {

                 return ( parent::filter_if_apply_wholesale_price_cart_level( $apply_wholesale_price , $cart_total , $cart_items , $cart_object , $user_wholesale_role ) );

            }
            
            
            
            
            /**
             * Properly calculate product prices using its wholesale price with taxing applied.
             * 
             * @since 1.23.5
             * @access public
             * 
             * @param int               $cart_total             Cart Totals from WWP
             * @param WC_Cart Object    $cart_object            Cart Object
             * @param array             $user_wholesale_role    Wholesale Role
             * 
             * @return int
             */
            public function calculate_cart_totals( $cart_total , $cart_object , $user_wholesale_role ) {

                global $wc_wholesale_prices_premium;

                $cart_wholesale_price_total = 0;
                
                foreach ( $cart_object->cart_contents as $cart_item_key => $cart_item ) {

                    $product_id                 = WWP_Helper_Functions::wwp_get_product_id( $cart_item[ 'data' ] );
                    $active_currency            = get_woocommerce_currency();
                    $wholesale_price            = WWP_Wholesale_Prices::get_product_wholesale_price_on_cart( WWP_Helper_Functions::wwp_get_product_id( $cart_item[ 'data' ] ) , $user_wholesale_role , $cart_item , $cart_object );
                    
                    add_filter( 'wc_price' , array( $this , 'return_unformatted_price' ) , 10 , 4 );

                    if ( isset($cart_item['blog_id']))
                        switch_to_blog( $cart_item['blog_id'] );
                        
                    $product_data = wc_get_product( $product_id );
                    
                    if ( isset($cart_item['blog_id']))
                        restore_current_blog();
                        
                    $unformatted_price = $wc_wholesale_prices_premium->wwpp_wholesale_prices->get_product_shop_price_with_taxing_applied( $product_data , $wholesale_price , array( 'currency' => $active_currency ) , $user_wholesale_role );
                    
                    if(!empty($unformatted_price)){
                        $unformatted_price = $unformatted_price * $cart_item[ 'quantity' ];
                        $cart_wholesale_price_total += $unformatted_price;
                    }

                    remove_filter( 'wc_price' , array( $this , 'return_unformatted_price' ) , 10 , 4 );
                    
                }
                
                return !empty( $cart_wholesale_price_total ) ? $cart_wholesale_price_total : $cart_total;

            }
                
           
        }

        
    $wwp_wholesale_roles  = WWP_Wholesale_Roles::getInstance();
    new WooGC_WWPP_Wholesale_Price_Requirement( array( 'WWPP_Wholesale_Price_Requirement' => $wwp_wholesale_roles ) );
    
?>
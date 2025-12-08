<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          WooCommerce Wholesale Prices
    * Since Version:        1.7
    */
    
    class WooGC_WWP_Wholesale_Prices extends WWP_Wholesale_Prices
        {
           
            private $wwp_wholesale_roles;
            
            public function __construct( $dependencies = array() ) 
                {
                    
                     
                    global $WooGC;
                    
                    //unregister the hook from original class
                    $WooGC->functions->remove_class_filter( 'woocommerce_before_calculate_totals', 'WWP_Wholesale_Prices', 'apply_product_wholesale_price_to_cart' );
                    
                    add_action( 'woocommerce_before_calculate_totals' , array( $this , 'apply_product_wholesale_price_to_cart' ) , 10 , 1 );
                    
                    if ( isset( $dependencies[ 'WWP_Wholesale_Roles' ] ) )
                        $this->wwp_wholesale_roles  = $dependencies[ 'WWP_Wholesale_Roles' ];
                      
                }
                
                
            
            /**
             * Apply product wholesale price upon adding to cart.
             *
             * @since 1.0.0
             * @since 1.2.3 Add filter hook 'wwp_filter_get_custom_product_type_wholesale_price' for which extensions can attach and add support for custom product types.
             * @since 1.4.0 Add filter hook 'wwp_wholesale_requirements_not_passed' for which extensions can attach and do something whenever wholesale requirement is not meet.
             * @since 1.5.0 Rewrote the code for speed and efficiency.
             * @access public
             *
             * @param $cart_object
             * @param $user_wholesale_role
             */    
            public function apply_product_wholesale_price_to_cart( $cart_object ) {

                $user_wholesale_role = $this->wwp_wholesale_roles->getUserWholesaleRole();
                
                if ( empty( $user_wholesale_role ) )
                    return false;

                $per_product_requirement_notices = array();
                $has_cart_items                  = false;
                $cart_total                      = 0;
                $cart_items                      = 0;
                $cart_items_price_cache          = array(); // Holds the original prices of products in cart

                do_action( 'wwp_before_apply_product_wholesale_price_cart_loop' , $cart_object , $user_wholesale_role );

                foreach ( $cart_object->cart_contents as $cart_item_key => $cart_item ) {

                    if ( !$has_cart_items )
                        $has_cart_items = true;

                    $wwp_data        = null;
                    $wholesale_price = '';
                    
                    if ( isset($cart_item['blog_id']))
                        switch_to_blog( $cart_item['blog_id'] );
                    
                    
                    
                    if ( in_array( WWP_Helper_Functions::wwp_get_product_type( $cart_item[ 'data' ] ) , array( 'simple' , 'variation' ) ) )
                        $wholesale_price = WWP_Wholesale_Prices::get_product_wholesale_price_on_cart( WWP_Helper_Functions::wwp_get_product_id( $cart_item[ 'data' ] ) , $user_wholesale_role , $cart_item , $cart_object );
                    else
                        $wholesale_price = apply_filters( 'wwp_filter_get_custom_product_type_wholesale_price' , $wholesale_price , $cart_item , $user_wholesale_role , $cart_object );


                    if ( $wholesale_price !== '' ) {

                        if ( get_option( 'woocommerce_prices_include_tax' ) === 'yes' )
                            $wp = wc_get_price_excluding_tax( $cart_item[ 'data' ] , array( 'qty' => 1 , 'price' => $wholesale_price ) );
                        else
                            $wp = $wholesale_price;

                        $apply_product_level_wholesale_price = apply_filters( 'wwp_apply_wholesale_price_per_product_level' , true , $cart_item , $cart_object , $user_wholesale_role , $wp );

                        if ( $apply_product_level_wholesale_price === true ) {

                            $cart_items_price_cache[ $cart_item_key ] = $cart_item[ 'data' ]->get_price();
                            $cart_item[ 'data' ]->set_price( WWP_Helper_Functions::wwp_wpml_price( $wholesale_price ) );
                            $wwp_data = array( 'wholesale_priced' => 'yes' , 'wholesale_role' => $user_wholesale_role[ 0 ] );

                        } else {

                            if ( is_array( $apply_product_level_wholesale_price ) )
                                $per_product_requirement_notices[] = $apply_product_level_wholesale_price;

                            $wwp_data = array( 'wholesale_priced' => 'no' , 'wholesale_role' => $user_wholesale_role[ 0 ] );

                        }

                    } else
                        $wwp_data = array( 'wholesale_priced' => 'no' , 'wholesale_role' => $user_wholesale_role[ 0 ] );

                    // Add additional wwp data to cart item. This is used for WWS Reporting
                    $cart_item[ 'data' ]->wwp_data = apply_filters( 'wwp_add_cart_item_meta' , $wwp_data , $cart_item , $cart_object , $user_wholesale_role );

                    if ( apply_filters( 'wwp_include_cart_item_on_cart_totals_computation' , true , $cart_item , $user_wholesale_role ) ) {

                        if ( $wholesale_price ) {

                            if ( get_option( 'woocommerce_prices_include_tax' ) === 'yes' )
                                $wp = wc_get_price_excluding_tax( $cart_item[ 'data' ] , array( 'qty' => 1 , 'price' => $wholesale_price ) );
                            else
                                $wp = $wholesale_price;

                        } else
                            $wp = $cart_item[ 'data' ]->get_price();

                        $cart_total += $wp * $cart_item[ 'quantity' ];
                        $cart_items += $cart_item[ 'quantity' ];

                    }
                    
                    
                    if ( isset($cart_item['blog_id']))
                        restore_current_blog();

                } // Cart loop
                
                do_action( 'wwp_after_apply_product_wholesale_price_cart_loop' , $cart_object , $user_wholesale_role );

                $apply_wholesale_price_cart_level = apply_filters( 'wwp_apply_wholesale_price_cart_level' , true , $cart_total , $cart_items , $cart_object , $user_wholesale_role );

                if ( ( $has_cart_items && $apply_wholesale_price_cart_level !== true ) || !empty( $per_product_requirement_notices ) )
                    do_action( 'wwp_wholesale_requirements_not_passed' , $cart_object , $user_wholesale_role );
                
                if ( $has_cart_items && $apply_wholesale_price_cart_level !== true ) {

                    // Revert back to original pricing
                    foreach ( $cart_object->cart_contents as $cart_item_key => $cart_item ) {

                        if ( array_key_exists( $cart_item_key , $cart_items_price_cache ) ) {

                            $cart_item[ 'data' ]->set_price( $cart_items_price_cache[ $cart_item_key ] );
                            $cart_item[ 'data' ]->wwp_data = array( 'wholesale_priced' => 'no' , 'wholesale_role' => $user_wholesale_role[ 0 ] );

                        }

                    }

                    if ( ( is_cart() || is_checkout() ) && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) )
                        $this->printWCNotice(  $apply_wholesale_price_cart_level );

                }

                if ( !empty( $per_product_requirement_notices ) )
                    foreach ( $per_product_requirement_notices as $notice )
                        if ( ( is_cart() || is_checkout() ) && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) )
                            $this->printWCNotice( $per_product_requirement_notices );

            }
          
            
            
        }

        
    $wwp_wholesale_roles  = WWP_Wholesale_Roles::getInstance();
    new WooGC_WWP_Wholesale_Prices( array( 'WWP_Wholesale_Roles' => $wwp_wholesale_roles ) );



?>
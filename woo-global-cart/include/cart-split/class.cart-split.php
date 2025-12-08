<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_Cart_Split 
        {
            /**
            * Raw cart content found within WooCommerce cart content
            * 
            * @var mixed
            */
            var $raw_cart_content           =   array();
            
            /**
            * Processed cart, held groups of products with checkouts
            * 
            * @var mixed
            */
            var $grouped_cart               =   array();
           
            
            /**
            * Current hash which is being processed
            * 
            * @var mixed
            */
            var $current_key                =   '';
            
            var $in_loop        =   FALSE;
               
            
            function init()
                {
                    
                    global $woocommerce;
                    
                    $this->_update_raw_cart_contents( $this->get_cart() );
                    
                    $this->process_contents();
                    
                }
            
                        
            
            /**
            * Put cart contents which need to be processed
            * 
            * @param mixed $cart_contnet
            */
            private function _update_raw_cart_contents( $cart_contnet )
                {
                    
                    $this->raw_cart_content =   $cart_contnet;   
                    
                }
  
                
            /**
            * Return the cart contents to be processed
            *     
            */
            function get_contents()
                {
                    
                    return $this->raw_cart_content;   
                    
                }
                
            /**
            * Process the contents
            * 
            */
            function process_contents()
                {
                       
                    if ( count ( $this->raw_cart_content )  <   1 )
                        return;
                    
                    global $woocommerce;
                        
                    if( is_object($woocommerce->session))
                        $session_key        =   $woocommerce->session->get_customer_id();
                    
                    if (empty($session_key ))
                        return;
                    
                    $cart_products_map  =   array();
                    foreach ( $this->raw_cart_content   as  $key    =>  $data) 
                        {
                            
                            $cart_products_map[$data['blog_id']][$key]  =   $data;
                        }
                        
                    foreach ( $cart_products_map    as $key =>  $data)
                        {
                            $this->grouped_cart[] =   array(
                                                                'blog_id'   =>  $key,
                                                                'cart'      =>  $data
                                                                );
                        }
                        
                    //create hash
                    foreach ( $this->grouped_cart as $key   =>  $data )
                        {
                            $value  =   json_encode( $data );
                            $hash   =   md5( $value );
                            
                            $this->grouped_cart[$key]['hash']   =   $hash;
                            
                        }
                    

                }
                
            
            /**
            * Return processed content
            *     
            */
            function get_processed_content()
                {
                    
                    return $this->grouped_cart;
                    
                }
                
                
            /**
            * set current block within cart
            *     
            */
            function set_block()
                {
                    
                    $block  =   $this->_get_current_block_data();
                    
                    if ( $block === FALSE )
                        {
                            $this->set_cart( array() );
                        }
                        else
                        {
                            $this->current_key =   $block['hash'];
                        
                            $this->set_cart( $block['cart'] );
                        }

                    WC()->cart->calculate_totals();
                    
                }
                
            
            /**
            * Retrieve the next block which require processing
            *     
            */
            private function _get_current_block_data()
                {
                    if ( count ($this->grouped_cart )   <   1)
                        return FALSE;
                    
                    global $blog_id;
                    
                    //attempt to retrieve a block for current site
                    foreach ($this->grouped_cart    as  $block_data)
                        {
                            if  ( $block_data['blog_id']    ==  $blog_id )
                                return $block_data;
                        }
                        
                    return FALSE;
                    
                }
                
            /**
            * Return the blog_id for current block
            *     
            */
            function get_current_block_blog_id()
                {
                    $block_data =   $this->_get_current_block_data();    
                    
                    if  ( $block_data   ==  FALSE ) 
                        return FALSE;
                        
                    $blog_id    =   $block_data['blog_id'];
                    
                    return $blog_id;
                    
                }
              
            /**
            * Remove a cart block from grouped_cart
            *     
            * @param mixed $group_cart_hash
            */
            private function _remove_grouped_cart( $group_cart_hash )
                {
                    foreach ( $this->grouped_cart as $key   =>  $data )
                        {
                            if ( $data['hash']    ==  $group_cart_hash )
                                {
                                    unset( $this->grouped_cart[$key] );
                                    return TRUE;
                                }
                        }
                        
                    return FALSE;
                }
                
            /**
            * Return a grouped_cart by specified criteria
            * 
            * @param mixed $get_by
            * @param mixed $value
            */
            function get_grouped_cart_block_by( $get_by, $value )
                {
                    
                    switch ($get_by)   
                        {
                            case 'blog_id' :   
                            
                                                foreach (  $this->grouped_cart  as  $key    =>  $data )
                                                    {
                                                        if ( $data['blog_id']   ==  $value )
                                                            return $data;
                                                    }
                            
                                                break;   
                            
                            
                        }
                    
                    return FALSE;
                    
                }
            
                
            
            /**
            * Return the number of groups
            * 
            */
            function get_grouped_cart_count()
                {
                    
                    return count(   $this->grouped_cart );    
                    
                }
                
                
            
            /**
            * put your comment there...
            *     
            */
            function exclude_processed_from_cart( $order )
                {
                    
                    if  ( ! is_object( $order ))
                        return;
                        
                    $cart_items =   $order->get_items();
                    if  ( count  ( $cart_items )    <   1   ||  $this->get_grouped_cart_count() <   1 )
                        return;
                    
                    //get the first product
                    reset( $cart_items );
                    $cart_product   =   current( $cart_items );
                    
                    $product_blog_id    =   $cart_product->get_meta('blog_id');
                    
                    //Checkout works for products from same blog, so we expect all other products in the order are from same blog_id
                    $block  =   $this->get_grouped_cart_block_by ( 'blog_id', $product_blog_id );
                    
                    if  ( $block    === FALSE   )
                        return;
                    
                    //Compare the block with ordr content
                    $block_cart =   $block['cart'];
                    foreach ( $block_cart    as  $hash   =>  $block_cart_item )
                        {
                            $found  =   FALSE;
                            foreach ($cart_items    as  $cart_item)
                                {
                                    if ( $cart_item->get_product_id()    ==  $block_cart_item['product_id'])    
                                        {
                                            $found  =   TRUE;
                                            break;
                                        }
                                }
                                
                            if  ( $found    === TRUE ) 
                                unset( $block_cart[$hash] );
                        }
                        
                    //check if something is wrong, we expect an empty cart array
                    if ( count  ( $block_cart ) >   0 ) 
                        return;
                        
                    //this is the grouped_cart block, remove it from everywhere                   
                    $cart   =   $this->get_cart();
                        
                    foreach ( $block['cart'] as $key   =>  $data)
                        {
                            if ( isset($cart[$key]) )
                                unset( $cart[$key] );
                        }
                        
                    $this->set_cart( $cart );
                    
                    //update the raw_cart
                    $this->_update_raw_cart_contents( $this->get_cart() );
                    
                    //rmove the block from grouped_cart
                    $this->_remove_grouped_cart( $block['hash'] );
                    
                    global $woocommerce;
                    //trigger the calculate_totals() to ensure specifica actions occour like sessions update
                    $woocommerce->cart->calculate_totals();
                    
                        
                }
            
            
            /**
            * Get the cart content
            * 
            * @param mixed $block
            */
            function get_cart( )
                {
                    
                    global $woocommerce;
                    
                    return $woocommerce->cart->cart_contents;
                    
                }
            
            
            /**
            * Update the cart with content
            * 
            * @param mixed $block
            */
            function set_cart( $block )
                {
                    
                    global $woocommerce;
                    
                    $woocommerce->cart->cart_contents   =   $block;
                    
                }
                
            
            /**
            * restore original cart
            *     
            */
            function restore_cart()
                {
                    $this->set_cart( $this->raw_cart_content );
                }
                
                
            /**
            * Return the checkout url based on current blog_id and grouped cart data
            * 
            */
            function get_checkout_url()
                {
                        
                    global $blog_id;
                    
                    //check if there's a block on current blog_id
                    $block  =   $this->get_grouped_cart_block_by ( 'blog_id', $blog_id );
                    if ( $block !== FALSE )
                        {
                            $checkout_url   =   $this->_retrieve_checkout_url(); 
                            
                            return $checkout_url;   
                        }
                        
                    //no block has been found so far, use the first block blog_id for checkout
                    reset ( $this->grouped_cart );
                    $block  =   current( $this->grouped_cart );
                    
                    if ( isset ( $block['blog_id'] ) )
                        {
                            $block_blog_id  =   $block['blog_id'];
                            switch_to_blog( $block_blog_id );
                            
                            $checkout_url   =   $this->_retrieve_checkout_url(); 
                                    
                            restore_current_blog();
                        }
                        else
                        {
                            global $WooGC;
                            
                            $WooGC->functions->remove_anonymous_object_filter('woocommerce_get_checkout_url', 'WooGC_Template',   'woocommerce_get_checkout_url',  999);
                                                    
                            $checkout_url   =   wc_get_checkout_url();
                            
                            add_filter('woocommerce_get_checkout_url',                  array( 'WooGC_Template',   'woocommerce_get_checkout_url'),  999);   
                        }
                    
                    return $checkout_url;
                    
                }
                
            
            /**
            * Retrive the checkout url
            * 
            */
            private function _retrieve_checkout_url()
                {
                    
                    $checkout_url = wc_get_page_permalink( 'checkout' );
                    if ( $checkout_url ) 
                        {
                            // Force SSL if needed.
                            if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) 
                                {
                                    $checkout_url = str_replace( 'http:', 'https:', $checkout_url );
                                }
                        }
                        
                    return $checkout_url;
                       
                }
            
            
        }


?>
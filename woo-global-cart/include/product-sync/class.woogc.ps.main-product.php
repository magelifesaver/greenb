<?php
    
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_PS_main_product
        {
            
            var $ID    =  FALSE;
                                        
            private $PS;
            public $data    =   array();
             
            /**
            * Contruct the object
            * 
            * @param mixed $product_id
            */
            function __construct( $product_id )
                {
                    if ( is_numeric( $product_id ) && $product_id > 0 )
                        {
                            $this->set_id( $product_id );
                            
                            $this->PS    =   new WooGC_PS();
                               
                        }
                
                }
                
            
            /**
            * Set the product ID
            *     
            * @param mixed $product_id
            */
            private function set_id ( $product_id) 
                {
                    $this->ID   =   $product_id;
                }
            
            /**
            * Return the product ID
            *     
            */
            public function get_id()
                {
                    return $this->ID;   
                }
            
            
            /**
            * Check if the current product have child at specified shop
            * 
            * @param mixed $shop_id
            */
            public function have_child_at_shop( $shop_id )
                {
                    if ( $this->PS->main_have_child_at_shop( $this->ID, $shop_id ) )   
                        return TRUE;
                        
                    return FALSE;
                }
                
            public function get_child_at_shop( $shop_id )
                {
                    global $blog_id; 
                    
                    $found_product_ID   =   $this->PS->get_product_synchronized_at_shop( $this->ID, $blog_id, $shop_id );
                        
                    return $found_product_ID;
                }
            
            /**
            * Used for Main product
            * Return the shops IDs where syncronyze
            *     
            */
            function get_children()
                {
                    if ( isset( $this->data['children'] ) )
                        return $this->data['children'];
                    
                    $this->data['children'] =   $this->PS->main_get_children( $this->ID );
                
                    return $this->data['children'];
                }
            
            /**
            * Return the maintained shops for this product
            *     
            */
            function get_maintained()
                {
                    if ( isset( $this->data['maintained'] ) )
                        return $this->data['maintained'];
                    
                    $this->data['maintained'] =   $this->PS->main_get_maintain_children( $this->ID );
                
                    return $this->data['maintained'];
                }
                
            
            /**
            * Return the maintained categories for this product
            *     
            */
            function get_maintained_categories()
                {
                    if ( isset( $this->data['maintained_categories'] ) )
                        return $this->data['maintained_categories'];
                    
                    $this->data['maintained_categories'] =   $this->PS->main_get_maintain_categories( $this->ID );
                
                    return $this->data['maintained_categories'];
                }
                
            /**
            * Return the maintained shops stock for this product
            *     
            */
            function get_maintained_stock()
                {
                    if ( isset( $this->data['maintained_stock'] ) )
                        return $this->data['maintained_stock'];
                    
                    $this->data['maintained_stock'] =   $this->PS->main_get_maintain_stock( $this->ID );
                
                    return $this->data['maintained_stock'];
                }
            
            
            
            
            
            
                
                
            /**
            * Set th product as main product
            *     
            */
            function set_as_main_product()
                {
                    update_post_meta ( $this->ID, '_woogc_ps_is_main_product',    'yes' );
                }
            
            
            /**
            * Set the synchroniz To list
            *     
            * @param mixed $sync_to_list_list array of shops where the products are synchronized
            */
            function set_sync_to ( $sync_to_list )
                {
                    update_post_meta ( $this->ID, '_woogc_ps_sync_to',            implode ( ",", $sync_to_list ) );
                }
                
            
            /**
            * Set the maintained list
            *     
            * @param mixed $maintained_list array of shops where the products are maintained when updated the main
            */
            function set_mintained( $maintained_list )
                {
                    update_post_meta ( $this->ID, '_woogc_ps_maintain_child',            implode ( ",", $maintained_list ) );
                }
                
            
            /**
            * Set the maintained list
            *     
            * @param mixed $maintained_stock_list array of shops where the products stocks are maintained when updated the main
            */
            function set_mintained_categories( $maintained_categories_list )
                {
                    update_post_meta ( $this->ID, '_woogc_ps_maintain_categories',            implode ( ",", $maintained_categories_list ) );
                }
                
            /**
            * Set the maintained list
            *     
            * @param mixed $maintained_stock_list array of shops where the products stocks are maintained when updated the main
            */
            function set_mintained_stock( $maintained_stock_list )
                {
                    update_post_meta ( $this->ID, '_woogc_ps_maintain_stock',            implode ( ",", $maintained_stock_list ) );
                }

        }
        
?>
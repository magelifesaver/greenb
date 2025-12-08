<?php
    
    defined( 'ABSPATH' ) || exit;
    
    class WooGC_PS_child_product
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
            * Return an array with origina Main Product ID and Blog ID
            * 
            */
            public function get_main_data()
                {
                    if ( isset( $this->data['main_id'] ) && isset( $this->data['main_blog_id'] ) )
                        return array ( $this->data['main_id'], $this->data['main_blog_id'] );
                    
                    list ( $origin_product_id, $origin_blog_id ) =   $this->PS->child_get_main( $this->ID );
                
                    $this->data['main_id']          =   $origin_product_id;
                    $this->data['main_blog_id']     =   $origin_blog_id;
                    
                    return array ( 
                                    'main_id'       =>  $this->data['main_id'], 
                                    'main_blog_id'  =>  $this->data['main_blog_id'] 
                                    ); 
                }
                
            /**
            * Return origina Main Product ID
            * 
            */
            public function get_main_id()
                {
                    if ( isset( $this->data['main_id'] ) )
                        return $this->data['main_id'];
                    
                    list ( $origin_product_id, $origin_blog_id ) =   $this->PS->child_get_main( $this->ID );
                
                    $this->data['main_id']          =   $origin_product_id;
                    $this->data['main_blog_id']     =   $origin_blog_id;
                    
                    return $this->data['main_id']; 
                }
                
            /**
            * Return origina Main Product Blog ID
            *     
            */
            public function get_main_shop_id()
                {
                    if ( isset( $this->data['main_blog_id'] ) )
                        return $this->data['main_blog_id'];
                    
                    list ( $origin_product_id, $origin_blog_id ) =   $this->PS->child_get_main( $this->ID );
                
                    $this->data['main_id']          =   $origin_product_id;
                    $this->data['main_blog_id']     =   $origin_blog_id;
                    
                    return $this->data['main_blog_id']; 
                }
            
            
            /**
            * Check if the product is maintained sync enabled
            *     
            */
            public function is_sync()
                {
                    if ( isset ( $this->data['is_sync'] ) )
                        return $this->data['is_sync'];
                    
                    $this->get_main_data();
                    
                    switch_to_blog( $this->data['main_blog_id'] );
                    
                    $main_sync_list    =   $this->PS->main_get_children( $this->data['main_id'] );
                    
                    restore_current_blog();
                    
                    global $blog_id;
                    
                    $status =   FALSE;
                    if ( array_search ( $blog_id, $main_sync_list ) !== FALSE )
                        $status =   TRUE;
                        
                    $this->data['is_sync']   =   $status;
                        
                    return $status;
                    
                }
                
                
            /**
            * Check if the product is maintained sync enabled
            *     
            */
            public function is_maintained_sync()
                {
                    if ( isset ( $this->data['is_maintained_sync'] ) )
                        return $this->data['is_maintained_sync'];
                    
                    $this->get_main_data();
                    
                    switch_to_blog( $this->data['main_blog_id'] );
                    
                    $main_mainained_list    =   $this->PS->main_get_maintain_children( $this->data['main_id'] );
                    
                    restore_current_blog();
                    
                    global $blog_id;
                    
                    $status =   FALSE;
                    if ( array_search ( $blog_id, $main_mainained_list ) !== FALSE )
                        $status =   TRUE;
                        
                    $this->data['is_maintained_sync']   =   $status;
                        
                    return $status;
                    
                }
            
            /**
            * Check if the product categories sync enabled
            *     
            */
            public function is_categories_sync()
                {
                    if ( isset ( $this->data['is_categories_sync'] ) )
                        return $this->data['is_categories_sync'];
                    
                    $this->get_main_data();
                    
                    switch_to_blog( $this->data['main_blog_id'] );
                    
                    $main_mainained_list    =   $this->PS->main_get_maintain_categories( $this->data['main_id'] );
                    
                    restore_current_blog();
                    
                    global $blog_id;
                    
                    $status =   FALSE;
                    if ( array_search ( $blog_id, $main_mainained_list ) !== FALSE )
                        $status =   TRUE;
                        
                    $this->data['is_categories_sync']   =   $status;
                        
                    return $status; 
                    
                }
                
            /**
            * Check if the product is stock sync enabled
            *     
            */
            public function is_stock_sync()
                {
                    if ( isset ( $this->data['is_stock_sync'] ) )
                        return $this->data['is_stock_sync'];
                    
                    $this->get_main_data();
                    
                    switch_to_blog( $this->data['main_blog_id'] );
                    
                    $main_mainained_list    =   $this->PS->main_get_maintain_stock( $this->data['main_id'] );
                    
                    restore_current_blog();
                    
                    global $blog_id;
                    
                    $status =   FALSE;
                    if ( array_search ( $blog_id, $main_mainained_list ) !== FALSE )
                        $status =   TRUE;
                        
                    $this->data['is_stock_sync']   =   $status;
                        
                    return $status; 
                    
                }
              
            
        }
        
?>
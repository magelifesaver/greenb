<?php

    defined( 'ABSPATH' ) || exit;

    class WooGC_Tax 
        {
            
            function __construct()
                {

                    add_filter ( 'woocommerce_matched_tax_rates',               array ( $this, 'woocommerce_matched_tax_rates' ),   99, 6 );
                    add_filter ( 'woocommerce_rate_compound',                   array ( $this, 'woocommerce_rate_compound' ),       99, 2 );
                    add_filter ( 'woocommerce_rate_label',                      array ( $this, 'woocommerce_rate_label' ),          99, 2 );
                    add_filter ( 'woocommerce_rate_code',                       array ( $this, 'woocommerce_rate_code' ),           99, 2 );                    
                }
       
       
            function woocommerce_matched_tax_rates ( $matched_tax_rates, $country, $state, $postcode, $city, $tax_class)
                {
                    remove_filter( 'woocommerce_matched_tax_rates', array ( $this, 'woocommerce_matched_tax_rates' ), 99, 6 );
                    
                    switch_to_blog( WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP__SITE_BASE_TAX );
                    
                    $args   =   array(
                                            'country'   => $country,
                                            'state'     => $state,
                                            'city'      => $city,
                                            'postcode'  => $postcode,
                                            'tax_class' => $tax_class,
                                        );
                    
                    $matched_tax_rates  =   WC_Tax::get_rates ( );
                    
                    
                    restore_current_blog();
                    
                    add_filter ( 'woocommerce_matched_tax_rates',               array ( $this, 'woocommerce_matched_tax_rates' ),   99, 6 );
                    return $matched_tax_rates;    
                }
                
                
                
            function woocommerce_rate_compound( $compound, $key )
                {
                    remove_filter ( 'woocommerce_rate_compound', array ( $this, 'woocommerce_rate_compound' ), 99, 2 );   
                    
                    switch_to_blog( WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP__SITE_BASE_TAX );
                    
                    $compound    =   WC_Tax::is_compound( $key );
                    
                    restore_current_blog();
                    
                    add_filter ( 'woocommerce_rate_compound',                   array ( $this, 'woocommerce_rate_compound' ),       99, 2 );        
                    return $compound;    
                }
                
                
            function woocommerce_rate_label( $rate_name, $key )
                {
                    remove_filter ( 'woocommerce_rate_label', array ( $this, 'woocommerce_rate_label' ), 99, 2 );   
                    
                    switch_to_blog( WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP__SITE_BASE_TAX );
                    
                    $rate_name    =   WC_Tax::get_rate_label( $key );
                    
                    restore_current_blog();
                    
                    add_filter ( 'woocommerce_rate_label',                      array ( $this, 'woocommerce_rate_label' ),          99, 2 );        
                    return $rate_name;    
                }
                
            function woocommerce_rate_code( $code_string, $key )
                {
                    remove_filter ( 'woocommerce_rate_code', array ( $this, 'woocommerce_rate_code' ), 99, 2 );   
                    
                    switch_to_blog( WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP__SITE_BASE_TAX );
                    
                    $code_string    =   WC_Tax::get_rate_code( $key );
                    
                    restore_current_blog();
                    
                    add_filter ( 'woocommerce_rate_code',                       array ( $this, 'woocommerce_rate_code' ),           99, 2 );        
                    return $code_string;    
                }
                
        }

    new WooGC_Tax();

?>
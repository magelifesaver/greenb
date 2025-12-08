<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_on_update
        {
            var $WooGC;
                                  
            function __construct()
                {
                    global $WooGC;
                    $this->WooGC    =   $WooGC;
                    
                    $this->_run();
                }
                
                
            private function _run()
                {                    
                    
                    $options    =   $this->WooGC->functions->get_options();
                    
                    $version        =   $options['version'];
                    if(empty($version))
                        $version    =   1;
                         
                    if (version_compare($version, WOOGC_VERSION, '>=')) 
                        return;            
              
                    //update from the free version                                                  
                    if(version_compare($version, '1.1.4.2', '<'))
                        {
                            
                            $this->WooGC->functions->copy_mu_files( TRUE );
                            
                            $version =   '1.1.4.2';
                        }
                        
                    if(version_compare($version, '1.1.6', '<'))
                        {
                            
                            $this->WooGC->functions->copy_mu_files( TRUE );
                            
                            $version =   '1.1.6';
                        }
                        
                    if(version_compare($version, '1.1.8.3', '<'))
                        {
                            
                            $this->WooGC->functions->copy_mu_files( TRUE );
                            
                            $version =   '1.1.8.3';
                        }
                        
                                   
                    if(version_compare($version, '2.0', '<'))
                        {
                                     
                            unset ( $options['login_on_sites'] );
                            unset ( $options['login_only_specific_roles'] );
                            
                            $version =   '2.0';
                        }
   
                    if(version_compare($version, '3.4.4', '<'))
                        {
                            
                            $this->WooGC->functions->remove_tables();
                            $this->WooGC->functions->create_tables();
                            
                            $this->WooGC->functions->wp_config_clean();
                            $this->WooGC->functions->wp_config_add();
                            
                            $this->WooGC->functions->copy_mu_files( TRUE );
                            
                            $version =   '3.4.4';
                        }
                                 
                    //save the last code version
                    $options['version'] =   WOOGC_VERSION;
                    $this->WooGC->functions->update_options( $options );
                     
                }
                           
        }
        
 
 
    new WooGC_on_update();
        
        
?>
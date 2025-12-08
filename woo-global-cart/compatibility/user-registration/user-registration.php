<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:  User Registration
    * Since:        1.9.4.1
    */

    class WooGC_UserRegistration
        {
            
            function __construct( ) 
                {
                    
                    add_filter( 'user_registration_login_redirect' , array ( $this , 'user_registration_login_redirect'), 10, 2);
                      
                }
                
     
            function user_registration_login_redirect( $redirect, $user )
                {
                                    
                    global $WooGC;
                            
                    if( strpos( $redirect, 'loggedin=true')   === FALSE )
                        {
                            //replace any loggedout argument
                            if( strpos($redirect, 'loggedout=true')   !== FALSE )
                                {
                                    preg_match('/(loggedout=true)(\&)?/i', $redirect, $matchs);
                                    if  (   ! empty ( $matchs[2] ) )
                                        $redirect    =   str_replace( 'loggedout=true&', '', $redirect);
                                        else
                                        $redirect    =   str_replace( 'loggedout=true', '', $redirect);
                                }
                            
                            if (strpos($redirect, "?")   ===    FALSE)
                                $redirect    .=  "?";
                                
                            if (substr($redirect, -1) != '?')
                                $redirect    .=  "&";
                                
                            $redirect    .=  "loggedin=true";
                            
                            if ( ! empty ( $WooGC->user_login_sso_hash ) )
                                $redirect    .=  "&login_hash=" . $WooGC->user_login_sso_hash;
                        }
                               
                    
                    return $redirect;
                }
            
        }

        
    new WooGC_UserRegistration();

?>
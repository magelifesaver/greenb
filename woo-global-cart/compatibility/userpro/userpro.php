<?php
    
    defined( 'ABSPATH' ) || exit;
    
    /**
    * Plugin Name:          UserPro
    * Since:        4.9.38
    */

    class WooGC_UserPro
        {
            
            function __construct( ) 
                {
                    
                    add_filter( 'userpro_register_redirect', array ( $this , 'userpro_register_redirect' ) );
                      
                }
                
     
            function userpro_register_redirect ( $redirect_to )
                {
                    global $WooGC;
                    
                    if( strpos($redirect_to, 'loggedin=true')   === FALSE )
                        {
                            //replace any loggedout argument
                            if( strpos($redirect_to, 'loggedout=true')   !== FALSE )
                                {
                                    preg_match('/(loggedout=true)(\&)?/i', $redirect_to, $matchs);
                                    if  (   ! empty ( $matchs[2] ) )
                                        $redirect_to    =   str_replace( 'loggedout=true&', '', $redirect_to);
                                        else
                                        $redirect_to    =   str_replace( 'loggedout=true', '', $redirect_to);
                                }
                            
                            if (strpos($redirect_to, "?")   ===    FALSE)
                                $redirect_to    .=  "?";
                                
                            if (substr($redirect_to, -1) != '?')
                                $redirect_to    .=  "&";
                                
                            $redirect_to    .=  "loggedin=true";
                            
                            if ( ! empty ( $WooGC->user_login_sso_hash ) )
                                $redirect_to    .=  "&login_hash=" . $WooGC->user_login_sso_hash;
                        }
                    
                    return $redirect_to;    
                }
            
        }

        
    new WooGC_UserPro();

?>
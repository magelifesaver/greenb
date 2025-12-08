<?php

        final class WooGC_Sync 
            {
                private $action_type    =   '';
                
                private $doing_bounce   =   FALSE;
                                
                private $sync           =   array();
                
                private $secure_cookie  =   '';
                
                private $return_url     =   '';
                
                function __construct()
                    {
                        
                        define('WOOGC_COOKIE_EXPIRE',         30 * 24 * 60 * 60);
                        
                        $this->secure_cookie             =   isset( $_SERVER['HTTPS'] )  ?   TRUE :   FALSE;
                        
                        if ( isset ( $_GET['sync_run'] )    &&  isset ( $_GET['sync_hash'] ) )
                            {    
                                $this->sync['run']   =   preg_replace("/[^A-Za-z0-9]/", '', $_GET['sync_run'] );
                                $this->sync['hash']  =   preg_replace("/[^A-Za-z0-9]/", '', $_GET['sync_hash'] );
                                
                                $this->doing_bounce  =   isset ( $_GET['bounce'] ) ?    TRUE    :   FALSE;
                                
                                $this->return_url    =   isset($_GET['return_url'])      ?  $_GET['return_url']    :   FALSE;
                                
                                if ( $this->sync['run'] == 'true'    &&  ! empty ( $this->sync['hash'] ) )
                                    $this->action_type          =   'sync';
                                    
                                if ( $this->do_sync() )
                                    $this->close();
                            }
                            
                            
                        if ( isset ( $_GET['prefetch'] ) )
                            $this->_output_pixel();    

                    }
                    
                
                private function close()
                    {
                        if ( $this->action_type == 'sync' )
                            {
                                if ( $this->doing_bounce)
                                    {
                                        $protocol   =   $this->secure_cookie  ?   'https' :   'http';
                                        $return_url =   $protocol . ":" . $this->return_url . '?bounce=true&sync_run=true&sync_hash=' . $this->sync['hash'] . '&rand=' . rand(9999, 99999);
                                        header("Location: " . $return_url );   
                                    }
                                    else
                                    $this->_output_pixel();
                                
                                return;
                            }    
                         
                    }
                
                
                private function do_sync()
                    {
                        
                        define( 'SHORTINIT', true );
                        
                        if ( file_exists ( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' ) )
                            require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
                            else
                            require_once( dirname( __DIR__ , 4) . '/wp-load.php' );
                        require_once ABSPATH . WPINC . '/pluggable.php'; 
                        require_once ABSPATH . WPINC . '/l10n.php';
                        
                        if ( defined ( 'WOOGC_COOKIE_DOMAIN' ) )
                            define ( 'COOKIE_DOMAIN' , WOOGC_COOKIE_DOMAIN );
                            else
                            define ( 'COOKIE_DOMAIN' , $_SERVER['SERVER_NAME'] );
                                                
                        global $wpdb;
                        
                        $this->sync['table']    =   $wpdb->base_prefix . 'woocommerce_woogc_sessions';
                        
                        //check the trigger hash if still valid
                        $data = $wpdb->get_row( $wpdb->prepare( "SELECT *  FROM {$this->sync['table']} WHERE trigger_key = %s", $this->sync['hash'] ), ARRAY_A );

                        if ( is_null ( $data ) )
                            return FALSE;
                        
                        if ( empty ($data['trigger_key'] )  ||  empty ($data['trigger_user_hash'] ) )
                            return FALSE;
                            
                        if ( empty ( $data['trigger_key_expiry'] ) || time() > $data['trigger_key_expiry'] )
                            return FALSE;
                        
                        $ip =   '';
                        if ( isset ( $_SERVER['HTTP_X_FORWARDED_FOR'] ) &&  ! empty ( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
                            {
                                //check cloudflare multiple ips
                                if ( strpos( $_SERVER['HTTP_X_FORWARDED_FOR'], ',' ) !== FALSE )
                                    {
                                        $ip_list    =   explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
                                        $ip =   $ip_list[0];
                                    }
                                    else
                                    $ip =   $_SERVER['HTTP_X_FORWARDED_FOR'];
                            }
                            else
                            $ip =   $_SERVER['REMOTE_ADDR'];
                            
                        $ua =   isset($_SERVER['HTTP_USER_AGENT'])  ?   $_SERVER['HTTP_USER_AGENT']  :   '';   
                        if ( defined ( 'WOOGC_SESSION_IGNORE_UA' ) &&  WOOGC_SESSION_IGNORE_UA    === true )
                            $ua =   '';
                        
                        $token_data = array(
                                                'ip'        =>  $ip,
                                                'ua'        =>  $ua
                                            );
                        $session_hash       = wp_hash( serialize( $token_data ) );
                        if ( $session_hash  != $data['trigger_user_hash'] )    
                            return FALSE;
                        
                        $woogc_session_key  =   $data['woogc_session_key'];
                            
                        //set the cookie
                        $this->set_cookie( 'woogc_session', $woogc_session_key, WOOGC_COOKIE_EXPIRE, '/' , COOKIE_DOMAIN, $this->secure_cookie, TRUE );
                        
                        return TRUE;
                        
                    }
                                        
                    
                private function set_cookie(    $CookieName, $CookieValue = '', $CookieMaxAge = 0, $CookiePath = '', $CookieDomain = '', $CookieSecure = false, $CookieHTTPOnly = false, $CookieSameSite = 'none') 
                    {
                        header( 'Set-Cookie: ' . rawurlencode( $CookieName ) . '=' . rawurlencode( $CookieValue )
                                            . ( empty($CookieMaxAge )   ? '' : '; Max-Age=' . $CookieMaxAge)
                                            . ( empty($CookiePath )     ? '' : '; path=' . $CookiePath)
                                            . ( empty($CookieDomain )   ? '' : '; domain=' . $CookieDomain)
                                            . ( !$CookieSecure          ? '' : '; secure')
                                            . ( !$CookieHTTPOnly        ? '' : '; HttpOnly')
                                            . ( empty($CookieSameSite)  ? '' : '; SameSite=' . $CookieSameSite )
                                            
                                            ,false);
                    }
                
                    
                public function _output_pixel()
                    {
                        
                        header('Content-Type: image/png');
                        
                        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
                    
                    }
       
            }
            
        new WooGC_Sync();

     
?>
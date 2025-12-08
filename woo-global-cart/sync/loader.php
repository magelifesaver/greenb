<?php
    
    //Check if MultiSite is enabled. Avoid direct invocation.
    if  ( ! defined( 'DOMAIN_CURRENT_SITE' ) )
        return;
    
    define('WOOGC_LOADER',                  '1.0.5');
    define('WOOGC_COOKIE_EXPIRE',           2 * 30 * 24 * 60 * 60);
    define('WOOGC_TRIGGER_KEY_EXPIRE',      10 );
    
    if ( defined ( 'WOOGC_COOKIE_DOMAIN' ) )
        define ( 'COOKIE_DOMAIN' , WOOGC_COOKIE_DOMAIN );
        else
        define ( 'COOKIE_DOMAIN' , $_SERVER['SERVER_NAME'] );
        
    final class  WooGC_loader
        {
            var $global_cookie_name         =   'woogc_session';
            var $trigger_cookie_name        =   'woogc_session_trigger';
            
            var $_woogc_session_id          =   FALSE;
            var $_woogc_trigger_id          =   FALSE;
            
            var $trigger_data               =   FALSE;
            
            var $has_set_global_cookie      =   FALSE;
            
            var $set_secure_cookie          =   FALSE;
            
            function __construct()
                {
                    
                    if ( isset ( $_POST )   &&  count ( $_POST ) > 0 )
                        return;
                    
                    $current_domain             =   $_SERVER['HTTP_HOST'];
                    
                    $this->set_secure_cookie    =   isset( $_SERVER['HTTPS'] )  ?   TRUE :   FALSE;
                    
                    $this->cleanup_sessions();
                    
                    //check for wrong server set-up
                    if ( COOKIE_DOMAIN  !=  $current_domain )
                        return;
                    
                    if ( $this->ignore_agent() ||   $this->ignore_uri() )
                        return;
                    
                    if ( $this->is_internal_call() )
                        return;
                    
                    if ( DOMAIN_CURRENT_SITE    ==  $current_domain )
                        {
                            if ( ! $this->is_valid_global_session() )
                                $this->create_global_session();
                                
                            if ( $this->check_for_trigger() &&  $this->assign_global_to_trigger() )
                                {
                                    header("Location: https://" . $this->trigger_data['domain'] );
                                    exit();   
                                }
                                
                        }
                        else
                        {
                            if ( ! $this->is_valid_global_session() )
                                {
                                    if ( $this->check_for_cookie_trigger()    &&  $this->is_valid_trigger() )
                                        {
                                            $this->set_global_cookie();
                                            $this->remove_trigger_cookie();
                                            
                                            $this->has_set_global_cookie    =   TRUE;
                                        }
                                        else
                                        {
                                            if ( $this->create_trigger() )
                                                {
                                                    header("Location: https://" . $this->untrailingslashit( DOMAIN_CURRENT_SITE ) . '?woogc_trigger=' . $this->_woogc_trigger_id );
                                                    exit();
                                                }
                                        }
                                }
                        
                        }                    
                }
               
            private function is_valid_global_session()
                {
                    if ( ! isset ( $_COOKIE[ $this->global_cookie_name ] )  ||  empty ( $_COOKIE[ $this->global_cookie_name ] ) )
                        return FALSE;
                        
                    $woogc_session_id    =   preg_replace("/[^a-zA-Z0-9]/", "", $_COOKIE[ $this->global_cookie_name ] );
                    
                    $conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
                    if ( $conn->connect_error ) 
                        return FALSE;
                    
                    global $table_prefix;    
                    $sql = sprintf ( "SELECT * FROM " . $table_prefix . "woocommerce_woogc_sessions   
                                    WHERE `woogc_session_key`   = '%s'", $conn->real_escape_string( $woogc_session_id ) );
                    $result = $conn->query( $sql );

                    if ($result->num_rows > 0) 
                        {
                            while( $row = mysqli_fetch_assoc( $result ) ) 
                                {
                                    if ( isset ( $row['woogc_session_key'] )    &&  ! empty ( $row['woogc_session_key'] ) &&    $this->validate_bind_session_key_to_user( $row['user_hash'] ) )
                                        $this->_woogc_session_id    =   $row['woogc_session_key'];
                                }
                        }

                    $conn->close();
                    
                    if ( ! $this->_woogc_session_id )
                        return FALSE;
                        
                    return TRUE;
                    
                }
            
            
            private function create_global_session()
                {
                    $woogc_session_id          =   md5 ( microtime() .rand( 1,999999 ) .   $_SERVER['REMOTE_ADDR'] );
                    $woogc_session_key_expire   =   WOOGC_COOKIE_EXPIRE +   time();
                    
                    $conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
                    if ( $conn->connect_error ) 
                        return FALSE;
                    
                    global $table_prefix;    
                    $sql = sprintf ( "INSERT INTO " . $table_prefix . "woocommerce_woogc_sessions ( `session_key`, `woogc_session_key`, `session_expiry`, `user_hash` )  
                                        VALUES ('', '%s', '%d', '%s' )", $conn->real_escape_string( $woogc_session_id ), $conn->real_escape_string( $woogc_session_key_expire ), $conn->real_escape_string( $this->get_user_hash() ) );
                    if ( $conn->query($sql) === TRUE )
                        {
                            $this->_woogc_session_id    =   $woogc_session_id;
                            $this->set_global_cookie();
                        } 

                    $conn->close();      
                    
                    
                }
                
            
            private function check_for_trigger()
                {
                    if ( isset ( $_GET['woogc_trigger'] )   &&  ! empty ( $_GET['woogc_trigger'] )  )
                        return TRUE;
                        
                    return FALSE;
                }
            
            private function check_for_cookie_trigger()
                {
                     if ( isset ( $_COOKIE[ $this->trigger_cookie_name ] )   &&  ! empty ( $_COOKIE[ $this->trigger_cookie_name ] )  )
                        return TRUE;
                        
                    return FALSE;  
                }
            
            private function is_valid_trigger()
                {
                    $cookie_trigger =   preg_replace("/[^a-zA-Z0-9]/", "", $_COOKIE[ $this->trigger_cookie_name ] );
                        
                    $this->trigger_data =   $this->retrieve_trigger_data( $cookie_trigger );
                    
                    if ( time() > $this->trigger_data['trigger_key_expiry'] )
                        return FALSE;
                    
                    if ( ! $this->validate_bind_session_key_to_user( $this->trigger_data['user_hash'] ) )
                        return FALSE;
                    
                    if( ! empty ( $this->trigger_data['woogc_session_key'] ) )
                        {
                            $this->_woogc_session_id    =   $this->trigger_data['woogc_session_key'];
                            
                            $this->remove_trigger( $this->trigger_data['id'] );
                                
                            return TRUE;
                        }
                    
                    return FALSE;   
                }
                
                
            
            private function create_trigger()
                {
                    $woogc_trigger_id       =   md5 ( microtime() . rand( 1,999999 ) .   $_SERVER['REMOTE_ADDR'] );
                    $woogc_trigger_expire   =   WOOGC_TRIGGER_KEY_EXPIRE +   time();
                    
                    $conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
                    if ( $conn->connect_error ) 
                        return FALSE;
                    
                    $request_uri    =   $_SERVER['HTTP_HOST']; 
                    $pathinfo       =   pathinfo( $_SERVER['REQUEST_URI'] );
                    $request_uri    .=  isset ( $pathinfo['extension'] )    &&  ! empty ( $pathinfo['extension'] )  ?   $_SERVER['REQUEST_URI']     :   $this->trailingslashit ( $_SERVER['REQUEST_URI'] );
                    
                    global $table_prefix;    
                    $sql = sprintf ( "INSERT INTO " . $table_prefix . "woocommerce_woogc_sessions_triggers ( `woogc_session_key`, `trigger_key`, `trigger_key_expiry`, `domain`, `user_hash` )  
                                        VALUES ('', '%s', '%d', '%s', '%s' )", $conn->real_escape_string( $woogc_trigger_id ), $conn->real_escape_string( $woogc_trigger_expire ), $conn->real_escape_string( $request_uri ), $conn->real_escape_string( $this->get_user_hash() ) );
                    if ( $conn->query($sql) === TRUE )
                        {
                            $this->_woogc_trigger_id    =   $woogc_trigger_id;
                            $this->set_trigger_cookie();
                        } 

                    $conn->close();
                    
                    if ( $this->_woogc_trigger_id )
                        return TRUE;
                        
                    return FALSE;
                }
            
            
            private function assign_global_to_trigger()
                {
                    $remote_trigger =   preg_replace("/[^a-zA-Z0-9]/", "", $_GET['woogc_trigger'] );
                    
                    $this->trigger_data =   $this->retrieve_trigger_data( $remote_trigger ); 

                    if ( !  $this->trigger_data )
                        return FALSE;
                    
                    $conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
                    if ( $conn->connect_error ) 
                        return FALSE;
                    
                    global $table_prefix;    
                    $sql = sprintf ( "UPDATE " . $table_prefix . "woocommerce_woogc_sessions_triggers 
                                        SET `woogc_session_key`  =   '%s'
                                        WHERE `id`  =   '%d'", $conn->real_escape_string( $this->_woogc_session_id ), $conn->real_escape_string( $this->trigger_data['id'] ) );
                    if ( $conn->query($sql) !== TRUE )
                        {
                            $conn->close();
                            return FALSE; 
                        } 
                        
                    $conn->close();
                    
                    return TRUE;
                
                }
                
                
            private function retrieve_trigger_data( $remote_trigger )
                {
                    $trigger_data   =   FALSE;
                    
                    $conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
                    if ( $conn->connect_error ) 
                        return FALSE;
                    
                    global $table_prefix;    
                    $sql = sprintf ( "SELECT * FROM " . $table_prefix . "woocommerce_woogc_sessions_triggers 
                                            WHERE  `trigger_key`    =   '%s'", $conn->real_escape_string( $remote_trigger ) );
                    $result = $conn->query( $sql );
                    if ( $result->num_rows > 0 ) 
                        {
                            while( $row = mysqli_fetch_assoc( $result ) ) 
                                {
                                    $trigger_data =   $row;
                                }
                        } 
                
                    $conn->close();
                    
                    return $trigger_data;
                }    
                
                
            private function validate_bind_session_key_to_user( $user_hash )
                {
                    if ( $user_hash !=  $this->get_user_hash() )
                        return FALSE;
                        
                    return TRUE;  
                    
                }
                
            
            private function get_user_hash()
                {
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
                    
                    $ua =   isset($_SERVER['HTTP_USER_AGENT'])  ?   $_SERVER['HTTP_USER_AGENT']   :   '';   
                    if ( defined ( 'WOOGC_SESSION_IGNORE_UA' ) &&  WOOGC_SESSION_IGNORE_UA    === true )
                        $ua =   '';
                    
                    $token_data = array(
                                            'ip'        =>  $ip,
                                            'ua'        =>  $ua
                                        );   
                    
                    return ( md5( serialize ( $token_data ) ) );
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
            
            
            private function cleanup_sessions()
                {
                    $conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
                    if ( $conn->connect_error ) 
                        return FALSE;
                    
                    global $table_prefix;
                    
                    $sql =  "SHOW TABLES LIKE '" . $table_prefix . "woocommerce_woogc_sessions'";
                    $result = $conn->query( $sql );
                    
                    if ( $result->num_rows > 0 )
                        {
                            $sql = sprintf ( "DELETE FROM " . $table_prefix . "woocommerce_woogc_sessions 
                                                    WHERE  `session_expiry`    <   '%d'", $conn->real_escape_string( time() ) );
                            $result = $conn->query( $sql );
                        }
                    
                    $sql =  "SHOW TABLES LIKE '" . $table_prefix . "woocommerce_woogc_sessions_triggers'";
                    $result = $conn->query( $sql );
                    
                    if ( $result->num_rows > 0 )
                        {    
                            $sql = sprintf ( "DELETE FROM " . $table_prefix . "woocommerce_woogc_sessions_triggers 
                                                    WHERE  `trigger_key_expiry`    <   '%d'", $conn->real_escape_string( time() ) );
                            $result = $conn->query( $sql );
                        }
                        
                    $conn->close();
                }
                
                
            private function remove_trigger( $id )
                {
                    $conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
                    if ( $conn->connect_error ) 
                        return FALSE;
                    
                    global $table_prefix;
                        
                    $sql = sprintf ( "DELETE FROM " . $table_prefix . "woocommerce_woogc_sessions_triggers 
                                            WHERE  `id`    =   '%d'", $conn->real_escape_string( $id ) );
                    $result = $conn->query( $sql );
                        
                    $conn->close();      
                    
                }
                    
            
            private function set_global_cookie()
                {
                    $this->set_cookie ( $this->global_cookie_name,  $this->_woogc_session_id, WOOGC_COOKIE_EXPIRE, '/', COOKIE_DOMAIN, $this->set_secure_cookie, TRUE );                    
                }
                
            private function set_trigger_cookie()
                {
                    $this->set_cookie ( $this->trigger_cookie_name,  $this->_woogc_trigger_id, WOOGC_TRIGGER_KEY_EXPIRE, '/', COOKIE_DOMAIN, $this->set_secure_cookie, TRUE );    
                }
            
            private function remove_trigger_cookie()
                {
                    $this->set_cookie ( $this->trigger_cookie_name,  '', -60, '/', COOKIE_DOMAIN, $this->set_secure_cookie, TRUE ); 
                }
            
            private function is_internal_call()
                {
                    if ( isset( $_SERVER['HTTP_USER_AGENT'] )   &&  preg_match( '/WordPress\/[0-9.]+; https?:\/\//s', $_SERVER['HTTP_USER_AGENT'] ) ) 
                        return TRUE;
                    
                    return FALSE;
                       
                }
            
            private function ignore_agent() 
                {
                    $is_boot    =   isset ( $_SERVER['HTTP_USER_AGENT'] ) && preg_match('/bot|crawl|slurp|spider|mediapartners|gtmetrix|google|yahoo|yandex|facebook|bingbot|msnbot|chrome-lighthouse|curl|Redirect\sChecker/i', $_SERVER['HTTP_USER_AGENT']) ?   TRUE    :   FALSE;
                    
                    return $is_boot;
                }
                
            private function ignore_uri() 
                {
                    $regex_pattern  =   '^\/wp-json\/?|\/wp-cron\.php|\/\?wc-api=wc_stripe|.*\.php(*SKIP)(*FAIL)|.*\.[\w]+';
                    if ( defined ( 'WOOGC_LOADER_IGNORE_URI' )  &&  is_string ( WOOGC_LOADER_IGNORE_URI )   &&  ! empty ( WOOGC_LOADER_IGNORE_URI ) )
                        $regex_pattern  .=  '|' .   WOOGC_LOADER_IGNORE_URI;
                        
                    $is_uri    =   isset ( $_SERVER['REQUEST_URI'] ) && preg_match('/' . $regex_pattern  .'/i', $_SERVER['REQUEST_URI']) ?   TRUE    :   FALSE;
                    
                    return $is_uri;
                }
            
            private function trailingslashit( $string ) 
                {
                    return $this->untrailingslashit( $string ) . '/';
                }

                    
            private function untrailingslashit( $string )
                {
                    return rtrim( $string, '/\\' );   
                }
            
        }
        
    global $WooGC_loader;
    $WooGC_loader   =   new WooGC_loader();
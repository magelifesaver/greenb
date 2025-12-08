<?php


use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Handle data for the current customers session.
 * Implements the WC_Session abstract class.
 *
 * From 2.5 this uses a custom table for session storage. Based on https://github.com/kloon/woocommerce-large-sessions.
 *
 * @class    WC_Session_Handler
 * @version  2.5.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WooGC_WC_Session_Handler extends WC_Session_Handler {

    /** @var string cookie name */
    protected $_cookie;

    /** @var string session due to expire timestamp */
    protected $_session_expiring;

    /** @var string session expiration timestamp */
    protected $_session_expiration;

    /** $var bool Bool based on whether a cookie exists **/
    protected $_has_cookie = false;

    /** @var string Custom session table name */
    protected $_table;
    
    
    private $_woogc_use_session     =   TRUE;
    
    private $_woogc_session         =   FALSE;
    
    private $_woogc_session_refresh =   FALSE;
    
    private $_woogc_session_id;
    
    private $_woogc_cookies         =   array();
    
    private $_woogc_do_sync         =   FALSE;
        
    private $_woogc_table;

    /**
     * Constructor for the session class.
     */
    public function __construct() {
        
        parent::__construct();
        
        $this->_woogc_cookies['cookie']            =   'woogc_session';
        $this->_woogc_cookies['cookie_trigger']    =   'woogc_sync_run';
        $this->_woogc_table                         =   $GLOBALS['wpdb']->base_prefix . 'woocommerce_woogc_sessions';
        
        $options    =   WooGC_Functions::get_options();
        global $blog_id;
        
        if ( isset ( $options['use_global_cart_for_sites'][$blog_id] )    &&  $options['use_global_cart_for_sites'][$blog_id] == 'no' )
            $this->_woogc_use_session  =   FALSE;
        
    }
    

    /**
     * Setup cookie and customer ID.
     *
     * @since 3.6.0
     */
    public function init_session_cookie() {
        $cookie = $this->get_session_cookie();
        
        if ( $cookie ) {
            $this->_customer_id        = $cookie[0];
            $this->_session_expiration = $cookie[1];
            $this->_session_expiring   = $cookie[2];
            $this->_has_cookie         = true;
            $this->_data               = $this->get_session_data();

            // Update session if its close to expiring.
            if ( time() > $this->_session_expiring ) {
                $this->set_session_expiration();
                $this->update_session_timestamp( $this->_customer_id, $this->_session_expiration );
            }
                        
        } else {
            $this->set_session_expiration();
            $this->_customer_id = $this->generate_customer_id();
            $this->_data        = $this->get_session_data();
        }
        
        if ( $this->_woogc_use_session )
            $this->woogc_open_session();
    }

    /**
     * Sets the session cookie on-demand (usually after adding an item to the cart).
     *
     * Since the cookie name (as of 2.1) is prepended with wp, cache systems like batcache will not cache pages when set.
     *
     * Warning: Cookies will only be set if this is called before the headers are sent.
     */
    public function set_customer_session_cookie( $set ) {
        if ( $set ) {
            
            parent::set_customer_session_cookie( $set );
                        
            if ( $this->_woogc_use_session )
                {            
                    //save the woogc sesion if created in this load
                    if ( $this->_woogc_session  === FALSE )
                        {
                            $this->woogc_insert_session();
                            $this->_woogc_session       =   $this->woogc_get_session( $this->_woogc_session_id );
                        }
                        
                    if ( ! $this->has_woogc_session()   ||  $this->_woogc_session_refresh   === TRUE )
                        $this->woogc_set_cookies();
                }
        }
    }
 
    
    function woogc_open_session()
        {
            if ( $this->has_woogc_session() )
                {
                    $this->_woogc_session_id    =   preg_replace("/[^a-zA-Z0-9]/", "", $_COOKIE[ $this->_woogc_cookies['cookie'] ] );   
                    $this->_woogc_session       =   $this->woogc_get_session( $this->_woogc_session_id );
                        
                    if ( $this->_woogc_session === FALSE    ||  time() > $this->_woogc_session['session_expiry'] )
                        {
                            $this->woogc_delete_session( $this->_woogc_session_id );
                            $this->_woogc_session           =   FALSE;
                            $this->_woogc_session_refresh   =   TRUE;
                            $this->_woogc_session_id        =   $this->generate_woogc_session();
                            return FALSE;
                        }
                        
                    //check for about to expire
                    if ( ( time() + 60 *60 ) > $this->_woogc_session['session_expiry'] )
                        {
                            $this->woogc_delete_session( $this->_woogc_session_id );
                            $this->_woogc_session           =   FALSE;
                            $this->_woogc_session_refresh   =   TRUE;
                            $this->_woogc_session_id        =   $this->generate_woogc_session();
                            
                            $this->woogc_insert_session();
                            $this->_woogc_session       =   $this->woogc_get_session( $this->_woogc_session_id );
                        }
                        
                    //if ( ! empty  ( $this->_woogc_session['session_key'] )  &&  $this->session_exists( $this->_customer_id ) )
                    if ( ! empty  ( $this->_woogc_session['session_key'] )  &&  $this->session_exists( $this->_woogc_session['session_key'] ) )
                        $this->_customer_id = $this->_woogc_session['session_key'];
                    $this->_data        = $this->get_session( $this->_customer_id );
                 }
                else
                {
                    //create the session
                    $this->_woogc_session_id    =   $this->generate_woogc_session();
                }    
            
        }
    
    function woogc_set_cookies()
        {
            $this->_woogc_do_sync   =   TRUE;
            wc_setcookie( $this->_woogc_cookies['cookie_trigger'], $this->_woogc_session['trigger_key'], time() + WOOGC_TRIGGER_KEY_EXPIRE, $this->use_secure_cookie() );                                                                
            
            wc_setcookie( $this->_woogc_cookies['cookie'], $this->_woogc_session_id, time() + WOOGC_COOKIE_EXPIRE, $this->use_secure_cookie(), TRUE );
        }
        
        
    public function woogc_get_do_sync_status()
        {
            return  $this->_woogc_do_sync;
        }

    function generate_woogc_session( $woogc_session_key = '' )
        {
            if ( empty ($woogc_session_key ) )
                $woogc_session_key      =   md5 ( microtime() .rand( 1,999999 ) );
                        
            return $woogc_session_key;
        }
      

    /**
     * Return true if the current user has an active session, i.e. a cookie to retrieve values.
     *
     * @return bool
     */
    public function has_session() {
        return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie;
    }
    
    
    public function has_woogc_session() {
        return isset( $_COOKIE[ $this->_woogc_cookies['cookie'] ] );
    }


    /**
     * Generate a unique customer ID for guests, or return user ID if logged in.
     *
     * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
     *
     * @return int|string
     */
    public function generate_customer_id() {
        $customer_id = '';

        if ( empty( $customer_id ) ) {
            require_once ABSPATH . 'wp-includes/class-phpass.php';
            $hasher      = new PasswordHash( 8, false );
            $customer_id = md5( $hasher->get_random_bytes( 32 ) );
        }

        return $customer_id;
    }
    
    public function get_customer_id() {
        
        if ( !empty($this->_customer_id) )
            return $this->_customer_id;
            
        return '';
    }
    
    public function woogc_get_session_id() {
        
        if ( !empty( $this->_woogc_session_id ) )
            return $this->_woogc_session_id;
            
        return '';
    }


    /**
     * Save data.
     */
    public function save_data( $old_session_key = 0 ) {
        // Dirty if something changed - prevents saving nothing new.
        if ( $this->_dirty && $this->has_session() ) {
            
            parent::save_data( $old_session_key );
            
            
            if ( $this->_woogc_use_session )
                {
                    //Save the woogc seession
                    if ( $this->_woogc_session !== FALSE )
                        {
                            //update
                            $this->woogc_update_session_customer_id( $this->_customer_id );
                               
                            return;
                        }
                    
                    $this->woogc_insert_session();
                }
             
        }
    }
    
        
    function woogc_insert_session()
        {
            global $wpdb;
            
            $woogc_trigger_key      =   substr( md5 ( microtime() .rand( 1,999999 ) ), 0 , 12 );
            
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
            
            // Create the token
            $token_data = array(
                                    'ip'        =>  $ip,
                                    'ua'        =>  $ua
                                );
            $session_hash       = wp_hash( serialize( $token_data ) );
            
            //store the session key
            $mysql_query    =   $wpdb->prepare(
                                                    "INSERT INTO {$this->_woogc_table} ( `session_key`, `woogc_session_key`, `session_expiry`, `trigger_key`, `trigger_key_expiry`, `trigger_user_hash` ) VALUES ( %s, %s, %s, %s, %s, %s )",
                                                    $this->_customer_id,
                                                    $this->_woogc_session_id,
                                                    time() + WOOGC_COOKIE_EXPIRE,
                                                    $woogc_trigger_key,
                                                    time() + WOOGC_TRIGGER_KEY_EXPIRE,
                                                    $session_hash );
            $wpdb->query ( $mysql_query );   
        }
        

        
    /**
     * Gets a cache prefix. This is used in session names so the entire cache can be invalidated with 1 function call.
     *
     * @return string
     */
    private function get_cache_prefix() {
        return WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP );
    }
    
        
    /**
     * Destroy all session data.
     */
    public function destroy_session() {
        parent::destroy_session();
        
        if ( $this->_woogc_use_session )
            $this->woogc_update_session_customer_id( '' );
    }



    /**
     * Cleanup sessions.
     */
    public function cleanup_sessions() {
        global $wpdb;

        parent::cleanup_sessions();
        
        $wpdb->query( $wpdb->prepare( "DELETE FROM $this->_woogc_table WHERE session_expiry < %d", time() + WOOGC_COOKIE_EXPIRE ) );
        
    }

    /**
     * Returns the session.
     *
     * @param string $customer_id
     * @param mixed $default
     * @return string|array
     */
    public function get_session( $customer_id, $default = false ) {
        global $wpdb;

        if ( Constants::is_defined( 'WP_SETUP_CONFIG' ) ) {
            return false;
        }

        $value  =   false;
        
        if ( false === $value ) {
            $value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ) ); // @codingStandardsIgnoreLine.

            if ( is_null( $value ) ) {
                $value = $default;
            }

            $cache_duration = $this->_session_expiration - time();
            if ( 0 < $cache_duration ) {
                wp_cache_add( $this->get_cache_prefix() . $customer_id, $value, WC_SESSION_CACHE_GROUP, $cache_duration );
            }
        }

        return maybe_unserialize( $value );
    }
    
    
    function session_exists( $customer_id )
        {
            global $wpdb;   
            
            $value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ) );
            
            if ( is_null( $value ) )
                return FALSE;
                
            return TRUE;
        }
        
    
    public function woogc_get_session ( $session_id, $default = false )
        {
            global $wpdb;

            $data = $wpdb->get_row( $wpdb->prepare( "SELECT *  FROM {$this->_woogc_table} WHERE woogc_session_key = %s", $session_id ), ARRAY_A );

            if ( is_null ( $data ) )
                return FALSE;
                
            return $data;
            
        }
        
   
    
    public function woogc_delete_session( $_woogc_session_id ) {
        global $wpdb;

        $wpdb->delete(
            $this->_woogc_table,
            array(
                'woogc_session_key' => $_woogc_session_id,
            )
        );
    }
    
    
    public function woogc_update_session_customer_id( $customer_id  =   ''  )
        {
            global $wpdb;

            $wpdb->update(
                $this->_woogc_table,
                array(
                    'session_key' => $customer_id,
                ),
                array(
                    'woogc_session_key' => $this->_woogc_session_id,
                )
            );   
            
            
        }

}

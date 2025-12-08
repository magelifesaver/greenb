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
    private $_woogc_session_id;
    private $_woogc_global_cookie   =   'woogc_session';
    private $_woogc_table;

	/**
	 * Constructor for the session class.
	 */
    public function __construct() {
        
        parent::__construct();

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
            // Customer ID will be an MD5 hash id this is a guest session.
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
            {
                global $WooGC_loader; 
                if ( $this->woogc_check_global_cookie() )
                    $this->_woogc_session_id    =   preg_replace("/[^a-zA-Z0-9]/", "", $_COOKIE[ $this->_woogc_global_cookie ] );
                else if ( is_object ( $WooGC_loader )   &&  isset ( $WooGC_loader->has_set_global_cookie ) )
                    $this->_woogc_session_id    =   $WooGC_loader->_woogc_session_id;

                $this->_woogc_session       =   $this->woogc_get_session( $this->_woogc_session_id );
 
                if ( ! empty  ( $this->_woogc_session['session_key'] ) )
                    {
                        $this->_customer_id = $this->_woogc_session['session_key'];
                        $this->_data        = $this->get_session( $this->_customer_id );
                    }
                if ( empty  ( $this->_woogc_session['session_key'] )    &&  $cookie &&  ! empty ( $this->_customer_id ) )
                    $this->woogc_update_session_customer_id( $this->_customer_id );
            }
    }

    
    public function woogc_check_global_cookie() {
        return isset( $_COOKIE[ $this->_woogc_global_cookie ] );
    }

 
    /**
     * Generate a unique customer ID for guests, or return user ID if logged in.
     *
     * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
     *
     * @return string
     */
    public function generate_customer_id() {
        $customer_id = '';

        if ( empty( $customer_id ) ) {
            require_once ABSPATH . 'wp-includes/class-phpass.php';
            $hasher      = new PasswordHash( 8, false );
            $customer_id = 't_' . substr( md5( $hasher->get_random_bytes( 32 ) ), 2 );
        }

        return $customer_id;
    }

	/**
	 * Save data.
	 */
    public function save_data( $old_session_key = 0 ) {
        // Dirty if something changed - prevents saving nothing new.
        if ( $this->_dirty && $this->has_session() ) {
            
            parent::save_data( $old_session_key );

            if ( $this->_woogc_use_session  &&  $this->_woogc_session !== FALSE )
                $this->woogc_update_session_customer_id( $this->_customer_id );             
        }
    }
        
        
	/**
	 * Destroy all session data.
	 */
    public function destroy_session() {
        
        $this->delete_session( $this->_customer_id );
        
        //delete the session from other shops
        global $WooGC, $blog_id ;
        
        $sites  =   $WooGC->functions->get_gc_sites( TRUE );
        foreach( $sites  as  $site )
            {
                if ( isset ( $options['use_global_cart_for_sites'][$site->blog_id] )    &&  $options['use_global_cart_for_sites'][$site->blog_id] == 'no' )
                    continue;
                if ( apply_filters( 'woogc/disable_global_cart',     FALSE,  $site->blog_id ) !== FALSE )
                    continue;
                if ( $blog_id    ==  $site->blog_id ) 
                    continue;
                    
                switch_to_blog( $site->blog_id );
                
                $this->delete_session( $this->_customer_id );
                    
                restore_current_blog();
            }
        
        if ( $this->_woogc_use_session )
            $this->woogc_update_session_customer_id( '' );
            
        $this->forget_session();
        
    }



	/**
	 * Cleanup sessions.
	 */
    public function cleanup_sessions() {
        
        parent::cleanup_sessions();
        
        global $wpdb;
        
        $wpdb->query( $wpdb->prepare( "DELETE FROM $this->_woogc_table WHERE session_expiry < %d", time() + WOOGC_COOKIE_EXPIRE ) );
        
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

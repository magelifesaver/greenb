<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_synchronization_screen 
        {
            var $functions;
            
            function __construct()
                {
                    //SSO sessions are expiring after 20 secconds
                    define('WOOGC_TRIGGER_KEY_EXPIRE',          20 );
                    
                    //WooGC Global Cart cookie holder
                    define('WOOGC_COOKIE_EXPIRE',         60 * 24 * 60 * 60);
                    
                    $this->functions    =   new WooGC_Functions();
                    
                    add_action( 'wp_footer',                            array( $this, 'on_action_wp_footer') );
                    add_action( 'admin_footer',                         array( $this, 'on_action_wp_footer') );
                    add_action( 'login_footer',                         array( $this, 'on_action_wp_footer') );
                
                    //after item add to cart through AJAX make sure other blogs know about the session id
                    add_action( 'woocommerce_add_to_cart_fragments',    array( $this, 'on_action_woocommerce_add_to_cart_fragments'));
                }
                
                
            function on_action_woocommerce_add_to_cart_fragments( $mini_cart )
                {
                    return $mini_cart;
                    
                    //only when doing AJAX
                    if ( ! defined( 'DOING_AJAX' ) )
                        return $mini_cart;
                    
                    ob_start(); 
                    ?>
                    <script type='text/javascript'> WooGC_Sync.init(); </script>
                    <?php
                    $html   =   ob_get_contents();
                    ob_end_clean();
                    
                    $mini_cart['div.widget_shopping_cart_content'] .=   $html;
                    
                    return $mini_cart;
                       
                }
                
            
            /**
            * Trigger on WordPress wp_footer action
            * Output front side JavaScript code for syncronisation
            * 
            */
            function on_action_wp_footer()
                {
                    //The options may changed, ensure this code still has to run
                    $options        =   $this->functions->get_options();
                    if ( $options['synchronization_type']   !=  'screen' )
                        return;
                       
                    //clear expired triggers
                    $this->clear_expired_triggers();
                        
                    if ( ! $this->functions->is_plugin_active( 'woocommerce/woocommerce.php') )
                        return;
                    
                    global $blog_id;
                                       
                    $sync_directory     =   WOOGC_URL   .   '/sync';
                       
                    $site_home  =   site_url();
                    $site_home  =   str_replace(array('http://', 'https://'), "", $site_home);
                    $site_home  =   trim($site_home, '/');
                      
                    $sync_directory_url     =   str_replace(array('http://', 'https://'), "", $sync_directory);
                    $sync_directory_url     =   str_replace($site_home, "", $sync_directory_url);
                    $sync_directory_url     =   apply_filters( 'woogc/sync_directory_url', $sync_directory_url );
                    
                    ?>
                    
                    
                    <div id="woogc_sync_wrapper" style="display: none"></div>
                    <script type='text/javascript'>
                    /* <![CDATA[ */
                    var WooGC_Sync_Url      =    '<?php echo $sync_directory_url ?>';
                    var WooGC_Sites = [<?php
                                                    
                            $first  =   TRUE;
                                                    
                            $processed_domains  =   array();
                            $blog_details   =   get_blog_details( $blog_id );
                            
                            //ignore current domain
                            $processed_domains[]    =   WooGC_get_domain( $blog_details->domain );
                            
                            $options    =   $this->functions->get_options();
                            
                            $sites      =   $this->functions->get_gc_sites( TRUE );
                            
                            $sites_ids  =   array();
                            foreach($sites  as  $site)
                                {
                                    if ( isset ( $options['use_global_cart_for_sites'][$site->blog_id] )    &&  $options['use_global_cart_for_sites'][$site->blog_id] == 'no' )
                                        continue;
                                        
                                    //check if the globalcart is disabled for site
                                    if ( apply_filters( 'woogc/disable_global_cart',     FALSE,  $site->blog_id ) === FALSE )
                                        $sites_ids[]    =   $site->blog_id;   
                                }
                            
                            $allowed_gc_sites   =   apply_filters('woogc/global_cart/sites', $sites_ids);

                            $disabled_gc_sites  =   array();

                            foreach( $sites  as  $site )
                                {
                                    
                                    //ignore the current site
                                    if( $site->blog_id   ==  $blog_id )
                                        {
                                            if  ( !in_array($blog_id, $allowed_gc_sites)) 
                                                {   
                                                    $disabled_gc_sites[]    =   $site->blog_id ;   
                                                }
                                            continue;
                                        }
                                        
                                    //no need to set for subfolder domains
                                    if($site->path  !=  '/')
                                        continue;
                                        
                                    $domain_root    =   WooGC_get_domain( $site->domain );
                                    
                                    //check if there is a shop that using the domain root. 
                                    $found  =   FALSE;
                                    foreach( $sites  as  $site_to_check )
                                        {
                                            if  ( ! in_array( $site_to_check->blog_id, $allowed_gc_sites ))
                                                continue;     
                                            
                                            if ( $site_to_check->blog_id    ==  $site->blog_id )
                                                continue;
                                                
                                            if ( $domain_root   ==  $site_to_check->domain )
                                                {
                                                    $found  =   TRUE;
                                                    break;
                                                }
                                        }
                                    if ( $found  === TRUE )
                                        continue;    
                                    
                                        
                                    //subdomain check  
                                    if( is_subdomain_install() )
                                        {
                                            $found  =   FALSE;
                                            
                                            foreach($processed_domains  as  $processed_domain)
                                                {
                                                    if (strpos($domain_root, "." . $processed_domain)    !== FALSE)
                                                        {
                                                            $found  =   TRUE;
                                                            break;   
                                                        }
                                                }
                                                
                                            if ( $found  === TRUE )
                                                continue;
                                        }
                                        
                                    //if domain already processed continue
                                    if( in_array( $domain_root, $processed_domains ) )
                                        continue;
                                    
                                    $processed_domains[]    =   $domain_root;
                                    
                                    if  ( in_array( $site->blog_id, $allowed_gc_sites )) 
                                        {
                                            if ( !$first )
                                                echo ', ';
                                            echo "'//" . $site->domain . "'";

                                            $first  =   FALSE;
                                        }
                                        else
                                        {
                                            $disabled_gc_sites[]    =   $site->blog_id ;   
                                        }
                                }
                              
                        ?>];
                    var WooGC_sd    =   <?php  echo ( is_subdomain_install() ? 'true' : 'false' ); ?>;
                    <?php            
  
                        //output JavaScript variable for POST action to catch on specific methods
                        $WooGC_on_PostVars  =   apply_filters('woogc/sync/on_post_vars', array());
                        if(is_array($WooGC_on_PostVars) &&  count($WooGC_on_PostVars) > 0)
                            {
                                ?>
                                var WooGC_on_PostVars   =   [<?php  
                                    
                                    $first = TRUE;
                                    foreach ($WooGC_on_PostVars as $key =>  $value)
                                        {
                                            if($first === TRUE)
                                                $first  =   FALSE;
                                                else
                                                echo ", ";
                                                
                                            echo '"' . $value . '"';    
                                        }
                                
                                 ?>];
                                <?php
                            }
                            else
                            {
                                ?>
                                var WooGC_on_PostVars   =   [];
                                <?php   
                            }
                            
                    ?>
                    /* ]]> */
                    </script>
                    <script type='text/javascript' src='<?php echo str_replace(array('http:', 'https:'), "", WOOGC_URL) ?>/js/woogc-sync.js?ver=1.1'></script>
    
                    <?php                    
                     
      
                }
                
                
                
            function clear_expired_triggers()
                {
                    global $wpdb;
                    
                    $mysql_query    =   "SHOW tables LIKE '"  .   $wpdb->base_prefix . "woocommerce_woogc_sessions'";
                    $found_table    =   $wpdb->get_var( $mysql_query );
                    if ( ! empty ( $found_table ) )
                        {                            
                            $mysql_query    =   "UPDATE "  .   $wpdb->base_prefix . 'woocommerce_woogc_sessions' . " 
                                                        SET trigger_key =   '', trigger_key_expiry = '', trigger_user_hash = ''
                                                        WHERE " . time() . " > trigger_key_expiry";
                            $wpdb->get_results( $mysql_query );
                        }
                }   
            
        }
    new WooGC_synchronization_screen();    
        
?>
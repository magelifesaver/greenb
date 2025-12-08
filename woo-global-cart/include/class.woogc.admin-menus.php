<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_admin_menus 
        {
            
            var $functions;
                                    
            function __construct()
                {
                    
                    $this->functions    =   new WooGC_Functions();
                    
                    add_action( 'plugins_loaded',                       array( $this, '_init') );
                    
                }
                
                
            function _init()
                {
                    /** Load WordPress Administration APIs */
                    require_once( ABSPATH . 'wp-admin/includes/admin.php' );
                    
                    include_once(WOOGC_PATH . '/include/admin/class-woogc-admin-network-orders.php');
                                
                    add_action( 'network_admin_menu',   array($this, 'network_admin_menu') );
                
                
                    if ( apply_filters( 'woogc/show_network_orders', false ) ) 
                        {
                            add_action( 'admin_menu', array( $this, 'addons_menu' ), 70 );
                        }
                
                    add_filter('set-screen-option',     array($this, 'set_screen_options'), 10, 3);
                    
                    add_action('current_screen',        array($this, 'current_screen'), -1);
                }
            
            
            public function addons_menu() 
                {
                    
                    $menus_hooks    =   array();

                    $menus_hooks[] =    add_submenu_page( 'woocommerce', 'Network Orders', 'Network Orders', 'manage_woocommerce', 'woogc-woocommerce-orders', array( $this, 'orders_interface' ),2 );
                    
                    foreach($menus_hooks    as  $menus_hook)
                        {
                            add_action('load-' . $menus_hook , array($this, 'load_dependencies'));
                            add_action('load-' . $menus_hook , array($this, 'admin_notices'));
                            add_action('load-' . $menus_hook , array($this, 'screen_options'));
                            
                            add_action('admin_print_styles-' . $menus_hook , array($this, 'admin_print_styles'));
                            add_action('admin_print_scripts-' . $menus_hook , array($this, 'admin_print_scripts'));
                        }
                }
                
            
            function network_admin_menu()
                {
                    
                    $menus_hooks    =   array();
                    
                    add_menu_page( __( 'WooCommerce', 'woocommerce' ), __( 'WooCommerce', 'woocommerce' ), 'manage_woocommerce', 'woogc-woocommerce-orders', null, null, '50' );
                    $menus_hooks[] =    add_submenu_page( 'woogc-woocommerce-orders', __( 'Orders', 'woocommerce' ), __( 'Orders', 'woocommerce' ), 'manage_product_terms', 'woogc-woocommerce-orders', array($this, 'orders_interface') ); 
                    $menus_hooks[] =    add_submenu_page( 'woogc-woocommerce-orders', __( 'Reports', 'woocommerce' ), __( 'Reports', 'woocommerce' ), 'manage_product_terms', 'woogc-woocommerce-reports', array($this, 'reports_interface') ); 
                    
                    
                    foreach($menus_hooks    as  $menus_hook)
                        {
                            add_action('load-' . $menus_hook , array($this, 'load_dependencies'));
                            add_action('load-' . $menus_hook , array($this, 'admin_notices'));
                            add_action('load-' . $menus_hook , array($this, 'screen_options'));
                            
                            add_action('admin_print_styles-' . $menus_hook , array($this, 'admin_print_styles'));
                            add_action('admin_print_scripts-' . $menus_hook , array($this, 'admin_print_scripts'));
                        }
                    
                }
                
                
            function load_dependencies()
                {

                }
                
            function admin_notices()
                {
                    global $WOO_SL_messages;
            
                    if(!is_array($WOO_SL_messages) || count($WOO_SL_messages) < 1)
                        return;
                    
                    foreach($WOO_SL_messages    as $message_data) 
                        {
                            echo "<div id='notice' class='". $message_data['status'] ." fade'><p>". $message_data['message'] ."</p></div>";                            
                        }

                }
                  
            function admin_print_styles()
                {
                    
                    $screen    = get_current_screen();
                    $screen_id = $screen ? $screen->id : '';
                    
                    $WC_url     =   plugins_url() . '/woocommerce';
                    wp_enqueue_style( 'woocommerce_admin_styles', $WC_url . '/assets/css/admin.css', array() );
                    
                    if ( in_array( $screen_id, apply_filters( 'woogc/woocommerce_reports_screen_ids', array( 'woocommerce_page_woogc-woocommerce-reports-network' ) ) ) ) {
                        wp_register_style( 'jquery-ui-style', $WC_url . '/assets/css/jquery-ui/jquery-ui.min.css', array(), WC_VERSION );
                        wp_enqueue_style( 'jquery-ui-style' );
                    }
                }
                
            function admin_print_scripts()
                {
                    $screen       = get_current_screen();
                    $screen_id    = $screen ? $screen->id : '';
                    $suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                    
                    $WC_url     =   plugins_url() . '/woocommerce';
                    
                    wp_register_script( 'woocommerce_admin', $WC_url . '/assets/js/admin/woocommerce_admin.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core' ) );
                    wp_enqueue_script('woocommerce_admin');
                    
                    // Reports Pages.
                    if ( in_array( $screen_id, apply_filters( 'woogc/woocommerce_reports_screen_ids', array( 'woocommerce_page_woogc-woocommerce-reports-network' ) ) ) ) {
                        wp_register_script( 'wc-reports', $WC_url . '/assets/js/admin/reports' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), WC_VERSION );
                        
                        wp_register_script( 'flot', $WC_url . '/assets/js/jquery-flot/jquery.flot' . $suffix . '.js', array( 'jquery' ), WC_VERSION );
                        wp_register_script( 'flot-resize', $WC_url . '/assets/js/jquery-flot/jquery.flot.resize' . $suffix . '.js', array( 'jquery', 'flot' ), WC_VERSION );
                        wp_register_script( 'flot-time', $WC_url . '/assets/js/jquery-flot/jquery.flot.time' . $suffix . '.js', array( 'jquery', 'flot' ), WC_VERSION );
                        wp_register_script( 'flot-pie', $WC_url . '/assets/js/jquery-flot/jquery.flot.pie' . $suffix . '.js', array( 'jquery', 'flot' ), WC_VERSION );
                        wp_register_script( 'flot-stack', $WC_url . '/assets/js/jquery-flot/jquery.flot.stack' . $suffix . '.js', array( 'jquery', 'flot' ), WC_VERSION );
                        
                        wp_enqueue_script( 'wc-reports' );
                        wp_enqueue_script( 'flot' );
                        wp_enqueue_script( 'flot-resize' );
                        wp_enqueue_script( 'flot-time' );
                        wp_enqueue_script( 'flot-pie' );
                        wp_enqueue_script( 'flot-stack' );
                    }
                    
                    wp_register_script( 'select2', $WC_url . '/assets/js/select2/select2.full.js', array( 'jquery' ) );
                    wp_deregister_script( 'wc-enhanced-select' );
                    wp_register_script( 'woogc-enhanced-select', WOOGC_URL . '/js/woogc-enhanced-select.js', array( 'jquery', 'select2' ) );
                    wp_localize_script( 'woogc-enhanced-select', 'wc_enhanced_select_params', array(
                        'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
                        'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
                        'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
                        'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
                        'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
                        'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
                        'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
                        'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
                        'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
                        'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
                        'ajax_url'                  => admin_url( 'admin-ajax.php' ),
                        'search_products_nonce'     => wp_create_nonce( 'search-products' ),
                        'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
                    ) );
                    
                    wp_enqueue_script( 'woogc-enhanced-select' );
                    wp_enqueue_script( 'select2' );
          
                }
              

            function screen_options()
                {
 
                    $screen = get_current_screen();
                 
                    if(is_object($screen) && in_array( $screen->id , array (  'toplevel_page_woogc-woocommerce-orders-network', 'woocommerce_page_woogc-woocommerce-orders' ) ) )
                        {
                            $args = array(
                                'label'     => __('Orders per Page', 'woo-global-cart'),
                                'default'   => 20,
                                'option'    => 'woogc_orders_per_page'
                            );
                            add_screen_option( 'per_page', $args );    
                        }
                 
                }
                
            function set_screen_options($status, $option, $value) 
                {
                    if ( 'woogc_orders_per_page' == $option ) 
                        return $value;
                }
              
            
            function current_screen( $current_screen )
                {
                    if (empty($current_screen)  ||  ! is_object($current_screen))
                        return;
                    
                    if ( ! in_array( $current_screen->id , array (  'toplevel_page_woogc-woocommerce-orders-network', 'woocommerce_page_woogc-woocommerce-orders' ) ))
                        return;
                    
                    global $typenow, $current_screen;
                    
                    $post_type  =   'shop_order';
                    
                    $typenow    =   $post_type;
                    
                    $current_screen->post_type = $post_type;
                       
                }
            
            
            function orders_interface()
                {
                    
                    include_once(WOOGC_PATH . '/include/admin/class-woogc-admin-network-orders.php');     
                    WooGC_Network_Admin_Orders_Table_List::output(); 
                    
                }
            
            function reports_interface()
                {
                    
                    include_once(WOOGC_PATH . '/include/admin/class-woogc-admin-network-reports.php');     
                    WooGC_Network_Admin_Reports::output();    

                }
                
        }

?>
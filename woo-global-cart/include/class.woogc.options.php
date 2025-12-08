<?php

    defined( 'ABSPATH' ) || exit;
    
    class WooGC_options_interface
        {
         
            var $WooGC;
         
            var $licence;
            
            var $current_tab;
         
            function __construct()
                {
                    
                    if ( !is_admin() )
                        return;
                    
                    global $WooGC;
                    $this->WooGC    =   $WooGC;
                    
                    $this->licence          =   $WooGC->licence;
                    
                    $this->current_tab      =   isset($_GET['item'])   ?   preg_replace( '/[^a-zA-Z0-9\-\_$]/m' , "", $_GET['item'] )  :   'license';
                    
                    if (isset($_GET['page']) && $_GET['page'] == 'woogc-options')
                        {
                            add_action( 'init', array($this, 'options_update'), 1 );
                        }

                    add_action( 'network_admin_menu', array($this, 'network_admin_menu') );
                    if(!$this->licence->licence_key_verify())
                        {
                            add_action('admin_notices',         array($this, 'admin_no_key_notices'));
                            add_action('network_admin_notices', array($this, 'admin_no_key_notices'));
                        }
                    
                }

            
            function network_admin_menu()
                {
                    $parent_slug    =   'settings.php';
                        
                    $hookID   = add_submenu_page($parent_slug, 'WooCommerce Global Cart', 'WooCommerce Global Cart', 'manage_options', 'woogc-options', array($this, 'options_interface'));

                    add_action('network_admin_notices'          ,   array($this, 'admin_notices'));
                    add_action('admin_print_styles-' .  $hookID ,   array($this, 'admin_print_styles'));
                    add_action('admin_print_scripts-' . $hookID ,   array($this, 'admin_print_scripts'));
                }
            
                
            function admin_print_scripts()
                {
                    $WC_url     =   plugins_url() . '/woocommerce';
                    
                    wp_register_script( 'woogc-options',  WOOGC_URL . '/js/woogc-options.js', array( 'jquery' ), NULL, true );
                    wp_enqueue_script(  'woogc-options');
                    
                }
                
                
            function admin_print_styles()
                {
                    wp_register_style(  'woogc-options', WOOGC_URL . '/css/woogc-options.css');
                    wp_enqueue_style(   'woogc-options');
                }    
            
                              
            function options_interface()
                {
                    
                    $options    =   $this->WooGC->functions->get_options();
                    
                    ?>
                        <div id="wpgc">
                                <div id="wpgc-header">
                                    <h1><img src="<?php echo WOOGC_URL ?>/images/shopping-icon.png">WooCommerce Global Cart - Network Settings</h1>
                                </div>
                             
                                <h2 class="nav-tab-wrapper">
                                    <?php
                                    
                                        $tabs   =   array ( 
                                                            'license'           =>  __( 'License', 'woo-global-cart' ),
                                                            'general'           =>  __( 'General', 'woo-global-cart' ),
                                                            'sync-type'         =>  __( 'Synchronization Type', 'woo-global-cart' ),
                                                            'cart'              =>  __( 'Cart', 'woo-global-cart' ),
                                                            'checkout'          =>  __( 'Checkout', 'woo-global-cart' ),
                                                            
                                                            'products-sync'     =>  __( 'Products Sync', 'woo-global-cart' ),
                                                            'orders'            =>  __( 'Orders', 'woo-global-cart' ),
                                                            );
                                                            
                                        if ( defined ( 'WOOGC_CALCULATE_SHIPPING_COSTS_EACH_SHOP' ) )
                                            $tabs['shipping']   =   __( 'Shipping', 'woo-global-cart' );
                                                            
                                        foreach ( $tabs as $tab_slug    =>  $tab_title )
                                            {
                                                ?><a href="<?php echo esc_url ( network_admin_url ( 'settings.php?page=woogc-options&item=' . $tab_slug ) ); ?>" class="wph-nav-tab <?php if ( $this->current_tab    === $tab_slug  ) { echo 'wpgc-nav-tab-active '; }  ?>option-data-collection"><?php echo $tab_title ?></a><?php
                                            }                                    
                                    ?>
                                </h2>
                                
                                <div id="wpgc-notices" class="no-wrap"></div>
                                                         
                                <div id="poststuff">
                                
                                    <form id="form_data" class="options checkout_type_<?php echo $options['cart_checkout_type'] ?>"  name="form" method="post">
                                        <table class="form-table">
                                            <tbody>
                                    
                                            <?php
                                            
                                                switch ( $this->current_tab )
                                                    {
                                                        case 'license'   :
                                                                            $this->_html_license();
                                                                            break;
                                                                            
                                                        case 'general'   :
                                                                            $this->_html_general( $options );
                                                                            break;
                                                                            
                                                        case 'sync-type'   :
                                                                            $this->_html_sync_type( $options );
                                                                            break;
                                                                            
                                                        case 'checkout'   :
                                                                            $this->_html_checkout( $options );
                                                                            break;
                                                                            
                                                        case 'orders'   :
                                                                            $this->_html_orders( $options );
                                                                            break;
                                                                            
                                                        case 'products-sync'   :
                                                                            $this->_html_products_sync( $options );
                                                                            break;
                                                                            
                                                        case 'cart'   :
                                                                            $this->_html_cart( $options );
                                                                            break;
                                                                            
                                                        case 'shipping'   :
                                                                            $this->_html_shipping( $options );
                                                                            break;
                                                            
                                                    }
                                                    
                                                do_action('woogc/options/options_html');
                                        
                                            ?>
                                            
                                            </tbody>
                                        </table>
   
                                        <p class="submit">
                                            <input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Settings', 'woo-global-cart') ?>">
                                        </p>
                                    
                                        <?php wp_nonce_field('woogc_form_submit','woogc_form_nonce'); ?>
                                        <input type="hidden" name="woogc_form_submit" value="true" />
                                        
                                    </form>
                                
                                </div>
                                <?php
    
                }
                
            
            /**
            * Output the License HTML
            *     
            */
            private function _html_license()
                {
                    if ( ! $this->licence->licence_key_verify() )
                        include( WOOGC_PATH . 'include/admin/views/html-admin-license.php' );
                        else
                        include( WOOGC_PATH . 'include/admin/views/html-admin-license-deactivate.php' );    
                    ?>
                    <div class="help-box">
                        <p><span class="dashicons dashicons-editor-help"></span> <?php _e( "The license key is crucial for ensuring access to updates, support, and premium features. By entering the license key, the plugin verifies its authenticity with the developer's server, allowing it to function fully.", 'woo-global-cart' );  ?></p>
                        <p><?php _e( 'Once licensed, the plugin receives regular updates, including security patches and new features, which are essential for maintaining compatibility and performance. Additionally, the license grants access to technical support, ensuring users can resolve issues efficiently.', 'woo-global-cart' );  ?></p>
                        <p><?php _e( 'Deactivating the license key, typically used when moving the license to a new site or installation, disable updates and support for previous domain.', 'woo-global-cart' );  ?></p>
                    </div>
                    <?php
                    
                }
                
            
            
            /**
            * Output the general HTML options
            *     
            * @param mixed $options
            */
            private function _html_general( $options )
                {
                    ?>
                                
                        <tr id="use_global_cart_for_sites" valign="top">
                            <th scope="row">
                
                                <div>
                                    <?php
                                    
                                        $sites  =   $this->WooGC->functions->get_gc_sites(  );
                                        foreach( $sites as  $site )
                                            {
                                                ?>
                                                    <p><label>
                                                       <?php echo rtrim ( $site->domain . $site->path , '/' ) ?>  <input name="use_global_cart_for_sites[<?php echo $site->blog_id ?>]" type="checkbox" value="yes" <?php if( ! isset ( $options['use_global_cart_for_sites'][ $site->blog_id ] ) || $options['use_global_cart_for_sites'][ $site->blog_id ] == 'yes' ) { ?>checked="checked"<?php } ?>>
                                                    </label><?php
                                                    
                                                    switch_to_blog( $site->blog_id );
                                                    if( ! $this->WooGC->functions->is_plugin_active( 'woocommerce/woocommerce.php'  ) )
                                                        { ?><br /><span>WooCommerce not available</span> <?php }
                                                    restore_current_blog();
                                                    
                                                    ?></p>
                                                <?php        
                                            }
                                        
                                    ?>
                                </div>
                            </th>
                            <td>
                                <label><?php _e( "Use Global Features for Selected Sites.", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The \"Use Global Features for Selected Sites\" option within the WP Global Cart plugin ensures that the Global Cart functionality is applied exclusively to sites that have been specifically selected and checked through this interface. For the Global Cart to operate correctly, the WooCommerce plugin must be active on the respective site, either as a network-activated plugin or installed and activated locally within the site itself.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "It is essential to verify that the selected site has its <b>Public</b> attribute enabled. This setting determines whether the site is visible and accessible in the interface, ensuring it can be included in the Global Cart network. Without this attribute active, the site will not be displayed in the selection area, and the Global Cart features will be unavailable for that site.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "If the WooCommerce plugin is not available or inactive on a selected site, the Global Cart routines will automatically be disabled for that site, preventing it from t in the shared cart functionality across the network.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "Additionally, the list of sites permitted to run the Global Cart feature can be further controlled and customized using the filter hook:", 'woo-global-cart' ) ?> <a href="https://wpglobalcart.com/documentation/woogc-global_cart-sites/" target="_blank">woogc/global_cart/sites</a>. <?php _e( " This filter allows developers to programmatically manage and restrict which sites have access to the Global Cart, providing a more flexible way to tailor the feature based on specific requirements.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                                                                                                       
                        <?php do_action('woogc/options/options_html/general');  ?>        
                        
                    <?php    
                }
                
                
            
            /**
            * Output the Sync Type HTML options
            *     
            * @param mixed $options
            */
            private function _html_sync_type( $options )
                {
                    ?>
                                
                        <tr id="synchronization_type" valign="top">
                            <th scope="row">
                                <select name="synchronization_type">
                                    <option value="screen" <?php selected('screen', $options['synchronization_type']); ?>><?php _e( "Screen", 'woo-global-cart' ) ?></option>
                                    <option value="headers" <?php selected('headers', $options['synchronization_type']); ?>><?php _e( "Headers", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Synchronization Type", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The \"Synchronization Type\" option in the WP Global Cart plugin determines how data synchronization is handled across selected shops, ensuring a unified and consistent experience for shared features like the global cart.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "There are two synchronization methods available:", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "<b>Screen</b> ( default ) - This method is the simplest to configure and offers broad compatibility across all server environments. Synchronization occurs visibly during user interactions, making it suitable for most setups.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "<b>Headers</b> - A more advanced synchronization method that operates through HTTP headers in the background, making the process seamless and invisible to users. However, it may face compatibility issues with environments using aggressive caching mechanisms, such as Varnish.", 'woo-global-cart' ) ?></p>
                                <br />
                                <p class="help"><?php _e( "The plugin is designed to automatically apply the chosen synchronization type, ensuring smooth data handling without requiring extensive configuration. Carefully select the method that best suits your server setup to maintain optimal performance and data consistency.", 'woo-global-cart' ) ?> <a href="https://wpglobalcart.com/documentation/how-to-setup-the-screen-or-header-synchronization-type/" target="_blank"><span class="dashicons dashicons-editor-help"></span></a></p>
                            </td>
                        </tr>           
                                                                                                       
                        <?php do_action('woogc/options/options_html/sync-type');  ?>        
                        
                    <?php    
                }
                    
            
                
            /**
            * Output the HTML for the checkout settings
            *     
            * @param mixed $options
            */
            private function _html_checkout( $options )
                {
                    ?>
                                
                        <tr id="cart_checkout_type" valign="top">
                            <th scope="row">
                                <select name="cart_checkout_type">
                                    <option value="single_checkout" <?php selected('single_checkout', $options['cart_checkout_type']); ?>><?php _e( "Single Checkout", 'woo-global-cart' ) ?></option>
                                    <option value="each_store" <?php selected('each_store', $options['cart_checkout_type']); ?>><?php _e( "Each Store Checkout", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Checkout Type", 'woo-global-cart' ) ?> </label>
                                <p class="help"><?php _e( "The <b>Single Checkout</b> option allows customers to complete a single, unified checkout process for all items in their cart, regardless of which store each product originates from. In this setup, the total payment is collected in full on the checkout site, simplifying the transaction process for the customer.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "Alternatively, selecting the <b>Each Store Checkout</b> option creates separate checkout sessions for each store contributing products to the cart. This means individual transactions are initiated at each respective store, with payments processed separately for their specific products. Each shop independently handles its own portion of the order, ensuring direct payment collection.", 'woo-global-cart' ) ?></p>
                                <p class="help"><b><?php _e( "Ensure the WooCommerce 'Enable taxes' option for the check-out site, is set accordingly to other shops in the network, to avoid adding or subtracting the value to total. If 'Enable taxes' is active, ensure all Tax rates use identic definition and rates across the shops.", 'woo-global-cart' ) ?></b></p>
                            </td>
                        </tr>
                    
                        <tr id="cart_checkout_location" class="hide _show_on_single_checkout" valign="top">
                            <th scope="row">
                                <select name="cart_checkout_location">
                                    <option value="" <?php selected('', $options['cart_checkout_location']); ?>><?php _e( "Any Site", 'woo-global-cart' ) ?></option>
                                    <?php
                                    
                                        $sites  =   $this->WooGC->functions->get_gc_sites( TRUE );
                                        foreach($sites  as  $site)
                                            {
                                                $blog_details = get_blog_details($site->blog_id);
                                                
                                                ?><option value="<?php echo $site->blog_id ?>" <?php selected($site->blog_id, $options['cart_checkout_location']); ?>><?php echo $blog_details->blogname ?></option><?php
                                            }
                                    
                                    ?>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Cart Checkout location", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The Cart Checkout Location option is applicable when the Checkout Type is set to <b>Single Checkout</b>.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "This setting determines where the customer will be redirected to finalize their order. You can configure it to direct users to a specific site for completing the checkout process, centralizing all transactions on that chosen site. Alternatively, it can be set to allow checkout on any participating site within the network, giving customers the flexibility to complete their purchase wherever they prefer.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "This option is useful for businesses seeking either a centralized checkout hub or a more distributed approach where multiple stores can handle the checkout process. Choose the location setup based on your preferred payment flow and site management structure.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <tr id="cart_checkout_split_orders" class="hide _show_on_single_checkout" valign="top">
                            <th scope="row">
                                <select name="cart_checkout_split_orders">
                                    <option value="no" <?php selected('no', $options['cart_checkout_split_orders']); ?>><?php _e( "No", 'woo-global-cart' ) ?></option>
                                    <option value="yes" <?php selected('yes', $options['cart_checkout_split_orders']); ?>><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Split Order", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The Split Order option is available when the Checkout Type is set to <b>Single Checkout</b>.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "When enabled, this feature automatically generates separate orders in each shop involved in the transaction, creating individual orders for every store contributing products to the main cart. Each split order will contain only the products associated with that specific shop, ensuring a clear separation of items and order management across the network.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "This setup allows each shop to manage its portion of the order independently, including order processing, inventory management, and fulfillment. It may provides better clarity and control for both store owners and customers when dealing with multi-store purchases under a single checkout session.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <?php do_action('woogc/options/options_html/checkout');  ?>
                                                           
                    <?php    
                }
                
            
            
            
            /**
            * Output the Orders HTML options
            *     
            * @param mixed $options
            */
            private function _html_orders( $options )
                {
                    ?>
                                
                        <tr id="use_sequential_order_numbers" valign="top">
                            <th scope="row">
                                <select name="use_sequential_order_numbers">
                                    <option value="no" <?php selected('no', $options['use_sequential_order_numbers']); ?>><?php _e( "No", 'woo-global-cart' ) ?></option>
                                    <option value="yes" <?php selected('yes', $options['use_sequential_order_numbers']); ?>><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Use Sequential Order Numbers", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The Use Sequential Order Numbers option ensures that order IDs are generated in a consecutive, uninterrupted sequence across the entire network, regardless of which shop the order originates from.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "This feature helps maintain a consistent order numbering system, simplifying order tracking, reporting, and bookkeeping, especially in multi-store setups. It is highly recommended when the <b>Cart Checkout Location</b> is configured for a <b>Any Shop</b>, as it prevents gaps or duplicated order numbers when processing sales across multiple stores.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "By enabling this option, you can achieve a more organized and professional order management system, improving clarity for both administrators and customers.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <tr id="use_sequential_order_numbers" valign="top">
                            <th scope="row">
                                
                            </th>
                            <td>
                                
                            </td>
                        </tr>
                        
                        <tr id="enable_order_synchronization" valign="top">
                            <th scope="row">
                                <select name="enable_order_synchronization">
                                    <option value="no" <?php selected('no', $options['enable_order_synchronization']); ?>><?php _e( "No", 'woo-global-cart' ) ?></option>
                                    <option value="yes" <?php selected('yes', $options['enable_order_synchronization']); ?>><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Enable Order Synchronization", 'woo-global-cart' ) ?></label>
                                <p class="help"><i><?php _e( "( recommended to <b>NO</b> )", 'woo-global-cart' ) ?></i></p>
                                <p class="help"><?php _e( "Enable this option to synchronize orders across specific shops in your network. Refer to the bellow \"Order Synchronization for selected Shops\" setting for shop selection and the \"Order Synchronization to selected Shop\" option to designate the target shop for synchronization.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "This feature is generally not required, as network orders are already accessible globally within the user's \"My Account\" section, and admins can view all orders in the WooCommerce Global Orders interface.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "However, this feature can be useful if you need network orders to trigger actions at a specific shop. Order synchronization is done through the REST API, ensuring that shop-specific actions and filters are applied. Unless you have a specific use case for this functionality, it is recommended to keep this option disabled.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <tr id="order_synchronization_consumer_api" valign="top">
                            <th scope="row">
                                
                            </th>
                            <td>
                                <label><?php _e( "Consumer API details", 'woo-global-cart' ) ?></label>
                                <p><input type="text" class="input-text regular-input" name="order_synchronization_consumer_key" value="<?php echo esc_html( $options['order_synchronization_consumer_key'] ) ?>" placeholder="Consumer key"></p>
                                <p><input type="text" class="input-text regular-input" name="order_synchronization_consumer_secret" value="<?php echo esc_html( $options['order_synchronization_consumer_secret'] ) ?>" placeholder="Consumer secret"></p>
                                <p class="help"><?php _e( "When 'Enable Order Synchronization' option is active, the Consumer API details are used to connect to the target shop through the API, where the order will be synchronized.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "An API credential can be generated at the target shop, at WooCommerce > Settings > Advanced > REST API.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <tr id="order_synchronization_for_shops" valign="top">
                            <th scope="row">
                                <?php
                                    
                                        $sites  =   $this->WooGC->functions->get_gc_sites(  );
                                        foreach( $sites as  $site )
                                            {
                                                ?>
                                                    <p><label>
                                                       <?php echo rtrim ( $site->domain . $site->path , '/' ) ?>  <input onclick="WoGC.click_action( 'synchronization_for_shops', this )" name="order_synchronization_for_shops[<?php echo $site->blog_id ?>]" type="checkbox" value="yes" data-shop_id="<?php echo $site->blog_id ?>" <?php if( isset ( $options['order_synchronization_for_shops'][ $site->blog_id ] ) &&  $options['order_synchronization_for_shops'][ $site->blog_id ] == 'yes' &&  $options['order_synchronization_to_shop'] !=  $site->blog_id ) { ?>checked="checked"<?php } ?> <?php if ( $options['order_synchronization_to_shop'] ==  $site->blog_id ) { echo 'disabled="disabled"'; }   ?>>
                                                    </label><?php
                                                    
                                                    switch_to_blog( $site->blog_id );
                                                    if( ! $this->WooGC->functions->is_plugin_active( 'woocommerce/woocommerce.php'  ) )
                                                        { ?><br /><span>WooCommerce not available</span> <?php }
                                                    restore_current_blog();
                                                    
                                                    ?></p>
                                                <?php        
                                            }
                                        
                                    ?>    
                            </th>
                            <td>
                                <label><?php _e( "Order Synchronization for selected Shops", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "When the 'Enable Order Synchronization' option is enabled, orders created on the selected shops will be automatically synchronized to the designated target shop. This ensures that all relevant order data is transferred seamlessly, allowing for centralized management of orders across multiple shops within your network. You can choose which shops to include in this synchronization process, ensuring flexibility in how orders are handled and processed.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "The option ensures that order data remains synchronized. When an order is updated or modified in the origin shop, the changes are automatically reflected in the other connected shop. This ensures consistency of order information across your network, maintaining accurate and up-to-date records in all selected shops.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <tr id="order_synchronization_to_shop" valign="top">
                            <th scope="row">
                                <?php
                                    
                                        $sites  =   $this->WooGC->functions->get_gc_sites(  );
                                        foreach( $sites as  $site )
                                            {
                                                ?>
                                                    <p><label>
                                                       <?php echo rtrim ( $site->domain . $site->path , '/' ) ?>  <input onclick="WoGC.click_action( 'synchronization_to_shop', this )" name="order_synchronization_to_shop" type="radio" value="<?php echo $site->blog_id ?>" data-shop_id="<?php echo $site->blog_id ?>" <?php if ( $options['order_synchronization_to_shop'] == $site->blog_id ) { ?>checked="checked"<?php } ?>>
                                                    </label><?php
                                                    
                                                    switch_to_blog( $site->blog_id );
                                                    if( ! $this->WooGC->functions->is_plugin_active( 'woocommerce/woocommerce.php'  ) )
                                                        { ?><br /><span>WooCommerce not available</span> <?php }
                                                    restore_current_blog();
                                                    
                                                    ?></p>
                                                <?php        
                                            }
                                        
                                    ?>    
                            </th>
                            <td>
                                <label><?php _e( "Order Synchronization to selected Shop", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "When the 'Enable Order Synchronization' option is enabled, any orders created on the designated shops will be automatically synchronized to the designated target shop. This ensures that the target shop receives all relevant order details, keeping the order information consistent across your network. This synchronization process helps centralize order management, making it easier to track and manage orders from multiple shops in one place.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <?php do_action('woogc/options/options_html/orders');  ?>    
                        
                    <?php    
                }
            
            
            
            /**
            * Output the Orders HTML options
            *     
            * @param mixed $options
            */
            private function _html_products_sync( $options )
                {
                    ?>
                        
                        <tr id="enable_product_synchronization" valign="top">
                            <th scope="row">
                                <select name="enable_product_synchronization">
                                    <option value="no" <?php selected('no', $options['enable_product_synchronization']); ?>><?php _e( "No", 'woo-global-cart' ) ?></option>
                                    <option value="yes" <?php selected('yes', $options['enable_product_synchronization']); ?>><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Enable Products Synchronization interface", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "Enabling the Products Synchronization option activates a dedicated tab within the Product Data interface, providing an intuitive way to manage product replication across the network.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "This feature allows you to easily synchronize product details to one or multiple shops, ensuring consistency in product listings across your entire network. It streamlines the process of duplicating products, saving time and reducing the risk of errors when managing multi-store inventories.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "By using this interface, you can control which stores receive the product data, making it ideal for networks where products need to be available across multiple sites with minimal manual effort.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <tr id="product_synchronization_op_type" valign="top">
                            <th scope="row">
                                <select name="product_synchronization_op_type">
                                    <option value="on_update" <?php selected('on_update', $options['product_synchronization_op_type']); ?>><?php _e( "On Product Update", 'woo-global-cart' ) ?></option>
                                    <option value="cron_async" <?php selected('cron_async', $options['product_synchronization_op_type']); ?>><?php _e( "Asynchronous Cron", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Synchronization operation type", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The Synchronization Operation Type option allows you to control how product synchronization updates are processed across your network.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "<b>On Product Update (Default)</b>: Synchronization begins immediately when a product is updated, ensuring real-time data consistency across selected shops. This method is straightforward and suitable for most environments where instant updates are preferred.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "<b>Asynchronous Cron</b>: This method schedules the synchronization as a delayed background task using the WordPress Cron system. It helps distribute server load more effectively, minimizing performance impact during bulk updates or on high-traffic websites.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <?php do_action('woogc/options/options_html/product-sync');  ?>
                        
                    <?php    
                }
            
            
            
            
            /**
            * Output the Cart HTML options
            *     
            * @param mixed $options
            */
            private function _html_cart( $options )
                {
                    ?>
                        
                        <tr id="replace_cart_product_with_origin_version" valign="top">
                            <th scope="row">
                                <select name="replace_cart_product_with_origin_version">
                                    <option value="no" <?php selected('no', $options['replace_cart_product_with_origin_version']); ?>><?php _e( "No", 'woo-global-cart' ) ?></option>
                                    <option value="yes" <?php selected('yes', $options['replace_cart_product_with_origin_version']); ?>><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Replace the Cart Products with origin version", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The Replace the Cart Products with Origin Version option controls how synchronized products are handled within the shopping cart.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "When a product in the cart is a synchronized item from another shop, enabling this option will replace it with the original version of the product from its source shop. This ensures that the cart reflects the exact product details and version from the store where the item originates.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <tr id="replace_cart_product_with_local_version" valign="top">
                            <th scope="row">
                                <select name="replace_cart_product_with_local_version">
                                    <option value="no" <?php selected('no', $options['replace_cart_product_with_local_version']); ?>><?php _e( "No", 'woo-global-cart' ) ?></option>
                                    <option value="yes" <?php selected('yes', $options['replace_cart_product_with_local_version']); ?>><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Replace the Cart Products with local version", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The Replace the Cart Products with Local Version option ensures that if a product in the cart is also available in the current store, it will be replaced with the local version of the product. The system identifies matching products by comparing their SKU attribute ( or programmatically for any other meta data ), ensuring an accurate substitution process. This option is particularly useful when you want to prioritize local inventory over synchronized products from other shops within the network.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "The substitution only occurs for products of the same type, meaning that the local version must match the original product. This ensures that the cart remains consistent with the store's available offerings while preventing mismatches or errors in product types.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <tr id="show_product_attributes" valign="top">
                            <th scope="row">
                                <select name="show_product_attributes">
                                    <option value="no" <?php selected('no', $options['show_product_attributes']); ?>><?php _e( "No", 'woo-global-cart' ) ?></option>
                                    <option value="yes" <?php selected('yes', $options['show_product_attributes']); ?>><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Filter to Show Product Attributres", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The Filter to Show Product Attributes option controls whether product attributes are displayed on the cart page. If the attributes have not already been shown in the product title, enabling this option will ensure they are listed separately on the cart page for better visibility and clarity.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "This feature is useful for providing additional product details, such as size, color, or other specifications, allowing customers to review key information before completing their purchase. It enhances the shopping experience by ensuring that all relevant product attributes are easily accessible, especially when they aren't included in the title itself.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <?php do_action('woogc/options/options_html/cart');  ?>
                        
                    <?php    
                }
            
            
            
            /**
            * Output the Shipping HTML options
            *     
            * @param mixed $options
            */
            private function _html_shipping( $options )
                {
                    ?>
                        <tr id="calculate_shipping_costs_for_each_shops" valign="top">
                            <th scope="row">
                                <select name="calculate_shipping_costs_for_each_shops">
                                    <option value="no" <?php selected('no', $options['calculate_shipping_costs_for_each_shops']); ?>><?php _e( "No", 'woo-global-cart' ) ?></option>
                                    <option value="yes" <?php selected('yes', $options['calculate_shipping_costs_for_each_shops']); ?>><?php _e( "Yes", 'woo-global-cart' ) ?></option>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Calculate Shipping costs for each Shops", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The Calculate Shipping Costs for Each Shop option enables separate shipping calculations for products from different shops within the same cart. When the cart contains items from multiple shops, shipping costs will be calculated individually for each store. This is particularly useful when customers will receive multiple packages from different shops, as it ensures accurate and specific shipping charges for each item.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "If set to <b>No</b>, the shipping setup from the checkout shop will apply to the entire order, regardless of the number of shops involved.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "Note that an update to the theme's /cart-shipping.php template file is required to fully support this feature. For more information, refer to the", 'woo-global-cart' ) ?> <a target="_blank" href="https://wooglobalcart.com/documentation/update-cart-shipping-template-when-using-calculate-shipping-costs-for-each-shops-option/">Update Cart-Shipping Template</a> <?php _e( "guide", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "Additionally, after changing this option, clearing your browser cache is necessary to ensure the changes take effect properly.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>
                        
                        <tr id="calculate_shipping_costs_for_each_shops__site_base_tax" valign="top">
                            <th scope="row">
                                <select name="calculate_shipping_costs_for_each_shops__site_base_tax">
                                    <option value="" <?php selected('', $options['calculate_shipping_costs_for_each_shops__site_base_tax']); ?>><?php _e( "Disabled - No taxes", 'woo-global-cart' ) ?></option>
                                    <?php
                                    
                                        $sites  =   $this->WooGC->functions->get_gc_sites( TRUE );
                                        foreach($sites  as  $site)
                                            {
                                                $blog_details = get_blog_details($site->blog_id);
                                                
                                                ?><option value="<?php echo $site->blog_id ?>" <?php selected($site->blog_id, $options['calculate_shipping_costs_for_each_shops__site_base_tax']); ?>><?php echo $blog_details->blogname ?></option><?php
                                            }
                                    
                                    ?>
                                </select>
                            </th>
                            <td>
                                <label><?php _e( "Use Global Taxe Rates", 'woo-global-cart' ) ?></label>
                                <p class="help"><?php _e( "The Use Global Tax Rates option ensures consistency in tax calculations across all shops in the network when taxes are enabled. By activating this feature, the same tax rates are applied uniformly across all stores, preventing discrepancies in tax values that could arise from using different tax rates at each shop.", 'woo-global-cart' ) ?></p>
                                <p class="help"><?php _e( "When enabled, the tax rates of the selected shop will be used as the standard for all other shops within the network, ensuring that taxes are calculated consistently and accurately for every transaction, regardless of the store where the product is purchased. This helps maintain clarity and fairness in pricing for customers across the entire network.", 'woo-global-cart' ) ?></p>
                            </td>
                        </tr>    
                        
                        <?php do_action('woogc/options/options_html/shipping');  ?>
                        
                    <?php    
                }
            

            
            /**
            * On interface save, process the data
            * 
            */
            function options_update()
                {
                    if ( $this->current_tab === 'license'  &&  isset($_POST['woogc_licence_form_submit']))
                        {
                            $this->licence_form_submit();
                            return;
                        }
                        
                    if ( isset ( $_POST['woogc_form_submit'] )  &&  wp_verify_nonce($_POST['woogc_form_nonce'],'woogc_form_submit') )
                        {
                            
                            $options        =   $this->WooGC->functions->get_options();
                            
                            global $woogc_interface_messages;
                            
                            switch ( $this->current_tab )
                                {
                                    case 'general':
                                                    $sites  =   $this->WooGC->functions->get_gc_sites( );
                                                    foreach( $sites as  $site )
                                                        {
                                                            $options['use_global_cart_for_sites'][$site->blog_id]   =   isset ( $_POST['use_global_cart_for_sites'][$site->blog_id] ) && $_POST['use_global_cart_for_sites'][$site->blog_id] == 'yes' ?   'yes'   :   'no';       
                                                        }
                                                    break;
                                    
                                    case 'sync-type':
                                                    $options['synchronization_type']                        =   wc_clean( wp_unslash( $_POST['synchronization_type'] ) );
                                                    break;
                                                    
                                    case 'checkout':
                                                    $options['cart_checkout_type']                          =   wc_clean( wp_unslash( $_POST['cart_checkout_type'] ) );
                                                    $options['cart_checkout_location']                      =   wc_clean( wp_unslash( $_POST['cart_checkout_location'] ) );
                                                    $options['cart_checkout_split_orders']                  =   wc_clean( wp_unslash( $_POST['cart_checkout_split_orders'] ) );
                                                    break;
                                                    
                                    case 'orders':
                                                    $options['use_sequential_order_numbers']                    =   wc_clean( wp_unslash( $_POST['use_sequential_order_numbers'] ) );
                                                    
                                                    $options['enable_order_synchronization']                    =   preg_replace( '/[^a-zA-Z0-9\-\_$]/m', '', $_POST['enable_order_synchronization'] );
                                                    $options['order_synchronization_consumer_key']              =   preg_replace( '/[^a-zA-Z0-9\-\_$]/m', '', $_POST['order_synchronization_consumer_key'] );
                                                    $options['order_synchronization_consumer_secret']           =   preg_replace( '/[^a-zA-Z0-9\-\_$]/m', '', $_POST['order_synchronization_consumer_secret'] );
                                                    
                                                    $sites  =   $this->WooGC->functions->get_gc_sites( );
                                                    foreach( $sites as  $site )
                                                        {
                                                            $options['order_synchronization_for_shops'][$site->blog_id]   =   isset ( $_POST['order_synchronization_for_shops'][$site->blog_id] ) && $_POST['order_synchronization_for_shops'][$site->blog_id] == 'yes' ?   'yes'   :   'no';       
                                                        }
                                                    $options['order_synchronization_to_shop']                =   preg_replace( '/[^a-zA-Z0-9\-\_$]/m', '', $_POST['order_synchronization_to_shop'] );
                                                    
                                                    break;
                                    
                                    case 'products-sync':
                                                    $options['enable_product_synchronization']              =   isset ( $_POST['enable_product_synchronization'] )              ?   wc_clean( wp_unslash( $_POST['enable_product_synchronization'] ) )  :   'no';
                                                    $options['product_synchronization_op_type']             =   isset ( $_POST['product_synchronization_op_type'] )             ?   wc_clean( wp_unslash( $_POST['product_synchronization_op_type'] ) )  :   'on_update';
                                                    break;
                                                    
                                    case 'cart':
                                                    $options['replace_cart_product_with_origin_version']    =   isset ( $_POST['replace_cart_product_with_origin_version'] )    ?   wc_clean( wp_unslash( $_POST['replace_cart_product_with_origin_version'] ) )  :   'no';
                                                    $options['replace_cart_product_with_local_version']     =   isset ( $_POST['replace_cart_product_with_local_version'] )     ?   wc_clean( wp_unslash( $_POST['replace_cart_product_with_local_version'] ) )  :   'no';
                                                    $options['show_product_attributes']                     =   wc_clean( wp_unslash( $_POST['show_product_attributes'] ) );
                                                    break;                
                                                    
                                    case 'shipping':
                                                    $options['calculate_shipping_costs_for_each_shops']                 =   isset ( $_POST['calculate_shipping_costs_for_each_shops'] ) ?                   wc_clean( wp_unslash( $_POST['calculate_shipping_costs_for_each_shops'] ) ) : '';
                                                    $options['calculate_shipping_costs_for_each_shops__site_base_tax']  =   isset ( $_POST['calculate_shipping_costs_for_each_shops__site_base_tax'] ) ?    wc_clean( wp_unslash( $_POST['calculate_shipping_costs_for_each_shops__site_base_tax'] ) ) : '';
                                                    
                                                    break;
                                }
 
                                                        
                            $options    =   apply_filters('woogc/options/options_save', $options, $this->current_tab );
                            
                            if ( $options['use_sequential_order_numbers'] ==  'yes' )
                                {
                                    include_once( WOOGC_PATH . '/include/class.woogc.sequential-order-numbers.php');
                                    
                                    WooGC_Sequential_Order_Numbers::network_update_order_numbers();
                                }
                            
                            $this->WooGC->functions->update_options( $options );  
                            
                            if ( $this->current_tab === 'sync-type' )
                                {
                                    if ( $options['synchronization_type']   ==  'screen' )
                                        {
                                            $this->WooGC->functions->remove_tables();
                                            $this->WooGC->functions->create_tables();
                                            
                                            $this->WooGC->functions->wp_config_clean();
                                            
                                            $this->WooGC->functions->copy_mu_files( TRUE );   
                                        }
                                    else if ( $options['synchronization_type']   ==  'headers' )
                                        {
                                            $this->WooGC->functions->remove_tables();
                                            $this->WooGC->functions->create_tables();

                                            $this->WooGC->functions->wp_config_add();
                                            
                                            $this->WooGC->functions->copy_mu_files( TRUE );    
                                        }
                                }
                            
                            wp_redirect( $this->WooGC->functions->current_url() . '&settings_updated=true');              
                        }
            
                }
                  
            function admin_notices()
                {
                    
                    //check for setings updated
                    if ( isset ( $_GET['settings_updated'] )    &&  $_GET['settings_updated']   ==  'true' )  
                        echo "<div class='notice notice-success'><p>". __('Settings Saved', 'woo-global-cart')  ."</p></div>";
                    
                    global $woogc_interface_messages;
            
                    if(!is_array($woogc_interface_messages))
                        return;
                              
                    if(count($woogc_interface_messages) > 0)
                        {
                            foreach ($woogc_interface_messages  as  $message)
                                {
                                    echo "<div class='". $message['type'] ." fade'><p>". $message['text']  ."</p></div>";
                                }
                        }

                }
                  
                        
            
            function admin_no_key_notices()
                {
                    if ( !current_user_can('manage_options'))
                        return;
                    
                    $screen = get_current_screen();
                        
                    if(is_multisite())
                        {
                            if(isset($screen->id) && $screen->id    ==  'settings_page_woogc-options-network')
                                return;
                            ?><div class="error fade"><p><?php _e( "WooCommerce Global Cart plugin is inactive, please enter your", 'woo-global-cart' ) ?> <a href="<?php echo network_admin_url() ?>settings.php?page=woogc-options"><?php _e( "Licence Key", 'woo-global-cart' ) ?></a></p></div><?php
                        }
                }
            
            function licence_form_submit()
                {
                    global $woogc_interface_messages; 
                    
                    //check for de-activation
                    if (isset($_POST['woogc_licence_form_submit']) && isset($_POST['woogc_licence_deactivate']) && wp_verify_nonce($_POST['woogc_license_nonce'],'woogc_licence'))
                        {
                            
                            $licence_data = $this->WooGC->licence->get_licence_data();                        
                            $licence_key = $licence_data['key'];

                            //build the request query
                            $args = array(
                                                'woo_sl_action'         => 'deactivate',
                                                'licence_key'           => $licence_key,
                                                'product_unique_id'     => WOOGC_PRODUCT_ID,
                                                'domain'                => WOOGC_INSTANCE
                                            );
                            $request_uri    = WOOGC_UPDATE_API_URL . '?' . http_build_query( $args , '', '&');
                            $data           = wp_remote_get( $request_uri );
                            
                            if(is_wp_error( $data ) || $data['response']['code'] != 200)
                                {
                                    $woogc_interface_messages[] = array(
                                                                            'type'  =>  'error',
                                                                            'text'  =>  __('There was a problem connecting to ', 'woo-global-cart') . WOOGC_UPDATE_API_URL);
                                    return;  
                                }
                                
                            $response_block = json_decode($data['body']);
                            $response_block = $response_block[count($response_block) - 1];
                            $response = $response_block->message;
                            
                            if(isset($response_block->status))
                                {
                                    //the license is active and the software is active
                                    $woogc_interface_messages[] = array(
                                                                            'type'  =>  'updated',
                                                                            'text'  =>  $response_block->message);
                                                                        
                                    //save the license
                                    $licence_data['key']          = '';
                                    $licence_data['last_check']   = time();
                                    
                                    $this->WooGC->licence->update_licence_data( $licence_data );
                                }
                                else
                                {
                                    $woogc_interface_messages[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'text'  => __('There was a problem with the data block received from ' . WOOGC_UPDATE_API_URL, 'woo-global-cart'));
                                    return;
                                }
                                
                            //redirect
                            $current_url    =   $this->WooGC->functions->current_url();
                            
                            wp_redirect($current_url);
                            
                            die();
                            
                        }   
                    
                    
                    
                    if (isset($_POST['woogc_licence_form_submit']) && wp_verify_nonce($_POST['woogc_license_nonce'],'woogc_licence'))
                        {
                            
                            $licence_key = isset($_POST['licence_key'])? sanitize_key(trim($_POST['licence_key'])) : '';

                            if($licence_key == '')
                                {
                                    $woogc_interface_messages[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'text'  =>  __("Licence Key can't be empty", 'woo-global-cart'));
                                    return;
                                }
                                
                            //build the request query
                            $args = array(
                                                'woo_sl_action'         => 'activate',
                                                'licence_key'           => $licence_key,
                                                'product_unique_id'     => WOOGC_PRODUCT_ID,
                                                'domain'                => WOOGC_INSTANCE
                                            );
                            $request_uri    = WOOGC_UPDATE_API_URL . '?' . http_build_query( $args , '', '&');
                            $data           = wp_remote_get( $request_uri );
                            
                            if(is_wp_error( $data ) || $data['response']['code'] != 200)
                                {
                                    $woogc_interface_messages[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'text'  =>  __('There was a problem connecting to ', 'woo-global-cart') . WOOGC_UPDATE_API_URL);
                                    return;  
                                }
                                
                            $response_block = json_decode($data['body']);
                            //retrieve the last message within the $response_block
                            $response_block = $response_block[count($response_block) - 1];
                            $response = $response_block->message;
                            
                            if(isset($response_block->status))
                                {
                                    if( $response_block->status == 'success' && ( $response_block->status_code == 's100' || $response_block->status_code == 's101' ) )
                                        {
                                            //the license is active and the software is active
                                            $woogc_interface_messages[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'text'  =>  $response_block->message);
                                            
                                            $licence_data = $this->WooGC->licence->get_licence_data();
                                            
                                            //save the license
                                            $licence_data['key']          = $licence_key;
                                            $licence_data['last_check']   = time();
                                            
                                            $this->WooGC->licence->update_licence_data( $licence_data );

                                        }
                                        else
                                        {
                                            $woogc_interface_messages[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'text'  =>  __('There was a problem activating the licence: ', 'woo-global-cart') . $response_block->message);
                                            return;
                                        }   
                                }
                                else
                                {
                                    $woogc_interface_messages[] =   array(  
                                                                                    'type'  =>  'error',
                                                                                    'text'  =>  __('There was a problem with the data block received from ' . WOOGC_UPDATE_API_URL, 'woo-global-cart'));
                                    return;
                                }
                                
                            //redirect
                            $current_url    =   $this->WooGC->functions->current_url();
                            
                            wp_redirect($current_url);
                            
                            die();
                        }   
                    
                }
                         
        }

    
    new WooGC_options_interface();                               

?>
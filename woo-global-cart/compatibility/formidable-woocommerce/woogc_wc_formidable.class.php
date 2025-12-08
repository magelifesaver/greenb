<?php
    
    class WooGC_WC_Formidable extends WC_Formidable
        {
            
            public static function get_instance() {
                // If the single instance hasn't been set, set it now.
                if ( null == self::$instance ) {
                    self::$instance = new self;
                }

                return self::$instance;
            }
            
            
            /**
             * Initialize the plugin.
             */
            private function __construct() {

                if ( ! $this->requirements() ) {
                    add_action( 'admin_notices', array( $this, 'required_plugins_error' ) );
                    if ( ! in_array( 'formidable', $this->errors, true ) ) {
                        $page = FrmAppHelper::get_param( 'page', '', 'get', 'sanitize_text_field' );
                        if ( 'formidable' === $page ) {
                            add_filter( 'frm_message_list', array( $this, 'required_plugins_error' ) );
                        }
                    }
                    return;
                }

                add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

                $dir = WP_PLUGIN_DIR . '/formidable-woocommerce';
                require_once $dir . '/helpers/class-wc-formidable-app-helper.php';
                require_once $dir . '/classes/class-wc-formidable-admin.php';
                require_once $dir . '/classes/class-wc-formidable-product.php'; 
                
                add_action( 'admin_init', array( $this, 'include_updater' ), 1 );

                new WC_Formidable_Admin();
                
                require_once( WOOGC_PATH . '/compatibility/formidable-woocommerce/classes/class-wc-formidable-product.php' );
                new WooGC_WC_Formidable_Product();

                if ( class_exists( 'FrmRegShortcodesController' ) ) {
                    require_once $dir . '/classes/class-wc-formidable-settings.php';
                    new WC_Formidable_Settings();
                }
                
                return;
                
                // Load plugin text domain
                add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

                // get required woo functions
                require_once( WP_PLUGIN_DIR . '/formidable-woocommerce/woo-includes/woo-functions.php' );

                // no sense in doing this if WC & FP aren't active
                if ( is_plugin_active('woocommerce/woocommerce.php') && function_exists( 'frm_forms_autoloader' ) ) {

                    add_action('admin_init', array( $this, 'include_updater' ), 1);

                    // load the classes
                    require_once( WP_PLUGIN_DIR . '/formidable-woocommerce/classes/class-wc-formidable-admin.php' );
                    $WC_Formidable_Admin = new WC_Formidable_Admin();

                    require_once( WP_PLUGIN_DIR . '/formidable-woocommerce/classes/class-wc-formidable-product.php' );
                    require_once( WOOGC_PATH . '/compatibility/formidable-woocommerce/classes/class-wc-formidable-product.php' );
                    $WC_Formidable_Product = new WooGC_WC_Formidable_Product();

                    require_once( WP_PLUGIN_DIR . '/formidable-woocommerce/woocommerce-formidable-functions.php' );

                } else {
                    // add admin notice about plugin requiring other plugins
                    add_action( 'admin_notices', array( $this, 'required_plugins_error' ) );
                }
            }
            
            
            
            
            /**
             * Checks if the system requirements are met.
             *
             * @since    1.11
             *
             * @return bool True if system requirements are met, false if not.
             */
            private function requirements() {
                if ( ! function_exists( 'load_formidable_forms' ) ) {
                    $this->errors[] = 'formidable';
                }

                if ( ! function_exists( 'load_formidable_pro' ) ) {
                    $this->errors[] = 'formidable_pro';
                }

                if ( ! function_exists( 'WC' ) ) {
                    $this->errors[] = 'woocommerce';
                }

                if ( ! empty( $this->errors ) ) {
                    return false;
                }

                return true;
            }
                                                                   
        }

    
?>
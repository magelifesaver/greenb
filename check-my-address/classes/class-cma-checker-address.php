<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('Delivery_Checker_Address')) {
    /**
     * Delivery_Checker_Address
     *
     * @since 1.0
     */
    class Delivery_Checker_Address {
        public static $checked;
        public static $is_shortcode;
        public static $do_driving_time_car_flag;
        public static $do_driving_time_bike_flag;
        public static $do_radius_flag;
        public static $do_driving_distance_flag;
        public static $do_bike_distance_flag;
        public static $do_car_dynamic_rate_flag;
        public static $do_bike_dynamic_rate_flag;
        public function __construct() {
            add_action('wp_ajax_nopriv_cma_validate_shipping_address', array(
                $this,
                'cma_validate_shipping_address'
            ));
            add_action('wp_ajax_cma_validate_shipping_address', array(
                $this,
                'cma_validate_shipping_address'
            ));
            add_action('wp_ajax_nopriv_cma_set_save_to_checkout', array(
                $this,
                'cma_set_save_to_checkout'
            ));
            add_action('wp_ajax_cma_set_save_to_checkout', array(
                $this,
                'cma_set_save_to_checkout'
            ));
            add_action('wp_ajax_nopriv_cma_get_default_address', array(
                $this,
                'cma_get_default_address'
            ));
            add_action('wp_ajax_cma_get_default_address', array(
                $this,
                'cma_get_default_address'
            ));
            add_action('wp', array(
                $this,
                'cma_autocomplete_cmainit'
            ));
          /*  add_filter( 'woocommerce_cart_shipping_packages', array(
                $this,
                'add_weight_to_package'), 1,1 );*/


        }
        
        
        function add_weight_to_package($packages){
            $packages[0]['weight']  = WC()->cart->get_cart_contents_weight();
            return $packages;
        }
        
        
        public function get_package_item_weight( $package ) {
    $total_weight = 0;
    foreach ( $package['contents'] as $item_id => $values ) {
      if ( $values['quantity'] > 0 && $values['data']->get_weight() > 0 ) {
        $total_weight += $values['quantity']  * $values['data']->get_weight();
      }
    }
    return $total_weight;
  }
   
        
        function cma_set_save_to_checkout() {
            check_ajax_referer('cma-script-nonce', 'nonce_ajax');
            $state = isset($_POST['save_state']) ? $_POST['save_state'] : false;
            WC()
                ->session
                ->set('cma_save_address', $state);
            $args = array(
                'state' => WC()
                    ->session
                    ->get('cma_save_address') ,
            );
            wp_send_json($args);
        }
        function cma_get_default_address() {
            check_ajax_referer('cma-script-nonce', 'nonce_ajax');
            $address_1 = WC()
                ->customer
                ->get_shipping_address_1();
            $address_2 = WC()
                ->customer
                ->get_shipping_address_2();
            $postcode = WC()
                ->customer
                ->get_shipping_postcode();
            $city = WC()
                ->customer
                ->get_shipping_city();
            $state = WC()
                ->customer
                ->get_shipping_state();
            $country = WC()
                ->customer
                ->get_shipping_country();
            $args = array(
                'address_1' => $address_1,
                'address_2' => $address_2,
                'postcode' => $postcode,
                'city' => $city,
                'state' => $state,
                'country' => $country,
            );
            wp_send_json($args);
        }

		static function set_customer_address($address_1,$postcode,$city,$state,$country){
			WC()->customer->{"set_shipping_state"}($state);
            WC()->customer->{"set_shipping_postcode"}($postcode);
            WC()->customer->{"set_shipping_address_1"}($address_1);
            WC()->customer->{"set_shipping_country"}($country);
            WC()->customer->{"set_shipping_city"}($city);
            // Set billing address same as shipping
            WC()->customer->{"set_billing_state"}($state);
            WC()->customer->{"set_billing_postcode"}($postcode);
            WC()->customer->{"set_billing_address_1"}($address_1);
            WC()->customer->{"set_billing_country"}($country);
            WC()->customer->{"set_billing_city"}($city);
		}
		
        function cma_validate_shipping_address() {
            check_ajax_referer('cma-script-nonce', 'nonce_ajax');
            do_action('szbd_clear_session');
			self::cma_clear_wc_shipping_rates_cache();
			CMA::third_party_compatibility();
            $cma_adm1_long = $state = $_POST['cma_adm1_long'];
            $state = $state !== '' ? $state : $_POST['cma_adm2_long'];
            $cma_adm1_short = $_POST['cma_adm1_short'];
            $cma_adm2_long = $_POST['cma_adm2_long'];
            $cma_adm2_short = $_POST['cma_adm2_short'];
            $country = $_POST['country'];
            if ($country == 'IE') {
                $cma_adm1_long = ltrim($cma_adm1_long, "County");
                $cma_adm1_long = trim($cma_adm1_long);
            }
            elseif ($country == 'TH') {
                if ($cma_adm1_long == "Krung Thep Maha Nakhon") {
                    $cma_adm1_long = 'Bangkok';
                }
            }
            $states = WC()
                ->countries
                ->get_states($country);
            if ($states) {
                $state_original = $state;
                foreach ($states as $state_key => $state_value) {
                    if (html_entity_decode($state_key) == $cma_adm1_short) {
                        $state = $state_key;
                        break;
                    }
                }
                foreach ($states as $state_key => $state_value) {
                    if (html_entity_decode($state_key) == $cma_adm2_short) {
                        $state = $state_key;
                        break;
                    }
                }
                foreach ($states as $state_key => $state_value) {
                    if (html_entity_decode($state_value) == $cma_adm1_long) {
                        $state = $state_key;
                        break;
                    }
                }
                foreach ($states as $state_key => $state_value) {
                    if (html_entity_decode($state_value) == $cma_adm2_long) {
                        $state = $state_key;
                        break;
                    }
                }
            }
			$country = strtoupper(wc_clean($country));
            $state = strtoupper(wc_clean($state));
            $postcode = wc_clean($_POST['postcode']);
            $postcode = wc_format_postcode( $postcode, $country );

            if (!WC()
                ->session
                ->has_session()) {
                WC()
                    ->session
                    ->set_customer_session_cookie(true);
            }
            WC()
                ->session
                ->set('cma_shipping_address_full', wc_clean($_POST['full_address']));

            if (get_option('cma_checkout_address', 'no') == 'yes' && isset($_POST['save_address']) && $_POST['save_address'] == 1) {



				self::set_customer_address(wc_clean($_POST['address_1']), $postcode, wc_clean($_POST['city']), $state, $country);


            }

            // Work out criteria for our zone search
            $destination = array(
                'country' => $country,
                'state' => $state,
                'postcode' => $postcode,
                'city' => wc_clean($_POST['city']) ,
                'address' => wc_clean($_POST['address_1']) ,
                'address_1' => wc_clean($_POST['address_1']) ,
                'address_2' => '',
            );
            $destination_for_zone = array(
                'destination' => array(
                    'country' => $country,
                    'state' => $state,
                    'postcode' => $postcode,
                ) ,
            );
             $current_cart = isset($_POST['current_cart']) ? sanitize_text_field($_POST['current_cart']) : 0;
             if (  !isset(WC()->cart) || WC()->cart->is_empty() ) {
            $current_cart = 0;
            
         }
          $zone_id = isset($_POST['zone_id']) ? sanitize_text_field($_POST['zone_id']) : null;
            $args = CMA_Del::get_shipping_methods($destination_for_zone, $destination, 'address', $current_cart, $zone_id);
            wp_send_json($args);
        }
        function cma_autocomplete_cmainit() {
            if (get_option('cma_google_maps_api') && CMA::get_is_shortcode()) {
                add_action('wp_enqueue_scripts', array(
                    $this,
                    'run_autocomplete_cmascripts'
                ) , 999);
            }
        }
        public function run_autocomplete_cmascripts() {
            self::cma_autocomplete_cmascripts();
        }
        static function cma_autocomplete_cmascripts() {
            
             

             $google_api_key = get_option('cma_google_maps_api') ;

   

                if (WP_DEBUG === true) {
                    wp_enqueue_script('cma-autocomplete', CMA_PLUGINDIRURL . 'assets/js/cma-autocomplete-address.js', array('underscore') , CMA_VERSION, true);
                }
                else{
                    wp_enqueue_script('cma-autocomplete', CMA_PLUGINDIRURL . 'assets/js/cma-autocomplete-address.min.js', array( 'underscore') , CMA_VERSION, true);
                }

                wp_add_inline_script( 'cma-autocomplete', '(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. CMA Autocomplete, Ignoring...",):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
                    key: "'.$google_api_key.'",
                    v: "quarterly",});','before' );
            
            
        }
      
	static function cma_clear_wc_shipping_rates_cache() {
    $packages = WC()
      ->cart
      ->get_shipping_packages();

    foreach ($packages as $key => $value) {
      $shipping_session = "shipping_for_package_$key";

      unset(WC()
        ->session
        ->$shipping_session);
    }
  }
    } // End of Class
    $Delivery_Checker_Address = new Delivery_Checker_Address();
}

<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('SZBD_Ajax')) {
    /**
     * Main Class SZBD_Ajax
     *
     * @since 
     */
    class SZBD_Ajax {
        // The Constructor
        public function __construct() {
           add_filter('wp_ajax_nopriv_szbd_check_address', array($this,'szbd_check_address'));
           add_filter('wp_ajax_szbd_check_address', array($this, 'szbd_check_address'));
           add_filter('wp_ajax_nopriv_szbd_get_address', array($this,'szbd_get_address'));
           add_filter('wp_ajax_szbd_get_address', array($this, 'szbd_get_address'));
        }
         function szbd_get_address() {
          
            check_ajax_referer( 'szbd-script-nonce', 'nonce_ajax' );
           
            $country   = WC()->cart->get_customer()->get_shipping_country();
            $country_text = WC()->countries->countries[ $country ];
			      $state    = WC()->cart->get_customer()->get_shipping_state();
            $states = WC()->countries->get_states( $country );
            $state_text  = ! empty( $states[ $state  ] ) ? $states[ $state  ] : '';
			       $postcode  = wc_format_postcode(WC()->cart->get_customer()->get_shipping_postcode(), $country);
            $city  = WC()->cart->get_customer()->get_shipping_city();

   


    $continent = strtoupper(wc_clean(WC()
      ->countries
      ->get_continent_code_for_country($country)));
      $destination =  array('country' => $country, 'country_text' => $country_text, 'state' => $state, 'postcode' => $postcode, 'city' => $city );
      add_filter('woocommerce_localisation_address_formats', 'szbd_modify_address_formats',999,1);
      $formatted_address_string =  WC()->countries->get_formatted_address( $destination , $separator = ',' );
      
       $delivery_address = array(
        'formatted_address' => $formatted_address_string,
        'country' => $country,
        'country_text' => $country_text,
        'state' => $state,
        'state_text' => $state_text,
        'postcode' => $postcode,
        	'address_1'  => WC()->cart->get_customer()->get_shipping_address_1(),
          	'address_2'  => WC()->cart->get_customer()->get_shipping_address_2(),
            	'city'  => WC()->cart->get_customer()->get_shipping_city(),
      );
      
      
      $session = WC()
      ->session
      ->get('szbd_delivery_address',false);
  
      $session_ =  $session == false ? $delivery_address : $session;
      $faild_requst =  WC()
      ->session
      ->get('szbd_delivery_address_faild',null);
      

    wp_send_json(array(
      
        
      
      'delivery_address' => $session_ ,
      'delivery_address_string' => WC()
        ->session
        ->get('szbd_delivery_address_string', false) ,
        'cust_loc' => $delivery_address,
        'faild' => $faild_requst,

      
     
     

    ));

  }
         function szbd_check_address() {
         
            check_ajax_referer( 'szbd-script-nonce', 'nonce_ajax' );

            $country   = WC()->cart->get_customer()->get_shipping_country();
            $country_text = WC()->countries->countries[ $country ];
            $state    = WC()->cart->get_customer()->get_shipping_state();
            $states = WC()->countries->get_states( $country );
            $state_text  = ! empty( $states[ $state  ] ) ? $states[ $state  ] : '';
            $postcode  = wc_format_postcode( WC()->cart->get_customer()->get_shipping_postcode(), $country );

          global $wpdb;


    $continent = strtoupper(wc_clean(WC()
      ->countries
      ->get_continent_code_for_country($country)));
      $destination = array('destination' => array('country' => $country, 'state' => $state, 'postcode' => $postcode,));
       $the_zone = WC_Shipping_Zones::get_zone_matching_package($destination);
        $methods = $the_zone->get_shipping_methods(true, 'admin');

         $available_methods = WC()->shipping()->calculate_shipping(WC()->cart->get_shipping_packages());





            $show_tax = get_option( 'woocommerce_tax_display_cart' );



             $cost_column = [];


            foreach ($available_methods[0]['rates'] as $t) {

                 $cost_column[$t->get_id() ] = $show_tax == 'incl'  ? (is_numeric($t->get_cost()) ? (float) $t->get_cost() + $t-> get_shipping_tax(): $t-> get_shipping_tax() ) : (float) $t->get_cost();
                  


            }
        // Keep flag to true until alternative origin is needed
        $default_origin_flag = true;

        foreach ($methods as $value) {
          $array_latlng = array();
          $value_id = $value->id;


          if ( $value_id == 'szbd-shipping-method') {
            $cost = array_key_exists($value->get_rate_id(), $cost_column) ? (float) $cost_column[$value->get_rate_id() ] : null;
            $min_amount = (float)$value->minamount;
            
            //Check origin
            
            $origin = $value->instance_settings['shipping_origin'];

            // Check if drawn zone
            $do_drawn_map = false;
            $do_radius = false;
            $zone_id = $value->instance_settings['map'];

            if ($zone_id !== 'radius' && $zone_id !== 'none') {
              $do_drawn_map = true;
              $do_drawn_map_flag = true;
              

              $meta = get_post_meta(intval($zone_id) , 'szbdzones_metakey', true);
              // Compatibility with shipping methods created in version 1.1 and lower
              if ($zone_id == '') {
                $meta = get_post_meta(intval($value->instance_settings['title']) , 'szbdzones_metakey', true);
              }
              //
              if (is_array($meta['geo_coordinates']) && count($meta['geo_coordinates']) > 0) {
                $i2 = 0;
                foreach ($meta['geo_coordinates'] as $geo_coordinates) {
                  if ($geo_coordinates[0] != '' && $geo_coordinates[1] != '') {
                    $array_latlng[$i2] = array(
                      $geo_coordinates[0],
                      $geo_coordinates[1]
                    );
                    $i2++;
                  }
                }
              }
              else {
                $array_latlng = null;
              }


            }
            else if ($zone_id == 'radius') {
             
              $do_radius = true;
              $do_radius_flag = true;
              $max_radius = (float) (sanitize_text_field($value->instance_settings['max_radius']));

              $default_origin_flag = $origin == 'default' ? $default_origin_flag : false; 

            }

            $do_driving_distance = false;
            $do_bike_distance = false;
            if ($value->instance_settings['max_driving_distance'] !== '0' && $value->instance_settings['max_driving_distance'] !== '') {
             
               $default_origin_flag = $origin == 'default' ? $default_origin_flag : false; 

              $max_driving_distance = (float) (sanitize_text_field($value->instance_settings['max_driving_distance']));
             

              $driving_mode = $value->instance_settings['driving_mode'];
              if ($driving_mode == 'car') {
                $do_driving_distance = true;
                $do_driving_distance_flag = true;
              }
              else if ($driving_mode == 'bike') {
                $do_bike_distance_flag = true;
                $do_bike_distance = true;
              }
            }
            if ($value->instance_settings['rate_mode'] !== 'flat') {

               $default_origin_flag = $origin == 'default' ? $default_origin_flag : false;
               
              if(is_null($cost)){
                $dynamic_rate_need_geolocation_flag = true;
              }
              $driving_mode = $value->instance_settings['driving_mode'];
              if ($driving_mode == 'car') {

                $do_car_dynamic_rate_flag = true;
              }
              else if ($driving_mode == 'bike') {
                $do_bike_dynamic_rate_flag = true;
              }
            }

            $do_driving_time_car = false;
            $do_driving_time_bike = false;
            if ($value->instance_settings['max_driving_time'] !== '0' && $value->instance_settings['max_driving_time'] !== '') {
               $default_origin_flag = $origin == 'default' ? $default_origin_flag : false; 

              $max_driving_time = (float) (sanitize_text_field($value->instance_settings['max_driving_time']));
             
              $driving_mode = $value->instance_settings['driving_mode'];
              if ($driving_mode == 'car') {
                $do_driving_time_car = true;
                $do_driving_time_car_flag = true;
              }
              else if ($driving_mode == 'bike') {
                $do_driving_time_bike_flag = true;
                $do_driving_time_bike = true;
              }
            }
           
           $origin_latlng =  szbd_get_origin_latlng($origin);

            $szbd_zone[] = array(
              'ok_categories' => $value->ok_categories,
              'is_cats_ok' => SZBD::is_cart_ok($value->ok_categories) ? 1 : 0,
              'zone_id' => $value->instance_id,
              'cost' =>  $cost,
              'wc_price_cost' => wc_price($cost) ,
              'geo_coordinates' => $array_latlng,
              'value_id' => $value->get_rate_id() ,
              'min_amount' => (float)$value->minamount,
              'ignore_discounts' => $value->ignore_discounts,
              'min_amount_formatted' => wc_price($value->minamount) ,

              'max_radius' => $do_radius ? array(
                'radius' => $max_radius,
                'bool' => true
                
              ) : false,
              'drawn_map' => $do_drawn_map ? array(
                'geo_coordinates' => $array_latlng,
                'bool' => true
               
              ) : false,

              'max_driving_distance' => $do_driving_distance ? array(
                'distance' => $max_driving_distance,
                'bool' => true
               
              ) : false,
              'max_bike_distance' => $do_bike_distance ? array(
                'distance' => $max_driving_distance,
                'bool' => true
                
              ) : false,
              'max_driving_time_car' => $do_driving_time_car ? array(
                'time' => $max_driving_time,
                'bool' => true
               
              ) : false,
              'max_driving_time_bike' => $do_driving_time_bike ? array(
                'time' => $max_driving_time,
                'bool' => true
                
              ) : false,
              'distance_unit' => $value->instance_settings['distance_unit'] == 'metric' ? 'km' : 'miles',
              'transport_mode' => $value->instance_settings['driving_mode'],
              'rate_mode' => $value->instance_settings['rate_mode'],
              'rate_fixed' => $value->instance_settings['rate_fixed'],
              'rate_distance' => $value->instance_settings['rate_distance'],
              'shipping_origin' => $origin_latlng,

            );
          }



    }
    $do_address_lookup = get_option('szbd_auto_marker','no') == 'yes' || isset($dynamic_rate_need_geolocation_flag) || isset($do_drawn_map_flag) || isset($do_radius_flag) || isset($do_driving_time_car_flag) || isset($do_driving_time_bike_flag) || isset($do_driving_distance_flag) || isset($do_bike_distance_flag);
    $store_address =  WC()
        ->session
        ->get('szbd_store_address', false);

  if($store_address === false){
          $store_address = get_option('szbd_store_address_mode', 'geo_woo_store') == 'pick_store_address' ? json_decode(get_option('SZbD_settings_test', '') , true) : SZBD::get_store_address('comma_seperated');

  }
  
  $cust_loc = array(
        'country' => $country,
        'country_text' => $country_text,
        'state' => $state,
        'state_text' => $state_text,
        'postcode' => $postcode,
        	'address_1'  => WC()->cart->get_customer()->get_shipping_address_1(),
          	'address_2'  => WC()->cart->get_customer()->get_shipping_address_2(),
            	'city'  => WC()->cart->get_customer()->get_shipping_city(),
              );

    wp_send_json(array(
      'szbd_zones' => isset($szbd_zone) ? $szbd_zone : null,
      'default_origin' => $default_origin_flag,
      'status' => true,
      'exclude' => get_option('szbd_exclude_shipping_methods', 'no') ,
      'tot_amount' => (float) szbd_get_subtotal() ,
      'discount_total' => (float)  WC()->cart->get_discount_total(),
      'do_address_lookup' => $do_address_lookup,
      'do_driving_time_car' => isset($do_driving_time_car_flag) ,
      'do_driving_time_bike' => isset($do_driving_time_bike_flag) ,
      'do_radius' => isset($do_radius_flag) ,
      'do_driving_dist' => isset($do_driving_distance_flag) ,
      'do_bike_dist' => isset($do_bike_distance_flag) ,
      'do_dynamic_rate_car' => isset($do_car_dynamic_rate_flag) ,
      'do_dynamic_rate_bike' => isset($do_bike_dynamic_rate_flag) ,
      'dynamic_rate_need_geolocation' => isset($dynamic_rate_need_geolocation_flag),


      'store_address' => $store_address,
      'delivery_address' => WC()
        ->session
        ->get('szbd_delivery_address', false) ,
      'delivery_address_string' => WC()
        ->session
        ->get('szbd_delivery_address_string', false) ,

      'delivery_duration_driving' => WC()
        ->session
        ->get('szbd_delivery_duration_car', false) ,
      'distance_driving' => WC()
        ->session
        ->get('szbd_distance_car', false) ,

      'delivery_duration_bicycle' => WC()
        ->session
        ->get('szbd_delivery_duration_bike', false) ,
      'distance_bicycle' => WC()
        ->session
        ->get('szbd_distance_bike', false) ,
      'cust_loc' => array(
        'country' => $cust_loc['country'],
        'country_text' => $cust_loc['country_text'],
        'state' => $cust_loc['state'],
        'state_text' => $cust_loc['state_text'],
        'postcode' => $cust_loc['postcode'],
        	'address_1'  => $cust_loc['address_1'],
          	'address_2'  => $cust_loc['address_2'],
            	'city'  => $cust_loc['city'],
              'formatted'  => SZBD::get_customer_address_string(array('destination' => $cust_loc), ','),
      ) ,
      'has_address' => '' !== ( WC()->cart->get_customer()->get_shipping_address_1() ),


    ));

  }
        
        
    }
    
    }
    new SZBD_Ajax();

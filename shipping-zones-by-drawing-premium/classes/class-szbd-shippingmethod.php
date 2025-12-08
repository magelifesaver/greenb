<?php
if (!defined('ABSPATH')) {
  exit;
}

if (is_plugin_active_for_network('woocommerce/woocommerce.php') || in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

  function szbd_shipping_method_init() {
    if (!class_exists('WC_SZBD_Shipping_Method')) {
      class WC_SZBD_Shipping_Method extends WC_Shipping_Method {

        protected $api;
        public $info;
        public $rate,$rate_mode,$rate_fixed,$rate_distance,$minamount,$map,$max_radius,$type,$ignore_discounts,$coupons_freeshipping,$ok_categories,$driving_mode,$max_driving_distance,$shipping_origin,$max_driving_time,$distance_unit,$fee_cost;
        public $is_simulated; // Used by CMA

       
        

        /**
         * Constructor for shipping class
         *
         * @access public
         * @return void
         */
        public function __construct($instance_id = 0) {
          $this->id = 'szbd-shipping-method';
          $this->instance_id = absint($instance_id);
          $this->method_title = __('Shipping Zones by Drawing', 'szbd');
          $this->method_description = __('Shipping method to be used with a drawn delivery zone', 'szbd');
          $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
          );
     
          add_action('woocommerce_update_options_shipping_' . $this->id, array(
            $this,
            'process_admin_options'
          ));

          $this->init();

        }
       

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init() {
         
          // Load the settings API
          $this->init_form_fields();
          $this->init_settings();
          $this->enabled = $this->get_option('enabled');
          //Check old options for BW compatibility
          $args = array(
            'numberposts' => 1,
            'post_type' => 'szbdzones',
            'include' => array(
              intval($this->get_option('title'))
            )
          );
          $a_zone = get_posts($args);

          if ((is_array($a_zone) || is_object($a_zone)) && !empty($a_zone)) {
            $title_pre = $a_zone[0]->post_title;
          }
          $title2 = is_string(($this->get_option('title'))) && $this->get_option('title') != '' ? ($this->get_option('title')) : __('Shipping Zones by Drawing', 'szbd');
          $title = isset($title_pre) ? $title_pre : $title2;
          $map = isset($title_pre) ? ($this->get_option('title')) : 'none';
          $this->title = $this->get_option('title2', $title);

          $this->info = $this->get_option('info');
          $this->rate = $this->get_option('rate');
          $this->type = $this->get_option('type', 'class');
          $this->rate_mode = $this->get_option('rate_mode');
          $this->rate_fixed = $this->get_option('rate_fixed');
          $this->rate_distance = $this->get_option('rate_distance');
          $this->tax_status = $this->get_option('tax_status');
          $this->minamount = $this->get_option('minamount', 0);
          $this->ignore_discounts = $this->get_option( 'ignore_discounts' );
          $this->coupons_freeshipping = $this->get_option( 'coupons_freeshipping' );

          
          $this->map = $this->get_option('map', $map);
          $this->max_radius = $this->get_option('max_radius');        
          $this->max_driving_distance = $this->get_option('max_driving_distance');
          $this->max_driving_time = $this->get_option('max_driving_time');
          $this->driving_mode = $this->get_option('driving_mode');
          $this->distance_unit = $this->get_option('distance_unit', 'metric');
          $this->ok_categories = $this->get_option('ok_categories', null);
          $this->shipping_origin = $this->get_option('shipping_origin', 'default');

          add_action('woocommerce_update_options_shipping_' . $this->id, array(
            $this,
            'process_admin_options'
          ));
          add_action('woocommerce_update_options_shipping_' . $this->id, array(
            $this,
            'clear_transients'
          ));

        }
        public function clear_transients() {
          global $wpdb;

          $wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_szbd-shipping-method_%') OR `option_name` LIKE ('_transient_timeout_szbd-shipping-method_%')");
        }
        function init_form_fields() {
          $args = array(
            'numberposts' => 100,
            'post_type' => 'szbdzones',
            'post_status' => 'publish',
            'orderby' => 'title',
          );
          $delivery_zoons = get_posts($args);
          if (is_array($delivery_zoons) || is_object($delivery_zoons)) {
            $attr_option = array();
            $calc_1 = array();
            foreach ($delivery_zoons as $calc_2) {
              $calc_3 = get_the_title($calc_2);
              $calc_1 += array(
                $calc_2->ID => ($calc_3)
              );
              $attr_option = $calc_1;
            }
            $attr_option += array(
              "radius" => esc_html__("By Radius", 'szbd') ,
              "none" => esc_html__("None", 'szbd') ,

            );
          }
          else {
            $attr_option = array(
              "radius" => esc_html__("By Radius", 'szbd') ,
              "none" => esc_html__("None", 'szbd') ,

            );
          }
           $args_ori = array(
            'numberposts' => 100,
            'post_type' => 'szbdorigins',
            'post_status' => 'publish',
            'orderby' => 'title',
          );
          $origins = get_posts($args_ori);
          $attr_option_ = array(
              
              "default" => esc_html__("Main Location", 'szbd') ,

            );
          if (is_array($origins) || is_object($origins)) {
          
            $calc_1_ = array();
            foreach ($origins as $calc_2_) {
              $calc_3_ = get_the_title($calc_2_);
              $attr_option_ += array(
                $calc_2_->ID => ($calc_3_)
              );
              
            }
            
            
          }
          
          
          
          $cat_option = array();
          foreach (self::get_all_categories('all',0) as $category) {



            $cat_option += array(

             esc_attr( $category->cat_ID ) =>  esc_html( $category->name )

            );



        }

          $settings = array(
            'title2' => array(
              'title' => __('Title', 'szbd') ,
              'type' => 'text',
              'description' => __('Your customers will see the name of this shipping method during checkout.', 'woocommerce') ,
              'desc_tip' => true,
              'default' => '',
            ) ,
            'title' => array(
              'class' => 'szbd_hide',
              'type' => 'hidden',
            ) ,

            'distance_unit' => array(
              'title' => __('Distance Unit', 'szbd') ,
              'type' => 'select',
              'desc_tip' => true,
              'description' => __('Choose what distance unit to use.', 'szbd') ,
              'default' => 'metric',
              'options' => array(
                'metric' => __('Metric (km)', 'szbd') ,
                'imperial' => __('Imperial (miles)', 'szbd') ,
              ) ,
            ) ,
            'rate_mode' => array(
              'title' => __('Shipping Rate', 'szbd') ,
              'type' => 'select',
              'class' => 'wc-enhanced-select',
              'default' => 'flat',
              'desc_tip' => true,
              'options' => array(
                'flat' => __('Flat Rate', 'szbd') ,
                'distance' => __('By transportation distance', 'szbd') ,
                'fixed_and_distance' => __('By fixed rate + transportation distance', 'szbd') ,
              ) ,
            ) ,
            'rate' => array(
              'title' => __('Flat Rate', 'szbd') ,
              'type' => 'text',
              'description' =>  __( 'Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'woocommerce' ) . '<br/><br/>' .__( 'Use <code> [weight] </code> for the total cart weight.', 'szbd' ) .'<br/>'. __( 'Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'woocommerce' ),
              'class'             => 'wc-shipping-modal-price',
              'desc_tip' => true,
              'placeholder'       => '',
              'default' => '0',
              'sanitize_callback' => array(
                $this,
                'sanitize_cost'
              ) ,
            ) ,
            'coupons_freeshipping' => array(
              'title'       => __( 'Coupon free shipping', 'szbd' ),
              'label'       => __( 'Free shipping with coupon code', 'szbd' ),
              'type'        => 'checkbox',
              'description' => __( 'Make this shipping method free if a valid coupon exists.', 'szbd' ),
              'default'     => 'no',
              'desc_tip'    => true,

            ),

            'rate_fixed' => array(
              'title' => __('Fixed Rate', 'szbd') ,
              'type' => 'text',
              'description' => __('Enter a fixed shipping rate.', 'szbd') ,
              'class'             => 'wc-shipping-modal-price',
              'desc_tip' => true,
              'default' => '0',
              'sanitize_callback' => array(
                $this,
                'sanitize_cost'
              ) ,
            ) ,
            'rate_distance' => array(
              'title' => __('Distance Unit Rate', 'szbd') ,
              'type' => 'text',
              'description' => __('Enter the rate per shipping distance unit.', 'szbd') ,
              'class'             => 'wc-shipping-modal-price',
              'desc_tip' => true,
              'default' => '0',
              'sanitize_callback' => array(
                $this,
                'sanitize_cost'
              ) ,
            ) ,
            
            
            'tax_status' => array(
              'title' => __('Tax status', 'woocommerce') ,
              'type' => 'select',
              'class' => 'wc-enhanced-select',
              'default' => 'taxable',
              'options' => array(
                'taxable' => __('Taxable', 'woocommerce') ,
                'none' => _x('None', 'Tax status', 'woocommerce') ,
              ) ,
            ) ,
            'minamount' => array(
              'title' => __('Minimum order amount', 'szbd') ,
              'type' => 'text',
              'description' => __('Select a minimum order amount.', 'szbd') ,
              'desc_tip' => true,
              'default' => '0',
              'sanitize_callback' => array(
                $this,
                'sanitize_cost'
              ) ,
              ),
              'ignore_discounts' => array(
				'title'       => __( 'Coupons discounts', 'szbd' ),
				'label'       => __( 'Apply minimum order rule before coupon discount', 'szbd' ),
				'type'        => 'checkbox',
				'description' => __( 'If checked, this method will be available based on pre-discount order amount.', 'szbd' ),
				'default'     => 'no',
				'desc_tip'    => true,
			
            ) ,
            'driving_mode' => array(
              'title' => __('Transport mode', 'szbd') ,
              'type' => 'select',
              'class' => 'wc-enhanced-select',
              'default' => 'car',
              'description' => __('Choose vehicle type to be used when calculating transportation distance och time.', 'szbd'),
              'desc_tip' => true,
              'options' => array(
                'car' => __('By Car', 'szbd') ,
                'bike' => __('By Bike', 'szbd') ,
              ) ,
            ) ,
            'shipping_origin' => array(
              'title' => __('Shipping Origin', 'szbd'),
              'type' => 'select',
              'description' => __('Choose shipping origin for this shipping method', 'szbd'),
              'desc_tip' => true,
              'default' =>  "default" ,
              'options' => $attr_option_,
            ) ,
             array(
              'title' => __('Restrict by Product Categories', 'szbd') ,
              'type' => 'title',
              'description' => __('All products in cart must belong to one of the selected categories to activate this shipping method', 'szbd') ,

            ) ,
             'ok_categories' => array(
              'title' => __('Product categories', 'szbd') ,
              'type' => 'multiselect',
              'description' => __('Leave empty to not restrict by product categories', 'szbd') ,
              'class' => 'wc-enhanced-select szbd-enhanced-select',
              'default' => array(),
              'options' => $cat_option,
            ) ,
             

            array(
              'title' => __('Restrict by Zone (Drawn zone or by Radius)', 'szbd') ,
              'type' => 'title',
             

            ) ,
            'map' => array(
              'title' => __('Delivery Zone', 'szbd') ,
              'type' => 'select',
              'description' => __('Select a drawn delivery area or specify the area by a radius', 'szbd') ,
              'desc_tip' => true,
              'options' => ($attr_option),
              'default' => '',
            ) ,

            'max_radius' => array(
              'title' => __('Maximum radius', 'szbd') ,
              'type' => 'text',
              'description' => __('Maximum radius (km/miles) from selected shipping origin.', 'szbd') ,
              'desc_tip' => true,
              'default' => '0',
              'sanitize_callback' => array(
                $this,
                'sanitize_cost'
              ) ,
            ) ,
            
            array(
              'title' => __('Restrict by Transportation Distance', 'szbd') ,
              'type' => 'title',

            ) ,
            'max_driving_distance' => array(
              'title' => __('Maximum transportation distance', 'szbd') ,
              'type' => 'text',
              'description' => __('Limit shipping by maximum transportation distance (km/miles) from selected store location and the selected mode (car/bike)', 'szbd') ,
              'desc_tip' => true,
              'default' => '0',
              'sanitize_callback' => array(
                $this,
                'sanitize_cost'
              ) ,
            ) ,
            
            array(
              'title' => __('Restrict by Transportation Time', 'szbd') ,
              'type' => 'title',

            ) ,
            'max_driving_time' => array(
              'title' => __('Max transportation time', 'szbd') ,
              'type' => 'text',
              'description' => __('Limit shipping by maximum transportation time (minutes) from selected store location and the selected mode (car/bike)', 'szbd') ,
              'desc_tip' => true,
              'default' => '0',
              'sanitize_callback' => array(
                $this,
                'sanitize_cost'
              ) ,
            ) ,
           
           

          );
          
          
          $shipping_classes = WC()->shipping()
            ->get_shipping_classes();

          if (!empty($shipping_classes)) {
            $settings['class_costs'] = array(
              'title' => __('Shipping class costs', 'woocommerce') ,
              'type' => 'title',
              'default' => '',
              /* translators: %s: URL for link. */
              'description' => sprintf(__('These costs can optionally be added based on the <a href="%s">product shipping class</a>.', 'woocommerce') , admin_url('admin.php?page=wc-settings&tab=shipping&section=classes')) ,
            );
            foreach ($shipping_classes as $shipping_class) {
              if (!isset($shipping_class->term_id)) {
                continue;
              }
              $settings['class_cost_' . $shipping_class->term_id] = array(
                /* translators: %s: shipping class name */
                'title' => sprintf(__('"%s" shipping class cost', 'woocommerce') , esc_html($shipping_class->name)) ,
                'type' => 'text',
                'placeholder' => __('N/A', 'woocommerce') ,
                'description' => '', //$cost_desc,
                'default' => $this->get_option('class_cost_' . $shipping_class->slug) , // Before 2.5.0, we used slug here which caused issues with long setting names.
                'desc_tip' => true,
                'sanitize_callback' => array(
                  $this,
                  'sanitize_cost'
                ) ,
              );
            }

            $settings['no_class_cost'] = array(
              'title' => __('No shipping class cost', 'woocommerce') ,
              'type' => 'text',
              'placeholder' => __('N/A', 'woocommerce') ,
              'description' => '', //$cost_desc,
              'default' => '',
              'desc_tip' => true,
              'sanitize_callback' => array(
                $this,
                'sanitize_cost'
              ) ,
            );

            $settings['type'] = array(
              'title' => __('Calculation type', 'woocommerce') ,
              'type' => 'select',
              'class' => 'wc-enhanced-select',
              'default' => 'class',
              'options' => array(
                'class' => __('Per class: Charge shipping for each shipping class individually', 'woocommerce') ,
                'order' => __('Per order: Charge shipping for the most expensive shipping class', 'woocommerce') ,
              ) ,
            );
          }
          $this->instance_form_fields = $settings;
         
        }
        public static function get_all_categories($fields_ = 'all',$empty_ = 0) {

				$taxonomy       = 'product_cat';

				$show_count     = 0; // 1 for yes, 0 for no
				$pad_counts     = 0; // 1 for yes, 0 for no
				$hierarchical   = 1; // 1 for yes, 0 for no
				$title          = '';
				$empty          = $empty_;
				$fields         = $fields_;

				$args           = array(
					'taxonomy'                => $taxonomy,

					'show_count'                => $show_count,
					'pad_counts'                => $pad_counts,
					'hierarchical'                => $hierarchical,
					'title_li'                => $title,
					'hide_empty'                => $empty,
					'fields'                => $fields,

				);
				$cats = get_categories($args);


				return $cats;


			}

        public static function get_store_address_latlng_string($latlngNeeded = false, $instance = null){
           if (get_option('szbd_store_address_mode', 'geo_woo_store') == 'pick_store_address') {
                $origin_raw = json_decode(get_option('SZbD_settings_test', '') , true);
                $lat = str_replace(',', '.', $origin_raw['lat']);
                 $lng = str_replace(',', '.', $origin_raw['lng']);
                  $lat = str_replace('"', ".", $lat);
                 $lng = str_replace("'", "", $lng);

                $origin = $lat . ',' . $lng;

              }
              else {
                if($latlngNeeded){
                  
                  $origin =  WC()
                  ->session
                  ->get('szbd_store_address',null);
                  if($origin == null){
                    
                         $store_raw_country = get_option('woocommerce_default_country', '');
            $split_country = explode(":", $store_raw_country);
            // Country and state
            $region = $split_country[0];
              $ok_types = array(
                        "establishment",
                        "subpremise",
                        "premise",
                        "street_address",
                        "route",
                        "intersection",
                        "plus_code"
                    );
             
              
              
             

               // Google region biasing taks a ccTLD code https://en.wikipedia.org/wiki/Country_code_top-level_domain
              if ('gb' === $region) {
                $region = 'uk';
              }

         
  

        $distance = $instance->get_geocode_request( self::get_shipping_address_string() ,null,  $region);
        if (get_option('szbd_debug', 'no') == 'yes' ) {
       
         
          $row1 = json_encode($ok_types);
          $row0 = 'OK TYPES:';
         
          if(is_ajax()){
           wc_add_notice( print_r($row0. '  '.$row1,true), 'notice');
          }
          else if( !is_checkout() && !is_cart()){
  
           
          }else{
  
            SZBD_Google_Server_Requests::console_debug($row0,$row1,'','','' ,'' );
          }
          
  
         
         
  
        }
          


          if ('OK' == $distance->status  &&
              is_array($distance->results[0]
                ->types) ) {

               
                $is_geo_types_ok_1 = array_intersect($ok_types, $distance->results[0]
                  ->types);
                $types_is_ok = count($is_geo_types_ok_1) > 0 ? true : false;
              }
              else {
                return null;

              }

              // Check if a valid response was received.
              if (!('OK' == $distance->status && $types_is_ok)) {

                return null;

              }
              else {
               
                 $origin = $distance->results[0]
                  ->geometry
                  ->location->lat
                  .','.
                  $distance->results[0]
                  ->geometry
                  ->location->lng;
                  
                  
                WC()
                  ->session
                  ->set('szbd_store_address', $origin );
               
          
        }
                    
                    
                    
                    
                   
                    
                  }
                  
                  }else{
                   $origin = self::get_shipping_address_string();
                }
               
              }
              return $origin;
        }
        public function get_package_item_qty( $package ) {
    $total_quantity = 0;
    foreach ( $package['contents'] as $item_id => $values ) {
      if ( $values['quantity'] > 0 && $values['data']->needs_shipping() ) {
        $total_quantity += $values['quantity'];
      }
    }
    return $total_quantity;
  }
  
   static function get_shipping_origin($origin_id){
    
  
    $meta = get_post_meta(intval($origin_id) , 'szbdorigins_metakey', true);
             
              
              if ($meta != '' && is_array($meta['geo_coordinates']) && count($meta['geo_coordinates']) > 0) {
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
                
                $origin = is_array($array_latlng) && is_array($array_latlng[0]) && is_numeric($array_latlng[0][0]) && is_numeric($array_latlng[0][1]) ? $array_latlng[0][0].','.$array_latlng[0][1] : null;
             
              }elseif(self::isJson($meta['geo_coordinates'])){
                $origin_ = json_decode($meta['geo_coordinates']);
                $origin = $origin_->lat . ',' . $origin_->lng;
               // print_r($origin);
              }
              else {
                $origin = null;
              }
   
    
    return $origin;
  }
 static function isJson($json, $depth = 512, $flags = 0) {
        if (!is_string($json)) {
            return false;
        }

        try {
            json_decode($json, false, $depth, $flags | JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }
public function get_distance_request($store_address,$delivery_address,$driving_mode,$flag,$unit,$region){
               $distance = $this->get_api()
                ->get_distance($store_address,$delivery_address,$driving_mode,$flag,$unit,$region);
    return $distance;

}
public function get_geocode_request($delivery_address,$delivery_components,$region){
               $location= $this->get_api()
                ->get_location($delivery_address,$delivery_components,$region);
    return $location;

}
public function do_directions_request( $delivery_location,$driving_mode,$region, $ok_types, $ok_types_store){

  
    $origin = $this-> shipping_origin == 'default' ? self::get_store_address_latlng_string(false, $this) : self::get_shipping_origin($this-> shipping_origin);
              
        $distance = $this->get_distance_request($origin, $delivery_location , $driving_mode, 'none', $this->distance_unit, $region);
        if (get_option('szbd_debug', 'no') == 'yes' ) {
       
         
          $row1 = json_encode($ok_types);
          $row0 = 'OK TYPES:';
         
          if(is_ajax()){
           wc_add_notice( print_r($row0. '  '.$row1,true), 'notice');
          }
          else if( !is_checkout() && !is_cart()){
  
           
          }else{
  
            SZBD_Google_Server_Requests::console_debug($row0,$row1,'','','' ,'' );
          }
          
  
         
         
  
        }
          


          if ('OK' == $distance->status  &&
              is_array($distance->geocoded_waypoints[0]
                ->types) && is_array($distance->geocoded_waypoints[1]
                ->types)) {

                $is_geo_types_ok_0 = array_intersect($ok_types_store, $distance->geocoded_waypoints[0]
                  ->types);
                $is_geo_types_ok_1 = array_intersect($ok_types, $distance->geocoded_waypoints[1]
                  ->types);
                $types_is_ok = count($is_geo_types_ok_0) > 0 && count($is_geo_types_ok_1) > 0 ? true : false;
              }
              else {
                return false;

              }

              // Check if a valid response was received.
              if (!('OK' == $distance->status && $types_is_ok)) {
                 // check if point is from UI
                  $session = WC()
                 ->session
                 ->get('szbd_delivery_address');
                 $new_location = $distance->routes[0]
                 ->legs[0]
                 ->end_location;
                 if(!isset($session->fromUI)){
                  WC()
                  ->session
                  ->set('szbd_delivery_address',false); 
                 }
               

                $failObject = $distance->routes[0]
                ->legs[0]
                ->end_location;

                $failObject->types = $distance->geocoded_waypoints[1]
                ->types;

                $failObject->isFail = true;
                WC()
                ->session
                ->set('szbd_delivery_address_faild',$failObject );

                return false;

              }
              else {
                $distance_value = $distance->routes[0]
                  ->legs[0]
                  ->distance->value;
                
                  
                  if($this-> shipping_origin == 'default'){
                WC()
                  ->session
                  ->set('szbd_distance_' . $this->driving_mode . '_default', (float) $distance_value);
                  WC()
                  ->session
                  ->set('szbd_delivery_duration_' . $this->driving_mode . '_default', floatval($distance->routes[0]
                  ->legs[0]
                  ->duration
                  ->value));

                WC()
                  ->session
                  ->set('szbd_store_address', $distance->routes[0]
                  ->legs[0]
                  ->start_location);
                  
                  }else{
                  WC()
                  ->session
                  ->set('szbd_distance_' . $this->driving_mode . '_'. $this-> shipping_origin, (float) $distance_value);
                  WC()
                  ->session
                  ->set('szbd_delivery_duration_' . $this->driving_mode . '_' . $this-> shipping_origin, floatval($distance->routes[0]
                  ->legs[0]
                  ->duration
                  ->value));
                
                    
                  }
                  

                  // check if point is from UI and if so keep that state
                  

                  $session = WC()
                  ->session
                  ->get('szbd_delivery_address');
                  $new_location = $distance->routes[0]
                  ->legs[0]
                  ->end_location;
                  if(isset($session->fromUI)){
                      $new_location->fromUI = true;
                  }

                WC()
                  ->session
                  ->set('szbd_delivery_address',$new_location );
                WC()
                  ->session
                  ->set('szbd_delivery_address_string', $distance->routes[0]
                  ->legs[0]
                  ->end_address);
              
                  
                
                 

              }
              return isset($distance_value) ? $distance_value : false;
}

        public function is_third_party_request()
                    {
                      if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
                        return false;
                      }
            
                      // Test 3rd party requests that are valid to perform shipping calculation on
                      return false !== strpos($_SERVER['REQUEST_URI'], 'wbte_sc_update_payment_method_on_session');
                    }

        public function calculate_shipping($package = array()) {


                        $is_cart = get_option('szbd_enable_at_cart','no') == 'yes' && is_cart();
                       
                        // Softer check if the request is a store Api request because WC()->is_store_api_request() does not handle all permalink structures e.g simple
                        $is_store_api_request = false !== strpos( $_SERVER['REQUEST_URI'],  'wc/store/' );
                        $is_checkout =  WC()->is_store_api_request() || is_checkout() || $is_store_api_request;
                        $is_address_validation = isset($package['is_cma_call']) || isset($package['is_cmp_call']) || isset($package['is_fdoe_call']) ;
                        $is_third_pary_request = $this->is_third_party_request();
                        
                        // skip calculations if is not checkout or cart or address validation requests
                        if(apply_filters( 'szbd_calculate_only_on_cart_and_checkout', true) && !$is_cart && !$is_checkout && !$is_address_validation && !$is_third_pary_request){

                            return;
                
                          }
                          
                    // Leave rate calculation if Food Online has pickup selected
                    if(  get_option('fdoe_disable_checkout_validation') != 'yes'  && class_exists('Food_Online_Del') &&  get_option('fdoe_enable_delivery_switcher', 'no') !== 'no' &&  get_option('fdoe_enable_delivery_switcher', 'no') !== 'only_delivery' && ('local_pickup' == WC()->session->get('fdoe_shipping') || 'eathere' == WC()->session->get('fdoe_shipping')) ){
                        return;
                        }

        // If categories is not allowed, pass a dummy rate not to let sending Google requests
        if( get_option('szbd_server_mode','yes') == 'yes' && (!empty($this->ok_categories) && !SZBD::is_cart_ok($this->ok_categories))){
          $rate_ = array(
            'label' => !isset($label_appendix) ? $this->title : $this->title . ' ' . $label_appendix,

            'cost' =>  null,
            'package' => $package,
            'calc_tax' => 'per_order',

          );
          $this->add_rate($rate_);
          return;
         
        }
         

          
          
          if( get_option('szbd_server_mode','yes') == 'yes' && is_cart() && get_option('szbd_enable_at_cart','no') == 'no' && $this->rate_mode !== 'flat'){
               $rate = null;
               $label_appendix = __('(Cost is depending on your delivery address)', 'szbd');
             }
          
        else if ($this->rate_mode !== 'flat' ) {
            $distance_session = WC()
              ->session
              ->get('szbd_distance_' . $this->driving_mode . '_' . $this->shipping_origin);
             
             if ( $distance_session !== null && is_numeric($distance_session)) {
              $distance_value = $distance_session;
              $fixed_rate = $this->rate_mode == 'fixed_and_distance' ? (float) $this->rate_fixed : 0;
              $unit_converter = $this->distance_unit == 'imperial' ? 1 / 1.609344 : 1;
              $rate = (( (float) $distance_value) / 1000) * $unit_converter * (float) $this->rate_distance + $fixed_rate;

            }
            elseif($distance_session === false){
              return;
            }
            else {
               $driving_mode = $this->driving_mode == 'car' ? 'driving' : 'bicycling';

              $region = empty($package['destination']['country']) ? '' : strtolower($package['destination']['country']);
              $standard_ok_types = array(
                        "establishment",
                        "subpremise",
                        "premise",
                        "street_address",
                        "route",
                        "intersection",
                        "plus_code"
                    );
                
                  
              $ok_types = $ok_types_store = get_option('szbd_types_custom','no') == 'yes' ? get_option('szbd_no_map_types',  $standard_ok_types  ) :  $standard_ok_types;
              
               if ('ie' === $region && get_option('szbd_types_custom','no') == 'no') {
                $ok_types = array(
                  "street_address",
                  "subpremise",
                  "premise",
                  "postal_code",
                  "establishment",
                  "plus_code"
                );
              }
               // Google region biasing taks a ccTLD code https://en.wikipedia.org/wiki/Country_code_top-level_domain
              if ('gb' === $region) {
                $region = 'uk';
              }
              // If calculation is from CMP with precise option, add postal_code as ok type
              if( isset($package['cmp_as_precise']) && $package['cmp_as_precise'] == true ){
                $ok_types[] = 'postal_code';
              }
             
           
              // Set delivery address
               if( get_option('szbd_precise_address','no') !== 'no' && isset($_POST['post_data']) ){
                 parse_str($_POST['post_data'], $post_data);

                 $picked = empty($post_data['szbd-picked']) ? '' : wc_clean($post_data['szbd-picked']);
                  $pluscode = empty($post_data['szbd-plus-code']) ? '' : wc_clean($post_data['szbd-plus-code']);
                  
                  
                 if( isset($picked) && $picked !== ''){
                  $location = $picked;
                 }else if(isset($pluscode) && $pluscode !== ''){
                   $location = $pluscode;
                   $ok_types = array('plus_code');
                 }
                  
                  // Now accepting "route" and "intersection" from vsersion 3.0.7.1
                 if (false !== $key = array_search('route', $ok_types)) {
                 //  unset($ok_types[$key]);
                  }
                   if (false !== $key = array_search('intersection', $ok_types)) {
                 //  unset($ok_types[$key]);
                  }

              }

              // Check if delivery location is stored in session as latlng point and then grab it
              $session_location =  WC()
              ->session
              ->get('szbd_delivery_address','');

              $location = isset($location)  && $location !== '' ? $location : ($session_location !== '' && isset($session_location->lat)? $session_location->lat.','.$session_location->lng : '');
              

          
             $delivery_location = isset($location) && $location !== '' ? $location : $this->get_customer_address_string($package);
           
             if(empty($delivery_location)){
               return;
             }
             
             $stored_distance = WC()
                  ->session
                  ->get('szbd_distance_' . $this->driving_mode . '_'. $this-> shipping_origin );
               
             if( $stored_distance == null){
                
             $distance_value = $this->do_directions_request($delivery_location,$driving_mode,$region, $ok_types, $ok_types_store);
            
             if($distance_value === false){
               WC()
                  ->session
                  ->set('szbd_distance_' . $this->driving_mode . '_'. $this-> shipping_origin, false);
                  
              return;
             }
       
             }else{
               $distance_value = $stored_distance;
             }
               $fix_ = str_replace(',', '.', $this->rate_fixed);
                 $fix =    str_replace(' ', '', $fix_);
                 $dyn_ = str_replace(',', '.', $this->rate_distance);
                 $dyn =    str_replace(' ', '', $dyn_);
                 
                $fixed_rate = $this->rate_mode == 'fixed_and_distance' && is_numeric( $fix ) ?  $fix : '0';
                $unit_converter = $this->distance_unit == 'imperial' ?  bcdiv('1' , '1.6093',12)  : '1';
                $rate_dyn =  is_numeric($dyn) && is_numeric($distance_value) ? bcmul($distance_value, $dyn,12) : '0';
                $converter = bcdiv($unit_converter , '1000',12);
                $rate_dyn_converted = is_numeric($rate_dyn) && is_numeric($converter)  ? bcmul( $rate_dyn ,$converter ,12) : null;
                $rate = is_numeric($rate_dyn_converted) ? $rate_dyn_converted + (float) $fixed_rate : $fixed_rate;
            }
          }
          else if ($this->rate_mode == 'flat') {
           
            // Set delivery address
               if(get_option('szbd_precise_address','no') !== 'no' && isset($_POST['post_data'])){
                 parse_str($_POST['post_data'], $post_data);

                 $picked = empty($post_data['szbd-picked']) ? '' : wc_clean($post_data['szbd-picked']);

                 $picked = empty($post_data['shipping-szbd-shipping_point']) ? $picked : wc_clean($post_data['shipping-szbd-shipping_point']);

              
                 

                   $delivery_location = isset($picked) && $picked !== '' ? $picked : false;
                   $delivery_location = $delivery_location !== false ? explode(',', $delivery_location) : false;

            if($delivery_location !== false){
             WC()
                  ->session
                  ->set('szbd_delivery_address', (object) ['lat' => (float) $delivery_location[0] , 'lng' => (float) $delivery_location[1] ]);

              }
               }
               
               if(get_option('szbd_server_mode','yes') == 'yes' ){
                if(  WC()
                  ->session
                  ->get('szbd_distance_' . $this->driving_mode . '_'. $this-> shipping_origin) === null && ((is_numeric($this->max_driving_distance) && $this->max_driving_distance != '0') ||
                    (is_numeric($this->max_driving_time) && $this->max_driving_time != '0' ))){
                  

            // Check if delivery location is stored in session as latlng point and then grab it
            $session_location =  WC()
              ->session
                ->get('szbd_delivery_address','');

              $location = isset($location)  && $location !== '' ? $location : ($session_location !== '' && isset($session_location->lat)? $session_location->lat.','.$session_location->lng : '');



            $delivery_location = isset($location) && $location !== '' ? $location : $this->get_customer_address_string($package);


                  
                   $driving_mode = $this->driving_mode == 'car' ? 'driving' : 'bicycling';

              $region = empty($package['destination']['country']) ? '' : strtolower($package['destination']['country']);
              $standard_ok_types = array(
                        "establishment",
                        "subpremise",
                        "premise",
                        "street_address",
                        "route",
                        "intersection",
                        "plus_code"
                    );
              $ok_types = $ok_types_store = get_option('szbd_types_custom','no') == 'yes' ? get_option('szbd_no_map_types',  $standard_ok_types  ) :  $standard_ok_types;
              
               if ('ie' === $region && get_option('szbd_types_custom','no') == 'no') {
                $ok_types = array(
                  "street_address",
                  "subpremise",
                  "premise",
                  "postal_code",
                  "establishment",
                  "plus_code"
                );
              }
               // Google region biasing taks a ccTLD code https://en.wikipedia.org/wiki/Country_code_top-level_domain
              if ('gb' === $region) {
                $region = 'uk';
              }
             
              $distance_value = $this->do_directions_request($delivery_location,$driving_mode,$region, $ok_types, $ok_types_store);
              
                 
             if($distance_value == false){
              WC()
                  ->session
                  ->set('szbd_distance_' . $this->driving_mode . '_'. $this-> shipping_origin, false);
               
              return;
             }
              
              
             }else{
               $this->maybe_geolocate_address($package);
             }
               }
               
               
    $has_costs = false; // True when a cost is set. False if all costs are blank strings.
		$rate_flat      = $this->get_option( 'rate' );

		if ( '' !== $rate_flat ) {
			$has_costs    = true;
			$rate = $this->evaluate_cost(
				$rate_flat,
				array(
					'qty'  => $this->get_package_item_qty( $package ),
					'cost' => $package['contents_cost'],
          'weight' => $this->get_package_item_weight($package),
				)
			);
		}

          }
          else {

            return;
          }
         

          $rate_ = array(
            'label' => !isset($label_appendix) ? $this->title : $this->title . ' ' . $label_appendix,

            'cost' => isset($rate) ? $rate : null,
            'package' => $package,
            'calc_tax' => 'per_order',

          );

          // Add shipping class costs.
          $shipping_classes = WC()->shipping()
            ->get_shipping_classes();

          if (!empty($shipping_classes)) {
            $found_shipping_classes = $this->find_shipping_classes($package);
            $highest_class_cost = 0;

            foreach ($found_shipping_classes as $shipping_class => $products) {
              // Also handles BW compatibility when slugs were used instead of ids.
              $shipping_class_term = get_term_by('slug', $shipping_class, 'product_shipping_class');
              $class_cost_string = $shipping_class_term && $shipping_class_term->term_id ? $this->get_option('class_cost_' . $shipping_class_term->term_id, $this->get_option('class_cost_' . $shipping_class, '')) : $this->get_option('no_class_cost', '');

              if ('' === $class_cost_string) {
                continue;
              }

              $has_costs = true;             
              $class_cost = $this->evaluate_cost($class_cost_string, array(
                'qty' => array_sum(wp_list_pluck($products, 'quantity')) ,
                'cost' => array_sum(wp_list_pluck($products, 'line_total')) ,
                'weight' => 0,
              ));

              if ('class' === $this->type) {
                $rate_['cost'] += $class_cost;
              }
              else {
                $highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
              }
            }

            if ('order' === $this->type && $highest_class_cost) {
              $rate_['cost'] += $highest_class_cost;
            }
          }

          $this->add_rate($rate_);

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
        protected function maybe_geolocate_address($package){
          
        
                  
         
             $delivery_point =  WC()
                  ->session
                  ->get('szbd_delivery_address',null);
                 
          
          if( !is_null($delivery_point) &&  $delivery_point != false ){
               return;
            }
            if( get_option('szbd_auto_marker','no') == 'yes'   && is_checkout() ){}
            else if( $this->map == 'none' ){
                return ;
            }
            
          
                  
              
          

              $region = empty($package['destination']['country']) ? '' : strtolower($package['destination']['country']);
              $standard_ok_types = array(
                        "establishment",
                        "subpremise",
                        "premise",
                        "street_address",
                        "route",
                        "intersection",
                        "plus_code"
                    );
              $ok_types = $ok_types_store = get_option('szbd_types_custom','no') == 'yes' ? get_option('szbd_no_map_types',  $standard_ok_types  ) :  $standard_ok_types;
              
               if ('ie' === $region && get_option('szbd_types_custom','no') == 'no') {
                $ok_types = array(
                  "street_address",
                  "subpremise",
                  "premise",
                  "postal_code",
                  "establishment",
                  "plus_code"
                );
              }
             

              // Google region biasing taks a ccTLD code https://en.wikipedia.org/wiki/Country_code_top-level_domain
              if ('gb' === $region) {
                $region = 'uk';
              }

             



             
             
              
              // Filter to apply coming new standard request format with a components part that restricts the request to a country and post code
              $use_pipes = apply_filters( 'exprimental_szbd_piped_request', true );

              if($use_pipes){
                $delivery_location =  $this->get_customer_address_string_with_pipes($package,'address');
                $delivery_components = $this->get_customer_address_string_with_pipes($package,'components');

              }else{
                $delivery_location =  $this->get_customer_address_string($package);
                $delivery_components = null;
              }
             
            
              if(empty($delivery_location)){
               return;
             }
 
      
      
      
             $distance = $this->get_geocode_request( $delivery_location ,$delivery_components,  $region);


        if (get_option('szbd_debug', 'no') == 'yes'  && !is_null($distance)) {
       
         
          $row1 = json_encode($ok_types);
          $row0 = 'OK TYPES:';
         
          if(is_ajax()){
           wc_add_notice( print_r($row0. '  '.$row1,true), 'notice');
          }
          else if( !is_checkout() && !is_cart()){
  
           
          }else{
  
           SZBD_Google_Server_Requests::console_debug($row0,$row1,'','','' ,'' );
          }
          
  
         
         
  
        }
          


          if ( !is_null($distance) && 'OK' == $distance->status  &&
              is_array($distance->results[0]
                ->types) ) {

                $is_geo_types_ok_1 = array_intersect($ok_types, $distance->results[0]
                  ->types);
                $types_is_ok = count($is_geo_types_ok_1) > 0 ? true : false;
              }
              else {
                WC()
                ->session
                ->set('szbd_delivery_address',false);
                return;

              }

              // Check if a valid response was received.
              if ( !('OK' == $distance->status && $types_is_ok)) {
                WC()
                ->session
                ->set('szbd_delivery_address',false);

                $failObject =$distance->results[0]
                ->geometry
                ->location;

                $failObject->types = $distance->results[0]
                ->types;

                $failObject->isFail = true;
                WC()
                ->session
                ->set('szbd_delivery_address_faild',$failObject );



                return;

              }
              else {
               
                  
                WC()
                  ->session
                  ->set('szbd_delivery_address', $distance->results[0]
                  ->geometry
                  ->location);
                WC()
                  ->session
                  ->set('szbd_delivery_address_string', $distance->results[0]
                  ->formatted_address);
          
        }
        }
        
        protected function evaluate_cost($sum, $args = array()) {
          include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

          if ( ! is_array( $args ) || ! array_key_exists( 'qty', $args ) || ! array_key_exists( 'cost', $args )  ) {
            wc_doing_it_wrong( __FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1' );

          }

          // Allow 3rd parties to process shipping cost arguments.
          $args = apply_filters('woocommerce_evaluate_shipping_cost_args', $args, $sum, $this);
          $locale = localeconv();
          $decimals = array(
            wc_get_price_decimal_separator() ,
            $locale['decimal_point'],
            $locale['mon_decimal_point'],
            ','
          );
          $this->fee_cost = $args['cost'];

          // Expand shortcodes.
          add_shortcode('fee', array(
            $this,
            'fee'
          ));

          $sum = do_shortcode(str_replace(array(
            '[qty]',
            '[cost]',
            '[weight]',
          ) , array(
            $args['qty'],
            $args['cost'],
             $args['weight'],
          ) , $sum));

          remove_shortcode('fee', array(
            $this,
            'fee'
          ));

          // Remove whitespace from string.
          $sum = preg_replace('/\s+/', '', $sum);

          // Remove locale from string.
          $sum = str_replace($decimals, '.', $sum);

          // Trim invalid start/end characters.
          $sum = rtrim(ltrim($sum, "\t\n\r\0\x0B+*/") , "\t\n\r\0\x0B+-*/");

          // Do the math.
          return $sum ? WC_Eval_Math::evaluate($sum) : 0;
        }
        public function fee( $atts ) {
		$atts = shortcode_atts(
			array(
				'percent' => '',
				'min_fee' => '',
				'max_fee' => '',
			),
			$atts,
			'fee'
		);

		$calculated_fee = 0;

		if ( $atts['percent'] ) {
			$calculated_fee = $this->fee_cost * ( floatval( $atts['percent'] ) / 100 );
		}

		if ( $atts['min_fee'] && $calculated_fee < $atts['min_fee'] ) {
			$calculated_fee = $atts['min_fee'];
		}

		if ( $atts['max_fee'] && $calculated_fee > $atts['max_fee'] ) {
			$calculated_fee = $atts['max_fee'];
		}

		return $calculated_fee;
	}
       

  public function sanitize_cost( $value ) {
		$value = is_null( $value ) ? '' : $value;
		$value = wp_kses_post( trim( wp_unslash( $value ) ) );
		$value = str_replace( array( get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ) ), '', $value );

		$contains_shortcodes = false !== strpos( $value, '[' ) || false !== strpos( $value, ']' );
		if ( ! $contains_shortcodes ) {
			$value = \Automattic\WooCommerce\Utilities\NumberUtil::sanitize_cost_in_current_locale( $value );
		}

		// Thrown an error on the front end if the evaluate_cost will fail.
		$dummy_cost = $this->evaluate_cost(
			$value,
			array(
				'cost' => 1,
				'qty'  => 1,
         'weight'  => 1,
			)
		);
		if ( false === $dummy_cost ) {
			throw new Exception( WC_Eval_Math::$last_error );
		}
		return $value;
	}

        public function find_shipping_classes($package) {
          $found_shipping_classes = array();

          foreach ($package['contents'] as $item_id => $values) {
            if ($values['data']->needs_shipping()) {
              $found_class = $values['data']->get_shipping_class();

              if (!isset($found_shipping_classes[$found_class])) {
                $found_shipping_classes[$found_class] = array();
              }

              $found_shipping_classes[$found_class][$item_id] = $values;
            }
          }

          return $found_shipping_classes;
        }
        public function get_api() {
          if (is_object($this->api)) {
            return $this->api;
          }

          $google_api_key = get_option('szbd_google_api_key_2', '');

          return $this->api = new SZBD_Google_Server_Requests($google_api_key);
        }
       
        public static function get_shipping_address_string() {
          $address = SZBD::get_store_address();
          $address_string = implode(',', array_values($address));
          $address_sanitazied = preg_replace("/,+/", ",", $address_string);

          return $address_sanitazied;
        }
        
    function szbd_in_array_field($needle, $needle_field, $haystack, $strict = false) {
    if ($strict) {
      foreach ($haystack as $item) {if (isset($item->$needle_field) && $item->$needle_field === $needle) {return true;}}
    }
    else {
      foreach ($haystack as $item){ if (isset($item->$needle_field) && $item->$needle_field == $needle) {return true;}}
    }
    return false;
  }
   public function get_customer_address_string($package) {
    


      $package['destination']['postcode'] = wc_format_postcode($package['destination']['postcode'] , $package['destination']['country'] );
      add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );
      add_filter('woocommerce_localisation_address_formats', 'szbd_modify_address_formats',999,1);
      $formatted_address_string =  WC()->countries->get_formatted_address( $package['destination'] , $separator = ' ' );
 
  
          return $formatted_address_string;
        }
        public function get_customer_address_string_with_pipes($package,$mode) {
    

          if($mode == 'components'){
          $postcode= wc_format_postcode($package['destination']['postcode'] , $package['destination']['country'] );
          $country = $package['destination']['country'];
              
           
            
                    return get_option('szbd_deactivate_postcode','no') == 'no' ? 'country:' . $country.'|'. 'postal_code:' . $postcode : 'country:' . $country;
          }else{
            
            if(get_option('szbd_deactivate_postcode','no') == 'no'){
                unset( $package['destination']['postcode'] );
            }
          
            add_filter( 'woocommerce_formatted_address_force_country_display', '__return_false' );
            add_filter('woocommerce_localisation_address_formats', 'szbd_modify_address_formats',999,1);
            $formatted_address_string =  WC()->countries->get_formatted_address( $package['destination'] , $separator = ' ' );
        
         
                 return $formatted_address_string;

          }
                  }
      }
    
    }}

 


  function szbd_add_shipping_method($methods) {
    if (class_exists('WC_SZBD_Shipping_Method')) {
      $methods['szbd-shipping-method'] = new WC_SZBD_Shipping_Method();
     
    }

    return $methods;
  }
  add_filter('woocommerce_shipping_methods', 'szbd_add_shipping_method');
  add_action('woocommerce_shipping_init', 'szbd_shipping_method_init');




}

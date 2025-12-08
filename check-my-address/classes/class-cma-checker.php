<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('CMA_Del')) {
    /**
     * Class CMA_Del
     *
     * @since
     */
    class CMA_Del
    {
        public static $checked;
        public static $do_driving_time_car_flag;
        public static $do_driving_time_bike_flag;
        public static $do_radius_flag;
        public static $do_driving_distance_flag;
        public static $do_bike_distance_flag;
        public static $do_car_dynamic_rate_flag;
        public static $do_bike_dynamic_rate_flag;
        public static $do_map_flag;
        public static $default_origin_flag;
        public static $package_is_simulated;
        public function __construct()
        {
            add_action('wp_ajax_nopriv_cma_get_wc_price', array(
                $this,
                'cma_get_wc_price'
            ));
            add_action('wp_ajax_cma_get_wc_price', array(
                $this,
                'cma_get_wc_price'
            ));
            add_action('wp_ajax_nopriv_cma_validate_shipping_location', array(
                $this,
                'cma_validate_shipping_location'
            ));
            add_action('wp_ajax_cma_validate_shipping_location', array(
                $this,
                'cma_validate_shipping_location'
            ));
            if (!is_admin()) {
                //Includes
                add_action('wp_enqueue_scripts', array(
                    $this,
                    'maybe_enqueue_scripts'
                ), 100);
                add_action('wp', array(
                    $this,
                    'wp2'
                ));
            }
        }
        public function wp2()
        {
            if (CMA::get_is_shortcode()) {
                add_action('wp_footer', array(
                    'CMA_Del',
                    'output_delivery_aromodals'
                ));
            }
        }
        public function maybe_enqueue_scripts()
        {
            if (CMA::get_is_shortcode()) {
                self::enqueue_main_script();
            }
        }
        static function enqueue_main_script()
        {
            wp_enqueue_style('cma-order-boot-css', CMA_PLUGINDIRURL . 'assets/bootstrap/css/bootstrap.min.css', array(), CMA_VERSION);
            wp_register_script('cma-order-boot-js', CMA_PLUGINDIRURL . 'assets/bootstrap/js/bootstrap.min.js', array(
                'jquery',
            ), CMA_VERSION, true);
            wp_enqueue_style('cma-order-font-1', CMA_PLUGINDIRURL . 'assets/fontawesome/css/fontawesome.min.css', array(), CMA_VERSION);
            wp_enqueue_style('cma-order-font-2', CMA_PLUGINDIRURL . 'assets/fontawesome/css/solid.min.css', array(), CMA_VERSION);
            wp_enqueue_style('cma-order-font-4', CMA_PLUGINDIRURL . 'assets/fontawesome/css/regular.min.css', array(), CMA_VERSION);
            if (!(wp_script_is('fdoe-order-boot-js', 'enqueued') || wp_script_is('cmp-order-boot-js', 'enqueued'))) {
                wp_enqueue_script('cma-order-boot-js');
            }
            if (WP_DEBUG === true){
                wp_enqueue_script('cma-script', CMA_PLUGINDIRURL . 'assets/js/cma.js', array(
                    'jquery',
                    'backbone'
                ), CMA_VERSION, true);
                wp_enqueue_style('cma-order-style', CMA_PLUGINDIRURL . 'assets/css/style.css', array(), CMA_VERSION);
            } else {
                wp_enqueue_script('cma-script', CMA_PLUGINDIRURL . 'assets/js/cma.min.js', array(
                    'jquery',
                    'backbone'
                ), CMA_VERSION, true);
                wp_enqueue_style('cma-order-style', CMA_PLUGINDIRURL . 'assets/css/style.min.css', array(), CMA_VERSION);
            }

            $google_api_key = get_option('cma_google_maps_api');
            wp_add_inline_script('cma-script', '(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. CMA Script, Ignoring...",):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
                key: "' . $google_api_key . '",
                v: "quarterly",
               
               
              });', 'before');


            $message = get_option('cma_top_bar_info', '');
            $args = array(
                'is_checkout' => is_checkout() || (class_exists('WC_Blocks_Utils') && WC_Blocks_Utils::has_block_in_page(get_the_ID(), 'woocommerce/checkout')) ? 1 : 0,
                'is_blocks_checkout' => class_exists('WC_Blocks_Utils') && WC_Blocks_Utils::has_block_in_page(get_the_ID(), 'woocommerce/checkout') ? 1 : 0,
                'del_message_1' => __('We are not able to make deliveries to this address.', 'check-my-address'),
                'del_message_2' => __('We deliver to your address for a cost of ', 'check-my-address'),
                'del_message_3' => __(' We deliver to your address for free.', 'check-my-address'),
                'del_message_4' => __(' We make deliveries to your address', 'check-my-address'),
                'del_message_5' => __('Please enter a valid delivery address.', 'check-my-address'),
                'del_message_6' => __('We make deliveries to your address from', 'check-my-address'),
                'del_message_7' => __('Free shipping on orders over ', 'check-my-address'),
                'del_message_8' => __('Please precise your address with a street number', 'check-my-address'),
                'del_message_9' => __('Not available', 'check-my-address'),
                'del_message_10' => __('The cart contents can´t be delivered to your location.', 'check-my-address'),
                'del_message_shortcode' => __('The delivery cost are depending on your cart contents.', 'check-my-address'),
                'when_the_order_is_above' => __('when the order is above', 'check-my-address'),
                'for' => __('for', 'check-my-address'),
                'and' => __('and', 'check-my-address'),
                'current_cart_text' => '<b>' . __('For Your Cart:', 'check-my-address') . '</b>',
                'simulated_text' => '(' . __('Shipment of one item', 'check-my-address') . ')',

                'min_amount_suffix' => __('for this address', 'check-my-address'),
                'cma_enable_delivery_switcher' => get_option('cma_enable_delivery_switcher', 'no'),
                'cma_skip_address_validation' => get_option('cma_skip_address_validation', 'no'),
                'restrict_country' => get_option('cma_restrict_country', 'no'),
                'shipping_time' => get_option('cma_shipping_time', 'no') == 'calculate' ? true : false,
                'fixed_ship' => get_option('cma_ready_for_delivery_show', 'no') == 'fixed_ship' ? 1 : 0,
                'variable_ship' => get_option('cma_ready_for_delivery_show', 'no') == 'variable_ship' ? 1 : 0,
                'shipping_mode' => get_option('shipping_vehicle', 'DRIVING'),
                'shipping_preselect' => get_option('cma_default_for_switch', 'local_pickup') == 'delivery' ? 'flat_rate' : 'local_pickup',
                'min_amount_message' => __('Minimum order value for delivery is', 'check-my-address'),
                'min_qty_message' => __('Minimum items for delivery is', 'check-my-address'),
                'review_message_error' => __('Make sure all required fields are filled in.', 'check-my-address'),
                'del_message_price_suffix' => get_option('cma_del_message_price_suffix', 'yes') == 'yes' ? 1 : 0,
                'geolocate_user' => get_option('cma_browser_geolocation', 'yes') == 'yes' ? 1 : 0,
                'delivery_color' => get_option('cma_delivery_colormode', 'no') == 'yes' ? 1 : 0,
                'print_message' => get_option('cma_message_format', 'print') == 'print' ? 1 : 0,
                'pick_from_map' => get_option('cma_pick_delivery_location', 'no'),
                'szbd_active' => defined('SZBD_PREM_VERSION') || defined('SZBD_VERSION') ? 1 : 0,
                'top_bar_info' => __($message, 'check-my-address'),
                'autocomplete_types' => get_option('cma_autocomplete_types', array(
                    'geocode'
                )),
                'result_types' => get_option('cma_result_types', array(
                    "establishment",
                    "subpremise",
                    "premise",
                    "street_address"
                )),
                'nonce' => wp_create_nonce('cma-script-nonce'),
                'suggestiontext' => __('Locate my address', 'check-my-address'),
                'geolocation_fail' => __('No address found', 'check-my-address'),
                'geolocation_placeholder' => __('Delivery Address?', 'check-my-address'),
                'geolocate_user_position' => get_option('cma_geolocate_position', 'no') == 'yes' ? 1 : 0,
                'validate_after_geolocation' => get_option('cma_validation_after_geolocation', 'no') == 'yes' ? 1 : 0,
                'max_accuracy' => get_option('cma_max_accuracy', 1000),
                'ajax_url' => admin_url('admin-ajax.php', 'relative'),





            );
            wp_localize_script('cma-script', 'cmadel', $args);
        }
        public static function output_delivery_aromodals()
        {
            ob_start();
            echo '<div class="cma-element" style="display:block;" >
	<div id="cma_precise_streetnumber" class="aromodal cma-aromodal fade-aro" role="dialog">
		<div class="aromodal-dialog ">
			<div class="aromodal-content">
				<div class="aromodal-header"> <button type="button" class="modal-close" data-dismiss="aromodal">
                        <i class="far fa-times-circle fa-2x"></i>
                    </button>
					<h4 class="aromodal-title">' . __('Precise your delivery location', 'check-my-address') . ' </h4> <button type="button" class="cma-confirm-marker button">
                       ' . __('Confirm Location', 'check-my-address') . '
                    </button> </div>
				<div class="aromodal-body">
					<div id="cma-precise-map-wrapper" style="height:auto; width:100%; ">
						<div class="cma-map-message-box"><i class=" cma-pick-icon fas fa-exclamation-circle"></i>' . __('Please precise your delivery location by marking a position at the map.', 'check-my-address') . '</div>
						<div id="cma-pick-content">
							<div class="cma-precise-map">
								<div id="cma_map"> </div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>';
            echo '<div class="cma-element">
	<div id="del_zone_warning" class="aromodal cma-aromodal fade-aro" role="dialog">
		<div class="aromodal-dialog aromodal-sm">
			<div class="aromodal-content">
				<div class="aromodal-header"> <button type="button" class="modal-close" data-dismiss="aromodal">
       <i class="far fa-times-circle fa-2x"></i>
        </button>
					<h4 class="aromodal-title">' . __('Delivery Address...', 'check-my-address') . ' </h4>
				</div>
				<div class="aromodal-body">
					<p>' . __('Sorry, we are not able to make deliveries to your address.', 'check-my-address') . ' </p>
				</div>
			</div>
		</div>
	</div>
	<!-- Please type in delivery address aromodal -->
	<div id="address_varning_aromodal" class="aromodal cma-aromodal fade-aro" role="dialog">
		<div class="aromodal-dialog aromodal-sm">
			<!-- Modal content-->
			<div class="aromodal-content">
				<div class="aromodal-header"> <button type="button" class="modal-close" data-dismiss="aromodal">
       <i class="far fa-times-circle fa-2x"></i>
        </button>
					<h4 class="aromodal-title">' . __('Home delivery?', 'check-my-address') . ' </h4>
				</div>
				<div class="aromodal-body">
					<p>' . __('Please enter a valid delivery address.', 'check-my-address') . ' </p>
				
			</div>
		</div>
	</div>
</div>';
            echo '<div class="cma-element" style="display:block;">
								<div id="cma_delivery_report" class="aromodal cma-aromodal fade-aro" role="dialog">
									<div class="aromodal-dialog ">
										<div class="aromodal-content">
											<div class="aromodal-header"> <button type="button" class="modal-close" data-dismiss="aromodal"> <i class="far fa-times-circle fa-2x"></i> </button>
												<h4 class="aromodal-title">' . __('Delivery specification', 'check-my-address') . ' </h4>
											</div>
											<div class="aromodal-body">
												<div id="cma-precise-map-wrapper_2" style="height:auto; width:100%; "> </div>
									</div>
										</div>
									</div>
								</div>
							</div>
							</div>';
            $output = ob_get_clean();
            echo $output;
        }
        public static function output_delivery_switch_static($is_shortcode = false, $delivery_mode = false, $default_delivery_mode = 'delivery', $selection_mandatory = false, $validation = false, $placement = 'extern')
        {
            $show_address_form = $validation == 'address';
            ob_start();
            echo '<div id="cma_checker_top-bar" class="cma_extern_switch cma_only_delivery">';
            if ($show_address_form) {
                self::config_default_customer_address('save');
                echo cma_get_address_form('show', 'extern');
                echo cma_get_address_form_fractions();
            }
            echo '</div>';
            return ob_get_clean();
        }
        static function catch_szbd_zones($value, $cost_, $current_cart)
        {

            $origin = isset($value->instance_settings['shipping_origin']) ? $value->instance_settings['shipping_origin'] : null;

            $array_latlng = null;
            $min_amount = (float) $value->minamount;
            // Check if drawn zone
            $do_drawn_map = false;
            $do_radius = false;
            $zone_id = $value->instance_settings['map'];
            if ($zone_id !== 'radius' && $zone_id !== 'none') {
                $do_drawn_map = true;
                self::$do_map_flag = true;
                $zoon_bool = (defined('SZBD_PREM_VERSION') && version_compare(SZBD_PREM_VERSION, '2.7.0.9', '>=')) || (defined('SZBD_VERSION') && version_compare(SZBD_VERSION, '2.7.0.9', '>=')) ? true : $value->instance_settings['zone_critical'] == 'yes';
                $meta = get_post_meta(intval($zone_id), 'szbdzones_metakey', true);
                // Compatibility with shipping methods created in version 1.1 and lower
                if ($zone_id == '') {
                    $meta = get_post_meta(intval($value->instance_settings['title']), 'szbdzones_metakey', true);
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
                } else {
                    $array_latlng = null;
                }
                // Check if maximum radius

            } elseif ($zone_id == 'radius') {
                $zoon_bool = (defined('SZBD_PREM_VERSION') && version_compare(SZBD_PREM_VERSION, '2.7.0.9', '>=')) || (defined('SZBD_VERSION') && version_compare(SZBD_VERSION, '2.7.0.9', '>=')) ? true : $value->instance_settings['zone_critical'] == 'yes';
                $do_radius = true;
                self::$do_radius_flag = true;
                self::$default_origin_flag = $origin == 'default' ? self::$default_origin_flag : false;
                $max_radius = (float) (sanitize_text_field($value->instance_settings['max_radius']));
                // Collect the store address

            }
            $do_driving_distance = false;
            $do_bike_distance = false;
            if ($value->instance_settings['max_driving_distance'] !== '0' && $value->instance_settings['max_driving_distance'] !== '') {
                self::$default_origin_flag = $origin == 'default' ? self::$default_origin_flag : false;

                $max_driving_distance = (float) (sanitize_text_field($value->instance_settings['max_driving_distance']));
                $driving_distance_bool = (defined('SZBD_PREM_VERSION') && version_compare(SZBD_PREM_VERSION, '2.7.0.9', '>=')) || (defined('SZBD_VERSION') && version_compare(SZBD_VERSION, '2.7.0.9', '>=')) ? true : $value->instance_settings['distance_critical'] == 'yes';
                $driving_mode = $value->instance_settings['driving_mode'];
                if ($driving_mode == 'car') {
                    $do_driving_distance = true;
                    self::$do_driving_distance_flag = true;
                } elseif ($driving_mode == 'bike') {
                    self::$do_bike_distance_flag = true;
                    $do_bike_distance = true;
                }
            }
            if ($value->instance_settings['rate_mode'] !== 'flat') {
                self::$default_origin_flag = $origin == 'default' ? self::$default_origin_flag : false;
                $driving_mode = $value->instance_settings['driving_mode'];
                if ($driving_mode == 'car') {
                    self::$do_car_dynamic_rate_flag = true;
                } elseif ($driving_mode == 'bike') {
                    self::$do_bike_dynamic_rate_flag = true;
                }
            }
            $do_driving_time_car = false;
            $do_driving_time_bike = false;
            if ($value->instance_settings['max_driving_time'] !== '0' && $value->instance_settings['max_driving_time'] !== '') {
                self::$default_origin_flag = $origin == 'default' ? self::$default_origin_flag : false;

                $max_driving_time = (float) (sanitize_text_field($value->instance_settings['max_driving_time']));
                $driving_time_bool = (defined('SZBD_PREM_VERSION') && version_compare(SZBD_PREM_VERSION, '2.7.0.9', '>=')) || (defined('SZBD_VERSION') && version_compare(SZBD_VERSION, '2.7.0.9', '>=')) ? true : $value->instance_settings['time_critical'] == 'yes';
                $driving_mode = $value->instance_settings['driving_mode'];
                if ($driving_mode == 'car') {
                    $do_driving_time_car = true;
                    $do_driving_time_car_flag = true;
                } elseif ($driving_mode == 'bike') {
                    $do_driving_time_bike_flag = true;
                    $do_driving_time_bike = true;
                }
            }
            $cat_names = array_map(function ($id) {
                if ($term = get_term_by('id', $id, 'product_cat')) {
                    return $term->name;
                }

            }, is_array($value->ok_categories) ? $value->ok_categories : array());


            $cats_ok = true;
            $cats = isset($value->ok_categories) ? $value->ok_categories : null;
            if (!is_null($cats) && method_exists('SZBD', 'is_cart_ok') && !SZBD::is_cart_ok($cats)) {
                $cats_ok = false;
            }



            $szbd_zone = array(
                'shipping_origin' => function_exists('szbd_get_origin_latlng') && $origin != false ? szbd_get_origin_latlng($origin) : null,
                'ok_categories_names' => $cat_names,
                'ok_categories' => isset($value->ok_categories) ? $value->ok_categories : '',
                'cats_ok' => $cats_ok,
                'zone_id_2' => $value->instance_id,
                'is_taxable' => $value->is_taxable(),
                'cost' => $cost_,
                'wc_price_cost' => wc_price($cost_),
                'geo_coordinates' => $array_latlng,
                'value_id' => $value->get_rate_id(),


                'max_radius' => $do_radius ? array(
                    'radius' => $max_radius,
                    'bool' => $zoon_bool
                ) : false,
                'drawn_map' => $do_drawn_map ? array(
                    'geo_coordinates' => $array_latlng,
                    'bool' => $zoon_bool
                ) : false,
                'max_driving_distance' => $do_driving_distance ? array(
                    'distance' => $max_driving_distance,
                    'bool' => $driving_distance_bool
                ) : false,
                'max_bike_distance' => $do_bike_distance ? array(
                    'distance' => $max_driving_distance,
                    'bool' => $driving_distance_bool
                ) : false,
                'max_driving_time_car' => $do_driving_time_car ? array(
                    'time' => $max_driving_time,
                    'bool' => $driving_time_bool
                ) : false,
                'max_driving_time_bike' => $do_driving_time_bike ? array(
                    'time' => $max_driving_time,
                    'bool' => $driving_time_bool
                ) : false,
                'distance_unit' => $value->instance_settings['distance_unit'] == 'metric' ? 'km' : 'miles',
                'transport_mode' => $value->instance_settings['driving_mode'],
                'rate_mode' => $value->instance_settings['rate_mode'],
                'rate_fixed' => $value->instance_settings['rate_fixed'],
                'rate_distance' => $value->instance_settings['rate_distance'],
                'zone_id' => intval($zone_id),
                'method' => $value->id,
                'method_sharp' => $value->get_rate_id(),
                'del_price' => wc_price($cost_),
                'min_amount' => isset($value->minamount) ? (float) $value->minamount : 0,
                'min_amount_formatted' => isset($value->minamount) ? wc_price($value->minamount) : '',
                'min_amount_html' => isset($value->minamount) ? wc_price($value->minamount) : '',
                'rate_has_shortcode' => $value->instance_settings['rate_mode'] == 'flat' ? (!self::$package_is_simulated && strpos($value->instance_settings['rate'], 'qty') !== false) || strpos($value->instance_settings['rate'], 'fee') !== false || strpos($value->instance_settings['rate'], 'weight') !== false || strpos($value->instance_settings['rate'], 'cost') !== false : false,
                'package_is_simulated' => $value->instance_settings['rate_mode'] == 'flat' && (self::$package_is_simulated && strpos($value->instance_settings['rate'], 'qty') !== false),

            );
            return $szbd_zone;
        }
        function reset_customer_shipping_address()
        {
            do_action('szbd_clear_session');
            WC()
                        ->customer->{"set_shipping_state"}(null);
            WC()
                        ->customer->{"set_shipping_postcode"}(null);
            WC()
                        ->customer->{"set_shipping_address_1"}(null);
            WC()
                        ->customer->{"set_shipping_address_2"}(null);
            WC()
                        ->customer->{"set_shipping_country"}(null);
            WC()
                        ->customer->{"set_shipping_city"}(null);
            WC()
                        ->customer->{"set_billing_state"}(null);
            WC()
                        ->customer->{"set_billing_postcode"}(null);
            WC()
                        ->customer->{"set_billing_address_1"}(null);
            WC()
                        ->customer->{"set_billing_address_2"}(null);
            WC()
                        ->customer->{"set_billing_country"}(null);
            WC()
                        ->customer->{"set_billing_city"}(null);
        }
        function cma_validate_shipping_location()
        {
            check_ajax_referer('cma-script-nonce', 'nonce_ajax');
            do_action('szbd_clear_session');
            Delivery_Checker_Address::cma_clear_wc_shipping_rates_cache();
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
            } elseif ($country == 'TH') {
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
            $postcode = wc_normalize_postcode(wc_clean($_POST['postcode']));
            if (
                !WC()
                    ->session
                    ->has_session()
            ) {
                WC()
                    ->session
                    ->set_customer_session_cookie(true);
            }
            WC()
                ->session
                ->set('cma_shipping_address_full', wc_clean($_POST['full_address']));
            if (get_option('cma_checkout_address', 'no') == 'yes' && isset($_POST['save_address']) && $_POST['save_address'] == 1) {



                Delivery_Checker_Address::set_customer_address(wc_clean($_POST['address_1']), $postcode, wc_clean($_POST['city']), $state, $country);


            }

            // Work out criteria for our zone search
            $destination = array(
                'country' => $country,
                'state' => $state,
                'postcode' => $postcode,
                'city' => wc_clean($_POST['city']),
                'address' => wc_clean($_POST['address_1']),
                'address_1' => wc_clean($_POST['address_1']),
                'address_2' => '',
            );
            $destination_for_zone = array(
                'destination' => array(
                    'country' => $country,
                    'state' => $state,
                    'postcode' => $postcode,
                ),
            );
            $current_cart = isset($_POST['current_cart']) ? sanitize_text_field($_POST['current_cart']) : 0;
            $zone_id = isset($_POST['zone_id']) ? sanitize_text_field($_POST['zone_id']) : null;
            $args = CMA_Del::get_shipping_methods($destination_for_zone, $destination, 'address', $current_cart, $zone_id);
            wp_send_json($args);
        }
        public static function config_default_customer_address($mode = '')
        {
            if (is_user_logged_in() && isset(WC()->session)) {
                $current_user_id = get_current_user_id();
                if (
                    $mode == 'save' && WC()
                        ->session
                        ->get('cma_shipping_address_1', false) == false
                ) {
                    $address_1 = get_user_meta($current_user_id, 'shipping_address_1', true);
                    $address_2 = get_user_meta($current_user_id, 'shipping_address_2', true);
                    $postcode = get_user_meta($current_user_id, 'shipping_postcode', true);
                    $city = get_user_meta($current_user_id, 'shipping_city', true);
                    $state = get_user_meta($current_user_id, 'shipping_state', true);
                    $country = get_user_meta($current_user_id, 'shipping_country', true);
                    $b_address_1 = get_user_meta($current_user_id, 'billing_address_1', true);
                    $b_address_2 = get_user_meta($current_user_id, 'billing_address_2', true);
                    $b_postcode = get_user_meta($current_user_id, 'billing_postcode', true);
                    $b_city = get_user_meta($current_user_id, 'billing_city', true);
                    $b_state = get_user_meta($current_user_id, 'billing_state', true);
                    $b_country = get_user_meta($current_user_id, 'billing_country', true);
                    WC()
                        ->session
                        ->set('cma_shipping_address_1', $address_1);
                    WC()
                        ->session
                        ->set('cma_shipping_address_2', $address_2);
                    WC()
                        ->session
                        ->set('cma_shipping_postcode', $postcode);
                    WC()
                        ->session
                        ->set('cma_shipping_city', $city);
                    WC()
                        ->session
                        ->set('cma_shipping_state', $state);
                    WC()
                        ->session
                        ->set('cma_shipping_country', $country);
                    WC()
                        ->session
                        ->set('cma_billing_address_1', $b_address_1);
                    WC()
                        ->session
                        ->set('cma_billing_address_2', $b_address_2);
                    WC()
                        ->session
                        ->set('cma_billing_postcode', $b_postcode);
                    WC()
                        ->session
                        ->set('cma_billing_city', $b_city);
                    WC()
                        ->session
                        ->set('cma_billing_state', $b_state);
                    WC()
                        ->session
                        ->set('cma_billing_country', $b_country);
                    return 'is_saved';
                } else if (
                    $mode == 'reset' && WC()
                        ->session
                        ->get('cma_shipping_address_1', false) != false
                ) {
                    WC()
                                ->customer->{"set_shipping_address_1"}(WC()
                            ->session
                            ->get('cma_shipping_address_1'));
                    WC()
                                ->customer->{"set_shipping_address_2"}(WC()
                            ->session
                            ->get('cma_shipping_address_2'));
                    WC()
                                ->customer->{"set_shipping_postcode"}(WC()
                            ->session
                            ->get('cma_shipping_postcode'));
                    WC()
                                ->customer->{"set_shipping_city"}(WC()
                            ->session
                            ->get('cma_shipping_city'));
                    WC()
                                ->customer->{"set_shipping_state"}(WC()
                            ->session
                            ->get('cma_shipping_state'));
                    WC()
                                ->customer->{"set_shipping_country"}(WC()
                            ->session
                            ->get('cma_shipping_country'));
                    WC()
                                ->customer->{"set_billing_address_1"}(WC()
                            ->session
                            ->get('cma_billing_address_1'));
                    WC()
                                ->customer->{"set_billing_address_2"}(WC()
                            ->session
                            ->get('cma_billing_address_2'));
                    WC()
                                ->customer->{"set_billing_postcode"}(WC()
                            ->session
                            ->get('cma_billing_postcode'));
                    WC()
                                ->customer->{"set_billing_city"}(WC()
                            ->session
                            ->get('cma_billing_city'));
                    WC()
                                ->customer->{"set_billing_state"}(WC()
                            ->session
                            ->get('cma_billing_state'));
                    WC()
                                ->customer->{"set_billing_country"}(WC()
                            ->session
                            ->get('cma_billing_country'));
                    return 'is_reset';
                } else if ($mode == 'get') {
                    $address_1 = get_user_meta($current_user_id, 'shipping_address_1', true); // WC()->customer->get_shipping_address_1();
                    $postcode = get_user_meta($current_user_id, 'shipping_postcode', true); // WC()->customer->get_shipping_postcode();
                    $city = get_user_meta($current_user_id, 'shipping_city', true); //WC()->customer->get_shipping_city();
                    $state = get_user_meta($current_user_id, 'shipping_state', true); //WC()->customer->get_shipping_state();
                    $country = get_user_meta($current_user_id, 'shipping_country', true); //WC()->customer->get_shipping_country();
                    $address_string = $address_1;
                    $address_string .= $postcode != '' ? ', ' . $postcode : '';
                    $address_string .= $city != '' ? ' ' . $city : '';
                    $address_string .= $state != '' ? ', ' . $state : '';
                    $address_string .= $country != '' ? ', ' . WC()
                        ->countries
                        ->countries[$country] : '';
                    return $address_string;
                }
            } else {
                return false;
            }
        }
        static function get_simulated_package($shipping_packages)
        {

            $args = array(
                'downloadable' => false,
                'virtual' => false,
                'type' => 'simple',
                'return' => 'ids',
                'limit' => 1
            );

            $products = wc_get_products($args);
            $pid = isset($products[0]) ? $products[0] : null;
            if (!is_null($pid)) {
                self::$package_is_simulated = true;
                $cart_id = WC()->cart->generate_cart_id($pid);



                $shipping_packages[0]['contents'][$cart_id]['quantity'] = 1;
                $shipping_packages[0]['contents'][$cart_id]['key'] = $cart_id;
                $shipping_packages[0]['contents'][$cart_id]['product_id'] = $pid;
                $shipping_packages[0]['contents'][$cart_id]['data_hash'] = wc_get_cart_item_data_hash(wc_get_product($pid));
                $shipping_packages[0]['contents'][$cart_id]['data'] = wc_get_product($pid);

            }
            return $shipping_packages;
        }

        public static function get_shipping_methods($destination_for_zone, $destination, $type, $current_cart, $zone_id)
        {
            add_filter('option_woocommerce_shipping_cost_requires_address', function () {
                return 'no'; });
                
            $cma_zoon = is_null($zone_id) ? WC_Shipping_Zones::get_zone_matching_package($destination_for_zone) : WC_Shipping_Zones::get_zone($zone_id);
            if ($cma_zoon == false) {
                
                return [];
            }
            $shipping_packages = WC()
                ->cart
                ->get_shipping_packages();
            self::$package_is_simulated = false;
            if ($current_cart == 0) {
                $shipping_packages[0]['contents'] = array();

                $shipping_packages = get_option('cma_simulate_quantity', 'no') == 'yes' ? self::get_simulated_package($shipping_packages) : $shipping_packages;

                $shipping_packages[0]['contents_cost'] = 0;
                $shipping_packages[0]['cart_subtotal'] = 0;
                $shipping_packages[0]['weight'] = 0;
            }

            $shipping_packages[0]['destination'] = $destination;
            $shipping_packages[0]['is_cma_call'] = true;
            $methods = $cma_zoon->get_shipping_methods(true, 'admin');
          
            $available_methods = WC()->shipping()
                ->calculate_shipping($shipping_packages);


            $cost_column = [];
            $show_tax = get_option('woocommerce_tax_display_cart');

            if (!is_null($zone_id)) {
               
                foreach ($methods as $t) {
                    $zone_rates = $t->get_rates_for_package($shipping_packages[0]);

                    $zone_rate = array_shift($zone_rates);


                    $cost_column[$zone_rate->get_id()] = $show_tax == 'incl' ? $zone_rate->get_cost() + $zone_rate->get_shipping_tax() : $zone_rate->get_cost();
                }
            } else {
                
                foreach ($available_methods[0]['rates'] as $t) {
                    $cost_column[$t->get_id()] = $show_tax == 'incl' ? $t->get_cost() + $t->get_shipping_tax() : $t->get_cost();
                }
            }

            // Keep flag to true until alternative origin is needed
            self::$default_origin_flag = true;
            foreach ($methods as $value) {

                $cost_ = array_key_exists($value->get_rate_id(), $cost_column) && is_numeric($cost_column[$value->get_rate_id()]) ? (float) $cost_column[$value->get_rate_id()] : null;

                if (is_null($cost_) && $value->id != 'free_shipping') {
                   
                    continue;
                }
                $value_id = $value->id;
                if ($value_id == 'flat_rate') {




                    $the_method[] = array(
                        'method' => $value->id,
                        'min_amount' => 0,
                        'min_amount_html' => wc_price(0),
                        'cost' => $cost_,
                        'is_free_shipping' => false,
                        'is_taxable' => $value->is_taxable(),
                        'value_id' => $value->get_rate_id(),
                        'del_price' => wc_price($cost_),
                        'rate_has_shortcode' => (!self::$package_is_simulated && strpos($value->cost, 'qty') !== false) || (strpos($value->cost, 'fee') !== false || strpos($value->cost, 'cost') !== false),
                        'package_is_simulated' => (self::$package_is_simulated && strpos($value->cost, 'qty') !== false),
                    );
                } elseif ($value_id == 'free_shipping') {
                    $free = true;
                    $free_method = $value->id;

                    if (($value->requires == "min_amount" || $value->requires == "either") && floatval($value->min_amount) > 0) {
                        $free_shipping_conditional = true;
                        $free = false;



                        $free_method = 'flat_rate';

                    } elseif (($value->requires == "min_amount" || $value->requires == "either") && floatval($value->min_amount) == 0) {
                        $free = true;
                    } elseif ($value->requires == "") {

                    } else {
                        continue;
                    }
                    $the_method[] = array(
                        'method' => $free_method,
                        'cost' => 0,
                        'del_price' => wc_price(0),
                        'is_free_shipping' => $free,
                        'min_amount' => ($value->requires == "min_amount" || $value->requires == "either") ? (float) ($value->min_amount) : 0,
                        'min_amount_html' => ($value->requires == "min_amount" || $value->requires == "either") ? wc_price(($value->min_amount)) : 0,
                        'is_taxable' => $value->is_taxable(),
                        'value_id' => $value->get_rate_id(),
                        'free_shipping_conditional' => isset($free_shipping_conditional) ? $free_shipping_conditional : false,
                        'rate_has_shortcode' => false,
                        'package_is_simulated' => false,
                    );
                    if ($free) {
                        WC()
                            ->session
                            ->__unset('cma_shipping');
                        WC()
                            ->session
                            ->set('cma_shipping', 'free_shipping');
                        break;
                    }
                } elseif ($value_id == 'szbd-shipping-method') {
                   
                    if (false && $type == 'post_code' && ($value->instance_settings['rate_mode'] !== 'flat' || $value->instance_settings['map'] != 'none' || ($value->instance_settings['max_driving_distance'] !== '' && $value->instance_settings['max_driving_distance'] !== '0') || ($value->instance_settings['max_driving_time'] !== '' && $value->instance_settings['max_driving_time'] !== '0'))) {
                        continue;
                    }
                    if (is_plugin_active('shipping-zones-by-drawing-for-woocommerce/shipping-zones-by-drawing.php') && $value->instance_settings['rate_mode'] !== 'flat') {
                        continue;
                    }
                    $zone = self::catch_szbd_zones($value, $cost_, $current_cart);
                   
                    if (is_null($zone)) {
                       
                        continue;

                    }
                    $szbd_zone[] = $zone;

                    $store_address = class_exists('WC_SZBD_Shipping_Method') ? WC_SZBD_Shipping_Method::get_store_address_latlng_string() : false;
                }
            }
            $method = null;
            $cost = null;
            $del_price = null;
            $is_free_shipping = false;
            $min_amount = 0;
            if (isset($the_method) && is_array($the_method)) {
                $method = $the_method[0]['method'];
                $is_free_shipping = $the_method[0]['is_free_shipping'];
                if (array_key_exists('cost', $the_method[0])) {
                    $cost = min(array_column($the_method, 'cost'));
                    $del_price = wc_price($cost);
                }
                if (array_key_exists('min_amount', $the_method[0])) {
                    $min_amount = min(array_column($the_method, 'min_amount'));
                }
                if (array_key_exists('free_shipping_conditional', $the_method[0])) {
                    $free_shipping_conditional = $the_method[0]['free_shipping_conditional'];
                }
                if (array_key_exists('is_taxable', $the_method[0])) {
                    $is_taxable = $the_method[0]['is_taxable'];
                }
            }
            if (defined('SZBD_PREM_VERSION') || defined('SZBD_VERSION')) {
                $szbd_extra = array(
                    'default_origin' => defined('SZBD_PREM_VERSION') && version_compare(SZBD_PREM_VERSION, '2.6.9', '>=') ? self::$default_origin_flag : true,

                    'extra' => WC()
                        ->session
                        ->get('chosen_shipping_methods'),
                    '$available_methods' => $available_methods,
                    'status' => true,
                    'exclude' => get_option('szbd_exclude_shipping_methods', 'no'),
                    'tot_amount' => self::get_cart_total(),
                    'do_driving_time_car' => self::$do_driving_time_car_flag,
                    'do_driving_time_bike' => self::$do_driving_time_bike_flag,
                    'do_radius' => self::$do_radius_flag,
                    'do_map' => self::$do_map_flag,
                    'do_driving_dist' => self::$do_driving_distance_flag,
                    'do_bike_dist' => self::$do_bike_distance_flag,
                    'do_dynamic_rate_car' => self::$do_car_dynamic_rate_flag,
                    'do_dynamic_rate_bike' => self::$do_bike_dynamic_rate_flag,
                    'store_address' => WC()
                        ->session
                        ->get('szbd_store_address', false),
                    'delivery_address' => WC()
                        ->session
                        ->get('szbd_delivery_address', false),
                    'delivery_address_string' => WC()
                        ->session
                        ->get('szbd_delivery_address_string', false),
                    'delivery_duration_driving' => WC()
                        ->session
                        ->get('szbd_delivery_duration_car', false),
                    'distance_driving' => WC()
                        ->session
                        ->get('szbd_distance_car', false),
                    'delivery_duration_bicycle' => WC()
                        ->session
                        ->get('szbd_delivery_duration_bike', false),
                    'distance_bicycle' => WC()
                        ->session
                        ->get('szbd_distance_bike', false),
                    //   'calculated_distance' => $distance_value,
                    'version' => defined('SZBD_VERSION') ? SZBD_VERSION : SZBD_PREM_VERSION,
                    'is_prem' => defined('SZBD_PREM_VERSION'),
                );
            }
            $args = array(
                'cost' => $cost,
                'is_taxable' => isset($is_taxable) ? $is_taxable : false,
                'min_amount' => isset($min_amount) ? $min_amount : 0,
                'free_shipping_conditional' => isset($free_shipping_conditional) ? $free_shipping_conditional : false,
                'status' => true,
                'del_price' => $del_price,
                'szbd_zones' => isset($szbd_zone) ? $szbd_zone : null,
                'woocommerce_methods' => isset($the_method) ? $the_method : null,
                'szbd_extra' => isset($szbd_extra) ? $szbd_extra : null,
                'method_sharp' => '',
                'method' => $method,
                'is_free_shipping' => $is_free_shipping,
                'tot_amount' => self::get_cart_total(),
                'store_address' => isset($store_address) ? $store_address : false,
                'current_cart' => $current_cart == 1 ? 1 : 0,
                'have_cart' => !isset(WC()->cart) || WC()->cart->is_empty() ? 0 : 1,
            );
            return $args;
        }
        static function get_cart_total($ignore_discounts = false)
        {

            // Doing subtotal same approach as WooCommerce shipping method "Free Shipping" since 1.4.8.10
            $total = WC()->cart->get_displayed_subtotal();

            if (WC()->cart->display_prices_including_tax()) {
                $total = $total - WC()->cart->get_discount_tax();
            }
            if (!is_numeric($total)) {
                $total = floatval($total);
            }
            $precision = wc_get_price_decimals();

            if (!$ignore_discounts) {
                $total = $total - WC()->cart->get_discount_total();
            }

            $tot_amount = round($total, $precision, PHP_ROUND_HALF_UP);
            return $tot_amount;
        }
        public function cma_get_wc_price()
        {
            if (!empty($_POST['cost'])) {
                $price = (float) ($_POST['cost']);
                $price2 = isset($_POST['next_best']) ? $_POST['next_best'] : null;
                $response = array(
                    'price' => wc_price($price),
                    'price2' => !is_null($price2) ? wc_price($price2) : null,
                );
            } else {
                $response = array(
                    'price' => ''
                );
            }
            wp_send_json($response);
        }
        public function cma_in_array_field($needle, $needle_field, $haystack, $strict = false)
        {
            if ($strict) {
                foreach ($haystack as $item)
                    if (isset($item->$needle_field) && $item->$needle_field === $needle)
                        return true;
            } else {
                foreach ($haystack as $item)
                    if (isset($item->$needle_field) && $item->$needle_field == $needle)
                        return true;
            }
            return false;
        }
    } // End of Class
    $CMA_Del = new CMA_Del();
}

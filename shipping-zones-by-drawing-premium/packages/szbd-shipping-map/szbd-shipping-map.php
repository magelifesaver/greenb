<?php
/**
 * Plugin Name:     SZBD Map
 * Version:         1.6
 * Author:          Arosoft.se
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     szbd
 *
 * @package         create-block
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

// Return directly if map features is disabled
if (get_option('szbd_precise_address', 'no') == 'no') {
    return;
}




$plugin_data = get_file_data(__FILE__, array('version' => 'version'));
define('SZBD_SHIPPING_MAP_VERSION', $plugin_data['version']);
define('SZBD_PREM_PLUGINDIRURL2', plugin_dir_url(__FILE__));

/**
 * Include the dependencies needed to instantiate the block.
 */
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;






// Insert the selected shipping point as an additional field

add_action(
    'woocommerce_init',
    function () {
        woocommerce_register_additional_checkout_field(
            array(
                'id' => 'szbd/shipping_point',
                'label' => 'Picked Delivery Location Point',

                'location' => 'address',
                'required' => false,
                'attributes' => array(
                'readOnly' => true,
                   
                ),
                // In future, aiming to hide/show and validate front end with json attributes
               /* 'hidden' => [
                    "type" => "object",
                    "properties" => [
                        "checkout" => [
                            "properties" => [
                                "prefersCollection" => [
                                    "const" => true
                                ]
                            ]
                        ]
                    ]
                                ],*/
            )
        );
    }
);

add_action('enqueue_block_assets', 'szbd_enqueue_checkout_block_assets');
function szbd_enqueue_checkout_block_assets()
{





    if (
        !is_checkout() || WC()
            ->cart
            ->needs_shipping() === false || is_wc_endpoint_url('order-pay')
    ) {
        return;
    }
    if (get_option('szbd_precise_address', 'no') == 'no') {

        return;
    }






    $country_pos = null;
    if (get_option('szbd_precise_address', 'no') != 'no') {

        $request = wp_remote_get(SZBD_PREM_PLUGINDIRURL2 . 'src/json/countries.json');


        if (!is_wp_error($request)) {
            $body = wp_remote_retrieve_body($request);
            $country_pos = json_decode($body);
        }
    }
    $to_localize = array(

        'customer_stored_location' => get_option('szbd_auto_marker_saved', 'no') == 'yes' ? get_user_meta(get_current_user_id(), 'shipping_szbd-picked-location', true) : null,
        'countries' => $country_pos,

        'cart_string_1' => __('More shipping alternatives may exist when a full shipping address is entered.', 'szbd'),

        'no_marker_error' => __('You have to precise a location at the map', 'szbd'),
        'store_address' => get_option('szbd_store_address_mode', 'geo_woo_store') == 'pick_store_address' ? json_decode(get_option('SZbD_settings_test', ''), true) : SZBD::get_store_address(),

        'debug' => get_option('szbd_debug', 'no') == 'yes' ? 1 : 0,
        'deactivate_postcode' => get_option('szbd_deactivate_postcode', 'no') == 'yes' ? 1 : 0,
        'select_top_method' => get_option('szbd_select_top_method', 'no') == 'yes' ? 1 : 0,
        'store_address_picked' => get_option('szbd_store_address_mode', 'geo_woo_store') == 'pick_store_address' ? 1 : 0,
        'precise_address' => get_option('szbd_precise_address', 'no'),


        'is_checkout' => is_checkout() ? 1 : 0,
        'auto_marker' => get_option('szbd_auto_marker', 'no') == 'yes' ? 1 : 0,
        'is_custom_types' => get_option('szbd_types_custom', 'no') == 'yes' ? 1 : 0,
        'result_types' => get_option(
            'szbd_result_types',
            array(
                "establishment",
                "subpremise",
                "premise",
                "street_address",
                "plus_code"
            )
        ),
        'googleapi' => get_option('szbd_google_api_key', ''),
        'iw_areaLabel' => __("Your latest shipping location", 'szbd'),
        'iw_content' => __("This was the shipping location for your last delivery.", 'szbd'),
        'maptype' => get_option('szbd_map_type','roadmap'),
        'mapid' => !empty(get_option('szbd_google_map_id','SZBD_CHECKOUT_MAP')) ? get_option('szbd_google_map_id','SZBD_CHECKOUT_MAP') : 'SZBD_CHECKOUT_MAP' ,



    );

    if (is_checkout() && class_exists('WC_Blocks_Utils') && WC_Blocks_Utils::has_block_in_page(get_the_ID(), 'woocommerce/checkout') && get_option('szbd_server_mode', 'yes') == 'yes') {
        $deps = array('jquery', 'wc-checkout', 'underscore');
        $google_api_key = get_option('szbd_google_api_key', '');


        $script_path = WP_DEBUG === true ? '/src/js/szbd-shipping-map-block/map.js' : '/src/js/szbd-shipping-map-block/map.min.js';
        $script_url = plugins_url($script_path, __FILE__);

        wp_enqueue_script('szbd-checkout-block-2', $script_url, $deps, SZBD_SHIPPING_MAP_VERSION, array('strategy' => 'async', 'in_footer' => false));

        wp_add_inline_script('szbd-checkout-block-2', '(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
            key: "' . $google_api_key . '",
            v: "quarterly",
           
           
          });', 'before');


        wp_localize_script('szbd-checkout-block-2', 'szbd', $to_localize);

    }





}




add_action('woocommerce_blocks_loaded', function () {

    // Validate mandatory point at checkout. Make sure this happens before saving point meta data and deleting meta data of additional fields. This action has priority 9 and saving has 10.
    add_action(
        'woocommerce_store_api_checkout_update_order_from_request',
        function ($order, $request) {
          

            if(get_option('szbd_precise_address') == 'always' && get_option('szbd_precise_address_mandatory') == 'yes'){

                $message = __('You have to pick a delivery point on the map.','szbd');

                if ($order->meta_exists('_wc_shipping/szbd/shipping_point') ) {

                    $point =   $order->get_meta('_wc_shipping/szbd/shipping_point');
    
                   
                    if(!szbd_is_json($point)){
    
                        throw new Exception( $message);
    
                    }
                }else{

                    throw new Exception( $message);

                }


            }

            



        },9,2);


    // Save extension data (pluscode) and picked delivery location to order meta data
    add_action(
        'woocommerce_store_api_checkout_update_order_from_request',
        function ($order, $request) {


            if (isset($request['extensions']['szbd'])) {





                $pluscode = $request['extensions']['szbd']['pluscode'];
                $doSave = false;



                if (!is_null($pluscode) && $pluscode !== '') {


                    $order->update_meta_data('szbd_picked_delivery_location_plus_code', $pluscode);
                    $doSave = true;
                }
            }

            if ($order->meta_exists('_wc_shipping/szbd/shipping_point') || $order->meta_exists('_wc_billing/szbd/shipping_point')) {

                $point =   $order->get_meta('_wc_shipping/szbd/shipping_point');

               
                if(szbd_is_json($point)){

                    $order->update_meta_data('szbd_picked_delivery_location', $point);

                }
               

                $order->update_meta_data('_wc_shipping/szbd/shipping_point', '');
                $order->update_meta_data('_wc_billing/szbd/shipping_point', '');

                $doSave = true;
            }






            if ($doSave) {
                $order->save();
            }

        },
        10,
        2
    );
    // Save customer picked point
    add_action(
        'woocommerce_store_api_checkout_update_customer_from_request',
        function ($customer, $request) {

            if (!isset($request['shipping_address']['szbd/shipping_point'])) {
                return;
            }
            if (!is_user_logged_in() ){
                return;
            }
                

            $value = $request['shipping_address']['szbd/shipping_point'];

            if ( szbd_is_json($value)) {
               
               
                update_user_meta($customer->get_id(), 'shipping_szbd-picked-location', stripslashes($value));


            }


        },
        10,
        2
    );



    $args4 = array(
        'endpoint' => CheckoutSchema::IDENTIFIER,
        'namespace' => 'szbd',
        'schema_callback' => function () {
            return array(
               /* 'point' => array(
                    'lat' => array(
                        'type' => 'number',
                    ),
                    'lng' => array(
                        'type' => 'number',
                    ),
                ),*/
                'pluscode' => array(
                    'type' => 'string',
                )
            );
        },
        'schema_type' => ARRAY_A,

    );

    woocommerce_store_api_register_endpoint_data(
        $args4
    );

    woocommerce_store_api_register_update_callback(
        [
            'namespace' => 'szbd-shipping-map-pluscode',
            'callback' => function ($pluscode) {

                WC()
                    ->session
                    ->set('szbd_delivery_pluscode', $pluscode);


            }
        ]
    );

    woocommerce_store_api_register_update_callback(
        [
            'namespace' => 'szbd-shipping-map-update',
            'callback' => function ($delivery_location) {

                szbd_clear_session();
                szbd_clear_wc_shipping_rates_cache();
                if ($delivery_location['lat'] != null) {
                    WC()
                        ->session
                        ->set('szbd_delivery_address', (object) ['lat' => (float) $delivery_location['lat'], 'lng' => (float) $delivery_location['lng'], 'fromUI' => true]);

                }

                add_filter('woocommerce_package_rates', array('SZBD', 'szbd_filter_shipping_methods_for_checkout_server_mode'), 999);

            }
        ]
    );

   


    $args = array(
        'endpoint' => CartSchema::IDENTIFIER,
        'namespace' => 'szbd-shipping-map',
        'data_callback' => function () {



            $m = WC()
                ->session
                ->get('szbd_delivery_address', null);
            $formatted_address = szbd_get_customer_formatted_address();

            $faild_requst = WC()
                ->session
                ->get('szbd_delivery_address_faild', null);

            return array(
                'shipping_point' => $m,
                'formatted_address' => $formatted_address,
                'faild' => $faild_requst,
            );
        },
        'schema_callback' => function () {
            return array(
                'properties' => array(
                    'shipping_point' => array(
                        'type' => 'string',
                    ),
                    'formatted_address' => array(
                        'type' => 'string',
                    ),
                    'failed' => array(
                        'type' => 'string',
                    ),
                ),
            );
        },
        'schema_type' => ARRAY_A,
    );


    woocommerce_store_api_register_endpoint_data(
        $args
    );
}, 2, 99);
//});
function szbd_get_customer_formatted_address()
{
    $country = WC()->cart->get_customer()->get_shipping_country();
    $country_text = WC()->countries->countries[$country];
    $state = WC()->cart->get_customer()->get_shipping_state();
    $states = WC()->countries->get_states($country);
    $state_text = !empty($states[$state]) ? $states[$state] : '';
    $postcode = wc_format_postcode(WC()->cart->get_customer()->get_shipping_postcode(), $country);
    $city = WC()->cart->get_customer()->get_shipping_city();





    $destination = array('country' => $country, 'country_text' => $country_text, 'state' => $state, 'postcode' => $postcode, 'city' => $city);
    add_filter('woocommerce_localisation_address_formats', 'szbd_modify_address_formats', 999, 1);
    $formatted_address_string = WC()->countries->get_formatted_address($destination, $separator = ',');

    return $formatted_address_string;
}

add_action('woocommerce_blocks_loaded', function () {
    require_once __DIR__ . '/szbd-shipping-map-blocks-integration.php';
    add_action(
        'woocommerce_blocks_checkout_block_registration',
        function ($integration_registry) {
            $integration_registry->register(new SZBD_Shipping_Map_Blocks_Integration());
        }
    );




});

/**
 * Registers the slug as a block category with WordPress.
 */
function register_SZBD_Shipping_Map_block_category($categories)
{
    return array_merge(
        $categories,
        [
            [
                'slug' => 'szbd-shipping-map',
                'title' => __('Shipping Map Blocks', 'szbd'),
            ],
        ]
    );
}
add_action('block_categories_all', 'register_SZBD_Shipping_Map_block_category', 10, 2);





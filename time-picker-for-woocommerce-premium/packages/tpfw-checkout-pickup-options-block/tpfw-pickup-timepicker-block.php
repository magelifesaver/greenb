<?php
/**
 * Plugin Name:     Pickup Timepicker for TMFW (tpfw-checkout-pickup-options-block)
 * Version:         1.1
 * Author:          Arosoft
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 
 *
 * @package         create-block
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;




define( 'TPFW_PICKUP_TIMEPICKER_VERSION', '1.1' );


if(get_option( 'tpfw_pickuptimes_enable', 'no' ) == 'no'){
    return;
}

/**
 * Include the dependencies needed to instantiate the block.
 */
add_action('woocommerce_blocks_loaded', function() {
    require_once __DIR__ . '/tpfw-pickup-timepicker-blocks-integration.php';
	add_action(
		'woocommerce_blocks_checkout_block_registration',
		function( $integration_registry ) {
			$integration_registry->register( new TPFW_Pickup_Timepicker_Blocks_Integration() );
		}
	);
});

add_action('woocommerce_blocks_enqueue_checkout_block_scripts_after', 'tpfw_enqueue_pickup_timepicker_checkout_block_assets');



function tpfw_enqueue_pickup_timepicker_checkout_block_assets(){
    
    if (
        !TPFW_Timepicker::is_pickup_enabled()
    ) {
         return;
    }

    wp_enqueue_style('tpfw-prem-timepicker-style', TPFW_PLUGINDIRURL . 'assets/jonthornton-jquery-timepicker/jquery.timepicker.min.css', array() , TPFW_VERSION);
    wp_enqueue_script('tpfw-prem-timepicker-pickup-script', TPFW_PLUGINDIRURL . 'assets/jonthornton-jquery-timepicker/jquery.timepicker.min.js', array(
        'jquery'
    ) , TPFW_VERSION, true);

    
        $script_path = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '/src/js/tpfw-pickup-timepicker-block/timepicker.js' : '/src/js/tpfw-pickup-timepicker-block/timepicker.min.js';
        $script_url = plugins_url($script_path, __FILE__);

        wp_enqueue_script('tpfw-tpfw-pickup-timepicker-script', $script_url , array(
            'jquery',
            'underscore',
            'tpfw-prem-timepicker-pickup-script'
        ) , TPFW_PICKUP_TIMEPICKER_VERSION, true);

        do_action('tpfw_before_get_picktime_args');
    
        $args = TPFW_Timepicker::get_picktime_args( true , false, null );
        wp_localize_script('tpfw-tpfw-pickup-timepicker-script', 'tpfwcheckoutpickup', $args);

    
   
   
    
    
  
}

add_action('woocommerce_blocks_loaded', function () {

    
    // Save order meta data
    add_action(
        'woocommerce_store_api_checkout_update_order_from_request',
        function ($order, $request) {
            $doSave = false;

          
       
            //Save Pickuptime meta
            if(isset($request['extensions']['tpfw-pickuptime']) ){
               

           
            $time = $request['extensions']['tpfw-pickuptime']['pickup_time'];
            $date = $request['extensions']['tpfw-pickuptime']['pickup_date'];
           
          
            
            

           

            if(!is_null($time) && $time !== '' && !is_null($date) && $date !== ''){

                $order->update_meta_data( 'tpfw_delivery_mode', 'pickup');
                $time = str_contains($time, '-') ? substr($time, 0, strrpos( $time, '-')) : $time;
                $order->update_meta_data( 'tpfw_picked_time_localized', stripslashes(TPFW_Time::format_datetime(sanitize_text_field($date . ' ' . $time) , $date, $time)));
                $order->update_meta_data( 'tpfw_picked_time', stripslashes(sanitize_text_field($date . ' ' . $time)));
                
                $order->update_meta_data( 'tpfw_picked_time_timestamp', stripslashes(TPFW_Time ::get_timestamp(sanitize_text_field($date . ' ' . $time))));
                $doSave = true;
                if (get_option('tpfw_timepicker_ranges', 'no') == 'yes') {
                    $range_size =  get_option('tpfw_pickuptime_step', 0) ;
                   $order->update_meta_data( 'tpfw_picked_time_range_end_localized', stripslashes(TPFW_Time::format_datetime_range_end(sanitize_text_field($date . ' ' . $time) , $range_size)));
                }
              
    
               
                
            }
        }
       
      
           
    if($doSave){
        $order->save();
    }
           
        },
        10,
        2
    );
   
    


    $args =	array(
        'endpoint'        => CheckoutSchema::IDENTIFIER,
        'namespace'       => 'tpfw-pickuptime',
        'schema_callback' => function() {
            return array(
                
                'pickup_time' =>  array(
                    'type' => 'string',
                ),
                'pickup_date' =>  array(
                    'type' => 'string',
                ),
               
            );
        },
        'schema_type'     => ARRAY_A,
    
);

woocommerce_store_api_register_endpoint_data(
    $args
);

   



   


   
}, 2);

/**
 * Registers the slug as a block category with WordPress.
 */
function register_TPFW_pickup_timepicker_block_category( $categories ) {
    return array_merge(
        $categories,
        [
            [
                'slug'  => 'tpfw-tpfw-pickup-timepicker',
                'title' =>  'TPFW Pickup Timepicker Blocks',
            ],
        ]
    );
}
add_action( 'block_categories_all', 'register_TPFW_pickup_timepicker_block_category', 11, 2 );

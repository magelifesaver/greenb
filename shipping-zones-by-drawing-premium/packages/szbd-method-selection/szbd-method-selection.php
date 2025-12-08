<?php
/**
 * Plugin Name:     SZBD Method selection
 * Version:         1.3
 * Author:          Arosoft.se
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     szbd
 *
 * @package         create-block
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


$plugin_data = get_file_data( __FILE__, array( 'version' => 'version' ) );
define( 'SHIPPING_METHOD_SELECTION_VERSION', $plugin_data['version'] );








 
/**
 * Include the dependencies needed to instantiate the block.
 */
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;


add_action(
	'woocommerce_blocks_loaded',
	function() {

		require_once __DIR__ . '/szbd-method-selection-blocks-integration.php';
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( new Szbd_Method_Selection_Blocks_Integration() );
			}
		);


		//Collect debug data
	if (get_option('szbd_debug', 'no') == 'yes' ) {
		
		 $debug_args = array(
        'endpoint' => CartSchema::IDENTIFIER,
        'namespace' => 'szbd-shipping-debug',
        'data_callback' => function () {

            $m = WC()
                ->session
                ->get('szbd_server_request_debug');
            return array(
                'debug' => [$m],
            );
        },
        'schema_callback' => function () {
            return array(
                'properties' => array(
                    'debug' => array(
                        'type' => 'string',
                    ),
                ),
            );
        },
        'schema_type' => ARRAY_A,
    );


    woocommerce_store_api_register_endpoint_data(
        $debug_args
    );
	}
}

	
);

/**
 * Registers the slug as a block category with WordPress.
 */
function register_Szbd_Method_Selection_block_category( $categories ) {
	return array_merge(
		$categories,
		[
			[
				'slug'  => 'szbd-method-selection',
				'title' => __( 'Szbd_method_selection Blocks', 'szbd' ),
			],
		]
	);
}
add_action( 'block_categories_all', 'register_Szbd_Method_Selection_block_category', 10, 2 );

add_action('woocommerce_blocks_loaded', function () {

add_action(
	'woocommerce_store_api_checkout_update_order_from_request',
	function ($order, $request) {

	return;   
   print_r($order);
	   
		if(isset($request['extensions']['fdoe-mode']) && get_option('fdoe_enable_delivery_switcher', 'no') !== 'no' ){
			$shipping_mode = $request['extensions']['fdoe-mode']['mode'];

		// Check that shipping method is ok with mode
		$ids = array();
		foreach ( $order->get_shipping_methods() as $shipping_method ) {
			$ids[] = $shipping_method->get_method_id();
		}
		   $shipping_method = $ids[0];
		 

		  $errors = 'blocks'; 
		  $ok_method = true;
		switch ($shipping_mode) {
			case 'delivery':

				$ok_method = $shipping_method != 'pickup_location' && $shipping_method !=  'fdoe-eathere-shipping-method';
				break;

			case 'pickup':

				$ok_method = $shipping_method == 'pickup_location';
				break;

			case 'eathere':

				$ok_method = $shipping_method == 'fdoe-eathere-shipping-method';
			  
				 
				break;

			   default: 
			   
			   break;
			}
		if($ok_method == false){
			$default_message = __("The selected shipping method is not for your shipping mode.", 'fdoep');
			Food_Online::add_checkout_error_message($errors,null, $default_message, '');
		}    
	}
},8,2);

});

<?php

defined('ABSPATH') || exit;



$plugin_data = get_file_data(__FILE__, array('version' => 'version'));




add_action('woocommerce_blocks_loaded', function () {

  
   woocommerce_store_api_register_update_callback(
        [
            'namespace' => 'cma-new-address-set',
            'callback' => function ($data) {
               
                //silence
                return $data;


            }
        ]
    );
    

    

  
}, 2, 99);




add_action('woocommerce_blocks_loaded', function () {
    require_once __DIR__ . '/integration.php';
    add_action(
        'woocommerce_blocks_checkout_block_registration',
        function ($integration_registry) {
            $integration_registry->register(new CMA_Integration());
        }
    );




});



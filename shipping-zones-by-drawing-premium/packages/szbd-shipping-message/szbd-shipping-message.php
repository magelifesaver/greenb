<?php
/**
 * Plugin Name:     SZBD Message
 * Version:         1.3
 * Author:          Arosoft.se
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     szbd
 *
 * @package         create-block
 */
defined('ABSPATH') || exit;





use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;





 

$plugin_data = get_file_data(__FILE__, array('version' => 'version'));
define('SZBD_SHIPPING_MESSAGE_VERSION', $plugin_data['version']);

add_action('woocommerce_blocks_loaded', function () {


    $args = array(
        'endpoint' => CartSchema::IDENTIFIER,
        'namespace' => 'szbd-shipping-message',
        'data_callback' => function () {

            $message = SZBD::$message;
            $message = is_string($message) ? $message : '';

           
            return array(
                'message' => [$message],
            );
        },
        'schema_callback' => function () {
            return array(
                'properties' => array(
                    'message' => array(
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



add_action('woocommerce_blocks_loaded', function () {
    require_once __DIR__ . '/szbd-shipping-message-blocks-integration.php';
    add_action(
        'woocommerce_blocks_checkout_block_registration',
        function ($integration_registry) {
            $integration_registry->register(new SZBD_Shipping_Message_Blocks_Integration());
        }
    );




});

/**
 * Registers the slug as a block category with WordPress.
 */
function register_SZBD_Shipping_Message_block_category($categories)
{
    return array_merge(
        $categories,
        [
            [
                'slug' => 'szbd-shipping-message',
                'title' => __('Shipping Message Blocks', 'szbd'),
            ],
        ]
    );
}
add_action('block_categories_all', 'register_SZBD_Shipping_Message_block_category', 10, 2);







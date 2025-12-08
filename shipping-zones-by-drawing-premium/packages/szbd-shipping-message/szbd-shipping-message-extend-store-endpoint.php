<?php
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;






class SZBD_Shipping_Message_Extend_Store_Endpoint
{

	private static $extend;


	const IDENTIFIER = 'szbd-shipping-message';


	public static function init()
	{
		self::$extend = Automattic\WooCommerce\StoreApi\StoreApi::container()->get(Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class);
		self::extend_store();




	}



	/**
	 * Registers the actual data into each endpoint.
	 */
	public static function extend_store()
	{

		$args = array(
			'endpoint' => CartSchema::IDENTIFIER,
			'namespace' => 'szbd-shipping-message',
			'data_callback' => function () {

				$message = SZBD::$message;
				$message = is_string($message) ? $message : '';

				$m = self::$extend->get_formatter('html')->format($message);
				return array(
					'message' => [$m],
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




	}







}


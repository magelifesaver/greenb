<?php
namespace Barn2\Plugin\WC_Fast_Cart\Admin;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Plugin\Licensed_Plugin;

/**
 * Provides functions for the plugin settings page in the WordPress admin.
 *
 * Settings are registered under: WooCommerce -> Settings -> Products -> Fast Cart
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Autocomplete implements Registerable, Standard_Service {

	private $plugin;

	/**
	 * Constructor
	 *
	 * @param Licensed_Plugin $plugin Plugin object.
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds hook to register autocomplete REST API
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', [ $this, 'init_rest_routes' ] );
	}

	/**
	 * Register Autocomplete REST API
	 *
	 * @return void
	 */
	public function init_rest_routes() {
		register_rest_route(
			'wc-fast-cart/v1',
			'apitest',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'test_api' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
			]
		);
	}

	/**
	 * Check to see if API key is valid.
	 *
	 * @param \WP_Rest_Request $request
	 * @return array|\WP_Error JSON response
	 */
	public function test_api( $request ) {

		if ( empty( $request->get_param( 'key' ) ) ) {
			return new \WP_Error( 'bad_params', 'The required parameters were not sent', [ 'code' => 403 ] );
		}

		$key = $request->get_param( 'key' );

		ob_start();

		?><html lang="en-US" itemscope="itemscope" itemtype="http://schema.org/WebPage">
			<head></head>
			<body>
				<script type="application/javascript">
					let failed;
					var placesApiSuccess = () => {
						window.parent.FastCartAdmin.mapApiResponse( 'success' );
					};
					var apiTest = () => {

						if ( failed ) {
							window.parent.FastCartAdmin.mapApiResponse( 'failure' );
							return;
						}

						//var testInput = document.getElementById( 'autocomplete-test' );
						const service = new google.maps.places.AutocompleteService();
						service.getQueryPredictions( { input: "pizza near london" }, placesApiSuccess );
					};
					var gm_authFailure = () => {
						let failed = true;
						window.parent.FastCartAdmin.mapApiResponse( 'failure' );
					};
				</script>
				<?php
				// phpcs:ignore WordPress.WP.EnqueuedResources
				printf( '<script src="https://maps.googleapis.com/maps/api/js?key=%s&libraries=places&callback=apiTest"></script>', esc_attr( $key ) );
				?>
			</body>
		</html>
		<?php

		$html = ob_get_clean();

		header( 'Content-Type:text/html' );

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $html;
		exit;
	}
}

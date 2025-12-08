<?php
namespace Barn2\Plugin\WC_Fast_Cart\Admin;

use Barn2\Plugin\WC_Fast_Cart\Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Util as Lib_Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Conditional;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Service_Container;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Plugin\Admin\Admin_Links;

/**
 * Handles general admin functions, such as adding links to our settings page in the Plugins menu.
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Admin_Controller implements Registerable, Standard_Service {

	use Service_Container;

	private $plugin;

	/**
	 * Undocumented function
	 *
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;
		$this->add_services();
	}

	/**
	 * Add hook to enqueue admin javascript.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_services();
		$this->start_all_services();
		add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_scripts' ] );
	}

	public function add_services() {
		$this->add_service( 'settings_page', new Settings_Page( $this->plugin ) );
		$this->add_service( 'admin_links',   new Admin_Links( $this->plugin ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param [type] $hook_suffix
	 * @return void
	 */
	public function load_admin_scripts( $hook_suffix ) {

		$script_version = $this->plugin->get_version();
		$settings       = [
			'keyTestEndpoint' => get_rest_url( null, 'wc-fast-cart/v1/apitest' ),
			'adminNonce'      => wp_create_nonce( 'wp_rest' ),
			'strings'         => [
				'emptyAPIKey'   => __( 'Please enter an API key', 'wc-fast-cart' ),
				'invalidAPIKey' => __( 'Your API key is invalid', 'wc-fast-cart' ),
				'validAPIKey'   => __( '✓ Valid key', 'wc-fast-cart' ),
			],
		];

		$debug_level = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 2 : ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 1 : 0 );

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( 'woocommerce_page_wc-settings' === $hook_suffix && 'fast-cart' === ( $_GET['tab'] ?? '' ) ) {
			wp_register_script( 'wc-fast-cart-admin', Util::get_asset_url( 'js/admin/wfc-admin.js' ), [ 'jquery' ], $script_version, true );
			wp_add_inline_script(
				'wc-fast-cart-admin',
				'const wc_fast_cart_admin_settings = ' . wp_json_encode( $settings ) . ';',
				'before'
			);
			wp_enqueue_script( 'wc-fast-cart-admin' );
		}
	}
}

<?php
namespace Barn2\Plugin\WC_Fast_Cart\Integration;

use Barn2\Plugin\WC_Fast_Cart\Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Conditional;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Rest\Rest_Server;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Util as Lib_Util;

/**
 * Integrates with WooCommerce Quick View Pro
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class WQV implements Premium_Service, Registerable {

	private $settings;
	private $theme;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = Util::get_settings();
	}

	/**
	 * Replace QVP loop button on checkout.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wc_quick_view_pro_shop_loop_button_hook', [ $this, 'integrate_quick_view_pro_loop_button' ] );
	}

	/**
	 * Adds quick view pro button to WFC product loop action
	 * hooks into `wc_quick_view_pro_shop_loop_button_hook` filter
	 *
	 * @since   v0.1
	 * @param   mixed   $hook   original value of action hook
	 * @return  string
	 */
	public function integrate_quick_view_pro_loop_button( $hook ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_REQUEST['wfc-cart'] ) ) {
			return $hook;
		}

		return 'wfc_after_shop_loop_item';
	}
}

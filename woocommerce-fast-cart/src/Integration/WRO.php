<?php
namespace Barn2\Plugin\WC_Fast_Cart\Integration;

use Barn2\Plugin\WC_Fast_Cart\Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;

/**
 * Integrates with WooCommerce Restaurant Ordering
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class WRO implements Premium_Service, Registerable {

	private $settings;
	private $theme;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = Util::get_settings();
	}

	/**
	 * Hide WRO notice when it's redundant with WFC notices.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wc_restaurant_ordering_show_cart_notice', [ $this, 'maybe_hide_wro_notice' ] );
	}

	/**
	 * Hides '[product] added to cart' notice from WC Restaurant Ordering plugin when Fast Cart auto-open setting has been enabled.
	 * hooks into `wc_restaurant_ordering_show_cart_notice` filter
	 *
	 * @since   v0.1
	 * @param   boolean $notice original value of whether or not to show the notice
	 * @return  boolean
	 */
	public function maybe_hide_wro_notice( $notice ) {

		if ( filter_var( $this->settings['enable_auto_open'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
			return false;
		}

		return $notice;
	}
}

<?php

namespace Barn2\Plugin\WC_Fast_Cart\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Setup_Wizard\Steps;

/**
 * Register the Fast Cart setup wizard
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Welcome extends Steps\Welcome {

	/**
	 * Undocumented function
	 */
	public function init() {

		parent::init();

		$this->set_tooltip( __( 'Use this setup wizard to quickly configure the most popular options for WooCommerce Fast Cart. You can easily change these options later on the plugin settings page or by relaunching the setup wizard.', 'wc-fast-cart' ) );
		$this->set_description( __( 'Speed up the customer journey in no time', 'wc-fast-cart' ) );
		$this->set_title( __( 'Welcome to WooCommerce Fast Cart', 'wc-fast-cart' ) );
	}
}

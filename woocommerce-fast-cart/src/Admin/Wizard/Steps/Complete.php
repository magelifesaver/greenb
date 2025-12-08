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
class Complete extends Steps\Ready {

	/**
	 * Constructor
	 */
	public function init() {
		parent::init();
		$this->set_id( 'ready' );
		$this->set_name( __( 'Done', 'wc-fast-cart' ) );
		$this->set_title( __( 'Setup Complete', 'wc-fast-cart' ) );
		$this->set_description( __( 'Congratulations, you have finished setting up the plugin! Customers can now complete their purchase much more quickly and easily.', 'wc-fast-cart' ) );
	}
}

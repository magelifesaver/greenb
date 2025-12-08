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
class Cross_Selling extends Steps\Cross_Selling {

	/**
	 * Constructor
	 */
	public function init() {

		parent::init();

		$this->set_id( 'more' );
		$this->set_name( __( 'More', 'wc-fast-cart' ) );
		$this->set_description(
			__( 'Enhance your store with these fantastic plugins from Barn2.', 'wc-fast-cart' )
		);
		$this->set_title( esc_html__( 'Extra features', 'wc-fast-cart' ) );
	}
}

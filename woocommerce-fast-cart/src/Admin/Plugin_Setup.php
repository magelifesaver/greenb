<?php
/**
 * Undocumented file.
 *
 * @package   Barn2\woocommerce-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Fast_Cart\Admin;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Util as Lib_Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Setup_Wizard\Starter as Setup_WizardStarter;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Plugin\Plugin_Activation_Listener;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Standard_Service;

/**
 * Undocumented class
 */
class Plugin_Setup implements Plugin_Activation_Listener, Registerable, Standard_Service {

	/**
	 * Wizard starter.
	 *
	 * @var Setup_WizardStarter
	 */
	private $starter;

	/**
	 * Plugin instance
	 *
	 * @var Licensed_Plugin
	 */
	private $plugin;

	/**
	 * Get things started
	 *
	 * @param string $file
	 * @param Licensed_Plugin $plugin Plugin object
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin  = $plugin;
		$this->starter = new Setup_WizardStarter( $plugin );
	}

	/**
	 * Register the service
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', [ $this, 'after_plugin_activation' ] );
	}

	/**
	 * On plugin activation determine if the setup wizard should run.
	 *
	 * @return void
	 */
	public function on_activate( $network_wide ) {
		if ( $this->starter->should_start() ) {
			$this->starter->create_transient();
		}
	}

	/**
	 * Do nothing.
	 *
	 * @return void
	 */
	public function on_deactivate( $network_wide ) {}

	/**
	 * Detect the transient and redirect to wizard.
	 *
	 * @return void
	 */
	public function after_plugin_activation() {
		if ( ! $this->starter->detected() ) {
			return;
		}

		if ( Lib_Util::is_woocommerce_active() ) {
			$this->starter->delete_transient();
			$this->starter->redirect();
		}
	}
}

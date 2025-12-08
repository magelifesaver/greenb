<?php
namespace Barn2\Plugin\WC_Fast_Cart;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Plugin\Premium_Plugin;

/**
 * The main plugin class. Responsible for setting up to core plugin services.
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin extends Premium_Plugin {

	const NAME    = 'WooCommerce Fast Cart';
	const ITEM_ID = 311420;

	/**
	 * Constructs and initalizes the main plugin class.
	 *
	 * @param string $file The root plugin __FILE__
	 * @param string $version The current plugin version
	 */
	public function __construct( $file = null, $version = '1.0' ) {
		parent::__construct(
			[
				'name'               => self::NAME,
				'id'                 => self::ITEM_ID,
				'version'            => $version,
				'file'               => $file,
				'is_woocommerce'     => true,
				'settings_path'      => 'admin.php?page=wc-settings&tab=' . Admin\Settings_Page::SECTION,
				'documentation_path' => '/kb-categories/fast-cart-kb/',
				'wc_features'        => [],
			]
		);

	}

	/**
	 * Registers the plugin services.
	 *
	 * @return void
	 */
	public function add_services() {
		$this->add_service( 'plugin_setup',              new Admin\Plugin_Setup( $this ) );
		$this->add_service( 'admin',                     new Admin\Admin_Controller( $this ) );
		$this->add_service( 'autocomplete',              new Admin\Autocomplete( $this ) );
		$this->add_service( 'setup_wizard',              new Admin\Wizard\Service( $this ) );
		$this->add_service( 'frontend_preview',          new Frontend_Preview( $this ) );
		$this->add_service( 'frontend_scripts',          new Frontend_Scripts( $this->get_version() ) );
		$this->add_service( 'frontend_templates',        new Frontend_Templates( $this->get_version() ) );
		$this->add_service( 'checkout_processing',       new Checkout_Processing() );
		$this->add_service( 'integration_themes',        new Integration\Themes() );
		$this->add_service( 'integration_paypal_legacy', new Integration\Paypal_Legacy() );
		$this->add_service( 'integration_paypal',        new Integration\Paypal() );
		$this->add_service( 'integration_wro',           new Integration\WRO() );
		$this->add_service( 'integration_wqv',           new Integration\WQV() );
	}

	/**
	 * Get the admin url of the setup wizard
	 *
	 * @return string
	 */
	public function get_wizard_url() {

		return admin_url( 'admin.php?page=' . $this->get_slug() . '-setup-wizard' );
	}
}

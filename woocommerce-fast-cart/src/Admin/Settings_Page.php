<?php
namespace Barn2\Plugin\WC_Fast_Cart\Admin;

use Barn2\Plugin\WC_Fast_Cart;
use Barn2\Plugin\WC_Fast_Cart\Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Conditional;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Standard_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\WooCommerce\Admin\Custom_Settings_Fields;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\WooCommerce\Admin\Plugin_Promo;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Admin\Settings_API_Helper;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Util as Lib_Util;

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
class Settings_Page implements Registerable, Conditional, Standard_Service {

	const SECTION = 'fast-cart';

	private $plugin;

	/**
	 * Constructor
	 *
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;

		$plugin_promo = new Plugin_Promo( $this->plugin, self::SECTION );
		$plugin_promo->register();
	}

	public function is_required() {
		return Lib_Util::is_admin();
	}

	/**
	 * Register settings page inside WooCommerce actions and filters
	 *
	 * @return void
	 */
	public function register() {
		$fields = new Custom_Settings_Fields( $this->plugin );
		$fields->register();

		add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_tab' ], 50 );
		
		add_action( 'woocommerce_update_options_' . self::SECTION, [ $this, 'update_settings' ] );
		add_action( 'woocommerce_settings_tabs_' . self::SECTION, [ $this, 'display_settings' ] );

		// Save license setting
		$license_setting = $this->plugin->get_license_setting();
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . $license_setting->get_license_setting_name(), [ $license_setting, 'save_license_key' ] );
	}

	/**
	 * Add tab to WooCommerce settings page.
	 *
	 * @param array $tabs Current WooCommerce tabs list
	 * @return array WooCommerce tabs list with Fast Cart added
	 */
	public function add_settings_tab( $tabs ) {
		$tabs[ self::SECTION ] = __( 'Fast Cart', 'wc-fast-cart' );
		return $tabs;
	}

	/**
	 * Register assets required for Fast Cart settings page.
	 *
	 * @return void
	 */
	public function display_settings() {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['skip-setup'] ) ) {
			update_option( 'wfc_plugin_setup_completed', 'skipped', false );
		}

		wp_enqueue_style( 'barn2-wfc-admin', Util::get_asset_url( 'css/admin/wfc-admin.css' ), [], WC_Fast_Cart\PLUGIN_VERSION );

		woocommerce_admin_fields( $this->get_settings() );

		add_action(
			'admin_footer',
			[ $this, 'add_wizard_popup_link' ]
		);
	}

	/**
	 * Add JavaScript that will display a warning when someone clicks on the Setup Wizard link.
	 *
	 * @return void
	 */
	public function add_wizard_popup_link() {

		$screen = get_current_screen();

		// translators: %s is replaced by Fast Cart
		$warning = sprintf( __( 'Warning: This will overwrite your existing settings for %s. Are you sure you want to continue?', 'wc-fast-cart' ), $this->plugin->get_name() );

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( $screen->id === 'woocommerce_page_wc-settings' && ( $_GET['tab'] ?? '' ) === 'fast-cart' ) {
			?>
			<script>
				jQuery( 'a[href*=woocommerce-fast-cart-setup-wizard]' ).on( 'click', function( e ) {
					return confirm( '<?php echo esc_html( $warning ); ?>' );
				});
			</script>
			<?php
		}
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public function update_settings() {

		$new_settings = $this->get_settings();

		woocommerce_update_options( $new_settings );

		$settings = get_option( 'wc_fast_cart_settings' );

		if ( isset( $settings['cart_icon_position'] ) && $settings['cart_icon_position'] !== 'none' ) {
			$settings['enable_cart_button'] = 'yes';
		} else {
			$settings['enable_cart_button'] = 'no';
		}

		update_option( 'wc_fast_cart_settings', $settings, 'yes' );
	}

	/**
	 * Get WooCommerce compatible list of settings.
	 *
	 * @return array
	 */
	public function get_settings() {

		$plugin_settings = Util::get_settings_list( $this->plugin, self::SECTION );

		return $plugin_settings;
	}
}

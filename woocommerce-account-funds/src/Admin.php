<?php
/**
 * Account Funds for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Account_Funds;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Admin\Orders;
use Kestrel\Account_Funds\Admin\Products;
use Kestrel\Account_Funds\Admin\Screens\Settings_Screen;
use Kestrel\Account_Funds\Admin\Screens\Store_Credit\Cashback_Screen;
use Kestrel\Account_Funds\Admin\Screens\Store_Credit\Milestones_Screen;
use Kestrel\Account_Funds\Admin\User_Profiles;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin as Base_Admin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Call_To_Action;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Menu;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Screen;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Admin as WordPress_Admin;
use WP_Screen;

/**
 * Admin handler.
 *
 * @since 4.0.0
 *
 * @method static Plugin plugin()
 */
final class Admin extends Base_Admin {

	/** @var string main menu slug */
	private const MAIN_MENU_SLUG = 'store-credit-cashback';

	/** @var class-string<Screen>[] */
	private const SCREENS = [
		Cashback_Screen::class,
		Milestones_Screen::class,
		Settings_Screen::class,
	];

	/**
	 * Admin constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		parent::__construct( $plugin );

		add_action( 'admin_init', function() use ( $plugin ) {
			Orders::initialize( $plugin );
			Products::initialize( $plugin );
			User_Profiles::initialize( $plugin );
		} );
	}

	/**
	 * Returns the main menu title.
	 *
	 * @NOTE Do not open access to public. This method is a helper to build the main menu slug for identifying submenu items.
	 *
	 * @since 4.0.0
	 * @internal
	 *
	 * @return string
	 */
	private static function main_menu_title() : string {

		return __( 'Account funds', 'woocommerce-account-funds' );
	}

	/**
	 * Returns the main menu slug.
	 *
	 * @NOTE Do not open access to public. This method is a helper to build the main menu slug for identifying submenu items.
	 *
	 * @since 4.0.0
	 * @internal
	 *
	 * @return string
	 */
	private static function main_menu_slug() : string {

		return Strings::string( self::main_menu_title() )->lowercase()->kebab_case()->to_string();
	}

	/**
	 * Returns the SVG icon for the menu item.
	 *
	 * @since 4.0.0
	 * @internal
	 *
	 * @return string
	 */
	private static function main_menu_icon() : string {

		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="woocommerce-account-funds-icon"><desc>' . self::plugin()->name() . '</desc><g><path d="M20.94 4.2V1.71a1.63 1.63 0 0 0 -0.66 -1.22 2.2 2.2 0 0 0 -1.8 -0.49L1 4a0.78 0.78 0 0 0 -0.36 0.22Z" fill="#000000" stroke-width="1"></path><path d="M22 5.7H0.5v16.51a1.5 1.5 0 0 0 1.5 1.5h20a1.5 1.5 0 0 0 1.5 -1.5V7.2A1.5 1.5 0 0 0 22 5.7ZM12.28 20a0.52 0.52 0 0 1 -0.56 0C10.53 19.21 6 15.9 6 12.71c0 -2 1 -3.5 3 -3.5 1.83 0 3 2.5 3 2.5s1.17 -2.5 3 -2.5c2 0 3 1.48 3 3.5 0 3.19 -4.53 6.5 -5.72 7.29Z" fill="#000000" stroke-width="1"></path></g></svg>';
	}

	/**
	 * Initialize the admin screens.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function initialize_screens() : void {

		parent::initialize_screens();

		Menu::register( self::MAIN_MENU_SLUG, self::main_menu_title() )
			->set_menu_icon( 'data:image/svg+xml;base64,' . base64_encode( self::main_menu_icon() ) ) // phpcs:ignore
			->set_position( 55.4999 )
			->set_submenu_items( self::SCREENS );
	}

	/**
	 * Returns the list of plugin screen IDs.
	 *
	 * @since 4.0.0
	 *
	 * @return string[]
	 */
	private static function get_plugin_screen_ids() : array {

		$base  = self::main_menu_slug() . '_page_';
		$items = [
			'toplevel_page_' . self::MAIN_MENU_SLUG,
		];

		foreach ( self::SCREENS as $screen ) {
			$items[] = $base . $screen::ID;
		}

		return $items;
	}

	/**
	 * Determines if the current screen belongs to the plugin.
	 *
	 * @since 4.0.0
	 *
	 * @param string|WP_Screen|null $screen ID or WordPress object
	 * @return bool
	 */
	public static function is_plugin_screen( $screen = null ) : bool {

		if ( null === $screen ) {
			$screen = WordPress_Admin::current_screen();
		}

		if ( $screen instanceof WP_Screen ) {
			$screen = $screen->id;
		}

		return is_string( $screen ) && in_array( $screen, self::get_plugin_screen_ids(), true );
	}

	/**
	 * Displays admin notices.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function add_admin_notices() : void {

		parent::add_admin_notices();

		$plugin  = self::plugin();
		$gateway = $plugin->gateway();

		if ( ! $gateway->is_enabled() ) {
			$message = __( 'The store credit gateway is not enabled. Customers will not be able to pay for orders using their store credit.', 'woocommerce-account-funds' );
			$cta     = Call_To_Action::create( [
				'label' => __( 'View gateway settings', 'woocommerce-account-funds' ),
				'url'   => $gateway->settings_url(),
			] );

			Notice::warning( $message )
				->without_title()
				->add_call_to_action( $cta )
				->set_dismissible( false )
				->set_display_condition( fn() => self::is_plugin_screen( WordPress_Admin::current_screen() ) )
				->dispatch();
		}

		if ( ! $plugin->is_new_installation() ) {

			$message = __( 'Version 4.0.0 of Kestrel Account funds for WooCommerce is a major upgrade paving the way for many new features to reward customers with store credit and expand your business opportunities in building customer loyalty.', 'woocommerce-account-funds' );
			$cta     = Call_To_Action::create( [
				'label' => __( 'Learn more', 'woocommerce-account-funds' ),
				'url'   => self::plugin()->documentation_url(),
			] );

			Notice::info( $message )
				->set_id( 'upgraded-to-v4.0.0' )
				->set_global( true )
				->set_title( __( 'Account funds for WooCommerce', 'woocommerce-account-funds' ) )
				->set_dismissible( true )
				->set_display_condition( fn() => current_user_can( 'manage_woocommerce' ) )
				->add_call_to_action( $cta )
				->dispatch();
		}
	}

}

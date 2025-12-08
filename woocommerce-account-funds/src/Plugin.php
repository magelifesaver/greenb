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

use Kestrel\Account_Funds\API\Store\Cart_Extension_Endpoint;
use Kestrel\Account_Funds\API\WooCommerce\Customers as WooCommerce_Customers_API;
use Kestrel\Account_Funds\Integrations\All_Products_Subscriptions;
use Kestrel\Account_Funds\Integrations\PayPal;
use Kestrel\Account_Funds\Integrations\Square;
use Kestrel\Account_Funds\Integrations\Subscriptions;
use Kestrel\Account_Funds\Lifecycle\Database;
use Kestrel\Account_Funds\Lifecycle\Installer;
use Kestrel\Account_Funds\Lifecycle\Migrations\Upgrade_To_Version_2_0_9;
use Kestrel\Account_Funds\Lifecycle\Migrations\Upgrade_To_Version_2_1_3;
use Kestrel\Account_Funds\Lifecycle\Migrations\Upgrade_To_Version_2_3_0;
use Kestrel\Account_Funds\Lifecycle\Migrations\Upgrade_To_Version_2_3_7;
use Kestrel\Account_Funds\Lifecycle\Migrations\Upgrade_To_Version_3_2_0;
use Kestrel\Account_Funds\Lifecycle\Migrations\Upgrade_To_Version_4_0_0;
use Kestrel\Account_Funds\Lifecycle\Milestones\Customer_Paid_With_Store_Credit;
use Kestrel\Account_Funds\Lifecycle\Milestones\First_Store_Credit_Awarded;
use Kestrel\Account_Funds\Lifecycle\Milestones\First_Store_Credit_Reward_Configured;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Marketing\Newsletter\Providers\Klaviyo;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Extension;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Features\Feature;
use WC_Account_Funds;

/**
 * Main plugin class.
 *
 * @since 3.2.0
 */
final class Plugin extends Extension {

	/** @var string plugin ID */
	protected const ID = 'account_funds';

	/** @var string plugin version */
	protected const VERSION = '4.0.8';

	/** @var string plugin vendor */
	protected const VENDOR = 'Kestrel';

	/** @var string plugin text domain */
	protected const TEXT_DOMAIN = 'woocommerce-account-funds';

	/** @var string URL pointing to the plugin's documentation */
	protected const DOCUMENTATION_URL = 'https://woocommerce.com/document/account-funds/';

	/** @var string URL pointing to the plugin's support */
	protected const SUPPORT_URL = 'https://kestrelwp.com/contact/';

	/** @var string URL pointing to the plugin's sales page */
	protected const SALES_PAGE_URL = 'https://woocommerce.com/products/account-funds/';

	/** @var string URL pointing to the plugin's reviews */
	protected const REVIEWS_URL = 'https://woocommerce.com/products/account-funds/#reviews';

	/** @var bool|null */
	protected ?bool $is_multilingual = true;

	/**
	 * Plugin constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args
	 */
	protected function __construct( array $args = [] ) {

		parent::__construct( wp_parse_args( $args, [
			'admin'        => [
				'handler' => Admin::class,
			],
			'blocks'       => [
				'handler' => Blocks::class,
			],
			'lifecycle'    => [
				'installer'  => Installer::class,
				'migrations' => [
					'2.0.9' => Upgrade_To_Version_2_0_9::class,
					'2.1.3' => Upgrade_To_Version_2_1_3::class,
					'2.3.0' => Upgrade_To_Version_2_3_0::class,
					'2.3.7' => Upgrade_To_Version_2_3_7::class,
					'3.2.0' => Upgrade_To_Version_3_2_0::class,
					'4.0.0' => Upgrade_To_Version_4_0_0::class,
				],
				'milestones' => [
					First_Store_Credit_Reward_Configured::class,
					First_Store_Credit_Awarded::class,
					Customer_Paid_With_Store_Credit::class,
				],
			],
			'marketing'    => [
				'newsletter' => [
					'provider' => Klaviyo::class,
					'config'   => [
						'company_id' => 'RYdheN',
						'list_id'    => 'TUtVjU',
					],
				],
			],
			'integrations' => [
				All_Products_Subscriptions::class,
				PayPal::class,
				Square::class,
				Subscriptions::class,
			],
			'woocommerce'  => [
				'payment_gateways'      => [
					Gateway::class,
				],
				'supported_features'    => [
					Feature::CART_CHECKOUT_BLOCKS,
					Feature::HPOS,
				],
				'system_status_handler' => System_Status_Report::class,
			],
		] ) );
	}

	/**
	 * Return the plugin name.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function name() : string {

		return __( 'Account Funds', 'woocommerce-account-funds' );
	}

	/**
	 * Return the plugin settings
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function settings_url() : string {

		return admin_url( 'admin.php?page=store-credit-settings' );
	}

	/**
	 * Returns the plugin gateway instance.
	 *
	 * @since 4.0.0
	 *
	 * @return Gateway
	 */
	public function gateway() : Gateway {

		return Gateway::instance( $this );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	protected function initialize() : void {

		Database::check_tables();
		Database::filter_legacy_user_account_funds();

		parent::initialize();

		Products::initialize( $this );
		Orders::initialize( $this );
		Users::initialize( $this );

		$this->initialize_legacy();

		Shortcodes::initialize( $this );

		Cart::initialize( $this );
		Cart_Extension_Endpoint::initialize( $this );

		WooCommerce_Customers_API::initialize( $this );

		self::add_action( 'init', [ $this, 'initialize_endpoints' ] );
	}

	/**
	 * Initialize the plugin endpoints.
	 *
	 * @NOTE Ideally this should be moved to a separate handler in the future.
	 *
	 * @see Installer::activate() may to be enought to guarantee that the endpoint is persisted.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function initialize_endpoints() : void {

		$endpoint = get_option( 'woocommerce_myaccount_account_funds_endpoint', 'account-funds' );

		add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES ); // @phpstan-ignore-line WP constants
	}

	/**
	 * Initialize the legacy codebase.
	 *
	 * @since 4.0.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	private function initialize_legacy() : void {

		$this->define_legacy_constants();

		require_once dirname( __DIR__ ) . '/legacy/class-wc-account-funds.php';

		new WC_Account_Funds(); // legacy codebase
	}

	/**
	 * Define legacy constants.
	 *
	 * These constants are defined for backward compatibility with legacy code and should not be relied upon nor used in new code.
	 *
	 * @since 3.2.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	private function define_legacy_constants() : void {

		$constants = [
			'WC_ACCOUNT_FUNDS_FILE'     => $this->absolute_file_path(),
			'WC_ACCOUNT_FUNDS_VERSION'  => self::VERSION,
			'WC_ACCOUNT_FUNDS_PATH'     => trailingslashit( $this->absolute_dir_path() ),
			'WC_ACCOUNT_FUNDS_URL'      => trailingslashit( $this->base_url() ),
			'WC_ACCOUNT_FUNDS_BASENAME' => plugin_basename( $this->absolute_file_path() ),
		];

		foreach ( $constants as $name => $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
	}

}

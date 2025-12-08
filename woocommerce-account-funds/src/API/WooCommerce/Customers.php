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

namespace Kestrel\Account_Funds\API\WooCommerce;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * WooCommerce Customers API handler.
 *
 * @since 4.0.3
 */
final class Customers {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 4.0.3
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		self::add_filter( 'woocommerce_rest_prepare_customer', [ $this, 'add_customer_store_credit_balance_to_payload' ], 10, 3 );
	}

	/**
	 * Adds account funds data to the customer REST API response.
	 *
	 * @since 4.0.3
	 *
	 * @param mixed|WP_REST_Response $response
	 * @param mixed|WP_User $user
	 * @param mixed|WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	protected function add_customer_store_credit_balance_to_payload( $response, $user, $request ) {

		if ( ! $response instanceof WP_REST_Response || ! $request instanceof WP_REST_Request || ! $user instanceof WP_User ) {
			return $response;
		}

		$wallet = Wallet::get( $user->ID );

		// this is so to account for possible multi-currency support in the future
		$response->data['store_credit'] = [
			'balances' => [
				[
					'currency'          => $wallet->currency(),
					'balance'           => $wallet->balance(),
					'available_balance' => $wallet->available_balance(),
				],
			],
		];

		return $response;
	}

}

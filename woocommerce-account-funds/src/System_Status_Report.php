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

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\System_Status_Report as Base_System_Status_Report;
use Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Rewards\Cashback;
use Kestrel\Account_Funds\Store_Credit\Rewards\Milestone;

/**
 * WooCommerce system status report handler.
 *
 * @since 3.2.0
 *
 * @method static Plugin plugin()
 */
final class System_Status_Report extends Base_System_Status_Report {

	/**
	 * Gets the system status report title.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	protected static function get_report_title() : string {

		return __( 'Account Funds', 'woocommerce-account-funds' );
	}

	/**
	 * Gets the system status report data for the plugin.
	 *
	 * @since 3.2.0
	 *
	 * @param array<string, mixed> $context_args the context of the system status report
	 * @return array<string, array{
	 *     id?: string,
	 *     label?: string,
	 *     help?: string|null,
	 *     html?: string,
	 *     value?: array<scalar>|scalar,
	 * }>
	 */
	protected static function get_report_data( array $context_args = [] ) : array {

		$top_up_enabled = Store_Credit_Account_Top_Up::enabled();
		$min_top_up     = Store_Credit_Account_Top_Up::minimum_top_up();
		$max_top_up     = Store_Credit_Account_Top_Up::maximum_top_up();
		$top_up_rewards = Store_Credit_Account_Top_Up::exclude_top_up_from_rewards();
		$top_up_details = '';

		if ( $top_up_enabled ) {
			$top_up_details = sprintf(
				/* translators: Placeholders: %1$s - Minimum store credit top-up amount, %2$s - Maximum store credit top-up amount */
				__( 'Minimum: %1$s - Maximum: %2$s', 'woocommerce-account-funds' ),
				wc_price( $min_top_up ),
				$max_top_up ? wc_price( $max_top_up ) : __( 'No limit', 'woocommerce-account-funds' )
			);
		}

		$rewards  = [];
		$statuses = Reward_Status::options();

		foreach ( $statuses as $status => $status_label ) {

			$rewards['cashback'][ $status_label ] = Cashback::count( [
				'status' => $status,
			] );

			$rewards['milestone'][ $status_label ] = Milestone::count( [
				'status' => $status,
			] );
		}

		return [
			'Store credit label'       => [
				'id'    => 'store_credit_label',
				/* translators: Context: Label used to describe store credit in the frontend */
				'label' => __( 'Store credit label', 'woocommerce-account-funds' ),
				'help'  => __( 'Label used to display store credit in the frontend.', 'woocommerce-account-funds' ),
				/* translators: Placeholders: %1$s - Singular store credit label, %2$s - Plural store credit label */
				'html'  => sprintf( __( 'Singular: "%1$s" - Plural: "%2$s"', 'woocommerce-account-funds' ), Store_Credit_Label::singular()->to_string(), Store_Credit_Label::plural()->to_string() ),
				'value' => [ 'singular' => Store_Credit_Label::singular()->to_string(), 'plural' => Store_Credit_Label::plural()->to_string() ],
			],
			'Gateway enabled'          => [
				'id'    => 'store_credit_gateway_enabled',
				'label' => __( 'Gateway enabled', 'woocommerce-account-funds' ),
				'help'  => __( 'Whether the store credit gateway is enabled.', 'woocommerce-account-funds' ),
				'html'  => self::get_boolean_flag_markup( self::plugin()->gateway()->is_enabled() ),
				'value' => wc_string_to_bool( self::plugin()->gateway()->is_enabled() ),
			],
			'Partial payments enabled' => [
				'id'    => 'store_credit_partial_payments_enabled',
				'label' => __( 'Partial payments enabled', 'woocommerce-account-funds' ),
				'help'  => __( 'Whether customers can use store credit for partial payments.', 'woocommerce-account-funds' ),
				'html'  => self::get_boolean_flag_markup( self::plugin()->gateway()->allows_partial_payment() ),
				'value' => wc_string_to_bool( self::plugin()->gateway()->allows_partial_payment() ),
			],
			'Cashback'                 => [
				'id'    => 'store_credit_cashback',
				'label' => __( 'Cashback', 'woocommerce-account-funds' ),
				'help'  => __( 'Number of configured cashback awards.', 'woocommerce-account-funds' ),
				'value' => array_sum( $rewards['cashback'] ),
				'html'  => implode( ', ', array_map(
					static function( $count, $status ) {
						return sprintf( '%s (%d)', $status, $count );
					},
					$rewards['cashback'],
					array_keys( $rewards['cashback'] )
				) ),
			],
			'Milestones'               => [
				'id'    => 'store_credit_milestones',
				'label' => __( 'Milestones', 'woocommerce-account-funds' ),
				'help'  => __( 'Number of configured milestone awards', 'woocommerce-account-funds' ),
				'value' => array_sum( $rewards['milestone'] ),
				'html'  => implode( ', ', array_map(
					static function( $count, $status ) {
						return sprintf( '%s (%d)', $status, $count );
					},
					$rewards['milestone'],
					array_keys( $rewards['milestone'] )
				) ),
			],
			'Top-up enabled'           => [
				'id'    => 'store_credit_top_up_enabled',
				'label' => __( 'Top-up enabled', 'woocommerce-account-funds' ),
				'help'  => __( 'Customers are allowed to top up store credit from their account page.', 'woocommerce-account-funds' ),
				'html'  => self::get_boolean_flag_markup( $top_up_enabled, $top_up_details ),
				'value' => $top_up_enabled,
			],
			// values  intentionally available only in REST payload (see HTML output further above)
			'Top-up min amount'        => [
				'id'    => 'top_up_min_amount',
				'value' => $min_top_up,
			],
			'Top-up max amount'        => [
				'id'    => 'top_up_max_amount',
				'value' => $max_top_up ?: null,
			],
			'Top-up rewards allowed'   => [
				'id'    => 'top_up_rewards_allowed',
				'label' => __( 'Top-up rewards allowed', 'woocommerce-account-funds' ),
				'help'  => __( 'Whether top-ups are excluded from earning rewards.', 'woocommerce-account-funds' ),
				'html'  => self::get_boolean_flag_markup( ! $top_up_rewards ),
				'value' => ! $top_up_rewards,
			],
		];
	}

}

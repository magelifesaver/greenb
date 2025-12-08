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

namespace Kestrel\Account_Funds\Products;

defined( 'ABSPATH' ) or exit;

use Exception;
use Kestrel\Account_Funds\Lifecycle\Milestones\First_Store_Credit_Awarded;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Store_Credit\Eligible_Products_Group;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Rewards\Milestone;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;
use WC_Product;

/**
 * WooCommerce product handler for determining store credit interactions.
 *
 * @since 4.0.0
 */
final class Product {

	/** @var WC_Product|null */
	private ?WC_Product $product;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param WC_Product|null $product
	 */
	private function __construct( ?WC_Product $product ) {

		$this->product = $product;
	}

	/**
	 * Returns an order instance based on the identifier provided.
	 *
	 * @since 4.0.0
	 *
	 * @param int|mixed|Product|WC_Product $identifier
	 * @return Product
	 */
	public static function get( $identifier ) : self {

		if ( $identifier instanceof self ) {
			return $identifier;
		}

		if ( is_numeric( $identifier ) ) {
			$product = wc_get_product( $identifier );
		} elseif ( is_string( $identifier ) ) {
			$product = wc_get_product( wc_get_product_id_by_sku( $identifier ) );
		} else {
			$product = $identifier;
		}

		return new self( $product instanceof WC_Product ? $product : null );
	}

	/**
	 * Returns applicable product review milestone rewards for the current product.
	 *
	 * @since 4.0.0
	 *
	 * @param Wallet $wallet
	 * @return Transaction[]
	 */
	public function applicable_review_rewards( Wallet $wallet ) : array {

		if ( ! $this->product ) {
			return [];
		}

		$applicable = [];
		$milestones = Milestone::find_many( [
			'status'  => Reward_Status::ACTIVE,
			'deleted' => false,
		] );

		foreach ( $milestones as $milestone ) {

			if ( $milestone->get_trigger() !== Transaction_Event::PRODUCT_REVIEW || $milestone->is_depleted() ) {
				continue;
			}

			if ( $milestone->is_unique() && $wallet->has_credit_from( $milestone ) ) {
				continue;
			}

			if ( $milestone->requires_verified_product_reviews() && ! wc_customer_bought_product( $wallet->email(), $wallet->id(), $this->product->get_id() ) ) {
				continue;
			}

			if ( $milestone->is_limited_to_once_per_product() && $wallet->has_credit_from( $milestone, [ 'event' => Transaction_Event::PRODUCT_REVIEW, 'event_id' => $this->product->get_id() ] ) ) {
				continue;
			}

			$eligible_products = $milestone->get_eligible_products();
			$is_applicable     = false;

			if ( Eligible_Products_Group::ALL_PRODUCTS === $eligible_products ) {
				$is_applicable = true;
			} elseif ( Eligible_Products_Group::SOME_PRODUCTS === $eligible_products ) {
				$is_applicable = in_array( $this->product->get_id(), $milestone->get_products_ids(), true );
			} elseif ( Eligible_Products_Group::SOME_PRODUCT_CATEGORIES === $eligible_products ) {
				foreach ( get_the_terms( $this->product->get_id(), 'product_cat' ) as $category ) {
					if ( in_array( $category->term_id, $milestone->get_product_category_ids(), true ) ) {
						$is_applicable = true;
						break;
					}
				}
			}

			if ( ! $is_applicable ) {
				continue;
			}

			$applicable[] = Transaction::seed( [
				'amount'    => $milestone->get_amount(),
				'reward_id' => $milestone->get_id(),
				'event'     => Transaction_Event::PRODUCT_REVIEW,
			] );
		}

		return $applicable;
	}

	/**
	 * Awards store credit for a product review milestone.
	 *
	 * @since 4.0.0
	 *
	 * @param int $review_id
	 * @param Wallet $wallet
	 * @return void
	 */
	public function award_review_milestone( int $review_id, Wallet $wallet ) : void {

		if ( ! $this->product || ! $review_id ) {
			return;
		}

		$awards = 0;

		foreach ( $this->applicable_review_rewards( $wallet ) as $reward ) {
			try {
				$reward = $reward->set_event_id( $review_id );

				$wallet->credit( $reward );

				$awards++;
			} catch ( Exception $exception ) {
				Logger::warning( sprintf( 'Failed to award store credit to customer %s for reviewing product #%s review: %s', $wallet->id() ? '#' . $wallet->id() : 'with email ' . $wallet->email(), $this->product->get_id(), $exception->getMessage() ) );
			}
		}

		if ( $awards > 0 ) {
			First_Store_Credit_Awarded::trigger();
		}
	}

}

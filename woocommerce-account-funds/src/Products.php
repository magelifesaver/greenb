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

use Exception;
use Kestrel\Account_Funds\Orders\Order;
use Kestrel\Account_Funds\Products\Data_Stores\Store_Credit_Top_Up_Post_Type;
use Kestrel\Account_Funds\Products\Product;
use Kestrel\Account_Funds\Products\Store_Credit_Top_Up;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Rewards\Cashback;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use WC_Object_Data_Store_Interface;
use WC_Order;
use WC_Product;

/**
 * Products handler.
 *
 * @since 4.0.0
 */
final class Products {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		// register custom product types with WooCommerce
		self::add_action( 'init', [ $this, 'register_store_credit_product_types' ], PHP_INT_MIN );
		// register the store credit deposit product data store
		self::add_filter( 'woocommerce_data_stores', [ $this, 'register_store_credit_product_data_stores' ] );
		// store credit deposits utilize the standard add to cart button in the shop pages
		add_action( 'woocommerce_deposit_add_to_cart', fn() => woocommerce_simple_add_to_cart() );
		// ensure to return the store credit product type in WooCommerce
		self::add_filter( 'woocommerce_product_type_query', [ $this, 'set_store_credit_top_up_product_type' ], 10, 2 );
		self::add_filter( 'woocommerce_product_class', [ $this, 'set_store_credit_top_up_product_class' ], 10, 4 );

		// display store credit reward information in relevant places
		self::add_action( 'woocommerce_single_product_summary', [ $this, 'display_cashback_reward_information' ], 12 );
		self::add_filter( 'woocommerce_product_review_comment_form_args', [$this, 'display_review_milestone_reward_information' ] );

		// handle store credit milestones for reviewing products
		self::add_action( 'comment_post', [ $this, 'award_store_credit_on_product_review' ], 10, 3 );
	}

	/**
	 * Registers the plugin's custom product types with WooCommerce.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function register_store_credit_product_types() : void {

		// this is necessary to preserve an internal WooCommerce classmap that does not support PSR autoloading
		require_once self::plugin()->path( 'src/Products/Compatibility.php' );
	}

	/**
	 * Registers the {@see Store_Credit_Top_Up_Post_Type} data store with WooCommerce.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, class-string<WC_Object_Data_Store_Interface>>|mixed $data_stores
	 * @return array<string, class-string<WC_Object_Data_Store_Interface>>|mixed
	 */
	protected function register_store_credit_product_data_stores( $data_stores ) {

		if ( ! is_array( $data_stores ) || isset( $data_stores['product-topup'] ) ) {
			return $data_stores;
		}

		$data_stores['product-topup'] = Store_Credit_Top_Up_Post_Type::class;

		return $data_stores;
	}

	/**
	 * Filters the product type for {@see Store_Credit_Top_Up} products.
	 *
	 * This ensures a top-up product is returned when the customer attempts to top-up from the "My account" page.
	 *
	 * @since 4.0.0
	 *
	 * @param false|mixed|string $override product type to override
	 * @param int|mixed $product_id product ID
	 * @return 'topup'|false|mixed returns 'topup' for topup product, otherwise false
	 */
	protected function set_store_credit_top_up_product_type( $override, $product_id ) {

		if ( wc_get_page_id( 'myaccount' ) === $product_id ) {
			return 'topup';
		}

		return $override;
	}

	/**
	 * Filters the product object class for {@see Store_Credit_Top_Up} products.
	 *
	 * This ensures a top-up product instance will be returned for the corresponding product type.
	 *
	 * @since 4.0.0
	 *
	 * @param class-string<WC_Product>|mixed $classname
	 * @param mixed|string $product_type
	 * @param mixed|string $post_type
	 * @param int|mixed $product_id
	 * @return class-string<WC_Product>|mixed
	 */
	protected function set_store_credit_top_up_product_class( $classname, $product_type, $post_type, $product_id ) {

		if ( wc_get_page_id( 'myaccount' ) === $product_id ) {
			return Store_Credit_Top_Up::class;
		}

		return $classname;
	}

	/**
	 * Displays product review reward information in the product review form.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|mixed $args
	 * @return array<string, mixed>|mixed
	 */
	protected function display_review_milestone_reward_information( $args ) {
		global $product;

		if ( ! is_array( $args ) || ! isset( $args['title_reply'] ) || ! is_string( $args['title_reply'] ) || ! $product instanceof WC_Product || ! is_singular( 'product' ) ) {
			return $args;
		}

		/**
		 * Filters whether to display the product review milestone reward information.
		 *
		 * @since 4.0.0
		 *
		 * @param WC_Product $product
		 * @param bool $display_cashback_reward_information
		 */
		if ( false === apply_filters( 'wc_account_funds_should_display_review_milestone_reward_information', true, $product ) ) {
			return $args;
		}

		$customer       = wp_get_current_user();
		$customer_email = $customer->user_email;

		if ( ! $customer_email ) {
			$customer_email = is_email( get_query_var( 'customer_email' ) ) ? get_query_var( 'customer_email' ) : null;
		}

		$wallet = $customer_email ? Wallet::get( $customer_email ) : null;

		if ( ! $wallet || ! $wallet->id() || ! $wallet->email() ) {
			return $args;
		}

		$the_product = Product::get( $product );
		$rewards     = $the_product->applicable_review_rewards( $wallet );
		$amount      = array_reduce( $rewards, static function( float $carry, Transaction $reward ) : float {
			return $carry + $reward->get_amount();
		}, 0.0 );

		if ( $amount > 0.0 ) {
			/**
			 * Filters the message displayed in the product review form for the store credit reward.
			 *
			 * @since 4.0.0
			 *
			 * @param float $amount
			 * @param Wallet $wallet
			 * @param WC_Product $product
			 * @param string $message
			 */
			$message = (string) apply_filters( 'wc_account_funds_review_milestone_reward_information', sprintf(
				/* translators: Placeholders: %1$s - formatted store credit amount HTML, %2$s - store credit label */
				esc_html__( 'Earn %1$s in %2$s for reviewing this product!', 'woocommerce-account-funds' ),
				'<strong>' . wc_price( $amount ) . '</strong>',
				wc_account_funds_store_credit_label( 'plural' )
			), $amount, $wallet, $product );

			$args['comment_field'] = ' <p class="store-credit-reward store-credit-milestone store-credit-review-reward">' . $message . '</p> ' . $args['comment_field'];
		}

		return $args;
	}

	/**
	 * Displays rewardable cashback information for the current product's summary.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function display_cashback_reward_information() : void {
		global $product;

		if ( ! $product instanceof WC_Product || ! is_singular( 'product' ) ) {
			return;
		}

		/**
		 * Filters whether to display the cashback reward information for the current product.
		 *
		 * @since 4.0.0
		 *
		 * @param WC_Product $product
		 * @param bool $display_cashback_reward_information
		 */
		if ( false === apply_filters( 'wc_account_funds_should_display_cashback_reward_information_for_product', true, $product ) ) {
			return;
		}

		$customer = is_user_logged_in() ? get_current_user_id() : ( is_email( get_query_var( 'customer_email' ) ) ? get_query_var( 'customer_email' ) : null );
		$wallet   = $customer ? Wallet::get( $customer ) : null;

		if ( ! $wallet || ! $wallet->id() || ! $wallet->email() ) {
			return;
		}

		$rewards = Cashback::find_many( [
			'status'  => Reward_Status::ACTIVE,
			'deleted' => false,
		] );

		if ( $rewards->is_empty() ) {
			return;
		}

		try {
			// set up a dummy order to calculate the cashback rewards
			$wc_order = new WC_Order();
			$wc_order->set_customer_id( $wallet->id() );
			$wc_order->set_billing_email( $wallet->email() );
			$wc_order->add_product( $product );
			$wc_order->calculate_totals();
		} catch ( Exception $exception ) {
			// @phpstan-ignore-next-line
			if ( isset( $wc_order ) && $wc_order instanceof WC_Order ) {
				$wc_order->delete( true );
			}

			return;
		}

		$order   = Order::get( $wc_order );
		$rewards = $order->applicable_cashback_rewards( $wallet );
		$amount  = array_reduce( $rewards, static function( float $carry, Transaction $reward ) : float {
			return $carry + $reward->get_amount();
		}, 0.0 );

		// we need to call this because WooCommerce will persist the order even if we don't call save() ಠ_ಠ
		$wc_order->delete( true );

		if ( $amount > 0.0 ) {
			/**
			 * Filters the message displayed in the product summary for cashback rewards.
			 *
			 * @since 4.0.0
			 *
			 * @param float $amount
			 * @param Wallet $wallet
			 * @param WC_Product $product
			 * @param string $message
			 */
			$message = (string) apply_filters( 'wc_account_funds_cashback_reward_information_for_product', sprintf(
				/* translators: Placeholders: %1$s - formatted store credit amount HTML, %2$s - store credit label */
				esc_html__( 'Earn up to %1$s in %2$s upon purchase!', 'woocommerce-account-funds' ),
				'<strong>' . wc_price( $amount ) . '</strong>',
				wc_account_funds_store_credit_label()
			), $amount, $wallet, $product );

			echo '<p class="store-credit-reward store-credit-cashback store-credit-cashback-reward">' . $message . '</p>'; // phpcs:ignore
		}
	}

	/**
	 * Awards store credit when a product review is approved.
	 *
	 * @since 4.0.0
	 *
	 * @param int|mixed $review_id
	 * @param int|mixed|numeric-string|string $approved_flag
	 * @param array<string, mixed>|mixed $review_data
	 * @return void
	 */
	protected function award_store_credit_on_product_review( $review_id, $approved_flag, $review_data ) : void {

		$is_review = is_array( $review_data ) && isset( $review_data['comment_type'] ) && 'review' === $review_data['comment_type'];
		$is_spam   = 'spam' === $approved_flag;
		$is_reply  = is_array( $review_data ) && isset( $review_data['comment_parent'] ) && intval( $review_data['comment_parent'] ) > 0;

		if ( ! $is_review || $is_spam || $is_reply ) {
			return;
		}

		$product_id     = (int) $review_data['comment_post_ID'] ?: 0;
		$customer_id    = (int) $review_data['user_id'] ?: 0;
		$customer_email = is_array( $review_data ) && isset( $review_data['comment_author_email'] ) ? $review_data['comment_author_email'] : '';

		if ( ! $product_id || ! is_string( $customer_email ) || ! is_email( $customer_email ) ) {
			return;
		}

		$wallet  = Wallet::get( $customer_id ?: $customer_email );
		$product = Product::get( $product_id );

		$product->award_review_milestone( (int) $review_id, $wallet );
	}

}

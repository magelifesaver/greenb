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

namespace Kestrel\Account_Funds\Admin\Screens\Store_Credit;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Store_Credit\Eligible_Orders_Group;
use Kestrel\Account_Funds\Store_Credit\Eligible_Products_Group;
use Kestrel\Account_Funds\Store_Credit\Reward;
use Kestrel\Account_Funds\Store_Credit\Reward_Type;
use Kestrel\Account_Funds\Store_Credit\Rewards\Cashback;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;

/**
 * Admin screen for managing store credit cashback.
 *
 * @since 4.0.0
 */
final class Cashback_Screen extends Reward_Screen {

	/** @var string screen ID */
	public const ID = 'store-credit-cashback';

	/** @var string */
	protected const REWARD_TYPE = Reward_Type::CASHBACK;

	/**
	 * Returns the screen title.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_menu_title() : string {

		return __( 'Cashback', 'woocommerce-account-funds' );
	}

	/**
	 * Returns the screen title.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $editing whether this is an editing screen
	 * @return string
	 */
	public function get_page_title( bool $editing = false ) : string {

		if ( $editing ) {
			return __( 'Edit cashback reward', 'woocommerce-account-funds' );
		}

		return __( 'Add new cashback reward', 'woocommerce-account-funds' );
	}

	/**
	 * Returns the description for the edit screen.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected function get_edit_screen_description() : string {

		return __( 'Award store credit to customers when they make a purchase. Choose to award credit as percentage or fixed amount, relative to the whole order value or specific products within the order.', 'woocommerce-account-funds' );
	}

	/**
	 * Outputs explanations about each trigger the reward can be configured with.
	 *
	 * @since 4.0.0
	 *
	 * @param Cashback&Reward $reward
	 * @return string
	 */
	protected function get_reward_triggers_description( Reward $reward ) : string {

		ob_start();

		foreach ( array_keys( $this->get_reward_triggers() ) as $milestone_trigger ) :

			$style = 'display: block; clear: both; font-size: 12px; line-height: 24px; padding: 9px 0 0;';

			$style = $reward->get_trigger() === $milestone_trigger ? $style : $style . ' display: none;';

			?>
			<span class="trigger-description"
				data-trigger="<?php echo esc_attr( $milestone_trigger ); ?>"
				style="<?php echo esc_attr( $style ); ?>">
				<?php

				switch ( $milestone_trigger ) :
					case Transaction_Event::ORDER_PAID:
						esc_html_e( 'Award cashback store credit when the customer pays for an order that fits the eligibility rules.', 'woocommerce-account-funds' );

						break;
					case Transaction_Event::PRODUCT_PURCHASE:
						esc_html_e( 'Award cashback store credit for every eligible product within a paid order.', 'woocommerce-account-funds' );
						break;
					default:
						break;
				endswitch;

				?>
			</span>
			<?php

		endforeach;

		return ob_get_clean();
	}

	/**
	 * Outputs the options for the configuration rules in the main edit screen metabox.
	 *
	 * @since 4.0.0
	 *
	 * @param Cashback&Reward $reward
	 * @return void
	 *
	 * @phpstan-ignore-next-line
	 */
	protected function output_configuration_rules_panel( Reward $reward ) : void {

		?>
		<div class="options_group order_paid">
			<?php $this->output_configuration_rules_order_paid_options( $reward ); ?>
			<div class="clear"></div>
		</div>
		<div class="options_group product_purchase">
			<?php $this->output_configuration_rules_product_purchase_options( $reward ); ?>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Outputs the options for the configuration rules for the order paid event trigger.
	 *
	 * @since 4.0.0
	 *
	 * @param Cashback $cashback
	 * @return void
	 */
	private function output_configuration_rules_order_paid_options( Cashback $cashback ) : void {

		if ( $cashback->is_new() ) :
			echo '<p><em>' . esc_html__( 'Define who qualifies for cashback based on order value or contents.', 'woocommerce-account-funds' ) . '</em></p>';

		endif;

		woocommerce_wp_select( [
			'id'          => 'orders',
			'label'       => __( 'Eligible orders', 'woocommerce-account-funds' ),
			'class'       => 'wc-enhanced-select',
			'style'       => 'width: 80%; max-width: 370px;',
			'desc_tip'    => true,
			'description' => __( 'Award cashback only if the paid order meets the specified criteria.', 'woocommerce-account-funds' ),
			'value'       => $cashback->get_eligible_orders() ?: Eligible_Orders_Group::default_value(),
			'default'     => Eligible_Orders_Group::default_value(),
			'options'     => Eligible_Orders_Group::list(),
		] );

		?>
		<p class="form-field product_ids_field">
			<label for="order_paid_products"><?php esc_html_e( 'Products', 'woocommerce-account-funds' ); ?></label>
			<select id="order_paid_products" class="wc-product-search" multiple="multiple" style="width: 80%; max-width: 370px;" name="product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for products&hellip;', 'woocommerce-account-funds' ); ?>" data-action="woocommerce_json_search_products_and_variations">
				<?php

				$product_ids = $cashback->get_products_ids();

				// @phpstan-ignore-next-line remove when todo above is done
				foreach ( $product_ids as $product_id ) :
					$product = wc_get_product( $product_id );

					if ( ! $product ) :
						continue;

					endif;

					?>
					<option value="<?php echo esc_attr( (string) $product_id ); ?>" <?php echo wc_selected( $product_id, $product_ids ); ?>><?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?></option>
					<?php

				endforeach;

				?>
			</select>
		</p>
		<p class="form-field product_cat_ids_field">
			<label for="order_paid_product_categories"><?php esc_html_e( 'Product categories', 'woocommerce-account-funds' ); ?></label>
			<select id="order_paid_product_categories" name="product_cat_ids[]" style="width: 80%; max-width: 370px;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select categories&hellip;', 'woocommerce-account-funds' ); ?>">
				<?php

				$category_ids = $cashback->get_product_category_ids();
				$categories   = get_terms( [
					'taxonomy'   => 'product_cat',
					'orderby'    => 'name',
					'hide_empty' => false,
				] );

				if ( $categories ) :
					foreach ( $categories as $cat ) :
						echo '<option value="' . esc_attr( (string) $cat->term_id ) . '"' . wc_selected( $cat->term_id, $category_ids ) . '>' . esc_html( $cat->name ) . '</option>';

					endforeach;
				endif;

				?>
			</select>
		</p>
		<?php

		woocommerce_wp_select( [
			'id'                => 'product_types',
			'name'              => 'product_types[]',
			'label'             => __( 'Product types', 'woocommerce-account-funds' ),
			'class'             => 'wc-enhanced-select',
			'style'             => 'width: 80%; max-width: 370px;',
			'value'             => $cashback->get_product_types() ?: [],
			'options'           => wc_get_product_types(),
			'custom_attributes' => [
				'multiple'         => 'multiple',
				'data-placeholder' => __( 'Select product types&hellip;', 'woocommerce-account-funds' ),
			],
		] );

		$min_amount = $cashback->get_minimum_order_amount();

		woocommerce_wp_text_input( [
			'id'          => 'minimum_order_amount',
			'type'        => 'text',
			'data_type'   => 'price',
			/* translators: Placeholder: %s - Currency symbol */
			'label'       => sprintf( __( 'Minimum order value (%s)', 'woocommerce-account-funds' ), WooCommerce::currency()->symbol() ),
			'default'     => '',
			'value'       => $min_amount ? wc_format_localized_price( strval( max( 0.0, $min_amount ) ) ) : '',
			'desc_tip'    => true,
			'description' => [
				__( 'The order must reach this value (after discounts, before tax and shipping) to qualify for cashback.', 'woocommerce-account-funds' ),
				__( 'Leave blank for no minimum.', 'woocommerce-account-funds' ),
			],
		] );

		$max_amount = $cashback->get_maximum_order_amount();

		woocommerce_wp_text_input( [
			'id'          => 'maximum_order_amount',
			'type'        => 'text',
			'data_type'   => 'price',
			/* translators: Placeholder: %s - Currency symbol */
			'label'       => sprintf( __( 'Maximum order value (%s)', 'woocommerce-account-funds' ), WooCommerce::currency()->symbol() ),
			'default'     => '',
			'value'       => $max_amount ? wc_format_localized_price( strval( max( 0.0, $max_amount ) ) ) : '',
			'desc_tip'    => true,
			'description' => [
				__( 'Award cashback only if the order total is less than this amount.', 'woocommerce-account-funds' ),
				__( 'Leave blank for no maximum.', 'woocommerce-account-funds' ),
			],
		] );

		woocommerce_wp_select( [
			'id'          => 'order_paid_award_limits',
			/* translators: Context: Limit awarding store credit once per customer */
			'label'       => __( 'Award limits', 'woocommerce-account-funds' ),
			'desc_tip'    => false,
			'class'       => 'wc-enhanced-select award-limits',
			'style'       => 'width: 80%; max-width: 370px;',
			'description' => $this->get_award_limits_description( $cashback ),
			'value'       => $cashback->is_unique() ? 'once_per_customer' : 'unlimited',
			'default'     => 'unlimited',
			'options'     => [
				'unlimited'         => __( 'Unlimited', 'woocommerce-account-funds' ),
				'once_per_customer' => __( 'Once per customer', 'woocommerce-account-funds' ),
			],
		] );

		woocommerce_wp_checkbox( [
			'id'          => 'exclude_free_products',
			'label'       => __( 'Exclude free items', 'woocommerce-account-funds' ),
			'description' => __( 'Do not award cashback if the order contains any free products', 'woocommerce-account-funds' ),
			'default'     => 'no',
			'value'       => wc_bool_to_string( $cashback->excludes_free_items() ),
		] );

		woocommerce_wp_checkbox( [
			'id'          => 'exclude_products_on_sale',
			'label'       => __( 'Exclude items on sale', 'woocommerce-account-funds' ),
			'description' => __( 'Do not award cashback if the order contains any products marked on sale', 'woocommerce-account-funds' ),
			'default'     => 'no',
			'value'       => wc_bool_to_string( $cashback->excludes_items_on_sale() ),
		] );

		woocommerce_wp_checkbox( [
			'id'          => 'exclude_coupon_discounts',
			'label'       => __( 'Exclude coupons', 'woocommerce-account-funds' ),
			'description' => __( 'Do not award cashback if any coupon was applied to the order', 'woocommerce-account-funds' ),
			'default'     => 'no',
			'value'       => wc_bool_to_string( $cashback->excludes_coupon_discounts() ),
		] );
	}

	/**
	 * Outputs the options for the configuration rules for the order paid event trigger.
	 *
	 * @since 4.0.0
	 *
	 * @param Cashback $cashback
	 * @return void
	 */
	private function output_configuration_rules_product_purchase_options( Cashback $cashback ) : void {

		if ( $cashback->is_new() ) :
			echo '<p><em>' . esc_html__( 'Define who qualifies for cashback based on products within an order.', 'woocommerce-account-funds' ) . '</em></p>';

		endif;

		woocommerce_wp_select( [
			'id'          => 'products',
			'label'       => __( 'Eligible products', 'woocommerce-account-funds' ),
			'class'       => 'wc-enhanced-select',
			'style'       => 'width: 80%; max-width: 370px;',
			'default'     => 'all_products',
			'value'       => $cashback->get_eligible_products() ?: Eligible_Products_Group::default_value(),
			'desc_tip'    => true,
			'description' => __( 'Select the products that customers can buy to earn cashback.', 'woocommerce-account-funds' ),
			'options'     => Eligible_Products_Group::list(),
		] );

		?>
		<p class="form-field product_ids_field">
			<label for="product_purchase_products"><?php esc_html_e( 'Products', 'woocommerce-account-funds' ); ?></label>
			<select id="product_purchase_products" class="wc-product-search" multiple="multiple" style="width: 80%; max-width: 370px;" name="product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for products&hellip;', 'woocommerce-account-funds' ); ?>" data-action="woocommerce_json_search_products_and_variations">
				<?php

				$product_ids = $cashback->get_products_ids();

				foreach ( $product_ids as $product_id ) :
					$product = wc_get_product( $product_id );

					if ( ! $product ) :
						continue;

					endif;

					?>
					<option value="<?php echo esc_attr( (string) $product_id ); ?>"  <?php echo wc_selected( $product_id, $product_ids ); ?>><?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?></option>
					<?php

				endforeach;

				?>
			</select><?php echo wc_help_tip( __( 'Award cashback when the customer purchases any of the selected products.', 'woocommerce-account-funds' ) ); ?>
		</p>
		<p class="form-field product_cat_ids_field">
			<label for="product_purchase_product_categories"><?php esc_html_e( 'Product categories', 'woocommerce-account-funds' ); ?></label>
			<select id="product_purchase_product_categories" name="product_cat_ids[]" style="width: 80%; max-width: 370px;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select categories&hellip;', 'woocommerce-account-funds' ); ?>">
				<?php

				$category_ids = $cashback->get_product_category_ids();
				$categories   = get_terms( [
					'taxonomy'   => 'product_cat',
					'orderby'    => 'name',
					'hide_empty' => false,
				] );

				if ( $categories ) :
					foreach ( $categories as $cat ) :
						echo '<option value="' . esc_attr( (string) $cat->term_id ) . '"' . wc_selected( $cat->term_id, $category_ids ) . '>' . esc_html( $cat->name ) . '</option>';

					endforeach;
				endif;

				?>
			</select><?php echo wc_help_tip( __( 'Award cashback when the customer purchases any products in the selected product categories.', 'woocommerce-account-funds' ) ); ?>
		</p>
		<?php

		$unique      = $cashback->is_unique();
		$per_product = $cashback->is_limited_to_once_per_product();

		if ( $per_product ) {
			$value = 'once_per_product';
		} elseif ( $unique ) {
			$value = 'once_per_customer';
		} else {
			$value = 'unlimited';
		}

		woocommerce_wp_select( [
			'id'          => 'product_purchase_award_limits',
			/* translators: Context: Limit awarding store credit once per customer */
			'label'       => __( 'Award limits', 'woocommerce-account-funds' ),
			'class'       => 'wc-enhanced-select award-limits',
			'style'       => 'width: 80%; max-width: 370px;',
			'desc_tip'    => false,
			'description' => $this->get_award_limits_description( $cashback ),
			'default'     => 'unlimited',
			'value'       => $value,
			'options'     => [
				'unlimited'         => __( 'Unlimited', 'woocommerce-account-funds' ),
				'once_per_customer' => __( 'Once per customer', 'woocommerce-account-funds' ),
				'once_per_product'  => __( 'Once per product', 'woocommerce-account-funds' ),
			],
		] );

		woocommerce_wp_radio( [
			'id'      => 'product_qty',
			'label'   => __( 'Product quantity', 'woocommerce-account-funds' ),
			'default' => 'multiply',
			'value'   => $cashback->get_product_quantity_behavior(),
			'options' => [
				'multiply' => __( 'Multiply cashback awarded for each eligible product by its quantity in the same order', 'woocommerce-account-funds' ),
				'ignore'   => __( 'Award cashback once per eligible product ignoring its quantity in the same order', 'woocommerce-account-funds' ),
			],
		] );
	}

	/**
	 * Validates the cashback configuration.
	 *
	 * @param Cashback&Reward $reward
	 * @return void
	 */
	protected function validate_item( Reward $reward ) : void {

		// @phpstan-ignore-next-line type check
		if ( $reward instanceof Cashback ) {

			$min_amount = $reward->get_minimum_order_amount();
			$max_amount = $reward->get_maximum_order_amount();

			if ( $max_amount && $max_amount < $min_amount ) {
				$this->notices[] = Notice::warning( __( 'The maximum order amount defined by this cashback should not be less than the minimum order amount, otherwise the reward will be unapplicable.', 'woocommerce-account-funds' ), [ 'title' => '' ] );
			}
		}

		parent::validate_item( $reward );
	}

	/**
	 * Processes the cashback configuration for storage.
	 *
	 * @since 4.0.0
	 *
	 * @param Cashback&Reward $reward
	 * @return bool
	 */
	protected function process_item( Reward &$reward ) : bool {

		// @phpstan-ignore-next-line
		$saving = parent::process_item( $reward );

		if ( ! $saving || ! $reward instanceof Cashback ) {
			return false;
		}

		if ( $reward->get_trigger() === Transaction_Event::ORDER_PAID ) {

			$reward->set_eligible_orders( wc_clean( wp_unslash( $_POST['orders'] ?? null ) ) );

			// orders min/max amounts
			$raw_min_amount = wc_clean( wp_unslash( $_POST['minimum_order_amount'] ?? '' ) );
			$min_amount     = max( 0.0, $raw_min_amount ? floatval( wc_format_decimal( $raw_min_amount, wc_get_price_decimals() ) ) : 0.0 );
			$raw_max_amount = wc_clean( wp_unslash( $_POST['maximum_order_amount'] ?? '' ) );
			$max_amount     = max( 0.0, $raw_max_amount ? floatval( wc_format_decimal( $raw_max_amount, wc_get_price_decimals() ) ) : 0.0 );

			$reward->set_minimum_order_amount( $min_amount > 0.0 ? $min_amount : null );
			$reward->set_maximum_order_amount( $max_amount > 0.0 ? $max_amount : null );

			// unapplicable to order paid reward types
			$reward->set_eligible_products( null );

			$limits = wc_clean( wp_unslash( $_POST['order_paid_award_limits'] ?? 'unlimited' ) );
		} else { // Transaction_Event::PRODUCT_PURCHASE

			$reward->set_eligible_products( wc_clean( wp_unslash( $_POST['products'] ?? null ) ) );

			$reward->set_product_quantity_behavior( wc_clean( wp_unslash( $_POST['product_qty'] ?? null ) ) );

			// unapplicable to product purchase reward types
			$reward->set_eligible_orders( null );
			$reward->set_minimum_order_amount( null );
			$reward->set_maximum_order_amount( null );

			$limits = wc_clean( wp_unslash( $_POST['product_purchase_award_limits'] ?? 'unlimited' ) );
		}

		if ( 'once_per_customer' === $limits ) {
			$reward->set_unique( true );
			$reward->set_limited_to_once_per_product( false );
		} elseif ( 'once_per_product' === $limits ) {
			$reward->set_unique( false );
			$reward->set_limited_to_once_per_product( true );
		} else {
			$reward->set_unique( false );
			$reward->set_limited_to_once_per_product( false );
		}

		// exclude free items, items on sale and coupon discounts
		$reward->set_exclude_free_items( wc_string_to_bool( wc_clean( wp_unslash( $_POST['exclude_free_products'] ?? 'no' ) ) ) );
		$reward->set_exclude_items_on_sale( wc_string_to_bool( wc_clean( wp_unslash( $_POST['exclude_products_on_sale'] ?? 'no' ) ) ) );
		$reward->set_exclude_coupon_discounts( wc_string_to_bool( wc_clean( wp_unslash( $_POST['exclude_coupon_discounts'] ?? 'no' ) ) ) );
		$reward->set_exclude_items_on_sale( wc_string_to_bool( wc_clean( wp_unslash( $_POST['exclude_products_on_sale'] ?? 'no' ) ) ) );

		if ( $reward->get_eligible_products() === Eligible_Products_Group::SOME_PRODUCTS || in_array( $reward->get_eligible_orders(), [ Eligible_Orders_Group::EXCLUDING_PRODUCTS, Eligible_Orders_Group::INCLUDING_PRODUCTS ], true ) ) {
			$reward->set_products_ids( wc_clean( ( (array) ( $_POST['product_ids'] ?? [] ) ) ) ); // phpcs:ignore
		} else {
			$reward->set_products_ids( null );
		}

		if ( $reward->get_eligible_products() === Eligible_Products_Group::SOME_PRODUCT_CATEGORIES || in_array( $reward->get_eligible_orders(), [ Eligible_Orders_Group::EXCLUDING_PRODUCT_CATEGORIES, Eligible_Orders_Group::INCLUDING_PRODUCT_CATEGORIES ], true ) ) {
			$reward->set_product_category_ids( wc_clean( ( (array) ( $_POST['product_cat_ids'] ?? [] ) ) ) ); // phpcs:ignore
		} else {
			$reward->set_product_category_ids( null );
		}

		if ( in_array( $reward->get_eligible_orders(), [ Eligible_Orders_Group::EXCLUDING_PRODUCT_TYPES, Eligible_Orders_Group::INCLUDING_PRODUCT_TYPES ], true ) ) {
			$reward->set_product_types( wc_clean( ( (array) ( $_POST['product_types'] ?? [] ) ) ) ); // phpcs:ignore
		} else {
			$reward->set_product_types( null );
		}

		// if no products, categories or types are selected, set eligible products to null
		if ( empty( $reward->get_product_types() ) && empty( $reward->get_products_ids() ) && empty( $reward->get_product_category_ids() ) ) {
			if ( $reward->get_trigger() === Transaction_Event::ORDER_PAID ) {
				$reward->set_eligible_orders( Eligible_Orders_Group::ALL_ORDERS );
			} elseif ( $reward->get_trigger() === Transaction_Event::PRODUCT_PURCHASE ) {
				$reward->set_eligible_products( Eligible_Products_Group::ALL_PRODUCTS );
			}
		}

		return true;
	}

}

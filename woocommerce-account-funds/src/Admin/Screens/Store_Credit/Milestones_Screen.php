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

use Kestrel\Account_Funds\Store_Credit\Eligible_Products_Group;
use Kestrel\Account_Funds\Store_Credit\Reward;
use Kestrel\Account_Funds\Store_Credit\Reward_Type;
use Kestrel\Account_Funds\Store_Credit\Rewards\Milestone;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;

/**
 * Admin screen for managing store credit rewards.
 *
 * @since 4.0.0
 */
final class Milestones_Screen extends Reward_Screen {

	/** @var string screen ID */
	public const ID = 'store-credit-milestone';

	/** @var string */
	protected const REWARD_TYPE = Reward_Type::MILESTONE;

	/**
	 * Returns the screen title.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_menu_title() : string {

		return __( 'Milestones', 'woocommerce-account-funds' );
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
			return __( 'Edit milestone reward', 'woocommerce-account-funds' );
		}

		return __( 'Add new milestone reward', 'woocommerce-account-funds' );
	}

	/**
	 * Returns the description for the edit screen.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected function get_edit_screen_description() : string {

		return __( 'Award store credit to customers when they perform specific actions in the shop.', 'woocommerce-account-funds' );
	}

	/**
	 * Outputs explanations about each trigger the reward can be configured with.
	 *
	 * @since 4.0.0
	 *
	 * @param Milestone&Reward $reward
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
					case Transaction_Event::ACCOUNT_SIGNUP:
						esc_html_e( 'Award milestone store credit when a guest customer signs up for an account.', 'woocommerce-account-funds' );

						break;
					case Transaction_Event::PRODUCT_REVIEW:
						esc_html_e( 'Award milestone store credit when a customer leaves a product review, based on the eligibility rules.', 'woocommerce-account-funds' );
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
	 * @param Milestone&Reward $reward
	 * @return void
	 *
	 * @phpstan-ignore-next-line
	 */
	protected function output_configuration_rules_panel( Reward $reward ) : void {

		?>
		<div class="options_group account_signup">
			<?php

			// no options for now: we will hide the rules via JS when "account_signup" is chosen

			?>
			<div class="clear"></div>
		</div>
		<div class="options_group product_review">
			<?php $this->output_configuration_rules_product_review_options( $reward ); ?>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Outputs the options for the product review configuration rules.
	 *
	 * @since 4.0.0
	 *
	 * @param Milestone $milestone
	 * @return void
	 */
	private function output_configuration_rules_product_review_options( Milestone $milestone ) : void {

		if ( $milestone->is_new() ) :
			echo '<p><em>' . esc_html__( 'Define who qualifies for store credit by leaving a product review.', 'woocommerce-account-funds' ) . '</em></p>';

		endif;

		woocommerce_wp_select( [
			'id'          => 'products',
			'label'       => __( 'Eligible products', 'woocommerce-account-funds' ),
			'class'       => 'wc-enhanced-select',
			'style'       => 'width: 80%; max-width: 370px;',
			'default'     => 'all_products',
			'desc_tip'    => true,
			'description' => __( 'Select the products that customers must review to earn the milestone award.', 'woocommerce-account-funds' ),
			'value'       => $milestone->get_eligible_products(),
			'options'     => [
				'all_products'            => __( 'Any product', 'woocommerce-account-funds' ),
				'some_products'           => __( 'Specific products', 'woocommerce-account-funds' ),
				'some_product_categories' => __( 'Products within specific categories', 'woocommerce-account-funds' ),
			],
		] );

		?>
		<p class="form-field product_ids_field">
			<label for="product_ids"><?php esc_html_e( 'Products', 'woocommerce-account-funds' ); ?></label>
			<select id="product_ids" class="wc-product-search" multiple="multiple" style="width: 80%; max-width: 370px;" name="product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for products&hellip;', 'woocommerce-account-funds' ); ?>" data-action="woocommerce_json_search_products_and_variations">
				<?php

				$product_ids = $milestone->get_products_ids();

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
			</select><?php echo wc_help_tip( __( 'Award store credit when the customer reviews any of the selected products.', 'woocommerce-account-funds' ) ); ?>
		</p>
		<p class="form-field product_cat_ids_field">
			<label for="product_categories"><?php esc_html_e( 'Product categories', 'woocommerce-account-funds' ); ?></label>
			<select id="product_categories" name="product_cat_ids[]" style="width: 80%; max-width: 370px;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select categories&hellip;', 'woocommerce-account-funds' ); ?>">
				<?php

				$category_ids = $milestone->get_product_category_ids();
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
			</select><?php echo wc_help_tip( __( 'Award store credit when the customer reviews any products in the selected product categories.', 'woocommerce-account-funds' ) ); ?>
		</p>
		<?php

		$unique      = $milestone->is_unique();
		$per_product = $milestone->get_limited_to_once_per_product();

		if ( null === $per_product || $per_product ) {
			$value = 'once_per_product';
		} elseif ( $unique ) {
			$value = 'once_per_customer';
		} else {
			$value = 'unlimited';
		}

		woocommerce_wp_select( [
			'id'          => 'product_review_award_limits',
			/* translators: Context: Limit awarding store credit once per customer */
			'label'       => __( 'Award limits', 'woocommerce-account-funds' ),
			'class'       => 'wc-enhanced-select award-limits',
			'style'       => 'width: 80%; max-width: 370px;',
			'desc_tip'    => false,
			'description' => $this->get_award_limits_description( $milestone ),
			'default'     => 'once_per_product',
			'value'       => $value,
			'options'     => [
				'once_per_product'  => __( 'Once per product', 'woocommerce-account-funds' ),
				'once_per_customer' => __( 'Once per customer', 'woocommerce-account-funds' ),
				'unlimited'         => __( 'Unlimited', 'woocommerce-account-funds' ),
			],
		] );

		$verified_purchase_only = $milestone->requires_verified_product_reviews();

		woocommerce_wp_checkbox( [
			'id'          => 'verified_purchase_only',
			'label'       => __( 'Verified purchase required', 'woocommerce-account-funds' ) . '&nbsp;',
			'description' => __( 'Only reward reviews for products the customer has purchased', 'woocommerce-account-funds' ),
			'desc_tip'    => false,
			'default'     => 'yes',
			'value'       => $verified_purchase_only === null ? 'yes' : wc_bool_to_string( $verified_purchase_only ),
		] );
	}

	/**
	 * Processes the milestone configuration for storage.
	 *
	 * @since 4.0.0
	 *
	 * @param Milestone&Reward $reward
	 * @return bool
	 */
	protected function process_item( Reward &$reward ) : bool {

		// @phpstan-ignore-next-line
		$saving = parent::process_item( $reward );

		if ( ! $saving || ! $reward instanceof Milestone ) {
			return false;
		}

		if ( Transaction_Event::ACCOUNT_SIGNUP === $reward->get_trigger() ) {

			$reward->set_unique( true );
			$reward->set_eligible_products( null );
			$reward->set_products_ids( null );
			$reward->set_product_category_ids( null );
			$reward->set_limited_to_once_per_product( false );
			$reward->set_require_verified_product_reviews( null );

		} else { // Transaction_Event::PRODUCT_REVIEW

			$limits = wc_clean( wp_unslash( $_POST['product_review_award_limits'] ?? 'unlimited' ) );

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

			$reward->set_eligible_products( wc_clean( wp_unslash( $_POST['products'] ?? null ) ) );
			$reward->set_require_verified_product_reviews( wc_clean( wp_unslash( $_POST['verified_purchase_only'] ?? 'no' ) ) === 'yes' );

			if ( $reward->get_eligible_products() === 'some_products' ) {
				$reward->set_products_ids( wc_clean( ( (array) ( $_POST['product_ids'] ?? [] ) ) ) ); // phpcs:ignore
			} else {
				$reward->set_products_ids( null );
			}

			if ( $reward->get_eligible_products() === 'some_product_categories' ) {
				$reward->set_product_category_ids( wc_clean( ( (array) ( $_POST['product_cat_ids'] ?? [] ) ) ) ); // phpcs:ignore
			} else {
				$reward->set_product_category_ids( null );
			}

			// if no products or categories are selected, set to all products
			if ( empty( $reward->get_products_ids() ) && empty( $reward->get_product_category_ids() ) ) {
				$reward->set_eligible_products( Eligible_Products_Group::ALL_PRODUCTS );
			}
		}

		// unapplicable for store credit milestones
		$reward->set_product_types( null );
		$reward->set_product_quantity_behavior( null );

		return true;
	}

}

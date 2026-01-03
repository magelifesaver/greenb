<?php

if ( ! class_exists('CtMPAC_Application') ) {
	class CtMPAC_Application {
		public function __construct() {
			add_action( 'woocommerce_check_cart_items', array($this, 'ct_mpac_set_minimum_requirements'));
			add_action( 'woocommerce_init', array($this, 'register_wc_shortcodes'));
			add_action( 'ct_mpac_filter_min_cart_total', array($this, 'filter_minimum_order_amount_based_on_the_roles'), 11 , 2);
			add_filter( 'ct_mpac_filter_current_cart_total' , array( $this , 'ct_mpac_get_conditional_cart_total_value' ), 12, 2);
			add_filter( 'woocommerce_package_rates', array( $this, 'ct_mpac_allow_free_shipping' ), 10, 2 );
			add_filter( 'woocommerce_get_checkout_url', array($this, 'disable_checkout_link'), 99, 1);
		}

		public function disable_checkout_link( $checkoutLink ) {
			$checkOutSetting = get_option('ct_mpac_cart_disable_checkout' , false);
			if ( $checkOutSetting ) {
				$minimumCartTotal = $this->get_minimum_purchase_value();
				$currentCartTotal = $this->get_current_cart_total();
				if ( 0!==$currentCartTotal && $currentCartTotal < $minimumCartTotal  ) {
					return '#';      
				}
			}
			return $checkoutLink;
		}

		public function get_minimum_purchase_value() {
			$minimumCartTotal = get_option( 'ct_mpac_minimum_purchase_value_for_all', 0);
			/**
			 * Filter the amount set as the minimum cart total to customize the behaviour with this hook.
			 * 
			 * @since 2.2
			 */
			$minimumCartTotal = apply_filters('ct_mpac_filter_min_cart_total', $minimumCartTotal, WC()->cart);
			return $minimumCartTotal;
		}

		public function get_current_cart_total() {
			$cartTotal = WC()->cart->total;
			/**
			 * Filter the cart total , value which is considered while checking if it matches the minimum cart amount
			 * set from the setting.
			 * 
			 * @since 2.2
			 */
			return apply_filters('ct_mpac_filter_current_cart_total', $cartTotal, WC()->cart);
		}

		public function get_cart_item_count() {
			return count(WC()->cart->cart_contents);
		}

		public function get_current_cart_total_for_free_shipping() {
			$cart	   = WC()->cart;
			$cartTotal = wc_prices_include_tax() ? $cart->get_cart_contents_total() + $cart->get_cart_contents_tax() : $cart->get_cart_contents_total();
			/**
			 * Filter the cart total , value which is considered while checking if it matches the minimum cart amount
			 * set for free shipping from the setting.
			 * 
			 * @since 2.2.9
			 */
			return apply_filters('ct_mpac_filter_current_cart_total_for_free_shipping', $cartTotal, WC()->cart);
		}

		public function ct_mpac_set_minimum_requirements() {			
			global $woocommerce;
			$minimumCartTotal = $this->get_minimum_purchase_value();
			$currentCartTotal = $this->get_current_cart_total();
			$itemsInACart	  = $this->get_cart_item_count();
			
			if (!$itemsInACart) {
				return ;
			}
			
			if ( self::isZeroCartTotalAllowed() && 0==$currentCartTotal) {
				return ;
			}
			
			$customCartMessage = self::getErrorNotice($minimumCartTotal, $currentCartTotal);
			wp_enqueue_script('ct-mpac-cart');
			wp_localize_script( 'ct-mpac-cart', 'ct_mpac_cart_obj', array('cartMessage'=> strip_tags($customCartMessage)) );
			
			if ( $currentCartTotal < $minimumCartTotal  ) {
				wc_add_notice($customCartMessage, 'error', array('type'=>'minimum_cart_value'));
			}			
		}

		public static function getErrorNotice( $minimumCartTotal, $currentCartTotal) {
			if (!empty($minimumCartTotal) && 0<$minimumCartTotal) {
				$remainingAmountToMakePurchase = '<span class="ct-mpac-remaining-amount">' . wc_price($minimumCartTotal - $currentCartTotal) . '</span>'; 
				$minimumCartTotal              = '<span class="ct-mpac-minimum-amount">' . wc_price($minimumCartTotal) . '</span>';
				$currentCartTotal			   = '<span class="ct-mpac-current-amount">' . wc_price($currentCartTotal) . '</span>';
				$customCartMessage             = get_option('ct_mpac_cart_limit_message', null);
				//load default message
				if (empty($customCartMessage) || null===$customCartMessage) {
					$customCartMessage = __('The minimum amount to make a purchase is', 'ct-minimum-purchase-amount-for-woo-cart' ) . ' [minimum-amount] ' . __(' & the current cart total is', 'ct-minimum-purchase-amount-for-woo-cart') . ' [current-total] ' . __('please add the products worth', 'ct-minimum-purchase-amount-for-woo-cart') . ' [amount-remaining] ' . __('or more to successfully make a purchase', 'ct-minimum-purchase-amount-for-woo-cart');
				}

				$customCartMessage = str_replace(__('[minimum-amount]', 'ct-minimum-purchase-amount-for-woo-cart'), $minimumCartTotal, $customCartMessage);
				$customCartMessage = str_replace('[minimum-amount]', $minimumCartTotal, $customCartMessage);
				$customCartMessage = str_replace(__('[amount-remaining]', 'ct-minimum-purchase-amount-for-woo-cart'), $remainingAmountToMakePurchase, $customCartMessage);
				$customCartMessage = str_replace('[amount-remaining]', $remainingAmountToMakePurchase, $customCartMessage);
				$customCartMessage = str_replace(__('[current-total]', 'ct-minimum-purchase-amount-for-woo-cart'), $currentCartTotal, $customCartMessage);
				$customCartMessage = str_replace('[current-total]', $currentCartTotal, $customCartMessage);

				return $customCartMessage;
			}
		}

		public function register_wc_shortcodes() {
			add_shortcode( 'ct_mpac_minimum_order_amount_message', array($this, 'ct_mpac_minimum_order_amount_shortcode'));
			$this->register_styles_and_scripts();
		}

		public function ct_mpac_minimum_order_amount_shortcode() {
			ob_start();

			if (!is_admin()) {
				if (WC() && null!==WC()->cart) {
					$minimumCartTotal = get_option( 'ct_mpac_minimum_purchase_value_for_all', 0);
					/**
					* Filter the amount set as the minimum cart total to customize the behaviour with this hook.
					* 
					* @since 2.2
					*/
					$minimumCartTotal   = apply_filters('ct_mpac_filter_min_cart_total', $minimumCartTotal, WC()->cart);
					$currentCartTotal   = $this->get_current_cart_total();
					$allowZeroCartTotal = self::isZeroCartTotalAllowed();
					$validCartTotal     = $allowZeroCartTotal ? $allowZeroCartTotal : ( 0!==$currentCartTotal ); 
					if ( $validCartTotal && $minimumCartTotal>$currentCartTotal) {
						$noticeText = self::getErrorNotice($minimumCartTotal, $currentCartTotal);
						include_once CT_MPAC_DIR_PATH . '/templates/ct-mpac-minimum-amount-notice.php';
						wp_enqueue_style('ct-mpac-min-amount-notice');
					}
				}
			}
			return ob_get_clean();
		}

		public function register_styles_and_scripts() {
			wp_register_style( 'ct-mpac-min-amount-notice', CT_MPAC_DIR_URL . '/assets/css/shortcode-notice.css', false, CT_MPAC_VERSION);
			wp_register_script( 'ct-mpac-cart', CT_MPAC_DIR_URL . '/assets/js/ct-mpac-cart.js', array('jquery'), CT_MPAC_VERSION);
		}

		public function filter_minimum_order_amount_based_on_the_roles( $minimumOrderAmount, $cart) {
			$roleBasedLimits = get_option( 'ct-mpac-role-specific-cart-limits', array());
			$userRoles       = self::getCurrentUsersRoles();
			$limits          = array();
			if (!empty($roleBasedLimits)) {
				foreach ($roleBasedLimits as $role => $settings) {
					if (in_array($role, $userRoles) && ( isset($settings['status']) && $settings['status'] )) {
						$limits[] = $settings['minimum_order_amount'];
					}
				}
			}
			if (!empty($limits)) {
				/**
			 * When the user has multiple roles & minimum order amount is set for each of the roles,
			 * this filters allow you to specify wether to consider the smallest amount or the largest amount
			 * as the minimum order amount for that user.
			 * 
			 * @since 2.2
			 */
				$minOrMaxRoleBasedMinimumOrderAmount = apply_filters( 'ct-mpac-min-max-minimum-order-amount-from-multiple-roles', 'min');
				$minimumOrderAmount                  = 'min'===$minOrMaxRoleBasedMinimumOrderAmount?min($limits):max($limits);
			}
			return $minimumOrderAmount;
		}

		public static function getCurrentUsersRoles() {
			$userRoles = array();
			if (is_user_logged_in()) {
				$user      = wp_get_current_user();
				$userRoles = $user->roles;
			}
			return $userRoles;
		}

		public function ct_mpac_get_conditional_cart_total_value( $cartTotal, $cart) {
			$shouldExcludeShipping  = get_option('ct_mpac_exclude_shipping_from_cart_total', false);
			$shouldIncludeDiscounts = get_option('ct_mpac_include_discount_coupons_in_total', false);

			if ( empty( $cart ) || ( !$shouldIncludeDiscounts && !$shouldExcludeShipping )) {
				return $cartTotal;
			}

			$cartSubtotal = $cart->subtotal;
			if ($shouldIncludeDiscounts && $shouldExcludeShipping) {
				return $cartSubtotal;
			} else if ( $shouldExcludeShipping && !$shouldIncludeDiscounts) {
				$cartSubtotal = $cartSubtotal - ( $cart->get_discount_total() + $cart->get_discount_tax() );
				$cartSubtotal = $cartSubtotal<0?0:$cartSubtotal;
				return $cartSubtotal;
			} elseif (!$shouldExcludeShipping && $shouldIncludeDiscounts) {
				return $cartSubtotal;
			}

			return $cartTotal;
		}

		public function get_minimum_amount_for_free_shipping() {
			$minimumCartTotal = get_option( 'ct_mpac_minimum_cart_total_for_free_shipping', 0);
			/**
			 * Filter the amount set as the minimum cart total for allowing free shipping.
			 * 
			 * @since 2.2.9
			 */
			$minimumCartTotal = apply_filters('ct_mpac_filter_minimum_cart_total_for_free_shipping', $minimumCartTotal, WC()->cart);
			return $minimumCartTotal;
		}

		public function ct_mpac_allow_free_shipping( $shipping_rates, $package ) {

			$freeShippingLimit = $this->get_minimum_amount_for_free_shipping();
			$currentCartTotal  = $this->get_current_cart_total_for_free_shipping();
			/**
			 * Filter to conditionally deside wether to make the user/cart eligible for the free shipping
			 * 
			 * @since 2.2.9
			 */
			$freeShippingEligible = apply_filters('ct_mpac_filter_free_shipping_eligible', true, $freeShippingLimit, $currentCartTotal, $package);
			
			/**
			 * Filter to conditionally deside wether to make the user/cart eligible for the free shipping
			 * 
			 * @since 2.3.15
			 */
			$freeShippingCost     = apply_filters('ct_mpac_filter_free_shipping_cost', 0, $freeShippingLimit, $currentCartTotal, $package);

			if ( $freeShippingEligible ) {
				if ( !empty( $currentCartTotal ) && !empty( $freeShippingLimit ) && $freeShippingLimit<=$currentCartTotal) {
					$new_shipping_option					= array();
					$freeShippingLabel 						= $this->getFreeShippingLabel($freeShippingLimit, $currentCartTotal, $freeShippingCost, $package);
					$new_shipping_option['free_shipping:1'] = new WC_Shipping_Rate('free_shipping:1', $freeShippingLabel, $freeShippingCost, array(), 'free_shipping');
					$shipping_rates 						= array_merge($new_shipping_option, $shipping_rates);	
				}
			}
			return $shipping_rates;
		}

		public function getFreeShippingLabel( $freeShippingLimit, $currentCartTotal, $freeShippingCost, $package) {
			/* translators:  %s amount specified as minimum amount to allow free shipping*/
			$freeShippingLabel = sprintf(__('Free shipping on orders above %s', 'ct-minimum-purchase-amount-for-woo-cart'), wc_price($freeShippingLimit));
				
			/**
			 * The filter hook 'ct_mpac_filter_free_shipping_label' can be used to filter the string to be displayed as the label of the free shipping method
			 * 
			 * @since 2.2.9
			 */
			return apply_filters( 'ct_mpac_filter_free_shipping_label', $freeShippingLabel, $freeShippingLimit, $currentCartTotal, $freeShippingCost, $package);
		}

		public static function isZeroCartTotalAllowed() {
			/**
			 * Filter which tells wether to apply minimum order logic when cart value is zero
			 * 
			 * @since 2.3.12
			*/
			return apply_filters('ct_mpac_allow_zero_total', false);
		}
	}
}

<?php


if ( ! class_exists('CtMPAC_Settings_Page') ) {
	class CtMPAC_Settings_Page {
		public function __construct() {
			add_action( 'admin_init', array($this, 'registerAdminSettings') );
			add_action( 'admin_menu', array($this, 'addSettingsMenu') );
			add_action( 'admin_enqueue_scripts', array($this, 'adminCustomFont') );
			add_action( 'wp_ajax_save_role_based_settings', array($this, 'saveRoleBasedLimits') );
			//Upgrade Options
			if (!defined('WC_MIN_MAX_QUANTITIES')) {
				add_action('woocommerce_product_options_general_product_data', array($this, 'showMinMaxUpgradeOptions'));
			}

		}

		public function adminCustomFont() {
			wp_enqueue_style('cttalks-font', CT_MPAC_DIR_URL . 'assets/fonts/ct-icons/style.css', false, CT_MPAC_VERSION);
			wp_enqueue_style('cttalks-admin-menu', CT_MPAC_DIR_URL . 'assets/css/admin-menu.css', false, CT_MPAC_VERSION);
		} 

		public function addSettingsMenu() {
			$mainMenuTitle	   = esc_html__('Cart Settings', 'ct-minimum-purchase-amount-for-woo-cart');
			$settingsPageTitle = esc_html__('Cart Settings', 'ct-minimum-purchase-amount-for-woo-cart');
			$settingsMenuName  = esc_html__('Settings', 'ct-minimum-purchase-amount-for-woo-cart');
			/**
			 * Filter 'ct_mpac_setting_page_capability' can be used to change who can access the Minimum order setting page
			 * 
			 * @since 2.2.0
			 * 
			 */
			$capability = apply_filters('ct_mpac_setting_page_capability', 'manage_options');
			//Menu Page
			add_menu_page($settingsPageTitle, $mainMenuTitle, $capability, 'ct_mpac_settings', array( $this, 'settingsPageContent' ), null, 56);

			$showWelcomeModal = get_option('ct_mpac_show_welcome_modal', false);
			if ($showWelcomeModal) {
				update_option('ct_mpac_show_welcome_modal', false);
				wp_enqueue_style( 'ct_mpac_welcome_modal_style', CT_MPAC_DIR_URL . '/assets/css/ct-admin-welcome-modal.css', array(), CT_MPAC_VERSION );
				wp_enqueue_script( 'ct_mpac_welcome_modal_script', CT_MPAC_DIR_URL . '/assets/js/ct-admin-welcome-modal.js', array( 'jquery' ), CT_MPAC_VERSION, true );
				include_once CT_MPAC_DIR_PATH . '/templates/ct-admin-welcome-modal.php';
			}

		}

		public function settingsPageContent() {
			settings_errors();
			$tab        = isset($_GET['tab'])?sanitize_text_field($_GET['tab']):'general_settings';
			$active_tab = array('general_settings'=>'', 'role_based_limits'=>'', 'cttalks_extras'=>'');
			switch ($tab) {
				case 'role_based_limits':
					$active_tab['role_based_limits'] = 'nav-tab-active';
					$this->settingsPageTabs($active_tab);
					$this->getRoleBasedCartLimitsTabData();
					break;
				
				case 'cttalks_extras':
					$active_tab['cttalks_extras'] = 'nav-tab-active';
					$this->settingsPageTabs($active_tab);
					include_once CT_MPAC_DIR_PATH . 'templates/ct-promotions.php';
					break;

				default:
					$active_tab['general_settings'] = 'nav-tab-active';
					$this->settingsPageTabs($active_tab);
					$this->getGeneralSettingsTabData();
					break;
			}			
		}

		private function settingsPageTabs( $active_tab) {
			$adminSettingPage = admin_url( 'admin.php?page=ct_mpac_settings');
			?>
			<div class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="<?php echo esc_url($adminSettingPage) . '&tab=general_settings'; ?>" class="nav-tab ct-mpac-general-settings <?php echo esc_attr($active_tab['general_settings']); ?>">
					<?php esc_html_e('General', 'ct-minimum-purchase-amount-for-woo-cart'); ?>
				</a>
				<a href="<?php echo esc_url($adminSettingPage) . '&tab=role_based_limits'; ?>" class="nav-tab ct-mpac-role-settings  <?php echo esc_attr($active_tab['role_based_limits']); ?>">
					<?php esc_html_e('Role Based Minimum Orders', 'ct-minimum-purchase-amount-for-woo-cart'); ?>
				</a>
				<a href="<?php echo esc_url($adminSettingPage) . '&tab=cttalks_extras'; ?>" class="nav-tab ct-extras  <?php echo esc_attr($active_tab['cttalks_extras']); ?>">
					<?php esc_html_e('Extras', 'ct-minimum-purchase-amount-for-woo-cart'); ?>
					<span class="notification-dot"></span>
				</a>
			</div>
			<?php
		}

		public function registerAdminSettings() {
			add_settings_section('ct_mpac_general_settings_section',
								esc_html__('Minimum Order Amount Settings', 'ct-minimum-purchase-amount-for-woo-cart'), 
								array($this, 'generalSettingsDescription'),
								'ct_mpac_general_settings'    
							);
			add_settings_section('ct_mpac_free_shipping_settings_section',
								esc_html__('Free Shipping Settings', 'ct-minimum-purchase-amount-for-woo-cart'), 
								array($this, 'freeShippinglSettingsDescription'),
								'ct_mpac_general_settings'    
							);
			
			add_settings_field('ct_mpac_minimum_purchase_value_for_all', esc_html__('Minimum Cart Amount To Make a Purchase', 'ct-minimum-purchase-amount-for-woo-cart'), array($this, 'showMinimumPurchaseValueForAllSetting'), 'ct_mpac_general_settings', 'ct_mpac_general_settings_section');
			add_settings_field('ct_mpac_cart_limit_message', esc_html__('Message to be displayed on the cart page', 'ct-minimum-purchase-amount-for-woo-cart'), array($this, 'showCartMessageSetting'), 'ct_mpac_general_settings', 'ct_mpac_general_settings_section');
			add_settings_field('ct_mpac_cart_disable_checkout', esc_html__('Disable Checkout Link', 'ct-minimum-purchase-amount-for-woo-cart'), array($this, 'showDisableCheckoutSetting'), 'ct_mpac_general_settings', 'ct_mpac_general_settings_section');
			add_settings_field('ct_mpac_include_discount_coupons_in_total', esc_html__('Include discounts in the cart total', 'ct-minimum-purchase-amount-for-woo-cart'), array($this, 'showIncludeDiscountTotalSetting'), 'ct_mpac_general_settings', 'ct_mpac_general_settings_section');
			add_settings_field('ct_mpac_exclude_shipping_from_cart_total', esc_html__('Exclude shipping charges from the cart total', 'ct-minimum-purchase-amount-for-woo-cart'), array($this, 'showExcludeShippingSetting'), 'ct_mpac_general_settings', 'ct_mpac_general_settings_section');
			
			add_settings_field('ct_mpac_minimum_cart_total_for_free_shipping', esc_html__('Minimum Cart Amount For Free Shipping', 'ct-minimum-purchase-amount-for-woo-cart'), array($this, 'showMinimumValueForFreeShipping'), 'ct_mpac_general_settings', 'ct_mpac_free_shipping_settings_section');//Free Shipping
			
			//Minimum purchase value setting
			$args = array('type' => 'number', 'sanitize_callback' => array ($this, 'validateMinimumCartValue'), 'default' => 0);
			register_setting('ct_mpac_general_settings', 'ct_mpac_minimum_purchase_value_for_all', $args);

			$args = array('type' => 'text', 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '');
			register_setting('ct_mpac_general_settings', 'ct_mpac_cart_limit_message', $args);

			$args = array('type' => 'checkbox', 'sanitize_callback' => array ($this, 'validateCheckoutCheckboxValue'), 'default' => false);
			register_setting('ct_mpac_general_settings', 'ct_mpac_cart_disable_checkout', $args);

			$args = array('type' => 'checkbox', 'sanitize_callback' => array ($this, 'validateCheckoutCheckboxValue'), 'default' => false);
			register_setting('ct_mpac_general_settings', 'ct_mpac_include_discount_coupons_in_total', $args);

			$args = array('type' => 'checkbox', 'sanitize_callback' => array ($this, 'validateCheckoutCheckboxValue'), 'default' => false);
			register_setting('ct_mpac_general_settings', 'ct_mpac_exclude_shipping_from_cart_total', $args);

			$args = array('type' => 'number', 'sanitize_callback' => array ($this, 'validateMinimumCartValue'), 'default' => 0);
			register_setting('ct_mpac_general_settings', 'ct_mpac_minimum_cart_total_for_free_shipping', $args);
		}

		public function showMinimumPurchaseValueForAllSetting() {
			$minimumPurchaseValue = get_option('ct_mpac_minimum_purchase_value_for_all');
			$description          = esc_html__('If the total value of the clients cart is lesser that the value specified the checkout will be restricted for the user', 'ct-minimum-purchase-amount-for-woo-cart');
			?>
			<input type="number" name="ct_mpac_minimum_purchase_value_for_all" id="ct_mpac_minimum_purchase_value_for_all" aria-describedby="desc-wdmws-custom-product-expiration" step="any" min="0" value="<?php echo esc_attr($minimumPurchaseValue); ?>">
			<p class="description" id="desc-wdmws-custom-product-expiration">
				<span style="font-size:10px;"><?php esc_html_e('Currently Saved Value : ', 'ct-minimum-purchase-amount-for-woo-cart'); ?><strong><?php echo wp_kses_data(wc_price($minimumPurchaseValue)); ?></strong></span>
				<br><?php esc_html_e($description); ?>
			</p>
			<?php
		}	

		public function showMinimumValueForFreeShipping() {
			$minimumValueFreeShippingValue = get_option('ct_mpac_minimum_cart_total_for_free_shipping', false);
			$description          		   = esc_html__('If the total value of the clients cart is more than the value specified, The option for free shipping will be enabled.', 'ct-minimum-purchase-amount-for-woo-cart');
			?>
			<input type="number" name="ct_mpac_minimum_cart_total_for_free_shipping" id="ct_mpac_minimum_cart_total_for_free_shipping" aria-describedby="desc-wdmws-custom-product-expiration" step="any" min="0" value="<?php echo esc_attr($minimumValueFreeShippingValue); ?>">
			<a href="#ct_mpac_minimum_cart_total_for_free_shipping"><span class="description" onClick="{jQuery('#ct_mpac_minimum_cart_total_for_free_shipping').val('')}"><?php esc_html_e('Clear', 'ct-minimum-purchase-amount-for-woo-cart'); ?></span></a>
			<p class="description" id="desc-wdmws-custom-product-expiration">
				<span style="font-size:10px;"><?php esc_html_e('Currently Saved Value : ', 'ct-minimum-purchase-amount-for-woo-cart'); ?></span><span style="font-size:10px;" id="min_total_fs_saved"><strong><?php echo $minimumValueFreeShippingValue?wp_kses_data(wc_price($minimumValueFreeShippingValue)):'--'; ?></strong></span>
				<br><?php esc_html_e($description); ?>
			</p>
			<?php
		}

		/**
		 * Outputs the setting field to set the custom message
		 * to be displayed on the cart & the checkout page.
		 */
		public function showCartMessageSetting() {
			$cartLimitMessage = get_option('ct_mpac_cart_limit_message');
			$description      = esc_html__('This message will be displayed on the cart when the products of a value lesser than the specified minimum amount are present in the cart.', 'ct-minimum-purchase-amount-for-woo-cart');
			$placeHolderInfo  = '[minimum-amount] - ' . esc_html__('The minimum amount required to make a purchase, ', 'ct-minimum-purchase-amount-for-woo-cart');
			$placeHolderInfo  = $placeHolderInfo . '[amount-remaining] - ' . esc_html__('The required amount to match the purchase amount.', 'ct-minimum-purchase-amount-for-woo-cart');
			$placeHolderInfo  = $placeHolderInfo . '[current-total] - ' . esc_html__('Current cart total.', 'ct-minimum-purchase-amount-for-woo-cart');
			
			$placeHolder      = esc_html__('The minimum amount to make a purchase is', 'ct-minimum-purchase-amount-for-woo-cart' ) . ' [minimum-amount] ' . esc_html__('& the current cart total is', 'ct-minimum-purchase-amount-for-woo-cart') . ' [current-total] ' . esc_html__('please add the products worth', 'ct-minimum-purchase-amount-for-woo-cart') . ' [amount-remaining] ' . esc_html__(' or more to successfully make a purchase', 'ct-minimum-purchase-amount-for-woo-cart');
			
			?>
			<textarea name="ct_mpac_cart_limit_message" id="ct_mpac_cart_limit_message" placeholder= "<?php echo esc_html($placeHolder); ?>"aria-describedby="ct_mpac_cart_limit_message" class="regular-text" rows="5"><?php echo esc_html($cartLimitMessage); ?></textarea>
			<p class="description" id="desc-ct_mpac_cart_limit_message">
			<span style="font-size:10px;"><?php echo esc_html( $placeHolderInfo ); ?></span>
			<br> <?php echo esc_html($description); ?>			
			</p>
			<?php
		}

		/**
		 * Outputs the setting field to enable/disable the checkout.
		 */
		public function showDisableCheckoutSetting() {
			$checkOutSetting = get_option('ct_mpac_cart_disable_checkout');
			$description     = esc_html__('When enabled the checkout link on the cart page will be disabled when cart value is lesser than the minimum value defined', 'ct-minimum-purchase-amount-for-woo-cart');
			
			?>
			<input type="checkbox" name="ct_mpac_cart_disable_checkout" id="ct_mpac_cart_disable_checkout" aria-describedby="desc-ct_mpac_cart_disable_checkout" class="regular-checkbox" <?php echo checked($checkOutSetting); ?>>
			<p class="description" id="desc-ct_mpac_cart_disable_checkout">
			<?php echo esc_html($description); ?>			
			</p>
			<?php
		}

		/**
		 * Outputs the html required to show the setting to enable/disable inclusion of the 
		 * coupon discounts in the cart totals while calculations
		 */
		public function showIncludeDiscountTotalSetting() {
			$includeDiscount = get_option('ct_mpac_include_discount_coupons_in_total');
			$description     = esc_html__('If the discount coupon is applied to the cart the cart total will also include discount value while checking if it matches the minimum cart total rules.', 'ct-minimum-purchase-amount-for-woo-cart');
			
			?>
			<input type="checkbox" name="ct_mpac_include_discount_coupons_in_total" id="ct_mpac_include_discount_coupons_in_total" aria-describedby="desc-ct_mpac_include_discount_coupons_in_total" class="regular-checkbox" <?php echo checked($includeDiscount); ?>>
			<p class="description" id="desc-ct_mpac_include_discount_coupons_in_total">
			<?php echo esc_html($description); ?>			
			</p>
			<?php
		}


		public function showExcludeShippingSetting() {
			$excludeShipping = get_option('ct_mpac_exclude_shipping_from_cart_total');
			$description     = esc_html__('Enabling this setting will exclude the shipping amount if from the cart total while matching with the minimum order amount.', 'ct-minimum-purchase-amount-for-woo-cart');
			
			?>
			<input type="checkbox" name="ct_mpac_exclude_shipping_from_cart_total" id="ct_mpac_exclude_shipping_from_cart_total" aria-describedby="desc-ct_mpac_exclude_shipping_from_cart_total" class="regular-checkbox" <?php echo checked($excludeShipping); ?>>
			<p class="description" id="desc-ct_mpac_exclude_shipping_from_cart_total">
			<?php echo esc_html($description); ?>			
			</p>
			<?php
		}
		

		public function generalSettingsDescription() {
			?>
			<div class="support-link-top">
				<a target="_blank" href="https://docs.google.com/forms/d/e/1FAIpQLSeB5uef5fneZE1zqJTAnEaih7B08P2RxK220Z96b4-zVlWcaQ/viewform?usp=sf_link">
					<?php esc_html_e('Reach Out For Support, Customizations/Feedback', 'ct-minimum-purchase-amount-for-woo-cart'); ?><span class="dashicons dashicons-external"></span>
				</a>
			</div>
			<?php
		}

		public function freeShippinglSettingsDescription() {
			?>
			<div class="support-link-top-fs">
				<p><?php esc_html_e('You can offer your customers free shipping when the cart total matches the entered value. This will help you build better upsell strategies.', 'ct-minimum-purchase-amount-for-woo-cart'); ?></p>
			</div>
			<?php
		}
		


		/****************************************************************
		 * Sanitisation and validation functions for the setting fields *
		 ****************************************************************/
		public function validateMinimumCartValue( $amount ) {
			if ( !isset( $amount ) || !is_numeric( $amount ) || $amount < 0 ) {
				$amount = false;
			}
			return $amount;
		}

		public function validateCheckoutCheckboxValue( $value = false ) {
			return (bool) $value;
		}


		/***************************
		 * Settings Page Functions *
		 ***************************/
		public function getGeneralSettingsTabData() {
			?>
			<form action="options.php" method="post">
				<?php
				settings_fields('ct_mpac_general_settings');
				do_settings_sections('ct_mpac_general_settings');
				do_settings_sections('ct_mpac_free_shipping_settings_section');
				submit_button('Save Settings');
				?>
			</form>
			<?php
		}


		public function getRoleBasedCartLimitsTabData() {
			$this->includeDataTables();
			$roles 			  = wp_roles()->roles;
			$rolesLimitsPairs = get_option('ct-mpac-role-specific-cart-limits', array());
			?>
			<h2><?php esc_html_e('Role Based Minimum Order Amount Settings', 'ct-minimum-purchase-amount-for-woo-cart'); ?></h2>
			<div class="role-based-cart-limits-setting-wrapper">
				<input type="hidden" id="ct-mpac-nonce" data-nonce="<?php echo esc_attr(wp_create_nonce('ct-mpac-nonce')); ?>">
				<table id="ct-mpac-role-based-order-limits-table" class="display stripe hover" style="width:100%;">
				<thead>
				<tr>
					<th><?php esc_html_e('User Role', 'ct-minimum-purchase-amount-for-woo-cart'); ?></th>
					<th><?php esc_html_e('Role Slug', 'ct-minimum-purchase-amount-for-woo-cart'); ?></th>
					<th><?php esc_html_e('Minimum Order Amount', 'ct-minimum-purchase-amount-for-woo-cart'); ?></th>
					<th><?php esc_html_e('Enable/Disable', 'ct-minimum-purchase-amount-for-woo-cart'); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ($roles as $role_slug => $role) {
					$minimumOrderAmount = '';
					$status				= false;
					if (!empty($rolesLimitsPairs) && isset($rolesLimitsPairs[$role_slug])) {
						$minimumOrderAmount = $rolesLimitsPairs[$role_slug]['minimum_order_amount'];
						$status				= $rolesLimitsPairs[$role_slug]['status'];
					}
					?>
						<tr class="role-limit-row" name="<?php echo esc_attr($role_slug); ?>">
							<td><span class="user-role"><?php echo esc_html($role['name']); ?></span></td>
							<td><span class="user-role-slug"><?php echo esc_html($role_slug); ?></span></td>
							<td class="ct-mpact-role-setting-input-fields">
								<input type="number" class="min-amount" name="<?php echo 'min_amount_' . esc_attr($role_slug); ?>" id="<?php echo 'min_amount_' . esc_attr($role_slug); ?>" step="any" min="0" value="<?php echo esc_html(wc_format_decimal($minimumOrderAmount, '', true)); ?>">
							</td>
							<td class="ct-mpact-role-setting-input-fields">
								<input type="checkbox" class="status" name="<?php echo 'status_' . esc_attr($role_slug); ?>" id="<?php echo 'status_' . esc_attr($role_slug); ?>" <?php echo checked($status); ?>>
							</td>
						</tr>
						<?php
				}
				?>
				</tbody>
				</table>
				<div class="ct-mpac-save-button-section">
					<button id="ct-mpac-save-role-limits" class="button button-primary">
						<?php esc_html_e('Save Changes', 'ct-minimum-purchase-amount-for-woo-cart'); ?>
					</button>
				</div>
			</div>
			<?php
		}

		private function includeDataTables() {
			wp_enqueue_style('cttalks-datatables-style', CT_MPAC_DIR_URL . 'assets/lib/DataTables/datatables.min.css', false, CT_MPAC_VERSION);
			wp_enqueue_style('cttalks-role-table-style', CT_MPAC_DIR_URL . 'assets/css/admin-role-settings-page.css', false, CT_MPAC_VERSION);
			wp_enqueue_script('cttalks-datatables-script', CT_MPAC_DIR_URL . 'assets/lib/DataTables/datatables.min.js', array('jquery'), CT_MPAC_VERSION, true);
			wp_enqueue_script('cttalks-role-table-script', CT_MPAC_DIR_URL . 'assets/js/admin-role-settings-page.js', array('jquery'), CT_MPAC_VERSION, true);

			$jsLocalizationData = array(
										'ajax_url' => admin_url('admin-ajax.php'),
										'success_message'=>esc_html__('Successfully saved the rules', 'ct-minimum-purchase-amount-for-woo-cart'),
										'failure_message'=>esc_html__('Some error occured, please try again', 'ct-minimum-purchase-amount-for-woo-cart')
									); 
			wp_localize_script( 'cttalks-role-table-script', 'ct_mpac_role_table', $jsLocalizationData);
		}


		public function saveRoleBasedLimits() {
			$post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
			if (!wp_verify_nonce($post['nonce'], 'ct-mpac-nonce')) {
				wp_send_json_error(array('status'=>'failed'));
			}
			$roleBasedLimits       = isset($post['roleBasedLimits'])?$post['roleBasedLimits']:array();
			$roleBasedLimitsToSave = array(); 
			if (!empty($roleBasedLimits)) {
				foreach ($roleBasedLimits as $settings) {
						$minAmount = ( isset( $settings['minAmount'] ) && 0<=$settings['minAmount'] )?wc_format_decimal($settings['minAmount'], '', true):0;
						$roleBasedLimitsToSave[$settings['role']]['minimum_order_amount'] = $minAmount;
						$roleBasedLimitsToSave[$settings['role']]['status']               = $settings['status'];
				}
				update_option('ct-mpac-role-specific-cart-limits', $roleBasedLimitsToSave);
			}
			wp_send_json(array('status'=>'saved'));
		}

		public function showMinMaxUpgradeOptions() {
			echo '<div class="options_group ct-mpac-min-max-upgrade">';
				woocommerce_wp_text_input(
					array(
						'id' => 'ct-mpac-min-max-upgrade-field-min',
						'placeholder' => 'Minimum Quantity',
						'label' => __('Minimum Quantity', 'woocommerce'),
						'type' => 'number',
						'custom_attributes' => array(
							'step' => 'any',
							'min' => '0',
							'disabled'=>true
						),
						'desc_tip' => true,
						'description'=> 'Enter a quantity to prevent the user buying this product if they have fewer than the allowed quantity in their cart'
					)
				);
				woocommerce_wp_text_input(
					array(
						'id' => 'ct-mpac-min-max-upgrade-field-max',
						'placeholder' => 'Maximum Quantity',
						'label' => __('Maximum Quantity', 'woocommerce'),
						'type' => 'number',
						'custom_attributes' => array(
							'step' => 'any',
							'min' => '0',
							'disabled'=>true
						),
						'desc_tip' => true,
						'description'=> 'Enter a quantity to prevent the user buying this product if they have more than the allowed quantity in their cart'
					)
				);
				woocommerce_wp_text_input(
					array(
						'id' => 'ct-mpac-min-max-upgrade-field-group-Of',
						'placeholder' => 'Group Of...',
						'label' => __('Group Of...', 'woocommerce'),
						'type' => 'number',
						'custom_attributes' => array(
							'step' => 'any',
							'min' => '0',
							'disabled'=>true
						),
						'desc_tip' => true,
						'description'=> 'Enter a quantity to only allow this product to be purchased in groups of X'
					)
				);
				echo '<a href="https://automattic.pxf.io/1rY3vD" target="_blank" class="overlay upgrade-to-min-max-quantities">
						<div class="description">
						<span class="upgrade-text">Enable WooCommerce Min-Max Quantities</span>
						<br/>
						<span class="upgrade-button">Get Plugin</span>
					  </div>
						</a>';
			echo '</div>';
			echo '<style> 
			a.overlay.upgrade-to-min-max-quantities {
				display: none;
			}	
			
			a.overlay.upgrade-to-min-max-quantities span.upgrade-button {
				background: linear-gradient(0deg, #FFFFFF, #FFFFFF);
				border: 1px solid #C4C4C4;
				box-sizing: border-box;
				box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
				border-radius: 4px;
				color: #008AD8;
				font-weight:normal;
				font-size:18px;
				padding: 5px 20px 5px 20px;
			}

			div.options_group.ct-mpac-min-max-upgrade {
				position:relative;
			}


			div.options_group.ct-mpac-min-max-upgrade a.overlay.upgrade-to-min-max-quantities {
				display:flex;
				justify-content: center;
				align-items: center;
				width: 100%;
				height: 100%;
				background: white;
				position: absolute;
				opacity: 0;
				top: 0px;
				-webkit-transition: background-color 0.25s ease-in, opacity 0.25s ease-in; 
				transition: background-color 0.25s ease-in, opacity 0.25s ease-in;  
				text-align: center;
				border-radius: 4px;
			}

			div.options_group.ct-mpac-min-max-upgrade a.overlay.upgrade-to-min-max-quantities span.upgrade-button {
				background: linear-gradient(0deg, #FFFFFF, #FFFFFF);
				border: 1px solid #C4C4C4;
				box-sizing: border-box;
				box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
				border-radius: 4px;
				color: #008AD8;
				font-weight:normal;
			}

			div.options_group.ct-mpac-min-max-upgrade a.overlay.upgrade-to-min-max-quantities span.upgrade-text {
				font-style: normal;
				font-weight: bold;
				font-size: 18px;
				line-height: 1.9;
				/* identical to box height */
				text-align: center;
				color: #FFFFFF;
				font-weight: 700;
			}

			div.options_group.ct-mpac-min-max-upgrade:hover a.overlay.upgrade-to-min-max-quantities {
				width: 100%;
    			height: 100%;
    			display:flex;
    			cursor: pointer;
    			justify-content: center;
    			align-items: center;
    			background-color: #565656;
    			opacity: 0.8;
    			top: 0px;
    			-webkit-transition: background-color 0.25s ease-in, opacity 0.25s ease-in; 
    			transition: background-color 0.25s ease-in, opacity 0.25s ease-in; 
    			text-align: center;
    			border-radius: 4px;
			}

			a.overlay.upgrade-to-min-max-quantities:link {
				text-decoration: none;
			}
			
			</style>';
		}
	}
}

<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
$operator = isset($options->operator) ? $options->operator : 'custom_coupon';
$values = isset($options->value) ? $options->value : false;
$custom_value = isset($options->custom_value) ? $options->custom_value : false;
$coupon_msg = false;
if($custom_value){
	$coupon_msg = \Wdr\App\Helpers\Woocommerce::validateDynamicCoupon($custom_value);
}
echo ($render_saved_condition == true) ? '' : '<div class="cart_coupon">';
?>
    <div class="wdr_cart_coupon_group wdr-condition-type-options">
        <div class="wdr-cart-coupon wdr-select-filed-hight">
            <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][operator]" class="wdr_copon_type awdr-left-align">
                <option value="custom_coupon" <?php echo ($operator == "custom_coupon") ? "selected" : ""; ?>><?php esc_html_e('Create your own coupon ', 'woo-discount-rules-pro') ?></option>
                <option value="at_least_one" <?php echo ($operator == "at_least_one") ? "selected" : ""; ?>><?php esc_html_e('Apply if any one coupon is applied (Select from Woocommerce)', 'woo-discount-rules-pro') ?></option>
                <option value="all" <?php echo ($operator == "all") ? "selected" : ""; ?>><?php esc_html_e('Apply if all coupon is applied (Select from Woocommerce)', 'woo-discount-rules-pro') ?></option>
            </select>
            <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('select coupon by', 'woo-discount-rules-pro'); ?></span>
        </div>
        <div class="wdr-cart-coupon-search wdr-coupon-search_box wdr-select-filed-hight"
             style="<?php echo ($operator != "custom_coupon" && $operator != "none_at_all" && $operator != "at_least_one_any") ? 'display: block' : 'display: none' ?>">
            <select multiple="" name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][value][]"
                    class="awdr-left-align <?php echo ($render_saved_condition == true) ? 'edit-filters' : '' ?>"
                    id="rm-coupon"
                    data-placeholder="<?php esc_html_e('Search Coupon', 'woo-discount-rules-pro');?>"
                    data-list="cart_coupon"
                    data-field="autocomplete"
                    style="width: 100%;min-width: 400%;">
				<?php
				if ($values) {
					foreach ($values as $value) { ?>
                        <option value="<?php echo esc_attr($value); ?>" selected><?php echo esc_html($value); ?></option>
					<?php }
				}
				?>
            </select>
            <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('Select coupon', 'woo-discount-rules-pro'); ?></span>
        </div>
        <div class="wdr-cart-coupon-value wdr-input-filed-hight"
             style="<?php echo ($operator == "custom_coupon") ? 'display: block' : 'display: none' ?>">
            <input class="coupon_name_msg awdr-left-align"
                   type="text"
                   name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][custom_value]"
                   placeholder="<?php esc_attr_e("Coupon Name", 'woo-discount-rules-pro'); ?>"
                   value="<?php if($custom_value){ echo esc_attr($custom_value); }?>"
            >
            <span class="wdr_desc_text awdr-clear-both"><?php esc_html_e('Enter Coupon name', 'woo-discount-rules-pro'); ?></span>
			<?php
			if(isset($coupon_msg['status']))
				if($coupon_msg['status'] == false && isset($coupon_msg['message']) && !empty($coupon_msg['message'])) { ?>
                    <span class="wdr_desc_text coupon_error_msg" style="color: #FF0000"><?php echo esc_html($coupon_msg['message']) ?></span><?php
				}
			?>
        </div>

        <div class="wdr-cart-coupon-url">
            <label>
                <input class="wdr-cart-coupon-url-enable" type="checkbox"
                       name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][enable_url]"
                       value="1" <?php if (isset($options->enable_url)) echo 'checked' ?>
                >
                URL Coupons
            </label>

            <div class="wdr-cart-coupon-url-lists" style="<?php if (!isset($options->enable_url)) echo 'display: none' ?>">
                <div class="wdr-cart-coupon-url-custom" style="<?php echo ($operator == "custom_coupon" && $custom_value) ? 'display: block' : 'display: none' ?>">
                    <span class="wdr-coupon-url-group">
                        <label>
                            <input type="url" value="<?php echo esc_url(home_url('?wdr_coupon=' . rawurlencode($custom_value))); ?>">
                        </label>
                        <button class="wdr-copy-coupon-url"><?php esc_html_e('Copy URL', 'woo-discount-rules-pro'); ?></button>
                    </span>
                </div>

                <div class="wdr-cart-coupon-url-one" style="<?php echo ($operator == "at_least_one" && !empty($values)) ? 'display: block' : 'display: none' ?>">
					<?php $checked = isset($options->url) ? $options->url : [];
					if (!empty($values) && is_array($values)) {
						foreach ($values as $value) { ?>
                            <span class="wdr-coupon-url-group">
                                <label>
                                    <input type="url" value="<?php echo esc_url(home_url('?wdr_coupon=' . rawurlencode($value))); ?>">
                                </label>
                                <button class="wdr-copy-coupon-url"><?php esc_html_e('Copy URL', 'woo-discount-rules-pro'); ?></button>
                            </span><br>
						<?php }
					} ?>
                </div>

                <div class="wdr-cart-coupon-url-all" style="<?php echo ($operator == "all" && !empty($values)) ? 'display: block' : 'display: none' ?>">
					<?php $checked = isset($options->url) ? $options->url : [];
                    $coupons = '';
                    $url = home_url('?wdr_coupon=');
                    if (!empty($values) && is_array($values)) {
						foreach ($values as $value) {
							$coupons .= $value . ", ";
							$url .= rawurlencode($value) . ',';
						}
						$coupons = rtrim($coupons, ", ");
						$url = rtrim($url, ',');
					} ?>
                    <span class="wdr-coupon-url-group">
                        <label>
                            <input type="url" value="<?php echo esc_url($url); ?>">
                        </label>
                        <button class="wdr-copy-coupon-url"><?php esc_html_e('Copy URL', 'woo-discount-rules-pro'); ?></button>
                    </span>
                </div>
            </div>
        </div>
    </div>
<?php echo ($render_saved_condition == true) ? '' : '</div>'; ?>
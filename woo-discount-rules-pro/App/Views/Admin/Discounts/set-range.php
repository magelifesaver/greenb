<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wdr-discount-group bundle-set-range-main" data-index="<?php echo esc_attr($set_range); ?>">
    <div class="range_setter_inner">
        <div class="wdr-simple-discount-main set-discount-row-main">
            <div class="wdr-simple-discount-inner wdr-input-filed-hight set-discount-row-inner">
                <div class="dashicons dashicons-menu awdr-set-sort-icon awdr-sortable-handle"></div>
                <div class="set-min">
                    <input type="number"
                           name="set_adjustments[ranges][<?php echo esc_attr($set_range); ?>][from]"
                           class="set_discount_min awdr-left-align"
                           value="<?php if (isset($range->from) && !empty($range->from)) {
                               echo esc_attr($range->from);
                           } ?>"
                           placeholder="<?php esc_attr_e('Quantity', 'woo-discount-rules-pro'); ?>"
                           min="0"
                           step="any"
                    >
                    <span class="wdr_desc_text awdr-clear-both"><?php esc_html_e('Quantity ', 'woo-discount-rules-pro'); ?></span>
                </div>

                <div class="set-for">
                    <p><?php _e('for ', 'woo-discount-rules-pro'); ?></p>
                </div>
                <div class="set_amount">
                    <input type="number"
                           name="set_adjustments[ranges][<?php echo esc_attr($set_range); ?>][value]"
                           class="set_discount_value bulk_value_selector awdr-left-align"
                           value="<?php if (isset($range->value) && $range->value >= 0) {
                               echo esc_attr(floatval($range->value));
                           } ?>"
                           placeholder="<?php esc_attr_e('Value', 'woo-discount-rules-pro'); ?>"
                           min="0"
                            step="any"
                    >
                    <span class="wdr_desc_text awdr-clear-both"><?php esc_html_e('Discount Value ', 'woo-discount-rules-pro'); ?></span>
                </div>
                <div class="bulk_gen_disc_type wdr-select-filed-hight">
                    <select name="set_adjustments[ranges][<?php echo esc_attr($set_range); ?>][type]" class="set-discount-type bulk_discount_select awdr-left-align ">
                        <option value="fixed_set_price" <?php if (isset($range->type) && $range->type == "fixed_set_price") {
                            echo "selected";
                        } ?>><?php esc_html_e('Fixed price for set / bundle', 'woo-discount-rules-pro') ?></option>
                        <option value="percentage" <?php if (isset($range->type) && $range->type == "percentage") {
                            echo "selected";
                        } ?>><?php esc_html_e('Percentage discount per item', 'woo-discount-rules-pro') ?></option>
                        <option value="flat" <?php if (isset($range->type) && $range->type == "flat") {
                            echo "selected";
                        } ?>><?php esc_html_e('Fixed discount per item', 'woo-discount-rules-pro') ?></option>
                    </select>
                    <span class="wdr_desc_text awdr-clear-both"><?php esc_html_e('Discount Type', 'woo-discount-rules-pro'); ?></span>
                </div>

                <div class="set_label">
                    <input type="text" name="set_adjustments[ranges][<?php echo esc_attr($set_range); ?>][label]"
                           class="bulk_value_selector awdr-left-align"
                           placeholder="<?php esc_attr_e('label', 'woo-discount-rules-pro'); ?>" value="<?php if (isset($range->label) && !empty($range->label)) {
                        echo wp_unslash($range->label);
                    } ?>">
                    <span class="wdr_desc_text awdr-clear-both"><?php esc_html_e('Title column For Bulk Table', 'woo-discount-rules-pro'); ?></span>
                </div>

                <div class="awdr-bundle-set-recursive" style="vertical-align: text-bottom;"><?php
                    $is_enabled_rtl = \WDRPro\App\Helpers\CoreMethodCheck::isRTLEnable();?>
                    <!-- -->
                    <div class="<?php echo (!$is_enabled_rtl) ? 'page__toggle' : ''; ?>">
                        <label class="<?php echo (!$is_enabled_rtl) ? 'toggle' : ''; ?>">
                            <input class="<?php echo (!$is_enabled_rtl) ? 'toggle__input' : ''; ?> awdr-bogo-recurcive" type="checkbox" style="<?php echo ($is_enabled_rtl) ? 'height: 18px !important;' : ''; ?>"
                                   name="set_adjustments[ranges][<?php echo esc_attr($set_range); ?>][recursive]"
                                   data-recursive-row="bundle-set-range-main"
                                   data-recursive-parent="awdr-bundle-set-recursive"
                                   data-hide-add-range="hide-add-row-button"
                                   data-bogo-max-range=""
                                   data-bogo-min-range="set-min"
                                   data-bogo-border="set-discount-row-inner"
                                   data-ranges-row-parent="set_range_setter"

                                   value="1" <?php echo (isset($range->recursive) && !empty($range->recursive)) ? 'checked' : ''; ?>>
                            <span class="<?php echo (!$is_enabled_rtl) ? 'toggle__label' : ''; ?>">
                                    <span class="<?php echo (!$is_enabled_rtl) ? 'toggle__text' : ''; ?>"><?php esc_html_e('Recursive?', 'woo-discount-rules-pro'); ?></span>
                                </span>
                        </label>
                    </div>
                </div>

                <div class="wdr-btn-remove set-remove-icon">
                                            <span class="dashicons dashicons-no-alt wdr_discount_remove"
                                                  data-rmdiv="bulk_range_group"></span>
                </div>
            </div>
        </div>
    </div>
</div>
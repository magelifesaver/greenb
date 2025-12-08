<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$operator = isset($options->operator) ? $options->operator : 'greater_than_or_equal';
$order_status = isset($options->status) ? $options->status : false;
echo ($render_saved_condition == true) ? '' : '<div class="purchase_last_order_amount">';
?>
    <div class="wdr_purchase_last_order_amount_group wdr-condition-type-options">
        <div class="wdr-last-order-amount-method wdr-select-filed-hight">
            <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][operator]" class="awdr-left-align">
                <option value="less_than" <?php echo ($operator == "less_than") ? "selected" : ""; ?>><?php esc_html_e('Less than ( &lt; )', 'woo-discount-rules-pro') ?></option>
                <option value="less_than_or_equal" <?php echo ($operator == "less_than_or_equal") ? "selected" : ""; ?>><?php esc_html_e('Less than or equal ( &lt;= )', 'woo-discount-rules-pro') ?></option>
                <option value="greater_than_or_equal" <?php echo ($operator == "greater_than_or_equal") ? "selected" : ""; ?>><?php esc_html_e('Greater than or equal ( &gt;= )', 'woo-discount-rules-pro') ?></option>
                <option value="greater_than" <?php echo ($operator == "greater_than") ? "selected" : ""; ?>><?php esc_html_e('Greater than ( &gt; )', 'woo-discount-rules-pro') ?></option>
            </select>
            <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('order amount should be', 'woo-discount-rules-pro'); ?></span>
        </div>

        <div class="wdr-last-order-amount-value wdr-input-filed-hight">
            <input name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][value]"
                   value="<?php echo (isset($options->value)) ? esc_attr($options->value) : '' ?>" type="text"
                   placeholder="<?php _e('0.00', 'woo-discount-rules-pro');?>" class="float_only_field awdr-left-align">
            <span class="wdr_desc_text awdr-clear-both "><?php _e('order amount', 'woo-discount-rules-pro'); ?></span>
        </div>
        <div class="wdr-last-order-amount-product-status wdr-select-filed-hight wdr-search-box">
            <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][status][]" multiple
                    class="wdr-wc-order-status <?php echo ($render_saved_condition == true) ? 'edit-all-loaded-values' : '' ?>"
                    data-placeholder="<?php esc_attr_e('Search Order Status', 'woo-discount-rules-pro');?>"
                    data-list="order_status"
                    data-field="autoloaded"
                    style="width: 100%; max-width: 400px;  min-width: 180px;"><?php
                if ($order_status) {
                    $settings_config = new \Wdr\App\Controllers\Admin\Settings();
                    $woo_order_status = $settings_config->getWoocommerceOrderStatus();
                    foreach ($order_status as $status) {
                        foreach ($woo_order_status as $woo_status) {
                            if ($woo_status['id'] == $status) {
                                $order_status_label = $woo_status['text'];
                            }
                        }
                        if ($order_status_label != '') { ?>
                            <option value="<?php echo esc_attr($status); ?>"
                                    selected><?php echo esc_html($order_status_label); ?></option><?php
                        }
                    }
                }
                ?>
            </select>
            <span class="wdr_select2_desc_text"><?php esc_html_e('order status', 'woo-discount-rules-pro'); ?></span>
        </div>
    </div>
<?php echo ($render_saved_condition == true) ? '' : '</div>'; ?>
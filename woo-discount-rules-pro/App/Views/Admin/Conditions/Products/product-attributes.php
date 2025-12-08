<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$operator = isset($options->operator) ? $options->operator : 'in_list';
$cartqty = isset($options->cartqty) ? $options->cartqty : 'greater_than_or_equal';
$values = isset($options->value) ? $options->value : false;
echo ($render_saved_condition == true) ? '' : '<div class="cart_item_product_attributes">';
?>
<div class="product_attributes_group wdr-condition-type-options">
    <div class="wdr-product_filter_method wdr-select-filed-hight">
        <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][operator]" class="awdr-left-align">
            <option value="in_list" <?php echo ($operator == "in_list") ? "selected" : ""; ?>><?php esc_html_e('In List', 'woo-discount-rules-pro'); ?></option>
            <option value="not_in_list" <?php echo ($operator == "not_in_list") ? "selected" : ""; ?>><?php esc_html_e('Not In List', 'woo-discount-rules-pro'); ?></option>
        </select>
        <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('Attributes should be', 'woo-discount-rules-pro'); ?></span>
    </div>
    <div class="wdr-product-attributes-selector wdr-select-filed-hight wdr-search-box">
        <select multiple
                class="awdr-attribute-validation <?php echo ($render_saved_condition == true) ? 'edit-filters' : '' ?>"
                data-list="product_attributes"
                data-field="autocomplete"
                data-placeholder="<?php esc_attr_e('Search Attributes', 'woo-discount-rules-pro');?>"
                name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][value][]"><?php
            if ($values) {
                foreach ($values as $value) {
                    global $wc_product_attributes;
                    $item_name = '';
                    foreach (array_keys($wc_product_attributes) as $att_key) {
                        $att_object = get_term_by('id', $value, $att_key);
                        if (!empty($att_object) && is_object($att_object)) {
                            $item_name = $att_object->name;
                        }
                    }
                    if ($item_name != '') { ?>
                        <option value="<?php echo esc_attr($value); ?>" selected><?php echo esc_html($item_name); ?></option><?php
                    }
                }
            }
            ?>
        </select>
        <span class="wdr_select2_desc_text"><?php esc_html_e('Select Attributes', 'woo-discount-rules-pro'); ?></span>
    </div>
    <div class="wdr-product-attributes wdr-select-filed-hight">
        <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][cartqty]" class="awdr-left-align">
            <option value="less_than_or_equal" <?php echo ($cartqty == "less_than_or_equal") ? "selected" : ""; ?>><?php esc_html_e('Less than or equal ( &lt;= )', 'woo-discount-rules-pro') ?></option>
            <option value="less_than" <?php echo ($cartqty == "less_than") ? "selected" : ""; ?>><?php esc_html_e('Less than ( &lt; )', 'woo-discount-rules-pro') ?></option>
            <option value="greater_than_or_equal" <?php echo ($cartqty == "greater_than_or_equal") ? "selected" : ""; ?>><?php esc_html_e('Greater than or equal ( &gt;= )', 'woo-discount-rules-pro') ?></option>
            <option value="greater_than" <?php echo ($cartqty == "greater_than") ? "selected" : ""; ?>><?php esc_html_e('Greater than ( &gt; )', 'woo-discount-rules-pro') ?></option>
        </select>
        <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('Attributes Quantity in cart', 'woo-discount-rules-pro'); ?></span>
    </div>
    <div class="wdr-product_filter_qty wdr-input-filed-hight">
        <input type="number" placeholder="<?php esc_attr_e('qty', 'woo-discount-rules-pro');?>" min="0" step="any"
               class="awdr-left-align awdr-num-validation"
               name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][qty]"
               value="<?php echo isset($options->qty) ? esc_attr($options->qty) : '1'; ?>">
        <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('Attributes Quantity', 'woo-discount-rules-pro'); ?></span>
    </div>
</div>
<?php echo ($render_saved_condition == true) ? '' : '</div>'; ?>

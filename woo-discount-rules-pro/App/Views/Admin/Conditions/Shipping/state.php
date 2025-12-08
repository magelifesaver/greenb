<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
echo ($render_saved_condition == true) ? '' : '<div class="shipping_state">';
$operator = isset($options->operator) ? $options->operator : 'in_list';
$values = isset($options->value) ? $options->value : false;
$country_values = isset($options->countries) ? $options->countries : false;
$get_state_list = \Wdr\App\Helpers\Woocommerce::getStatesList();
?>
<div class="wdr_shipping_state_group wdr-condition-type-options">
    <div class="wdr-shipping-state wdr-select-filed-hight">
        <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][operator]" class="awdr-left-align">
            <option value="in_list" <?php echo ($operator == "in_list") ? "selected" : ""; ?>><?php esc_html_e('In List', 'woo-discount-rules-pro'); ?></option>
            <option value="not_in_list" <?php echo ($operator == "not_in_list") ? "selected" : ""; ?>><?php esc_html_e('Not In List', 'woo-discount-rules-pro'); ?></option>
        </select>
        <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('States should be', 'woo-discount-rules-pro'); ?></span>
    </div>

    <div class="awdr_state_based_country_selector wdr-select-filed-hight wdr-search-box">
        <select multiple
                class="get_awdr_state_based_country <?php echo ($render_saved_condition == true) ? 'edit-preloaded-values' : '' ?>"
                data-list="countries"
                data-field="preloaded"
                data-placeholder="<?php esc_attr_e('Search Country', 'woo-discount-rules-pro') ?>"
                name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][countries][]"><?php
            if ($country_values) {
                $countries_list = \Wdr\App\Helpers\Woocommerce::getCountriesList();
                foreach ($country_values as $value) {
                    if (isset($countries_list[$value])) {
                        ?>
                        <option value="<?php echo esc_attr($value); ?>"
                                selected><?php echo esc_html($countries_list[$value]); ?></option><?php
                    }
                }
            }
            ?>
        </select>
        <span class="wdr_select2_desc_text"><?php esc_html_e('Select Country', 'woo-discount-rules-pro'); ?></span>
    </div>

    <div class="wdr-shipping-state-value wdr-select-filed-hight wdr-search-box">
        <select multiple
                class="get_awdr_shipping_state <?php echo ($render_saved_condition == true) ? 'edit-preloaded-values' : '' ?>"
                data-list="states"
                data-field="preloaded"
                data-placeholder="<?php esc_attr_e('Search State', 'woo-discount-rules-pro') ?>"
                name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][value][]"><?php
            if ($values) {
                $state_label = '';
                $get_state_list = \Wdr\App\Helpers\Woocommerce::getStatesList();
                foreach ($values as $value) {
                    $state_label = array_column($get_state_list, $value);
                    if (isset($state_label) && is_array($state_label) && $state_label != '') {
                        ?>
                        <option value="<?php echo esc_attr($value); ?>" selected><?php echo esc_html($state_label[0]); ?></option><?php
                    }
                }
            }
            ?>
        </select>
        <span class="wdr_select2_desc_text"><?php esc_html_e('Select State', 'woo-discount-rules-pro'); ?></span>
    </div>
</div>
<?php echo ($render_saved_condition == true) ? '' : '</div>'; ?>


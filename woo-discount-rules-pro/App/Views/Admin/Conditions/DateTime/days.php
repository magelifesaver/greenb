<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$values = isset($options->value) ? $options->value : false;
$operator = isset($options->operator) ? $options->operator : 'in_list';
echo ($render_saved_condition == true) ? '' : '<div class="order_days">';
?>
<div class="wdr_days_group wdr-condition-type-options">
    <div class="wdr-days-method wdr-select-filed-hight">
        <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][operator]" class="awdr-left-align">
            <option value="in_list" <?php echo ($operator == "in_list") ? "selected" : ""; ?>><?php esc_html_e('In List', 'woo-discount-rules-pro'); ?></option>
            <option value="not_in_list" <?php echo ($operator == "not_in_list") ? "selected" : ""; ?>><?php esc_html_e('Not In List', 'woo-discount-rules-pro'); ?></option>
        </select>
        <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('Day should be', 'woo-discount-rules-pro'); ?></span>
    </div>

    <div class="wdr-days-value wdr-select-filed-hight wdr-search-box">
        <select multiple
                class="wdr_order_days <?php echo ($render_saved_condition == true) ? 'edit-all-loaded-values' : '' ?>"
                data-list="weekdays"
                data-field="autoloaded"
                data-placeholder="<?php esc_html_e('Search Days', 'woo-discount-rules-pro') ?>"
                name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][value][]"><?php
            if ($values) {
                $settings_config = new \Wdr\App\Controllers\Admin\Settings();
                $week_days = $settings_config->getWeekDays();
                $week_day_label = '';
                foreach ($values as $value) {
                    foreach ($week_days as $week_day) {
                        if ($value == $week_day['id']) {
                            $week_day_label = $week_day['text'];
                        }
                    }
                    if ($week_day_label != '') {
                        ?>
                        <option value="<?php echo esc_attr($value); ?>" selected><?php echo esc_html($week_day_label); ?></option><?php
                    }
                }
            }
            ?>
        </select>
        <span class="wdr_select2_desc_text"><?php esc_html_e('Select Days', 'woo-discount-rules-pro'); ?></span>
    </div>
</div>
<?php echo ($render_saved_condition == true) ? '' : '</div>'; ?>

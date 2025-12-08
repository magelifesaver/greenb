<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$operator = isset($options->operator) ? $options->operator : 'in_list';
$values = isset($options->value) ? $options->value : false;
$settings = new \Wdr\App\Controllers\Admin\Settings();
$user_roles = $settings->getUserRoles();
echo ($render_saved_condition == true) ? '' : '<div class="user_role">';
?>
<div class="wdr_user_role_group wdr-condition-type-options">
    <div class="wdr-select-filed-hight">
        <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][operator]" class="awdr-left-align">
            <option value="in_list" <?php echo ($operator == "in_list") ? "selected" : ""; ?>><?php esc_html_e('in list', 'woo-discount-rules-pro') ?></option>
            <option value="not_in_list" <?php echo ($operator == "not_in_list") ? "selected" : ""; ?>><?php esc_html_e('not in list', 'woo-discount-rules-pro') ?></option>
        </select>
        <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('user role should be', 'woo-discount-rules-pro'); ?></span>
    </div>

    <div class="wdr-select-filed-hight wdr-search-box">
        <select multiple
                class="wdr_user_role <?php echo ($render_saved_condition == true) ? 'edit-all-loaded-values' : '' ?>"
                data-list="user_roles"
                data-field="autoloaded"
                data-placeholder="<?php esc_attr_e('Search User Roles', 'woo-discount-rules-pro');?>"
                name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][value][]"><?php
            if ($values) {
                foreach ($values as $value) {
                    foreach ($user_roles as $user_key => $user_role) {
                        if ($user_role['id'] == $value) {
                            ?>
                            <option value="<?php echo esc_attr($value); ?>" selected><?php echo esc_html($user_role['text']); ?></option>
                            <?php
                        }
                    }
                }
            }
            ?>
        </select>
        <span class="wdr_select2_desc_text"><?php esc_html_e('Select User Roles', 'woo-discount-rules-pro'); ?></span>
    </div>
</div>
<?php echo ($render_saved_condition == true) ? '' : '</div>'; ?>

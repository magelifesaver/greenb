<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/image-generator/partials/settings-token-management.php
// Status: NEW FILE

/**
 * Partial: Image Generator Token Management Settings
 * Renders token limit settings for the Image Generator module.
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Images\AIPKit_Image_Settings_Ajax_Handler;
use WPAICG\Chat\Storage\BotSettingsManager; // Use for default constants

// Get settings
$settings_data = AIPKit_Image_Settings_Ajax_Handler::get_settings();
$token_settings = $settings_data['token_management'] ?? [];

// --- Defaults ---
$default_reset_period = BotSettingsManager::DEFAULT_TOKEN_RESET_PERIOD;
$default_limit_message = BotSettingsManager::DEFAULT_TOKEN_LIMIT_MESSAGE ?: __('You have reached your token limit for this period.', 'gpt3-ai-content-generator');
$default_limit_mode = BotSettingsManager::DEFAULT_TOKEN_LIMIT_MODE;

// --- Get saved values ---
$guest_limit = $token_settings['token_guest_limit'] ?? null;
$user_limit = $token_settings['token_user_limit'] ?? null;
$reset_period = $token_settings['token_reset_period'] ?? $default_reset_period;
$limit_message = $token_settings['token_limit_message'] ?? $default_limit_message;
$limit_mode = $token_settings['token_limit_mode'] ?? $default_limit_mode;

// Handle role limits - they are JSON encoded in the option
$role_limits_raw = $token_settings['token_role_limits'] ?? '[]';
$role_limits = is_string($role_limits_raw) ? json_decode($role_limits_raw, true) : ($role_limits_raw ?: []);
if (!is_array($role_limits)) {
    $role_limits = [];
}


$guest_limit_value = ($guest_limit === null) ? '' : (string)$guest_limit;
$user_limit_value = ($user_limit === null) ? '' : (string)$user_limit;
?>

<p class="aipkit_form-help" style="margin-top: 0; margin-bottom: 15px;">
    <?php esc_html_e('Control token usage for the Image Generator module. Each generated image has a fixed token cost.', 'gpt3-ai-content-generator'); ?>
</p>

<div class="aipkit_form-row" style="align-items: flex-start;">
    <div class="aipkit_form-col aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_image_token_guest_limit"><?php esc_html_e('Guest Limit', 'gpt3-ai-content-generator'); ?></label>
        <input type="number" id="aipkit_image_token_guest_limit" name="image_token_guest_limit" class="aipkit_form-input aipkit_settings_input" value="<?php echo esc_attr($guest_limit_value); ?>" min="0" step="1" placeholder="<?php esc_attr_e('(Unlimited)', 'gpt3-ai-content-generator'); ?>" />
        <div class="aipkit_form-help"><?php esc_html_e('0 = disabled.', 'gpt3-ai-content-generator'); ?></div>
    </div>

    <div class="aipkit_form-col aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_image_token_limit_mode"><?php esc_html_e('User Limit Type', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_image_token_limit_mode" name="image_token_limit_mode" class="aipkit_form-input aipkit_token_limit_mode_select">
            <option value="general" <?php selected($limit_mode, 'general'); ?>><?php esc_html_e('General', 'gpt3-ai-content-generator'); ?></option>
            <option value="role_based" <?php selected($limit_mode, 'role_based'); ?>><?php esc_html_e('Role-Based', 'gpt3-ai-content-generator'); ?></option>
        </select>
    </div>

    <div class="aipkit_form-col aipkit_form-group aipkit_token_general_user_limit_field" style="display: <?php echo ($limit_mode === 'general') ? 'block' : 'none'; ?>;">
        <label class="aipkit_form-label" for="aipkit_image_token_user_limit"><?php esc_html_e('General User Limit', 'gpt3-ai-content-generator'); ?></label>
        <input type="number" id="aipkit_image_token_user_limit" name="image_token_user_limit" class="aipkit_form-input aipkit_settings_input" value="<?php echo esc_attr($user_limit_value); ?>" min="0" step="1" placeholder="<?php esc_attr_e('(Unlimited)', 'gpt3-ai-content-generator'); ?>" />
        <div class="aipkit_form-help"><?php esc_html_e('0 = disabled.', 'gpt3-ai-content-generator'); ?></div>
    </div>
    
    <div class="aipkit_form-col aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_image_token_reset_period"><?php esc_html_e('Reset Period', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_image_token_reset_period" name="image_token_reset_period" class="aipkit_form-input aipkit_settings_input">
            <option value="never" <?php selected($reset_period, 'never'); ?>><?php esc_html_e('Never', 'gpt3-ai-content-generator'); ?></option>
            <option value="daily" <?php selected($reset_period, 'daily'); ?>><?php esc_html_e('Daily', 'gpt3-ai-content-generator'); ?></option>
            <option value="weekly" <?php selected($reset_period, 'weekly'); ?>><?php esc_html_e('Weekly', 'gpt3-ai-content-generator'); ?></option>
            <option value="monthly" <?php selected($reset_period, 'monthly'); ?>><?php esc_html_e('Monthly', 'gpt3-ai-content-generator'); ?></option>
        </select>
    </div>
</div>

<div class="aipkit_token_role_limits_container" style="display: <?php echo ($limit_mode === 'role_based') ? 'block' : 'none'; ?>;">
    <hr class="aipkit_hr" style="margin-top:0; margin-bottom: 15px;">
    <h4><?php esc_html_e('Role-Based Image Limits', 'gpt3-ai-content-generator'); ?></h4>
    <div class="aipkit_form-help" style="margin-bottom: 10px;"><?php esc_html_e('Set limits for specific roles. Leave empty for unlimited, use 0 to disable access for a role.', 'gpt3-ai-content-generator'); ?></div>
    <?php
    $editable_roles = get_editable_roles();
    foreach ($editable_roles as $role_slug => $role_info) :
        $role_name = translate_user_role($role_info['name']);
        $role_limit = $role_limits[$role_slug] ?? null;
        $role_limit_value = ($role_limit === null) ? '' : (string)$role_limit;
    ?>
        <div class="aipkit_form-group" style="margin-bottom: 8px;">
            <label class="aipkit_form-label" for="aipkit_image_token_role_<?php echo esc_attr($role_slug); ?>" style="width: 150px; display: inline-block; margin-right: 10px; text-align: right;"><?php echo esc_html($role_name); ?>:</label>
            <input type="number" id="aipkit_image_token_role_<?php echo esc_attr($role_slug); ?>" name="image_token_role_limits[<?php echo esc_attr($role_slug); ?>]" class="aipkit_form-input aipkit_settings_input" value="<?php echo esc_attr($role_limit_value); ?>" min="0" step="1" style="max-width: 150px; display: inline-block;" placeholder="<?php esc_attr_e('(Unlimited)', 'gpt3-ai-content-generator'); ?>" />
        </div>
    <?php endforeach; ?>
</div>

<hr class="aipkit_hr">

<div class="aipkit_form-group">
    <label class="aipkit_form-label" for="aipkit_image_token_limit_message"><?php esc_html_e('Token Limit Message', 'gpt3-ai-content-generator'); ?></label>
    <input type="text" id="aipkit_image_token_limit_message" name="image_token_limit_message" class="aipkit_form-input aipkit_settings_input" value="<?php echo esc_attr($limit_message); ?>" placeholder="<?php echo esc_attr($default_limit_message); ?>" />
    <div class="aipkit_form-help"><?php esc_html_e('The message shown to users when they exceed their token limit for the period.', 'gpt3-ai-content-generator'); ?></div>
</div>
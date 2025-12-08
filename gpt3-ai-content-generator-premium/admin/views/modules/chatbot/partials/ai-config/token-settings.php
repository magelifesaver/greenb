<?php
/**
 * Partial: Chatbot Token Management Settings (Modal Body)
 */
if (!defined('ABSPATH')) { exit; }

use WPAICG\Chat\Storage\BotSettingsManager;
use WPAICG\Core\TokenManager\Constants\CronHookConstant;

$default_reset_period = BotSettingsManager::DEFAULT_TOKEN_RESET_PERIOD;
$default_limit_message = __('You have reached your token limit for this period.', 'gpt3-ai-content-generator');

$guest_limit = $bot_settings['token_guest_limit'] ?? null;
$user_limit = $bot_settings['token_user_limit'] ?? null;
$reset_period = $bot_settings['token_reset_period'] ?? $default_reset_period;
$limit_message = $bot_settings['token_limit_message'] ?? $default_limit_message;
$limit_mode = $bot_settings['token_limit_mode'] ?? BotSettingsManager::DEFAULT_TOKEN_LIMIT_MODE;
$role_limits = $bot_settings['token_role_limits'] ?? [];

$guest_limit_value = ($guest_limit === null) ? '' : (string)$guest_limit;
$user_limit_value = ($user_limit === null) ? '' : (string)$user_limit;
?>

<div class="aipkit_settings_sections">
  <!-- Token Limits -->
  <section class="aipkit_settings_section" data-section="token-limits">
    <div class="aipkit_settings_section-header">
      <h5 class="aipkit_settings_section-title"><?php esc_html_e('Token Limits', 'gpt3-ai-content-generator'); ?></h5>
    </div>
    <div class="aipkit_settings_section-body">
      <div class="aipkit_settings_grid aipkit_settings_grid--4">
        <!-- Guest Limit -->
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_guest_limit_modal"><?php esc_html_e('Guest Limit', 'gpt3-ai-content-generator'); ?></label>
          <input type="number" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_guest_limit_modal" name="token_guest_limit" class="aipkit_form-input" value="<?php echo esc_attr($guest_limit_value); ?>" min="0" step="1" placeholder="<?php esc_attr_e('(Unlimited)', 'gpt3-ai-content-generator'); ?>" />
          <div class="aipkit_form-help"><?php esc_html_e('0 = disabled.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <!-- Limit Type -->
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_limit_mode_modal"><?php esc_html_e('Limit Type', 'gpt3-ai-content-generator'); ?></label>
          <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_limit_mode_modal" name="token_limit_mode" class="aipkit_form-input aipkit_token_limit_mode_select">
            <option value="general" <?php selected($limit_mode, 'general'); ?>><?php esc_html_e('General Limit', 'gpt3-ai-content-generator'); ?></option>
            <option value="role_based" <?php selected($limit_mode, 'role_based'); ?>><?php esc_html_e('Role-Based Limits', 'gpt3-ai-content-generator'); ?></option>
          </select>
          <div class="aipkit_form-help"><?php esc_html_e('For logged-in users.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <!-- General User Limit (Conditional) -->
        <div class="aipkit_form-group aipkit_token_general_user_limit_field" style="display: <?php echo ($limit_mode === 'general') ? 'block' : 'none'; ?>;">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_user_limit_modal"><?php esc_html_e('User Limit', 'gpt3-ai-content-generator'); ?></label>
          <input type="number" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_user_limit_modal" name="token_user_limit" class="aipkit_form-input" value="<?php echo esc_attr($user_limit_value); ?>" min="0" step="1" placeholder="<?php esc_attr_e('(Unlimited)', 'gpt3-ai-content-generator'); ?>" />
          <div class="aipkit_form-help"><?php esc_html_e('0 = disabled.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <!-- Reset Period -->
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_reset_period_modal"><?php esc_html_e('Reset Period', 'gpt3-ai-content-generator'); ?></label>
          <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_reset_period_modal" name="token_reset_period" class="aipkit_form-input">
            <option value="never" <?php selected($reset_period, 'never'); ?>><?php esc_html_e('Never', 'gpt3-ai-content-generator'); ?></option>
            <option value="daily" <?php selected($reset_period, 'daily'); ?>><?php esc_html_e('Daily', 'gpt3-ai-content-generator'); ?></option>
            <option value="weekly" <?php selected($reset_period, 'weekly'); ?>><?php esc_html_e('Weekly', 'gpt3-ai-content-generator'); ?></option>
            <option value="monthly" <?php selected($reset_period, 'monthly'); ?>><?php esc_html_e('Monthly', 'gpt3-ai-content-generator'); ?></option>
          </select>
          <div class="aipkit_form-help"><?php esc_html_e('How often usage resets.', 'gpt3-ai-content-generator'); ?></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Role-Based Limits -->
  <section class="aipkit_settings_section aipkit_token_role_limits_container" data-section="role-limits" style="display: <?php echo ($limit_mode === 'role_based') ? 'block' : 'none'; ?>;">
    <div class="aipkit_settings_section-header">
      <h5 class="aipkit_settings_section-title"><?php esc_html_e('Role-Based Token Limits', 'gpt3-ai-content-generator'); ?></h5>
    </div>
    <div class="aipkit_settings_section-body">
      <div class="aipkit_form-help" style="margin-bottom: 10px;">
        <?php esc_html_e('Set limits for specific roles. Leave empty for unlimited, use 0 to disable access for a role.', 'gpt3-ai-content-generator'); ?>
      </div>
      <?php
      $editable_roles = get_editable_roles();
      foreach ($editable_roles as $role_slug => $role_info) :
        $role_name = translate_user_role($role_info['name']);
        $role_limit_value = isset($role_limits[$role_slug]) ? $role_limits[$role_slug] : '';
      ?>
        <div class="aipkit_form-group" style="margin-bottom: 8px;">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_role_<?php echo esc_attr($role_slug); ?>_modal" style="width: 150px; display: inline-block; margin-right: 10px; text-align: right;">
            <?php echo esc_html($role_name); ?>:
          </label>
          <input type="number" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_role_<?php echo esc_attr($role_slug); ?>_modal" name="token_role_limits[<?php echo esc_attr($role_slug); ?>]" class="aipkit_form-input" value="<?php echo esc_attr($role_limit_value); ?>" min="0" step="1" style="max-width: 150px; display: inline-block;" placeholder="<?php esc_attr_e('(Unlimited)', 'gpt3-ai-content-generator'); ?>" />
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Limit Message -->
  <section class="aipkit_settings_section" data-section="limit-message">
    <div class="aipkit_settings_section-header">
      <h5 class="aipkit_settings_section-title"><?php esc_html_e('Limit Message', 'gpt3-ai-content-generator'); ?></h5>
    </div>
    <div class="aipkit_settings_section-body">
      <div class="aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_limit_message_modal"><?php esc_html_e('Token Limit Message', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_token_limit_message_modal" name="token_limit_message" class="aipkit_form-input" value="<?php echo esc_attr($limit_message); ?>" placeholder="<?php echo esc_attr($default_limit_message); ?>" />
        <div class="aipkit_form-help">
          <?php esc_html_e('The message shown to users when they exceed their token limit for the period.', 'gpt3-ai-content-generator'); ?>
          <?php if (!wp_next_scheduled(CronHookConstant::CRON_HOOK)) : ?>
            <strong class="aipkit_token_reset_warning"><?php esc_html_e('Warning: WP Cron task for resets is not scheduled!', 'gpt3-ai-content-generator'); ?></strong>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>

<?php

/**
 * Partial: Appearance - Feature Toggles (Checkboxes)
 * UPDATED: Combined all feature toggles into a single row to save space.
 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Chat\Storage\BotSettingsManager;

// Variables available from parent script:
// $bot_id, $bot_settings, $starters_addon_active

$popup_enabled = isset($bot_settings['popup_enabled']) ? $bot_settings['popup_enabled'] : '0';
$enable_fullscreen = isset($bot_settings['enable_fullscreen']) ? $bot_settings['enable_fullscreen'] : '0';
$enable_download = isset($bot_settings['enable_download']) ? $bot_settings['enable_download'] : '0';
$enable_copy_button = isset($bot_settings['enable_copy_button'])
    ? $bot_settings['enable_copy_button']
    : BotSettingsManager::DEFAULT_ENABLE_COPY_BUTTON;
$enable_conversation_starters = isset($bot_settings['enable_conversation_starters'])
    ? $bot_settings['enable_conversation_starters']
    : BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_STARTERS;
$enable_conversation_sidebar = isset($bot_settings['enable_conversation_sidebar'])
    ? $bot_settings['enable_conversation_sidebar']
    : BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_SIDEBAR;
$enable_feedback = isset($bot_settings['enable_feedback'])
    ? $bot_settings['enable_feedback']
    : BotSettingsManager::DEFAULT_ENABLE_FEEDBACK;

$sidebar_disabled_tooltip = __('Sidebar is not available when Popup mode is enabled.', 'gpt3-ai-content-generator');
?>

<!-- Features grid (match AI tab style) -->
<div class="aipkit_settings_subsection-body aipkit_features_grid">
    <!-- Popup Mode Switch -->
    <div class="aipkit_feature_toggle_item aipkit_form-group">
        <div class="aipkit_feature_row">
            <label
                class="aipkit_checkbox-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_enabled"
            >
                <input
                    type="checkbox"
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_enabled"
                    name="popup_enabled"
                    class="aipkit_toggle_switch aipkit_popup_toggle_switch"
                    value="1"
                    data-sidebar-target="aipkit_bot_<?php echo esc_attr($bot_id); ?>_sidebar_group"
                    <?php checked($popup_enabled, '1'); ?>
                >
                <span><?php esc_html_e('Popup', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <!-- Placeholder Configure button next to Popup -->
            <?php
            $popup_config_btn_title = ($popup_enabled === '1')
                ? __('Configure popup settings', 'gpt3-ai-content-generator')
                : __('Enable to configure', 'gpt3-ai-content-generator');
            $popup_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_popup_settings_modal';
            ?>
            <button
                type="button"
                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn aipkit_popup_config_btn_placeholder"
                title="<?php echo esc_attr($popup_config_btn_title); ?>"
                data-modal-target="<?php echo esc_attr($popup_modal_id); ?>"
                <?php echo ($popup_enabled === '1') ? '' : 'disabled'; ?>
            >
                <?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?>
            </button>
        </div>
        <div class="aipkit_form-help"><?php esc_html_e('Show a floating chat icon on pages.', 'gpt3-ai-content-generator'); ?></div>
    </div>

    <?php if (!empty($starters_addon_active)): ?>
    <!-- Conversation Starters Toggle (moved up) -->
    <div class="aipkit_feature_toggle_item aipkit_form-group">
        <div class="aipkit_feature_row">
            <label
                class="aipkit_checkbox-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_conversation_starters"
            >
                <input
                    type="checkbox"
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_conversation_starters"
                    name="enable_conversation_starters"
                    class="aipkit_toggle_switch aipkit_starters_toggle_switch"
                    value="1"
                    <?php checked($enable_conversation_starters, '1'); ?>
                >
                <span><?php esc_html_e('Conversation Starters', 'gpt3-ai-content-generator'); ?></span>
            </label>
            <?php $starters_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_starters_modal'; ?>
            <button
                type="button"
                class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn aipkit_starters_config_btn"
                data-feature="conversation_starters"
                data-modal-target="<?php echo esc_attr($starters_modal_id); ?>"
                title="<?php echo esc_attr( ($enable_conversation_starters === '1') ? __('Configure', 'gpt3-ai-content-generator') : __('Enable to configure', 'gpt3-ai-content-generator') ); ?>" <?php echo ($enable_conversation_starters === '1') ? '' : 'disabled'; ?>><?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?></button>
        </div>
        <div class="aipkit_form-help"><?php esc_html_e('Show clickable starter prompts for users.', 'gpt3-ai-content-generator'); ?></div>
    </div>
    <?php endif; ?>

    <!-- Download Checkbox -->
    <div class="aipkit_feature_toggle_item aipkit_form-group">
        <div class="aipkit_feature_row">
            <label
                class="aipkit_checkbox-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_download"
            >
                <input
                    type="checkbox"
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_download"
                    name="enable_download"
                    class="aipkit_toggle_switch"
                    value="1"
                    <?php checked($enable_download, '1'); ?>
                >
                <span><?php esc_html_e('Download', 'gpt3-ai-content-generator'); ?></span>
            </label>
        </div>
        <div class="aipkit_form-help"><?php esc_html_e('Allow users to download chat transcripts.', 'gpt3-ai-content-generator'); ?></div>
    </div>

    <!-- Copy Button Checkbox -->
    <div class="aipkit_feature_toggle_item aipkit_form-group">
        <div class="aipkit_feature_row">
            <label
                class="aipkit_checkbox-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_copy_button"
            >
                <input
                    type="checkbox"
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_copy_button"
                    name="enable_copy_button"
                    class="aipkit_toggle_switch"
                    value="1"
                    <?php checked($enable_copy_button, '1'); ?>
                >
                <span><?php esc_html_e('Copy', 'gpt3-ai-content-generator'); ?></span>
            </label>
        </div>
        <div class="aipkit_form-help"><?php esc_html_e('Show copy-to-clipboard on messages.', 'gpt3-ai-content-generator'); ?></div>
    </div>

    <?php
    // Theme select inside Features: dropdown + Configure button (Custom only)
    $saved_theme = isset($bot_settings['theme']) ? $bot_settings['theme'] : 'light';
    $available_themes = [
        'light'   => __('Light', 'gpt3-ai-content-generator'),
        'dark'    => __('Dark', 'gpt3-ai-content-generator'),
        'chatgpt' => __('ChatGPT', 'gpt3-ai-content-generator'),
        'custom'  => __('Custom', 'gpt3-ai-content-generator'),
    ];
    $custom_theme_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_custom_theme_modal';
    ?>
    <!-- Theme selection as a feature row (moved above Feedback) -->
    <div class="aipkit_feature_toggle_item aipkit_form-group">
      <div class="aipkit_feature_row">
        <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_theme_select">
          <?php esc_html_e('Theme', 'gpt3-ai-content-generator'); ?>
        </label>
        <div style="display:flex; align-items:center; gap:8px; width:100%; justify-content:flex-end;">
          <select
            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_theme_select"
            name="theme"
            class="aipkit_select-as-btn-small"
          >
            <?php foreach ($available_themes as $theme_key => $theme_name): ?>
              <option value="<?php echo esc_attr($theme_key); ?>" <?php selected($saved_theme, $theme_key); ?>><?php echo esc_html($theme_name); ?></option>
            <?php endforeach; ?>
          </select>
          <button
            type="button"
            class="aipkit_btn aipkit_btn-secondary aipkit_btn-small aipkit_feature_config_btn aipkit_open_custom_theme_modal_btn"
            data-modal-target="<?php echo esc_attr($custom_theme_modal_id); ?>"
            style="display: <?php echo ($saved_theme === 'custom') ? 'inline-flex' : 'none'; ?>;"
          >
            <?php esc_html_e('Configure', 'gpt3-ai-content-generator'); ?>
          </button>
        </div>
      </div>
      <div class="aipkit_form-help"><?php esc_html_e('Choose a theme for the chat UI.', 'gpt3-ai-content-generator'); ?></div>
    </div>

    <!-- Fullscreen Checkbox (moved down) -->
    <div class="aipkit_feature_toggle_item aipkit_form-group">
        <div class="aipkit_feature_row">
            <label
                class="aipkit_checkbox-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_fullscreen"
            >
                <input
                    type="checkbox"
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_fullscreen"
                    name="enable_fullscreen"
                    class="aipkit_toggle_switch"
                    value="1"
                    <?php checked($enable_fullscreen, '1'); ?>
                >
                <span><?php esc_html_e('Fullscreen', 'gpt3-ai-content-generator'); ?></span>
            </label>
        </div>
        <div class="aipkit_form-help"><?php esc_html_e('Open chat in a fullscreen overlay.', 'gpt3-ai-content-generator'); ?></div>
    </div>

    <!-- Feedback Checkbox (moved after Theme) -->
    <div class="aipkit_feature_toggle_item aipkit_form-group">
        <div class="aipkit_feature_row">
            <label
                class="aipkit_checkbox-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_feedback"
            >
                <input
                    type="checkbox"
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_feedback"
                    name="enable_feedback"
                    class="aipkit_toggle_switch"
                    value="1"
                    <?php checked($enable_feedback, '1'); ?>
                >
                <span><?php esc_html_e('Feedback', 'gpt3-ai-content-generator'); ?></span>
            </label>
        </div>
        <div class="aipkit_form-help"><?php esc_html_e('Collect thumbs up/down on responses.', 'gpt3-ai-content-generator'); ?></div>
    </div>

    <!-- Conversation Sidebar Checkbox -->
    <div
        class="aipkit_feature_toggle_item aipkit_form-group aipkit_sidebar_toggle_group"
        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_sidebar_group"
        title=""
        data-tooltip-disabled="<?php echo esc_attr($sidebar_disabled_tooltip); ?>"
    >
        <div class="aipkit_feature_row">
            <label
                class="aipkit_checkbox-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_conversation_sidebar"
            >
                <input
                    type="checkbox"
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_conversation_sidebar"
                    name="enable_conversation_sidebar"
                    class="aipkit_toggle_switch aipkit_sidebar_toggle_switch"
                    value="1"
                    <?php checked($enable_conversation_sidebar, '1'); ?>
                    <?php disabled($popup_enabled === '1'); ?>
                >
                <span><?php esc_html_e('Sidebar', 'gpt3-ai-content-generator'); ?></span>
            </label>
        </div>
        <div class="aipkit_form-help"><?php esc_html_e('Show the conversation list sidebar.', 'gpt3-ai-content-generator'); ?></div>
    </div>
</div>

<?php
// Render Popup Settings modal (hidden by default); moved from inline tab
$popup_modal_id = isset($popup_modal_id) ? $popup_modal_id : ('aipkit_bot_' . esc_attr($bot_id) . '_popup_settings_modal');
?>
<div
    id="<?php echo esc_attr($popup_modal_id); ?>"
    class="aipkit_chatbot_settings_modal"
    aria-hidden="true"
    style="display:none;"
>
    <div class="aipkit_modal_backdrop"></div>
    <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($popup_modal_id); ?>_title">
        <div class="aipkit_modal_header">
            <h4 id="<?php echo esc_attr($popup_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('Popup Settings', 'gpt3-ai-content-generator'); ?></h4>
            <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
        </div>
        <div class="aipkit_modal_body">
            <?php include __DIR__ . '/popup-settings.php'; ?>
        </div>
        <div class="aipkit_modal_footer">
            <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>

<?php if (!empty($starters_addon_active)): ?>
<?php $starters_modal_id = 'aipkit_bot_' . esc_attr($bot_id) . '_starters_modal'; ?>
<div
    id="<?php echo esc_attr($starters_modal_id); ?>"
    class="aipkit_chatbot_settings_modal"
    aria-hidden="true"
    style="display:none;"
>
    <div class="aipkit_modal_backdrop"></div>
    <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($starters_modal_id); ?>_title">
        <div class="aipkit_modal_header">
            <h4 id="<?php echo esc_attr($starters_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('Conversation Starters', 'gpt3-ai-content-generator'); ?></h4>
            <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
        </div>
        <div class="aipkit_modal_body">
            <?php include __DIR__ . '/conversation-starters.php'; ?>
        </div>
        <div class="aipkit_modal_footer">
            <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Custom Theme modal markup (moved from Theme section) -->
<div
  id="<?php echo esc_attr($custom_theme_modal_id); ?>"
  class="aipkit_chatbot_settings_modal"
  aria-hidden="true"
  style="display:none;"
>
  <div class="aipkit_modal_backdrop"></div>
  <div class="aipkit_modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($custom_theme_modal_id); ?>_title">
    <div class="aipkit_modal_header">
      <h4 id="<?php echo esc_attr($custom_theme_modal_id); ?>_title" class="aipkit_modal_title"><?php esc_html_e('Customize Theme', 'gpt3-ai-content-generator'); ?></h4>
      <button type="button" class="aipkit_modal_close" aria-label="<?php esc_attr_e('Close', 'gpt3-ai-content-generator'); ?>">&times;</button>
    </div>
    <div class="aipkit_modal_body">
      <?php include __DIR__ . '/custom-theme-settings.php'; ?>
    </div>
    <div class="aipkit_modal_footer">
      <span id="aipkit_reset_theme_status_<?php echo esc_attr($bot_id); ?>" class="aipkit_form-help aipkit_reset_theme_status"></span>
      <button
        type="button"
        id="aipkit_reset_custom_theme_btn_<?php echo esc_attr($bot_id); ?>"
        class="aipkit_btn aipkit_btn-secondary aipkit_reset_custom_theme_btn aipkit_btn-icon"
        data-bot-id="<?php echo esc_attr($bot_id); ?>"
        title="<?php esc_attr_e('Reset all custom theme settings to their defaults.', 'gpt3-ai-content-generator'); ?>"
      >
        <span class="dashicons dashicons-image-rotate"></span>
        <span class="aipkit_btn-text"><?php esc_html_e('Reset', 'gpt3-ai-content-generator'); ?></span>
      </button>
      <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_modal_close_btn"><?php esc_html_e('Close', 'gpt3-ai-content-generator'); ?></button>
    </div>
  </div>
</div>

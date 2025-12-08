<?php
/**
 * Partial: Chatbot Popup Settings (for modal body)
 *
 * Extracted from accordion-popup.php to be reusable inside a modal.
 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Chat\Storage\BotSettingsManager;
use WPAICG\Chat\Utils\AIPKit_SVG_Icons;

// Variables available from parent script: $bot_id, $bot_settings
$popup_position     = isset($bot_settings['popup_position']) ? $bot_settings['popup_position'] : 'bottom-right';
$popup_delay        = isset($bot_settings['popup_delay']) ? absint($bot_settings['popup_delay']) : BotSettingsManager::DEFAULT_POPUP_DELAY;
$site_wide_enabled  = isset($bot_settings['site_wide_enabled']) ? $bot_settings['site_wide_enabled'] : '0';

// Icon settings
$popup_icon_type  = $bot_settings['popup_icon_type']  ?? BotSettingsManager::DEFAULT_POPUP_ICON_TYPE;
$popup_icon_style = $bot_settings['popup_icon_style'] ?? BotSettingsManager::DEFAULT_POPUP_ICON_STYLE;
$popup_icon_value = $bot_settings['popup_icon_value'] ?? BotSettingsManager::DEFAULT_POPUP_ICON_VALUE;
// Icon size option
$popup_icon_size  = $bot_settings['popup_icon_size'] ?? BotSettingsManager::DEFAULT_POPUP_ICON_SIZE;

// Default SVG icons
$default_icons = [];
if (class_exists(AIPKit_SVG_Icons::class)) {
    $default_icons = [
        'chat-bubble'   => AIPKit_SVG_Icons::get_chat_bubble_svg(),
        'plus'          => AIPKit_SVG_Icons::get_plus_svg(),
        'question-mark' => AIPKit_SVG_Icons::get_question_mark_svg(),
    ];
}

// Popup Hint settings
$popup_label_enabled           = isset($bot_settings['popup_label_enabled']) ? $bot_settings['popup_label_enabled'] : '0';
$popup_label_text              = isset($bot_settings['popup_label_text']) ? $bot_settings['popup_label_text'] : '';
$popup_label_mode              = isset($bot_settings['popup_label_mode']) ? $bot_settings['popup_label_mode'] : 'on_delay';
$popup_label_delay_seconds     = isset($bot_settings['popup_label_delay_seconds']) ? absint($bot_settings['popup_label_delay_seconds']) : 2;
$popup_label_auto_hide_seconds = isset($bot_settings['popup_label_auto_hide_seconds']) ? absint($bot_settings['popup_label_auto_hide_seconds']) : 0;
$popup_label_dismissible       = isset($bot_settings['popup_label_dismissible']) ? $bot_settings['popup_label_dismissible'] : '1';
$popup_label_frequency         = isset($bot_settings['popup_label_frequency']) ? $bot_settings['popup_label_frequency'] : 'once_per_visitor';
$popup_label_show_on_mobile    = isset($bot_settings['popup_label_show_on_mobile']) ? $bot_settings['popup_label_show_on_mobile'] : '1';
$popup_label_show_on_desktop   = isset($bot_settings['popup_label_show_on_desktop']) ? $bot_settings['popup_label_show_on_desktop'] : '1';
$popup_label_version           = isset($bot_settings['popup_label_version']) ? $bot_settings['popup_label_version'] : '';
// Popup hint size (UI option)
$popup_label_size              = isset($bot_settings['popup_label_size']) ? $bot_settings['popup_label_size'] : 'medium';
?>

<div class="aipkit_settings_sections">

  <!-- Section: Basic Settings -->
  <section class="aipkit_settings_section" data-section="basic">
    <div class="aipkit_settings_section-header">
      <h5 class="aipkit_settings_section-title"><?php esc_html_e('Basic Settings', 'gpt3-ai-content-generator'); ?></h5>
    </div>
    <div class="aipkit_settings_section-body">
      <div class="aipkit_settings_grid">
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_position"><?php esc_html_e('Popup Position', 'gpt3-ai-content-generator'); ?></label>
          <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_position" name="popup_position" class="aipkit_form-input">
            <option value="bottom-right" <?php selected($popup_position, 'bottom-right'); ?>><?php esc_html_e('Bottom Right', 'gpt3-ai-content-generator'); ?></option>
            <option value="bottom-left" <?php selected($popup_position, 'bottom-left'); ?>><?php esc_html_e('Bottom Left', 'gpt3-ai-content-generator'); ?></option>
            <option value="top-right" <?php selected($popup_position, 'top-right'); ?>><?php esc_html_e('Top Right', 'gpt3-ai-content-generator'); ?></option>
            <option value="top-left" <?php selected($popup_position, 'top-left'); ?>><?php esc_html_e('Top Left', 'gpt3-ai-content-generator'); ?></option>
          </select>
          <div class="aipkit_form-help"><?php esc_html_e('Select the screen corner for the popup.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_delay"><?php esc_html_e('Auto-open Delay (sec)', 'gpt3-ai-content-generator'); ?></label>
          <input type="number" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_delay" name="popup_delay" class="aipkit_form-input aipkit_input-number-compact" value="<?php echo esc_attr($popup_delay); ?>" min="0" step="1" />
          <div class="aipkit_form-help"><?php esc_html_e('0 disables auto-open.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <div class="aipkit_form-group aipkit_site_wide_group aipkit_form-group--inline">
          <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_site_wide_enabled">
            <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_site_wide_enabled" name="site_wide_enabled" class="aipkit_toggle_switch" value="1" <?php checked($site_wide_enabled, '1'); ?>>
            <?php esc_html_e('Site-wide', 'gpt3-ai-content-generator'); ?>
          </label>
          <div class="aipkit_form-help"><?php esc_html_e('Show the popup on all site pages.', 'gpt3-ai-content-generator'); ?></div>
        </div>
      </div>
      <script>
        (function(){
          try {
            var container = document.currentScript ? document.currentScript.closest('.aipkit_settings_section-body') : null;
            if (!container) container = document.querySelector('[data-section="hint"] .aipkit_settings_section-body');
            var toggle = container ? container.querySelector('#aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_enabled') : null;
            if (!toggle) return;
            function updateExtras(){
              var extras = container.querySelectorAll('.aipkit_popup_hint_extra');
              var show = !!toggle.checked;
              extras.forEach(function(el){ el.style.display = show ? '' : 'none'; });
            }
            toggle.addEventListener('change', updateExtras);
          } catch(e) { /* noop */ }
        })();
      </script>
    </div>
  </section>

  <!-- Section: Icon Settings -->
  <section class="aipkit_settings_section" data-section="icon">
    <div class="aipkit_settings_section-header">
      <h5 class="aipkit_settings_section-title"><?php esc_html_e('Icon Settings', 'gpt3-ai-content-generator'); ?></h5>
    </div>
    <div class="aipkit_settings_section-body">
      <div class="aipkit_settings_grid aipkit_settings_grid--5">
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_style"><?php esc_html_e('Icon Style', 'gpt3-ai-content-generator'); ?></label>
          <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_style" name="popup_icon_style" class="aipkit_form-input">
            <option value="circle" <?php selected($popup_icon_style, 'circle'); ?>><?php esc_html_e('Circle', 'gpt3-ai-content-generator'); ?></option>
            <option value="square" <?php selected($popup_icon_style, 'square'); ?>><?php esc_html_e('Square', 'gpt3-ai-content-generator'); ?></option>
            <option value="none" <?php selected($popup_icon_style, 'none'); ?>><?php esc_html_e('None', 'gpt3-ai-content-generator'); ?></option>
          </select>
          <!-- Hidden radios for backward-compat (JS saver reads radios) -->
          <div style="display:none;">
            <input type="radio" name="popup_icon_style" value="circle" <?php checked($popup_icon_style, 'circle'); ?> />
            <input type="radio" name="popup_icon_style" value="square" <?php checked($popup_icon_style, 'square'); ?> />
            <input type="radio" name="popup_icon_style" value="none" <?php checked($popup_icon_style, 'none'); ?> />
          </div>
          <div class="aipkit_form-help"><?php esc_html_e('Choose the shape for the popup icon.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <!-- Icon Size -->
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_size"><?php esc_html_e('Icon Size', 'gpt3-ai-content-generator'); ?></label>
          <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_size" name="popup_icon_size" class="aipkit_form-input">
            <option value="small" <?php selected($popup_icon_size, 'small'); ?>><?php esc_html_e('Small', 'gpt3-ai-content-generator'); ?></option>
            <option value="medium" <?php selected($popup_icon_size, 'medium'); ?>><?php esc_html_e('Medium (default)', 'gpt3-ai-content-generator'); ?></option>
            <option value="large" <?php selected($popup_icon_size, 'large'); ?>><?php esc_html_e('Large', 'gpt3-ai-content-generator'); ?></option>
            <option value="xlarge" <?php selected($popup_icon_size, 'xlarge'); ?>><?php esc_html_e('Extra Large', 'gpt3-ai-content-generator'); ?></option>
          </select>
          <div class="aipkit_form-help"><?php esc_html_e('Controls overall trigger button size.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <!-- Icon Source -->
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_type"><?php esc_html_e('Icon Source', 'gpt3-ai-content-generator'); ?></label>
          <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_type" name="popup_icon_type" class="aipkit_form-input aipkit_popup_icon_type_select">
            <option value="default" <?php selected($popup_icon_type, 'default'); ?>><?php esc_html_e('Default Icons', 'gpt3-ai-content-generator'); ?></option>
            <option value="custom" <?php selected($popup_icon_type, 'custom'); ?>><?php esc_html_e('Custom URL', 'gpt3-ai-content-generator'); ?></option>
          </select>
          <!-- Hidden radios for backward-compat (JS saver/toggles read radios) -->
          <div style="display:none;">
            <input type="radio" name="popup_icon_type" value="default" <?php checked($popup_icon_type, 'default'); ?> />
            <input type="radio" name="popup_icon_type" value="custom" <?php checked($popup_icon_type, 'custom'); ?> />
          </div>
          <div class="aipkit_form-help"><?php esc_html_e('Use your own icon.', 'gpt3-ai-content-generator'); ?></div>
        </div>
        <!-- Default Icon Selector (grid item) -->
        <div class="aipkit_popup_icon_default_selector_container" style="display: <?php echo $popup_icon_type === 'default' ? 'block' : 'none'; ?>;">
        <label class="aipkit_form-label"><?php esc_html_e('Choose Icon', 'gpt3-ai-content-generator'); ?></label>
        <div class="aipkit_popup_icon_default_selector">
          <?php foreach ($default_icons as $icon_key => $svg_html) :
            $icon_checked = ($popup_icon_type === 'default' && $popup_icon_value === $icon_key);
            $radio_id = 'aipkit_bot_' . esc_attr($bot_id) . '_popup_icon_' . esc_attr($icon_key);
          ?>
            <label class="aipkit_option_card" for="<?php echo $radio_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" title="<?php echo esc_attr(ucfirst(str_replace('-', ' ', $icon_key))); ?>">
              <input type="radio" id="<?php echo $radio_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" name="popup_icon_default" value="<?php echo esc_attr($icon_key); ?>" <?php checked($icon_checked); ?> />
              <?php echo $svg_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="aipkit_form-help"><?php esc_html_e('Select a default icon for the popup.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <!-- Custom Icon URL Input (grid item) -->
        <div class="aipkit_popup_icon_custom_input_container aipkit_form-group" style="display: <?php echo $popup_icon_type === 'custom' ? 'block' : 'none'; ?>;">
          <div class="aipkit_popup_icon_custom_input" style="display:block;">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_custom_url">
            <?php esc_html_e('Icon URL', 'gpt3-ai-content-generator'); ?>
          </label>
          <input type="url" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_custom_url" name="popup_icon_custom_url" class="aipkit_form-input" value="<?php echo ($popup_icon_type === 'custom') ? esc_url($popup_icon_value) : ''; ?>" placeholder="<?php esc_attr_e('Enter full URL (e.g., https://.../icon.png)', 'gpt3-ai-content-generator'); ?>" />
          </div>
          <div class="aipkit_form-help"><?php esc_html_e('Ideal ~32x32. PNG or SVG.', 'gpt3-ai-content-generator'); ?></div>
        </div>

      </div>
      <script>
        (function(){
          try {
            var select = document.getElementById('aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_type');
            if (!select) return;
            var modal = select.closest('.aipkit_chatbot_settings_modal') || document;
            function updateIconSourceVisibility(){
              if (typeof window.aipkit_togglePopupIconValueInputs === 'function') {
                window.aipkit_togglePopupIconValueInputs(modal, select.value);
              } else {
                // Fallback: simple show/hide if helper not present
                var defC = modal.querySelector('.aipkit_popup_icon_default_selector_container');
                var cusC = modal.querySelector('.aipkit_popup_icon_custom_input_container');
                if (defC) defC.style.display = (select.value === 'default') ? 'block' : 'none';
                if (cusC) cusC.style.display = (select.value === 'custom') ? 'block' : 'none';
              }
              // Sync hidden radios for backward-compat
              try {
                var radios = modal.querySelectorAll('input[name="popup_icon_type"]');
                radios.forEach(function(r){ r.checked = (r.value === select.value); });
              } catch (e) { /* noop */ }
            }
            select.addEventListener('change', updateIconSourceVisibility);
            // Also sync hidden icon style radios when its select changes
            var styleSelect = document.getElementById('aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_icon_style');
            if (styleSelect) {
              styleSelect.addEventListener('change', function(){
                try {
                  var rs = modal.querySelectorAll('input[name="popup_icon_style"]');
                  rs.forEach(function(r){ r.checked = (r.value === styleSelect.value); });
                } catch (e) { /* noop */ }
              });
            }
          } catch(e) { /* noop */ }
        })();
      </script>
    </div>
  </section>

  <!-- Section: Popup Hint -->
  <section class="aipkit_settings_section" data-section="hint">
    <div class="aipkit_settings_section-header">
      <h5 class="aipkit_settings_section-title"><?php esc_html_e('Popup Hint', 'gpt3-ai-content-generator'); ?></h5>
    </div>
    <div class="aipkit_settings_section-body">
      <div class="aipkit_form-group">
        <div class="aipkit_checkbox_row aipkit_checkbox_row--with-helps">
          <div class="aipkit_checkbox_item">
            <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_enabled">
              <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_enabled" name="popup_label_enabled" class="aipkit_toggle_switch aipkit_popup_hint_toggle_switch" value="1" <?php checked($popup_label_enabled, '1'); ?>>
              <?php esc_html_e('Enable Popup Hint', 'gpt3-ai-content-generator'); ?>
            </label>
            <div class="aipkit_form-help"><?php esc_html_e('Displays a short hint above the floating icon.', 'gpt3-ai-content-generator'); ?></div>
          </div>
          <div class="aipkit_checkbox_item aipkit_popup_hint_extra" style="display: <?php echo $popup_label_enabled === '1' ? 'flex' : 'none'; ?>;">
            <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_dismissible">
              <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_dismissible" name="popup_label_dismissible" class="aipkit_toggle_switch" value="1" <?php checked($popup_label_dismissible, '1'); ?>>
              <?php esc_html_e('Dismissible', 'gpt3-ai-content-generator'); ?>
            </label>
            <div class="aipkit_form-help"><?php esc_html_e('Users can manually hide the hint.', 'gpt3-ai-content-generator'); ?></div>
          </div>
          <div class="aipkit_checkbox_item aipkit_popup_hint_extra" style="display: <?php echo $popup_label_enabled === '1' ? 'flex' : 'none'; ?>;">
            <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_show_on_desktop">
              <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_show_on_desktop" name="popup_label_show_on_desktop" class="aipkit_toggle_switch" value="1" <?php checked($popup_label_show_on_desktop, '1'); ?>>
              <?php esc_html_e('Show on Desktop', 'gpt3-ai-content-generator'); ?>
            </label>
            <div class="aipkit_form-help"><?php esc_html_e('Display the hint on desktop screens.', 'gpt3-ai-content-generator'); ?></div>
          </div>
          <div class="aipkit_checkbox_item aipkit_popup_hint_extra" style="display: <?php echo $popup_label_enabled === '1' ? 'flex' : 'none'; ?>;">
            <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_show_on_mobile">
              <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_show_on_mobile" name="popup_label_show_on_mobile" class="aipkit_toggle_switch" value="1" <?php checked($popup_label_show_on_mobile, '1'); ?>>
              <?php esc_html_e('Show on Mobile', 'gpt3-ai-content-generator'); ?>
            </label>
            <div class="aipkit_form-help"><?php esc_html_e('Display the hint on mobile screens.', 'gpt3-ai-content-generator'); ?></div>
          </div>
        </div>
      </div>

      <div class="aipkit_popup_hint_conditional_row" style="display: <?php echo $popup_label_enabled === '1' ? 'block' : 'none'; ?>;">
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_text"><?php esc_html_e('Hint Text', 'gpt3-ai-content-generator'); ?></label>
          <input type="text" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_text" name="popup_label_text" class="aipkit_form-input" value="<?php echo esc_attr($popup_label_text); ?>" maxlength="120" placeholder="<?php esc_attr_e('e.g., Need help? Ask me!', 'gpt3-ai-content-generator'); ?>">
          <div class="aipkit_form-help"><?php esc_html_e('Plain text only; keep it short (1–60 chars).', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <div class="aipkit_settings_grid aipkit_settings_grid--6">
          <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_mode"><?php esc_html_e('Show Mode', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_mode" name="popup_label_mode" class="aipkit_form-input">
              <option value="on_delay" <?php selected($popup_label_mode, 'on_delay'); ?>><?php esc_html_e('On delay (once)', 'gpt3-ai-content-generator'); ?></option>
              <option value="until_open" <?php selected($popup_label_mode, 'until_open'); ?>><?php esc_html_e('Until chat opened', 'gpt3-ai-content-generator'); ?></option>
              <option value="until_dismissed" <?php selected($popup_label_mode, 'until_dismissed'); ?>><?php esc_html_e('Until dismissed', 'gpt3-ai-content-generator'); ?></option>
              <option value="always" <?php selected($popup_label_mode, 'always'); ?>><?php esc_html_e('Always (re-shows after close)', 'gpt3-ai-content-generator'); ?></option>
            </select>
            <div class="aipkit_form-help">
              <?php esc_html_e('Choose when the hint appears and persists.', 'gpt3-ai-content-generator'); ?>
            </div>
          </div>
          <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_size"><?php esc_html_e('Hint Size', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_size" name="popup_label_size" class="aipkit_form-input">
              <option value="small" <?php selected($popup_label_size, 'small'); ?>><?php esc_html_e('Small', 'gpt3-ai-content-generator'); ?></option>
              <option value="medium" <?php selected($popup_label_size, 'medium'); ?>><?php esc_html_e('Medium (default)', 'gpt3-ai-content-generator'); ?></option>
              <option value="large" <?php selected($popup_label_size, 'large'); ?>><?php esc_html_e('Large', 'gpt3-ai-content-generator'); ?></option>
              <option value="xlarge" <?php selected($popup_label_size, 'xlarge'); ?>><?php esc_html_e('Extra Large', 'gpt3-ai-content-generator'); ?></option>
            </select>
            <div class="aipkit_form-help"><?php esc_html_e('Controls hint font size and padding.', 'gpt3-ai-content-generator'); ?></div>
          </div>
          <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_delay_seconds"><?php esc_html_e('Delay (sec)', 'gpt3-ai-content-generator'); ?></label>
            <input type="number" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_delay_seconds" name="popup_label_delay_seconds" class="aipkit_form-input aipkit_input-number-compact" min="0" step="1" value="<?php echo esc_attr($popup_label_delay_seconds); ?>">
            <div class="aipkit_form-help">
              <?php esc_html_e('Time to wait before showing. 0 = immediate.', 'gpt3-ai-content-generator'); ?>
            </div>
          </div>
          <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_auto_hide_seconds"><?php esc_html_e('Auto-hide (sec)', 'gpt3-ai-content-generator'); ?></label>
            <input type="number" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_auto_hide_seconds" name="popup_label_auto_hide_seconds" class="aipkit_form-input aipkit_input-number-compact" min="0" step="1" value="<?php echo esc_attr($popup_label_auto_hide_seconds); ?>">
            <div class="aipkit_form-help"><?php esc_html_e('0 disables auto-hide.', 'gpt3-ai-content-generator'); ?></div>
          </div>
          <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_frequency"><?php esc_html_e('Frequency', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_frequency" name="popup_label_frequency" class="aipkit_form-input">
              <option value="once_per_visitor" <?php selected($popup_label_frequency, 'once_per_visitor'); ?>><?php esc_html_e('Once per visitor', 'gpt3-ai-content-generator'); ?></option>
              <option value="once_per_session" <?php selected($popup_label_frequency, 'once_per_session'); ?>><?php esc_html_e('Once per session', 'gpt3-ai-content-generator'); ?></option>
              <option value="always" <?php selected($popup_label_frequency, 'always'); ?>><?php esc_html_e('Always', 'gpt3-ai-content-generator'); ?></option>
            </select>
            <div class="aipkit_form-help"><?php esc_html_e('Controls persistence after seen/dismissed.', 'gpt3-ai-content-generator'); ?></div>
          </div>
          <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_version"><?php esc_html_e('Version', 'gpt3-ai-content-generator'); ?></label>
            <input type="text" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_popup_label_version" name="popup_label_version" class="aipkit_form-input" value="<?php echo esc_attr($popup_label_version); ?>" placeholder="v1">
            <div class="aipkit_form-help"><?php esc_html_e('Change to re-show the hint for everyone.', 'gpt3-ai-content-generator'); ?></div>
          </div>
        </div>

        
      </div> <!-- /.aipkit_popup_hint_conditional_row -->
    </div>
  </section>

</div> <!-- /.aipkit_settings_sections -->

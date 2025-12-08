<?php
/**
 * Partial: Chatbot Image Settings (Modal Body)
 * Extracted from accordion-images.php to be reusable inside a modal.
 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Chat\Storage\BotSettingsManager;

// Variables available: $bot_id, $bot_settings, $replicate_model_list
$image_triggers = $bot_settings['image_triggers'] ?? BotSettingsManager::DEFAULT_IMAGE_TRIGGERS;
$chat_image_model_id = $bot_settings['chat_image_model_id'] ?? BotSettingsManager::DEFAULT_CHAT_IMAGE_MODEL_ID;

$replicate_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('replicate');
$available_image_models = [
    'OpenAI' => [
        ['id' => 'gpt-image-1', 'name' => 'GPT Image 1'],
        ['id' => 'dall-e-3',    'name' => 'DALL-E 3'],
        ['id' => 'dall-e-2',    'name' => 'DALL-E 2'],
    ],
    'Azure'  => \WPAICG\AIPKit_Providers::get_azure_image_models(),
    'Google' => \WPAICG\AIPKit_Providers::get_google_image_models(),
];
if (isset($replicate_model_list) && is_array($replicate_model_list) && !empty($replicate_model_list)) {
    $available_image_models['Replicate'] = $replicate_model_list;
}
?>

<div class="aipkit_settings_sections">
  <section class="aipkit_settings_section" data-section="image-settings">
    <div class="aipkit_settings_section-header">
      <h5 class="aipkit_settings_section-title"><?php esc_html_e('Image Generation', 'gpt3-ai-content-generator'); ?></h5>
    </div>
    <div class="aipkit_settings_section-body">
      <div class="aipkit_settings_grid">
        <!-- Image Generation Model -->
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_chat_image_model_id_modal">
            <?php esc_html_e('Image Generation Model', 'gpt3-ai-content-generator'); ?>
          </label>
          <select
            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_chat_image_model_id_modal"
            name="chat_image_model_id"
            class="aipkit_form-input"
          >
            <?php
            $found_current_model = false;
            foreach ($available_image_models as $provider_group => $models) {
                $is_disabled_group = ($provider_group === 'Replicate' && !$replicate_addon_active);
                echo '<optgroup label="' . esc_attr($provider_group) . '">';
                foreach ($models as $model) {
                    $is_selected = selected($chat_image_model_id, $model['id'], false);
                    if (strpos($is_selected, 'selected') !== false) {
                        $found_current_model = true;
                    }
                    $disabled_attr = $is_disabled_group ? 'disabled' : '';
                    $disabled_text = $is_disabled_group ? ' (' . esc_html__('Addon Disabled', 'gpt3-ai-content-generator') . ')' : '';
                    echo '<option value="' . esc_attr($model['id']) . '" ' . $is_selected . ' ' . esc_attr($disabled_attr) . '>' . esc_html($model['name']) . $disabled_text . '</option>';
                }
                echo '</optgroup>';
            }
            if (!$found_current_model && !empty($chat_image_model_id)) {
                echo '<option value="' . esc_attr($chat_image_model_id) . '" selected="selected">' . esc_html($chat_image_model_id) . ' (Manual/Current)</option>';
            }
            ?>
          </select>
          <div class="aipkit_form-help"><?php esc_html_e('Select the image model for this bot.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <!-- Image Generation Triggers -->
        <div class="aipkit_form-group">
          <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_image_triggers_modal">
            <?php esc_html_e('Image Generation Triggers', 'gpt3-ai-content-generator'); ?>
          </label>
          <input
            type="text"
            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_image_triggers_modal"
            name="image_triggers"
            class="aipkit_form-input"
            value="<?php echo esc_attr($image_triggers); ?>"
            placeholder="/image, /generate, /img"
          />
          <div class="aipkit_form-help"><?php esc_html_e('Comma-separated commands (e.g., /image, /generate) to trigger.', 'gpt3-ai-content-generator'); ?></div>
        </div>
      </div>
    </div>
  </section>
</div>


<?php
/**
 * Partial: Chatbot Image Settings Accordion Content
 * Allows defining image generation triggers and selecting the model for this specific chatbot.
 * UPDATED: Added OpenAI AND Google model selection dropdown.
 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Chat\Storage\BotSettingsManager; // Use for constants

// Variables available from parent script (chatbot/index.php):
// $bot_id, $bot_settings, $replicate_model_list
$image_triggers = $bot_settings['image_triggers'] ?? BotSettingsManager::DEFAULT_IMAGE_TRIGGERS;
$chat_image_model_id = $bot_settings['chat_image_model_id'] ?? BotSettingsManager::DEFAULT_CHAT_IMAGE_MODEL_ID;

// Define available Image models (OpenAI, Azure and Google)
$replicate_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('replicate');
$available_image_models = [
    'OpenAI' => [
        ['id' => 'gpt-image-1', 'name' => 'GPT Image 1'],
        ['id' => 'dall-e-3',    'name' => 'DALL-E 3'],
        ['id' => 'dall-e-2',    'name' => 'DALL-E 2'],
    ],
    'Azure' => \WPAICG\AIPKit_Providers::get_azure_image_models(),
    'Google' => \WPAICG\AIPKit_Providers::get_google_image_models(),
];
if (isset($replicate_model_list) && is_array($replicate_model_list) && !empty($replicate_model_list)) {
    $available_image_models['Replicate'] = $replicate_model_list;
}

?>
<div class="aipkit_accordion" data-section="images">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Images', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_settings_subsection">
            <div class="aipkit_settings_subsection-header">
                <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Image Generation', 'gpt3-ai-content-generator'); ?></h5>
            </div>
            <div class="aipkit_settings_subsection-body">
                <div class="aipkit_form-row">
                    <!-- Model Selection for Chatbot Image Generation -->
                    <div class="aipkit_form-col" style="flex-grow: 3;">
                        <div class="aipkit_form-group">
                    <label
                        class="aipkit_form-label"
                        for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_chat_image_model_id"
                    >
                        <?php esc_html_e('Image Generation Model', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_chat_image_model_id"
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
                                // The following line is safe. $is_selected is from WP's `selected()` and $disabled_text is pre-escaped.
                                echo '<option value="' . esc_attr($model['id']) . '" ' . $is_selected . ' ' . esc_attr($disabled_attr) . '>' . esc_html($model['name']) . $disabled_text . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: $is_selected is output of selected(), $disabled_text is pre-escaped.
                            }
                            echo '</optgroup>';
                        }
                        // If the saved model is not in the list (e.g., custom or old value), add it as selected
                        if (!$found_current_model && !empty($chat_image_model_id)) {
                            echo '<option value="' . esc_attr($chat_image_model_id) . '" selected="selected">' . esc_html($chat_image_model_id) . ' (Manual/Current)</option>';
                        }
                        ?>
                    </select>
                    <div class="aipkit_form-help">
                        <?php esc_html_e('Select the image model for this bot.', 'gpt3-ai-content-generator'); ?>
                    </div>
                </div>
                </div>
                <!-- Image Generation Triggers -->
                <div class="aipkit_form-col" style="flex-grow: 7;">
                    <div class="aipkit_form-group">
                        <label
                            class="aipkit_form-label"
                            for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_image_triggers"
                        >
                            <?php esc_html_e('Image Generation Triggers', 'gpt3-ai-content-generator'); ?>
                        </label>
                        <input
                            type="text"
                            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_image_triggers"
                            name="image_triggers"
                            class="aipkit_form-input"
                            value="<?php echo esc_attr($image_triggers); ?>"
                            placeholder="/image, /generate, /img"
                        />
                        <div class="aipkit_form-help">
                            <?php esc_html_e('Comma-separated commands (e.g., /image, /generate) to trigger.', 'gpt3-ai-content-generator'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

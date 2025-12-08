<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/chatbot/partials/ai-config/parameters.php
// Status: MODIFIED

/**
 * Partial: AI Config - AI Parameter Sliders
 */
if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\Chat\Storage\BotSettingsManager; // Use new class for constants

// Variables required from parent script (accordion-ai-config.php):
// $bot_id, $bot_settings, $openai_conversation_state_enabled_val, $current_provider_for_this_bot
// $openai_web_search_enabled_val, $openai_web_search_context_size_val, $openai_web_search_loc_type_val, etc.
// $google_search_grounding_enabled_val, $google_grounding_mode_val, etc.
// $reasoning_effort_val (NEW)
// --- NEW: $enable_image_upload variable passed from accordion-ai-config.php
$enable_image_upload = isset($bot_settings['enable_image_upload'])
                        ? $bot_settings['enable_image_upload']
                        : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_IMAGE_UPLOAD;
// --- NEW: $enable_voice_input variable passed from accordion-ai-config.php
$enable_voice_input = isset($bot_settings['enable_voice_input'])
                      ? $bot_settings['enable_voice_input']
                      : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT;
// --- END NEW ---

// Extract AI param values from bot_settings with defaults from BotSettingsManager
$saved_temperature = isset($bot_settings['temperature'])
                     ? floatval($bot_settings['temperature'])
                     : BotSettingsManager::DEFAULT_TEMPERATURE;
$saved_max_tokens = isset($bot_settings['max_completion_tokens'])
                    ? absint($bot_settings['max_completion_tokens'])
                    : BotSettingsManager::DEFAULT_MAX_COMPLETION_TOKENS;
$saved_max_messages = isset($bot_settings['max_messages'])
                      ? absint($bot_settings['max_messages'])
                      : BotSettingsManager::DEFAULT_MAX_MESSAGES;

// Ensure they are clamped
$saved_temperature = max(0.0, min($saved_temperature, 2.0));
$saved_max_tokens = max(1, min($saved_max_tokens, 128000));
$saved_max_messages = max(1, min($saved_max_messages, 1024));

?>
    <div class="aipkit_settings_sections">
        <section class="aipkit_settings_section" data-section="ai-params-core">
            <div class="aipkit_settings_section-header">
                <h5 class="aipkit_settings_section-title"><?php esc_html_e('Parameters', 'gpt3-ai-content-generator'); ?></h5>
            </div>
            <div class="aipkit_settings_section-body">
                <!-- Row for Temperature, Max Tokens -->
                <div class="aipkit_form-row">
        <!-- Temperature Column -->
        <div class="aipkit_form-group aipkit_form-col">
            <label
                class="aipkit_form-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_temperature"
            >
                <?php esc_html_e('Temperature', 'gpt3-ai-content-generator'); ?>
            </label>
            <div class="aipkit_slider_wrapper">
                <input
                    type="range"
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_temperature"
                    name="temperature"
                    class="aipkit_form-input aipkit_range_slider"
                    min="0" max="2" step="0.1"
                    value="<?php echo esc_attr($saved_temperature); ?>"
                />
                <span
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_temperature_value"
                    class="aipkit_slider_value"
                >
                    <?php echo esc_html($saved_temperature); ?>
                </span>
            </div>
            <div class="aipkit_form-help"><?php esc_html_e('Higher = more creative; lower = more focused.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        <!-- Max Completion Tokens Column -->
        <div class="aipkit_form-group aipkit_form-col">
            <label
                class="aipkit_form-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_max_completion_tokens"
            >
                 <?php esc_html_e('Max Token', 'gpt3-ai-content-generator'); ?>
            </label>
             <div class="aipkit_slider_wrapper">
                <input
                    type="range"
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_max_completion_tokens"
                    name="max_completion_tokens"
                    class="aipkit_form-input aipkit_range_slider"
                    min="1" max="128000" step="1"
                    value="<?php echo esc_attr($saved_max_tokens); ?>"
                />
                <span
                    id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_max_completion_tokens_value"
                    class="aipkit_slider_value"
                >
                    <?php echo esc_html($saved_max_tokens); ?>
                </span>
            </div>
            <div class="aipkit_form-help"><?php esc_html_e('Upper limit on tokens generated in one response.', 'gpt3-ai-content-generator'); ?></div>
        </div>

        
                </div><!-- /AI Params Row -->
            </div>
        </section>

        <!-- Reasoning moved to Conversations modal -->
    </div>

    <!-- Provider-specific Web/Grounding settings moved to dedicated modal -->

    <!-- Feature toggles moved to Features subsection -->

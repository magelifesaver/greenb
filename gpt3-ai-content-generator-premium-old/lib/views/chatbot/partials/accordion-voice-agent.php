<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/views/chatbot/partials/accordion-voice-agent.php
// Status: MODIFIED

/**
 * Partial: Chatbot Realtime Voice Agent Settings Accordion Content (Pro Feature)
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use WPAICG\Chat\Storage\BotSettingsManager;

// Variables available from parent script (chatbot-settings-pane.php):
// $bot_id, $bot_settings

$popup_enabled = isset($bot_settings['popup_enabled']) ? $bot_settings['popup_enabled'] : '0';
$enable_realtime_voice = $bot_settings['enable_realtime_voice'] ?? BotSettingsManager::DEFAULT_ENABLE_REALTIME_VOICE;
$direct_voice_mode = $bot_settings['direct_voice_mode'] ?? BotSettingsManager::DEFAULT_DIRECT_VOICE_MODE;

$realtime_model = $bot_settings['realtime_model'] ?? BotSettingsManager::DEFAULT_REALTIME_MODEL;
$realtime_voice = $bot_settings['realtime_voice'] ?? BotSettingsManager::DEFAULT_REALTIME_VOICE;
$turn_detection = $bot_settings['turn_detection'] ?? BotSettingsManager::DEFAULT_TURN_DETECTION;
$speed = isset($bot_settings['speed']) ? floatval($bot_settings['speed']) : BotSettingsManager::DEFAULT_SPEED;

// Advanced settings (with defaults if not set)
$input_audio_format = $bot_settings['input_audio_format'] ?? BotSettingsManager::DEFAULT_INPUT_AUDIO_FORMAT;
$output_audio_format = $bot_settings['output_audio_format'] ?? BotSettingsManager::DEFAULT_OUTPUT_AUDIO_FORMAT;
$input_audio_noise_reduction = $bot_settings['input_audio_noise_reduction'] ?? BotSettingsManager::DEFAULT_INPUT_AUDIO_NOISE_REDUCTION;

$realtime_models = ['gpt-4o-realtime-preview', 'gpt-4o-mini-realtime'];
$realtime_voices = ['alloy', 'ash', 'ballad', 'coral', 'echo', 'fable', 'onyx', 'nova', 'shimmer', 'verse'];

$direct_voice_mode_disabled = !($popup_enabled === '1' && $enable_realtime_voice === '1');
$direct_voice_mode_tooltip = $direct_voice_mode_disabled ? __('Requires "Popup Enabled" (in Appearance) and "Enable Realtime Voice Agent" to be active.', 'gpt3-ai-content-generator') : '';

?>
<div class="aipkit_accordion" data-section="voice-agent">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Realtime Voice Agent', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">

        <!-- Subsection: Realtime Agent Toggle -->
        <div class="aipkit_settings_subsection">
          <div class="aipkit_settings_subsection-header">
            <h5 class="aipkit_settings_subsection-title"><?php esc_html_e('Realtime Agent', 'gpt3-ai-content-generator'); ?></h5>
          </div>
          <div class="aipkit_settings_subsection-body">
            <div class="aipkit_form-row aipkit_checkbox-row">
            <div class="aipkit_form-group">
                <label
                    class="aipkit_form-label aipkit_checkbox-label"
                    for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_realtime_voice"
                >
                    <input
                        type="checkbox"
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_enable_realtime_voice"
                        name="enable_realtime_voice"
                        class="aipkit_toggle_switch aipkit_enable_realtime_voice_toggle"
                        value="1"
                        <?php checked($enable_realtime_voice, '1'); ?>
                    >
                    <?php esc_html_e('Enable Realtime Voice Agent', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_form-help"><?php esc_html_e('Enable live voice conversation.', 'gpt3-ai-content-generator'); ?></div>
            </div>
             <div class="aipkit_form-group" data-tooltip-disabled="<?php echo esc_attr($direct_voice_mode_tooltip); ?>" title="<?php echo esc_attr($direct_voice_mode_tooltip); ?>">
                <label
                    class="aipkit_form-label aipkit_checkbox-label <?php echo $direct_voice_mode_disabled ? 'aipkit-disabled-tooltip' : ''; ?>"
                    for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_direct_voice_mode"
                >
                    <input
                        type="checkbox"
                        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_direct_voice_mode"
                        name="direct_voice_mode"
                        class="aipkit_toggle_switch"
                        value="1"
                        <?php checked($direct_voice_mode, '1'); ?>
                        <?php disabled($direct_voice_mode_disabled); ?>
                    >
                    <?php esc_html_e('Enable Direct Voice Mode', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_form-help"><?php esc_html_e('Auto-listen when the chat opens.', 'gpt3-ai-content-generator'); ?></div>
            </div>

            <!-- Noise Reduction moved to the same row -->
            <div class="aipkit_form-group">
                <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_audio_noise_reduction">
                    <input type="checkbox" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_audio_noise_reduction" name="input_audio_noise_reduction" class="aipkit_toggle_switch" value="1" <?php checked($input_audio_noise_reduction, '1'); ?> >
                    <?php esc_html_e('Noise Reduction', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipkit_form-help"><?php esc_html_e('Reduce background noise.', 'gpt3-ai-content-generator'); ?></div>
            </div>

            <!-- Wrapper (kept for JS) holding realtime fields (flattened) -->
            <div class="aipkit_realtime_voice_settings_container" style="display: <?php echo $enable_realtime_voice === '1' ? 'block' : 'none'; ?>;">
            <!-- Row 1: Realtime Model - Voice - Turn Detection -->
            <div class="aipkit_form-row aipkit_form-row-align-bottom">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_realtime_model"><?php esc_html_e('Realtime Model', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_realtime_model" name="realtime_model" class="aipkit_form-input">
                        <?php foreach ($realtime_models as $model_id): ?>
                            <option value="<?php echo esc_attr($model_id); ?>" <?php selected($realtime_model, $model_id); ?>><?php echo esc_html($model_id); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="aipkit_form-help"><?php esc_html_e('Choose an OpenAI realtime model.', 'gpt3-ai-content-generator'); ?></div>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_realtime_voice"><?php esc_html_e('Voice', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_realtime_voice" name="realtime_voice" class="aipkit_form-input">
                        <?php foreach ($realtime_voices as $voice_id): ?>
                            <option value="<?php echo esc_attr($voice_id); ?>" <?php selected($realtime_voice, $voice_id); ?>><?php echo esc_html(ucfirst($voice_id)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="aipkit_form-help"><?php esc_html_e('Pick the synthetic voice for replies.', 'gpt3-ai-content-generator'); ?></div>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_turn_detection"><?php esc_html_e('Turn Detection', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_turn_detection" name="turn_detection" class="aipkit_form-input">
                        <option value="none" <?php selected($turn_detection, 'none'); ?>><?php esc_html_e('None (Push-to-Talk)', 'gpt3-ai-content-generator'); ?></option>
                        <option value="server_vad" <?php selected($turn_detection, 'server_vad'); ?>><?php esc_html_e('Automatic (Voice Activity)', 'gpt3-ai-content-generator'); ?></option>
                        <option value="semantic_vad" <?php selected($turn_detection, 'semantic_vad'); ?>><?php esc_html_e('Smart (Semantic Detection)', 'gpt3-ai-content-generator'); ?></option>
                    </select>
                    <div class="aipkit_form-help"><?php esc_html_e('Decide when speech has ended.', 'gpt3-ai-content-generator'); ?></div>
                </div>
            </div>

            <!-- Row 2: Input Audio Format - Output Audio Format - Response Speed -->
            <div class="aipkit_form-row aipkit_form-row-align-bottom">
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_audio_format"><?php esc_html_e('Input Audio Format', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_audio_format" name="input_audio_format" class="aipkit_form-input">
                        <option value="pcm16" <?php selected($input_audio_format, 'pcm16'); ?>>pcm16</option>
                        <option value="g711_ulaw" <?php selected($input_audio_format, 'g711_ulaw'); ?>>g711_ulaw</option>
                        <option value="g711_alaw" <?php selected($input_audio_format, 'g711_alaw'); ?>>g711_alaw</option>
                    </select>
                    <div class="aipkit_form-help"><?php esc_html_e('Format of audio sent.', 'gpt3-ai-content-generator'); ?></div>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_output_audio_format"><?php esc_html_e('Output Audio Format', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_output_audio_format" name="output_audio_format" class="aipkit_form-input">
                        <option value="pcm16" <?php selected($output_audio_format, 'pcm16'); ?>>pcm16</option>
                        <option value="g711_ulaw" <?php selected($output_audio_format, 'g711_ulaw'); ?>>g711_ulaw</option>
                        <option value="g711_alaw" <?php selected($output_audio_format, 'g711_alaw'); ?>>g711_alaw</option>
                    </select>
                    <div class="aipkit_form-help"><?php esc_html_e('Format of audio received.', 'gpt3-ai-content-generator'); ?></div>
                </div>
                <div class="aipkit_form-group aipkit_form-col">
                    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_speed"><?php esc_html_e('Response Speed', 'gpt3-ai-content-generator'); ?></label>
                    <div class="aipkit_slider_wrapper">
                        <input type="range" id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_speed" name="speed" class="aipkit_form-input aipkit_range_slider" min="0.25" max="1.5" step="0.05" value="<?php echo esc_attr($speed); ?>" />
                        <span id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_speed_value" class="aipkit_slider_value"><?php echo esc_html(number_format($speed, 2)); ?></span>
                    </div>
                    <div class="aipkit_form-help"><?php esc_html_e('Controls reply pacing.', 'gpt3-ai-content-generator'); ?></div>
                </div>
            </div>

            
          </div>
        </div>
    </div>
</div>

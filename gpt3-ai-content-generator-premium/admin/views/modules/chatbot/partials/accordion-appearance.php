<?php
/**
 * Partial: Chatbot Appearance Settings Accordion Content
 * UPDATED: Includes new custom-theme-settings.php partial.
 * UPDATED: Merged theme select into text-inputs.php
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\aipkit_dashboard; // Use the dashboard class
use WPAICG\Chat\Storage\BotSettingsManager;

// Check if Conversation Starters addon is active
$starters_addon_active = aipkit_dashboard::is_addon_active('conversation_starters');

// Variables available from parent script:
// $bot_id, $bot_settings
// Note: These variables are now used within the included partials.

$enable_voice_input = isset($bot_settings['enable_voice_input'])
    ? $bot_settings['enable_voice_input']
    : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT;
$stt_provider = isset($bot_settings['stt_provider'])
                ? $bot_settings['stt_provider']
                : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_STT_PROVIDER;
// Get saved OpenAI STT model
$stt_openai_model_id = isset($bot_settings['stt_openai_model_id'])
                        ? $bot_settings['stt_openai_model_id']
                        : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_STT_OPENAI_MODEL_ID;
// NEW: Get saved Azure STT model
$stt_azure_model_id = isset($bot_settings['stt_azure_model_id'])
                        ? $bot_settings['stt_azure_model_id']
                        : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_STT_AZURE_MODEL_ID;

// Get synced OpenAI STT models
$openai_stt_models = \WPAICG\AIPKit_Providers::get_openai_stt_models();
?>
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Appearance', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">

        <?php
        // Determine saved theme to control initial visibility of custom theme section
        $saved_theme_for_section = isset($bot_settings['theme']) ? $bot_settings['theme'] : 'light';
        ?>

        <div class="aipkit_settings_sections">

            <!-- Section: Text & Typing -->
            <section class="aipkit_settings_section" data-section="text-typing">
                <div class="aipkit_settings_section-header">
                    <h5 class="aipkit_settings_section-title"><?php esc_html_e('Text & Typing', 'gpt3-ai-content-generator'); ?></h5>
                </div>
                <div class="aipkit_settings_section-body">
                    <?php include __DIR__ . '/appearance/text-inputs.php'; ?>
                </div>
            </section>

            <!-- Section: Features -->
            <section class="aipkit_settings_section" data-section="features">
                <div class="aipkit_settings_section-header">
                    <h5 class="aipkit_settings_section-title"><?php esc_html_e('Features', 'gpt3-ai-content-generator'); ?></h5>
                </div>
                <div class="aipkit_settings_section-body">
                    <?php include __DIR__ . '/appearance/feature-toggles.php'; ?>
                </div>
            </section>
        </div>

    </div><!-- /.aipkit_accordion-content -->
</div><!-- /.aipkit_accordion -->

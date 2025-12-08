<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/configurator/get-tts-settings.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\ConfiguratorMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares TTS (Text-to-Speech) related settings.
 *
 * @param array $settings Bot settings.
 * @return array An array containing tts_provider and tts_voice_id.
 */
function get_tts_settings_logic(array $settings): array {
    if (!class_exists(BotSettingsManager::class)) {
        return [
            'tts_provider' => 'Google',
            'tts_voice_id' => '',
            'tts_auto_play' => false,
            'tts_openai_model_id' => 'tts-1', // Default if BotSettingsManager constants not available
            'tts_elevenlabs_model_id' => '',  // Default if BotSettingsManager constants not available
        ];
    }
    $tts_provider = $settings['tts_provider'] ?? BotSettingsManager::DEFAULT_TTS_PROVIDER;
    $tts_voice_id = $settings['tts_voice_id'] ?? ''; // This should be the combined one after bot settings are fetched
    $tts_auto_play = ($settings['tts_auto_play'] ?? BotSettingsManager::DEFAULT_TTS_AUTO_PLAY) === '1';
    $tts_openai_model_id = $settings['tts_openai_model_id'] ?? BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID;
    $tts_elevenlabs_model_id = $settings['tts_elevenlabs_model_id'] ?? BotSettingsManager::DEFAULT_TTS_ELEVENLABS_MODEL_ID;


    return [
        'tts_provider' => $tts_provider,
        'tts_voice_id' => $tts_voice_id,
        'tts_auto_play' => $tts_auto_play,
        'tts_openai_model_id' => $tts_openai_model_id,
        'tts_elevenlabs_model_id' => $tts_elevenlabs_model_id,
    ];
}
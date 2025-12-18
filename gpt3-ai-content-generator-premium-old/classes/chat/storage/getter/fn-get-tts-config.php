<?php

// File: classes/chat/storage/getter/fn-get-tts-config.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves Text-to-Speech (TTS) configuration settings.
 *
 * @param int $bot_id The ID of the bot post.
 * @param callable $get_meta_fn A function to retrieve post meta.
 * @return array Associative array of TTS settings.
 */
function get_tts_config_logic(int $bot_id, callable $get_meta_fn): array
{
    $settings = [];

    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = dirname(__DIR__) . '/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        }
    }

    $settings['tts_enabled'] = in_array($get_meta_fn('_aipkit_tts_enabled', BotSettingsManager::DEFAULT_TTS_ENABLED), ['0','1'])
        ? $get_meta_fn('_aipkit_tts_enabled', BotSettingsManager::DEFAULT_TTS_ENABLED)
        : BotSettingsManager::DEFAULT_TTS_ENABLED;

    $settings['tts_provider'] = $get_meta_fn('_aipkit_tts_provider', BotSettingsManager::DEFAULT_TTS_PROVIDER);
    if (!in_array($settings['tts_provider'], ['Google', 'OpenAI', 'ElevenLabs'])) {
        $settings['tts_provider'] = BotSettingsManager::DEFAULT_TTS_PROVIDER;
    }

    $settings['tts_google_voice_id'] = $get_meta_fn('_aipkit_tts_google_voice_id', '');
    $settings['tts_openai_voice_id'] = $get_meta_fn('_aipkit_tts_openai_voice_id', 'alloy');
    $settings['tts_openai_model_id'] = $get_meta_fn('_aipkit_tts_openai_model_id', BotSettingsManager::DEFAULT_TTS_OPENAI_MODEL_ID);
    $settings['tts_elevenlabs_voice_id'] = $get_meta_fn('_aipkit_tts_elevenlabs_voice_id', '');
    $settings['tts_elevenlabs_model_id'] = $get_meta_fn('_aipkit_tts_elevenlabs_model_id', BotSettingsManager::DEFAULT_TTS_ELEVENLABS_MODEL_ID);

    $settings['tts_voice_id'] = ''; // Determine combined voice ID based on provider
    switch ($settings['tts_provider']) {
        case 'Google': $settings['tts_voice_id'] = $settings['tts_google_voice_id'];
            break;
        case 'OpenAI': $settings['tts_voice_id'] = $settings['tts_openai_voice_id'];
            break;
        case 'ElevenLabs': $settings['tts_voice_id'] = $settings['tts_elevenlabs_voice_id'];
            break;
    }

    $settings['tts_auto_play'] = in_array($get_meta_fn('_aipkit_tts_auto_play', BotSettingsManager::DEFAULT_TTS_AUTO_PLAY), ['0','1'])
        ? $get_meta_fn('_aipkit_tts_auto_play', BotSettingsManager::DEFAULT_TTS_AUTO_PLAY)
        : BotSettingsManager::DEFAULT_TTS_AUTO_PLAY;

    return $settings;
}

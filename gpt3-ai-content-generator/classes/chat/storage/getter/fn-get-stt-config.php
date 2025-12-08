<?php

// File: classes/chat/storage/getter/fn-get-stt-config.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves Speech-to-Text (STT) configuration settings.
 *
 * @param int $bot_id The ID of the bot post.
 * @param callable $get_meta_fn A function to retrieve post meta.
 * @return array Associative array of STT settings.
 */
function get_stt_config_logic(int $bot_id, callable $get_meta_fn): array
{
    $settings = [];

    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = dirname(__DIR__) . '/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        }
    }

    $settings['enable_voice_input'] = in_array($get_meta_fn('_aipkit_enable_voice_input', BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_voice_input', BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT)
        : BotSettingsManager::DEFAULT_ENABLE_VOICE_INPUT;

    $settings['stt_provider'] = $get_meta_fn('_aipkit_stt_provider', BotSettingsManager::DEFAULT_STT_PROVIDER);
    if (!in_array($settings['stt_provider'], ['OpenAI', 'Azure'])) { // Add other valid providers as needed
        $settings['stt_provider'] = BotSettingsManager::DEFAULT_STT_PROVIDER;
    }

    $settings['stt_openai_model_id'] = $get_meta_fn('_aipkit_stt_openai_model_id', BotSettingsManager::DEFAULT_STT_OPENAI_MODEL_ID);
    $settings['stt_azure_model_id'] = $get_meta_fn('_aipkit_stt_azure_model_id', BotSettingsManager::DEFAULT_STT_AZURE_MODEL_ID);

    return $settings;
}

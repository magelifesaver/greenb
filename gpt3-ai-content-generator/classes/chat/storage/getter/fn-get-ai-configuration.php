<?php

// File: classes/chat/storage/getter/fn-get-ai-configuration.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves AI configuration settings like provider, model, temperature, etc.
 *
 * @param int $bot_id The ID of the bot post.
 * @param string|null $current_provider_from_main_settings The current global provider.
 * @param callable $get_meta_fn A function to retrieve post meta.
 * @return array Associative array of AI configuration settings.
 */
function get_ai_configuration_logic(int $bot_id, ?string $current_provider_from_main_settings, callable $get_meta_fn): array
{
    $settings = [];

    // Ensure dependencies are loaded for defaults
    if (!class_exists(AIPKit_Providers::class)) {
        $path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
    if (!class_exists(AIPKIT_AI_Settings::class)) {
        $path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_ai_settings.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = dirname(__DIR__) . '/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        }
    }

    $default_provider = $current_provider_from_main_settings ?: (class_exists(AIPKit_Providers::class) ? AIPKit_Providers::get_current_provider() : 'OpenAI');
    $settings['provider'] = $get_meta_fn('_aipkit_provider', $default_provider);
    $settings['model'] = $get_meta_fn('_aipkit_model'); // No default model here, depends on provider sync

    $global_ai_params = class_exists(AIPKIT_AI_Settings::class) ? AIPKIT_AI_Settings::get_ai_parameters() : [];
    $default_temp = BotSettingsManager::DEFAULT_TEMPERATURE;
    $default_max_tokens = BotSettingsManager::DEFAULT_MAX_COMPLETION_TOKENS;
    $default_max_messages = BotSettingsManager::DEFAULT_MAX_MESSAGES;

    $temp_val = $get_meta_fn('_aipkit_temperature', 'not_set');
    $settings['temperature'] = ($temp_val === 'not_set')
        ? floatval($global_ai_params['temperature'] ?? $default_temp)
        : floatval($temp_val);
    $settings['temperature'] = max(0.0, min($settings['temperature'], 2.0));

    $max_tokens_val = $get_meta_fn('_aipkit_max_completion_tokens', 'not_set');
    $settings['max_completion_tokens'] = ($max_tokens_val === 'not_set')
        ? absint($global_ai_params['max_completion_tokens'] ?? $default_max_tokens)
        : absint($max_tokens_val);
    $settings['max_completion_tokens'] = max(1, min($settings['max_completion_tokens'], 128000));

    $max_msgs_val = $get_meta_fn('_aipkit_max_messages', 'not_set');
    $settings['max_messages'] = ($max_msgs_val === 'not_set')
        ? $default_max_messages
        : absint($max_msgs_val);
    $settings['max_messages'] = max(1, min($settings['max_messages'], 1024));

    $settings['stream_enabled'] = in_array($get_meta_fn('_aipkit_stream_enabled', '0'), ['0','1'])
        ? $get_meta_fn('_aipkit_stream_enabled', '0')
        : '0';

    return $settings;
}

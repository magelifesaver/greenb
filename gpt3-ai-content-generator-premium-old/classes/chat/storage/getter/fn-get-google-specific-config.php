<?php

// File: classes/chat/storage/getter/fn-get-google-specific-config.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves Google-specific configuration settings (Search Grounding).
 *
 * @param int $bot_id The ID of the bot post.
 * @param callable $get_meta_fn A function to retrieve post meta.
 * @return array Associative array of Google-specific settings.
 */
function get_google_specific_config_logic(int $bot_id, callable $get_meta_fn): array
{
    $settings = [];

    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = dirname(__DIR__) . '/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        }
    }

    $settings['google_search_grounding_enabled'] = in_array(
        $get_meta_fn('_aipkit_google_search_grounding_enabled', BotSettingsManager::DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED),
        ['0', '1']
    ) ? $get_meta_fn('_aipkit_google_search_grounding_enabled', BotSettingsManager::DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED)
      : BotSettingsManager::DEFAULT_GOOGLE_SEARCH_GROUNDING_ENABLED;

    $settings['google_grounding_mode'] = $get_meta_fn('_aipkit_google_grounding_mode', BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE);
    if (!in_array($settings['google_grounding_mode'], ['DEFAULT_MODE', 'MODE_DYNAMIC'])) {
        $settings['google_grounding_mode'] = BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE;
    }

    $raw_threshold = $get_meta_fn('_aipkit_google_grounding_dynamic_threshold');
    if ($raw_threshold === '' || !is_numeric($raw_threshold)) {
        $settings['google_grounding_dynamic_threshold'] = BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD;
    } else {
        $settings['google_grounding_dynamic_threshold'] = floatval($raw_threshold);
    }
    $settings['google_grounding_dynamic_threshold'] = max(0.0, min($settings['google_grounding_dynamic_threshold'], 1.0));

    return $settings;
}

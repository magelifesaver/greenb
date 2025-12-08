<?php

// File: classes/chat/storage/getter/fn-get-openai-specific-config.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves OpenAI-specific configuration settings.
 *
 * @param int $bot_id The ID of the bot post.
 * @param callable $get_meta_fn A function to retrieve post meta.
 * @return array Associative array of OpenAI-specific settings.
 */
function get_openai_specific_config_logic(int $bot_id, callable $get_meta_fn): array
{
    $settings = [];

    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = dirname(__DIR__) . '/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        }
    }

    $settings['openai_conversation_state_enabled'] = in_array(
        $get_meta_fn('_aipkit_openai_conversation_state_enabled', BotSettingsManager::DEFAULT_OPENAI_CONVERSATION_STATE_ENABLED),
        ['0', '1']
    ) ? $get_meta_fn('_aipkit_openai_conversation_state_enabled', BotSettingsManager::DEFAULT_OPENAI_CONVERSATION_STATE_ENABLED)
      : BotSettingsManager::DEFAULT_OPENAI_CONVERSATION_STATE_ENABLED;

    // OpenAI Web Search Settings
    $settings['openai_web_search_enabled'] = in_array(
        $get_meta_fn('_aipkit_openai_web_search_enabled', BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_ENABLED),
        ['0', '1']
    ) ? $get_meta_fn('_aipkit_openai_web_search_enabled', BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_ENABLED)
      : BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_ENABLED;

    $settings['openai_web_search_context_size'] = $get_meta_fn('_aipkit_openai_web_search_context_size', BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_CONTEXT_SIZE);
    if (!in_array($settings['openai_web_search_context_size'], ['low', 'medium', 'high'])) {
        $settings['openai_web_search_context_size'] = BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_CONTEXT_SIZE;
    }

    $settings['openai_web_search_loc_type'] = $get_meta_fn('_aipkit_openai_web_search_loc_type', BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_LOC_TYPE);
    if (!in_array($settings['openai_web_search_loc_type'], ['none', 'approximate'])) {
        $settings['openai_web_search_loc_type'] = BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_LOC_TYPE;
    }

    $settings['openai_web_search_loc_country'] = $get_meta_fn('_aipkit_openai_web_search_loc_country', '');
    $settings['openai_web_search_loc_city'] = $get_meta_fn('_aipkit_openai_web_search_loc_city', '');
    $settings['openai_web_search_loc_region'] = $get_meta_fn('_aipkit_openai_web_search_loc_region', '');
    $settings['openai_web_search_loc_timezone'] = $get_meta_fn('_aipkit_openai_web_search_loc_timezone', '');
    
    // Reasoning Effort Setting
    $default_reasoning_effort = defined('WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_REASONING_EFFORT') ? BotSettingsManager::DEFAULT_REASONING_EFFORT : 'medium';
    $settings['reasoning_effort'] = $get_meta_fn('_aipkit_reasoning_effort', $default_reasoning_effort);
    if (!in_array($settings['reasoning_effort'], ['minimal', 'low', 'medium', 'high', ''])) {
        $settings['reasoning_effort'] = $default_reasoning_effort;
    }


    return $settings;
}

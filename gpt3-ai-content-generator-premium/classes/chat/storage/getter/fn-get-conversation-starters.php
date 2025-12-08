<?php

// File: classes/chat/storage/getter/fn-get-conversation-starters.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\GetterMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves conversation starters settings.
 *
 * @param int $bot_id The ID of the bot post.
 * @param callable $get_meta_fn A function to retrieve post meta.
 * @return array Associative array of conversation starters settings.
 */
function get_conversation_starters_logic(int $bot_id, callable $get_meta_fn): array
{
    $settings = [];

    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = dirname(__DIR__) . '/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        }
    }

    $default_enable_starters = BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_STARTERS;
    $settings['enable_conversation_starters'] = in_array($get_meta_fn('_aipkit_enable_conversation_starters', $default_enable_starters), ['0','1'])
        ? $get_meta_fn('_aipkit_enable_conversation_starters', $default_enable_starters)
        : $default_enable_starters;

    $starters_json = $get_meta_fn('_aipkit_conversation_starters', '[]');
    $starters_array = json_decode($starters_json, true);
    $settings['conversation_starters'] = is_array($starters_array) ? $starters_array : [];

    return $settings;
}

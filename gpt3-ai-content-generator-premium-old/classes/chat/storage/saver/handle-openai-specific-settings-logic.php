<?php

// File: classes/chat/storage/saver/handle-openai-specific-settings-logic.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\SaverMethods;

use WPAICG\AIPKit_Providers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles OpenAI-specific settings, such as ensuring global 'store_conversation' is true
 * if the bot's conversation state is enabled.
 *
 * @param int $botId The ID of the bot.
 * @param array $sanitized_settings The array of sanitized settings for the bot.
 * @return void
 */
function handle_openai_specific_settings_logic(int $botId, array $sanitized_settings): void
{
    if (isset($sanitized_settings['provider']) && $sanitized_settings['provider'] === 'OpenAI' &&
        isset($sanitized_settings['openai_conversation_state_enabled']) && $sanitized_settings['openai_conversation_state_enabled'] === '1') {
        if (class_exists(\WPAICG\AIPKit_Providers::class)) {
            $openai_global_settings = AIPKit_Providers::get_provider_data('OpenAI');
            if (($openai_global_settings['store_conversation'] ?? '0') !== '1') {
                $openai_global_settings['store_conversation'] = '1';
                AIPKit_Providers::save_provider_data('OpenAI', $openai_global_settings);
            }
        }
    }
}

<?php
// File: classes/core/token-manager/helpers/FetchChatbotSettingsLogic.php

namespace WPAICG\Core\TokenManager\Helpers;

use WPAICG\Core\TokenManager\AIPKit_Token_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic to fetch chatbot settings for token management.
 *
 * @param AIPKit_Token_Manager $managerInstance The instance of AIPKit_Token_Manager.
 * @param int $bot_id The ID of the chatbot.
 * @return array The settings for the specified bot.
 */
function FetchChatbotSettingsLogic(AIPKit_Token_Manager $managerInstance, int $bot_id): array {
    $bot_storage = $managerInstance->get_bot_storage();
    if (!$bot_storage) {
        return []; // Return empty array if storage not available
    }
    return $bot_storage->get_chatbot_settings($bot_id);
}
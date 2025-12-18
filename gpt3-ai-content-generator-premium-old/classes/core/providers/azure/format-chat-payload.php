<?php
// File: classes/core/providers/azure/format-chat-payload.php

namespace WPAICG\Core\Providers\Azure\Methods;

use WPAICG\Core\Providers\AzureProviderStrategy;
use WPAICG\Core\Providers\Azure\AzurePayloadFormatter; // For direct call

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_chat_payload method of AzureProviderStrategy.
 *
 * @param AzureProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $user_message The user's message (already included in history for Azure).
 * @param string $instructions System instructions.
 * @param array  $history Conversation history.
 * @param array  $ai_params AI parameters (temperature, max_tokens, etc.).
 * @param string $model The target model/deployment ID (unused here as payload formatter handles it).
 * @return array The formatted request body data.
 */
function format_chat_payload_logic(
    AzureProviderStrategy $strategyInstance,
    string $user_message,
    string $instructions,
    array $history,
    array $ai_params,
    string $model
): array {
    // This method in AzureProviderStrategy directly calls AzurePayloadFormatter::format_chat.
    if (!class_exists(\WPAICG\Core\Providers\Azure\AzurePayloadFormatter::class)) {
        $formatter_bootstrap = __DIR__ . '/bootstrap-payload-formatter.php';
        if (file_exists($formatter_bootstrap)) {
            require_once $formatter_bootstrap;
        } else {
            // This should not happen if ProviderDependenciesLoader is correct.
            return []; // Or throw an error
        }
    }
    // The $user_message for Azure is typically part of the $history for the formatter
    // The original strategy passed $user_message, but the formatter's format_chat uses instructions and history
    // Let's adjust to ensure the latest user message is part of history if not already.
    $final_history = $history;
    if(!empty($user_message)){ // if $user_message is not empty and meant to be the last message
        $last_msg = end($final_history);
        if(!$last_msg || $last_msg['role'] !== 'user' || $last_msg['content'] !== $user_message){
             $final_history[] = ['role' => 'user', 'content' => $user_message];
        }
    }
    return \WPAICG\Core\Providers\Azure\AzurePayloadFormatter::format_chat($instructions, $final_history, $ai_params, $model);
}
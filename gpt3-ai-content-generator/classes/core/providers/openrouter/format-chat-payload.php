<?php
// File: classes/core/providers/openrouter/format-chat-payload.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WPAICG\Core\Providers\OpenRouterProviderStrategy;
use WPAICG\Core\Providers\OpenRouter\OpenRouterPayloadFormatter; // For direct call
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_chat_payload method of OpenRouterProviderStrategy.
 *
 * @param OpenRouterProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $user_message The user's message (already included in history for OpenRouter).
 * @param string $instructions System instructions.
 * @param array  $history Conversation history.
 * @param array  $ai_params AI parameters (temperature, max_tokens, etc.).
 * @param string $model Model name (required by OpenRouter in payload).
 * @return array The formatted request body data.
 */
function format_chat_payload_logic(
    OpenRouterProviderStrategy $strategyInstance,
    string $user_message,
    string $instructions,
    array $history,
    array $ai_params,
    string $model
): array {
    // Ensure OpenRouterPayloadFormatter is available
    if (!class_exists(\WPAICG\Core\Providers\OpenRouter\OpenRouterPayloadFormatter::class)) {
        $formatter_bootstrap = dirname(__FILE__) . '/bootstrap-payload-formatter.php';
        if (file_exists($formatter_bootstrap)) {
            require_once $formatter_bootstrap;
        } else {
            return []; // Or throw an error
        }
    }
    // The latest user_message should be part of the history for format_chat
    $final_history = $history;
    if (!empty($user_message)) {
        $last_msg = end($final_history);
        if (!$last_msg || $last_msg['role'] !== 'user' || $last_msg['content'] !== $user_message) {
            $final_history[] = ['role' => 'user', 'content' => $user_message];
        }
    }
    return \WPAICG\Core\Providers\OpenRouter\OpenRouterPayloadFormatter::format_chat($instructions, $final_history, $ai_params, $model);
}
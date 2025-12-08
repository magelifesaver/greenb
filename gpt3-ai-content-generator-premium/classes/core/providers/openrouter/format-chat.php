<?php
// File: classes/core/providers/openrouter/format-chat.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_chat static method of OpenRouterPayloadFormatter.
 *
 * @param string $instructions System instructions.
 * @param array  $history Conversation history.
 * @param array  $ai_params AI parameters (temperature, max_tokens, etc.).
 * @param string $model Model name.
 * @return array The formatted payload.
 */
function format_chat_logic_for_payload_formatter(string $instructions, array $history, array $ai_params, string $model): array {
    return _shared_format_logic($instructions, $history, $ai_params, $model);
}
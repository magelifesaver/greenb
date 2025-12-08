<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/format-chat-payload.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAIProviderStrategy;
use WPAICG\Core\Providers\OpenAI\OpenAIPayloadFormatter; // Use the new Formatter class

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_chat_payload method of OpenAIProviderStrategy.
 *
 * @param OpenAIProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $user_message The user's message.
 * @param string $instructions System instructions.
 * @param array  $history Conversation history.
 * @param array  $ai_params AI parameters (temperature, max_tokens, etc.).
 * @param string $model The target model/deployment ID.
 * @return array The formatted request body data.
 */
function format_chat_payload_logic(
    OpenAIProviderStrategy $strategyInstance,
    string $user_message,
    string $instructions,
    array $history,
    array $ai_params,
    string $model
): array {
    $use_openai_conversation_state = $ai_params['use_openai_conversation_state'] ?? false;
    $previous_response_id = $ai_params['previous_response_id'] ?? null;
    // Web search config is already part of $ai_params if set by AIService/SSERequestHandler
    return OpenAIPayloadFormatter::format_chat($instructions, $history, $ai_params, $model, $use_openai_conversation_state, $previous_response_id);
}
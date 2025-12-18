<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/build-sse-payload.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAIProviderStrategy;
use WPAICG\Core\Providers\OpenAI\OpenAIPayloadFormatter; // Use the new Formatter class

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build_sse_payload method of OpenAIProviderStrategy.
 *
 * @param OpenAIProviderStrategy $strategyInstance The instance of the strategy class.
 * @param array $messages Formatted messages/input/contents array.
 * @param string|array|null $system_instruction Formatted system instruction.
 * @param array $ai_params AI parameters.
 * @param string $model Target model/deployment.
 * @return array The formatted request body data for SSE.
 */
function build_sse_payload_logic(
    OpenAIProviderStrategy $strategyInstance,
    array $messages,
    $system_instruction,
    array $ai_params,
    string $model
): array {
    $use_openai_conversation_state = $ai_params['use_openai_conversation_state'] ?? false;
    $previous_response_id = $ai_params['previous_response_id'] ?? null;
    // Web search config is already part of $ai_params if set by SSERequestHandler
    return OpenAIPayloadFormatter::format_sse($messages, $system_instruction, $ai_params, $model, $use_openai_conversation_state, $previous_response_id);
}
<?php

// File: classes/chat/core/ai-service/generate-response/finalize-ai-response.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Applies final filters to the successful AI response and prepares the return structure.
 *
 * @param array $ai_call_success_result The successful result from AI Caller.
 * @param string $main_provider The main AI provider.
 * @param string $model The selected AI model.
 * @param array $history Conversation history (used by the filter).
 * @param string $base_instructions Base system instructions (used by the filter).
 * @param array $final_ai_params Final AI parameters (used by the filter).
 * @param string|null $actual_previous_response_id_to_use OpenAI stateful ID, if used.
 * @return array The final response structure.
 */
function finalize_ai_response_logic(
    array $ai_call_success_result,
    string $main_provider,
    string $model,
    array $history,
    string $base_instructions,
    array $final_ai_params,
    ?string $actual_previous_response_id_to_use
): array {
    $final_instructions_for_filter = $ai_call_success_result['request_payload_log']['system_instruction'] ?? $base_instructions;

    $ai_call_success_result['content'] = apply_filters(
        'aipkit_ai_response',
        $ai_call_success_result['content'],
        null, // Stream type is null for non-streaming
        $main_provider,
        $model,
        $history,
        $final_instructions_for_filter,
        null, // SSE chunk data (null here)
        $final_ai_params
    );

    if ($actual_previous_response_id_to_use !== null && ($main_provider === 'OpenAI' && ($final_ai_params['use_openai_conversation_state'] ?? false))) {
        $ai_call_success_result['used_previous_response_id'] = true;
    }

    return $ai_call_success_result;
}

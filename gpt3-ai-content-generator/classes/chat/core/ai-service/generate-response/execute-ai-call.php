<?php

// File: classes/chat/core/ai-service/generate-response/execute-ai-call.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Executes the AI call using AIPKit_AI_Caller.
 *
 * @param \WPAICG\Core\AIPKit_AI_Caller $ai_caller Instance of AI Caller.
 * @param string $main_provider The main AI provider.
 * @param string $model The selected AI model.
 * @param array $messages_payload The prepared messages payload for the API.
 * @param array $final_ai_params The final AI parameters.
 * @param string $instructions_processed The processed system instruction.
 * @param array $instruction_context_for_logging Context used for instruction building (for logging).
 * @return array|WP_Error The result from AI Caller.
 */
function execute_ai_call_logic(
    \WPAICG\Core\AIPKit_AI_Caller $ai_caller,
    string $main_provider,
    string $model,
    array $messages_payload,
    array $final_ai_params,
    string $instructions_processed,
    array $instruction_context_for_logging
): array|WP_Error {
    return $ai_caller->make_standard_call(
        $main_provider,
        $model,
        $messages_payload,
        $final_ai_params,
        $instructions_processed,
        $instruction_context_for_logging // Pass context for logging
    );
}

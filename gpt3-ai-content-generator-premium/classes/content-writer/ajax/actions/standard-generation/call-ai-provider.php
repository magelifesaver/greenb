<?php

// File: classes/content-writer/ajax/actions/standard-generation/call-ai-provider.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\StandardGeneration;

use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Standard_Generation_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Makes the call to the AI provider using the AI Caller.
 *
 * @param AIPKit_Content_Writer_Standard_Generation_Action $handler The handler instance.
 * @param string $provider The AI provider.
 * @param string $model The AI model.
 * @param array $messages The message payload for the API.
 * @param array $ai_params_override AI parameters to override globals.
 * @param string $system_instruction The system instruction for the AI.
 * @return array|WP_Error The result from the AI Caller.
 */
function call_ai_provider_logic(
    AIPKit_Content_Writer_Standard_Generation_Action $handler,
    string $provider,
    string $model,
    array $messages,
    array $ai_params_override,
    string $system_instruction
): array|WP_Error {
    return $handler->get_ai_caller()->make_standard_call(
        $provider,
        $model,
        $messages,
        $ai_params_override,
        $system_instruction,
        []
    );
}

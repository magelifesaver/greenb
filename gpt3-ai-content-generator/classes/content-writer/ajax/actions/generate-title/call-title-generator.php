<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/generate-title/call-title-generator.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions\GenerateTitle;

use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Generate_Title_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Makes the call to the AI provider using the AI Caller.
 *
 * @param AIPKit_Content_Writer_Generate_Title_Action $handler The handler instance.
 * @param string $provider The AI provider.
 * @param string $model The AI model.
 * @param array $messages The message payload for the API.
 * @param array $ai_params_override AI parameters to override globals.
 * @param string $system_instruction The system instruction for the AI.
 * @param array $form_data The form data containing vector settings.
 * @return array|WP_Error The result from the AI Caller.
 */
function call_title_generator_logic(
    AIPKit_Content_Writer_Generate_Title_Action $handler,
    string $provider,
    string $model,
    array $messages,
    array $ai_params_override,
    string $system_instruction,
    array $form_data = []
): array|WP_Error {
    
    // Initialize to avoid undefined variable notices when vector search is disabled
    $collected_vector_search_scores = [];
    // DRY: use shared helper to prepare vector context, ai_params additions, and instruction_context
    $ai_caller = $handler->get_ai_caller();
    $vector_store_manager = $handler->get_vector_store_manager();
    $user_message = $messages[0]['content'] ?? '';
    if (!function_exists('WPAICG\\Core\\Stream\\Vector\\prepare_vector_standard_call')) {
        $helper_path = WPAICG_PLUGIN_DIR . 'classes/core/stream/vector/fn-prepare-vector-standard-call.php';
        if (file_exists($helper_path)) {
            require_once $helper_path;
        }
    }
    $instruction_context = [];
    if (function_exists('WPAICG\\Core\\Stream\\Vector\\prepare_vector_standard_call')) {
        $prep = \WPAICG\Core\Stream\Vector\prepare_vector_standard_call(
            $ai_caller,
            $vector_store_manager,
            $user_message,
            $form_data,
            $provider,
            $system_instruction,
            $ai_params_override
        );
        $system_instruction = $prep['system_instruction'] ?? $system_instruction;
        $ai_params_override = $prep['ai_params'] ?? $ai_params_override;
        $instruction_context = $prep['instruction_context'] ?? [];
    }

    return $handler->get_ai_caller()->make_standard_call(
        $provider,
        $model,
        $messages,
        $ai_params_override,
        $system_instruction,
        $instruction_context
    );
}

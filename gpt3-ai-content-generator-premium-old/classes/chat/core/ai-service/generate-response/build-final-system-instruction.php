<?php
// File: classes/chat/core/ai-service/generate-response/build-final-system-instruction.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse;

// AIPKit_Instruction_Manager is loaded by load_instruction_manager_logic

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Builds the final system instruction string using AIPKit_Instruction_Manager.
 *
 * @param array $bot_settings Settings of the specific bot.
 * @param int $post_id ID of the current post.
 * @param string $base_instructions The user-defined base instructions.
 * @param string $all_formatted_results_for_instruction Pre-formatted string of vector search results.
 * @return string The fully constructed system instruction string.
 */
function build_final_system_instruction_logic(
    array $bot_settings,
    int $post_id,
    string $base_instructions,
    string $all_formatted_results_for_instruction
): string {
    $instruction_context = [
        'base_instructions' => $base_instructions,
        'bot_settings' => $bot_settings,
        'post_id' => $post_id
    ];
    if (!empty($all_formatted_results_for_instruction)) {
        $instruction_context['vector_search_results'] = trim($all_formatted_results_for_instruction);
    }
    return \WPAICG\Core\AIPKit_Instruction_Manager::build_instructions($instruction_context);
}
<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/shared/build-prompts.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions\Shared;

use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_System_Instruction_Builder;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_User_Prompt_Builder;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds the system instruction and user prompt for the Content Writer.
 * UPDATED: Simplified to remove guided mode logic. Replaces placeholders in custom prompt.
 *
 * @param array $validated_params The validated settings from the request.
 * @return array|WP_Error An array containing 'system_instruction' and 'user_prompt' or WP_Error.
 */
function build_prompts_logic(array $validated_params): array|WP_Error
{
    if (!class_exists(AIPKit_Content_Writer_System_Instruction_Builder::class) || !class_exists(AIPKit_Content_Writer_User_Prompt_Builder::class)) {
        return new WP_Error('dependency_missing', 'Content writer prompt builders are unavailable.');
    }

    // 1. Build the system instruction. This function is now very simple.
    $system_instruction = AIPKit_Content_Writer_System_Instruction_Builder::build($validated_params);

    // 2. Build the user prompt *template*. This will now only return the custom prompt.
    $user_prompt_template = AIPKit_Content_Writer_User_Prompt_Builder::build($validated_params);
    if (empty($user_prompt_template)) {
        return new WP_Error('missing_prompt', 'Content Prompt cannot be empty.', ['status' => 400]);
    }

    // 3. Replace placeholders in the final prompt template.
    // The `content_title` in $validated_params has already been parsed.
    $final_title_for_prompt = $validated_params['content_title'] ?? 'AI Generated Content';
    // Prioritize inline keywords, fall back to global, then to empty.
    $final_keywords = !empty($validated_params['inline_keywords']) ? $validated_params['inline_keywords'] : ($validated_params['content_keywords'] ?? '');

    $user_prompt = str_replace('{topic}', $final_title_for_prompt, $user_prompt_template);
    $user_prompt = str_replace('{keywords}', $final_keywords, $user_prompt);
    $user_prompt = str_replace('{description}', '', $user_prompt); // For RSS

    return [
        'system_instruction' => $system_instruction,
        'user_prompt' => $user_prompt,
    ];
}

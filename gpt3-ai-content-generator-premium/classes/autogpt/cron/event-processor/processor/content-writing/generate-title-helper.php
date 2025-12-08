<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/processor/content-writing/generate-title-helper.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Cron\EventProcessor\Processor\ContentWriting;

use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\ContentWriter\AIPKit_Content_Writer_Prompts;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates a new title for the content if requested.
 * UPDATED: Simplified to remove "Guided Mode" logic. Title generation is now only controlled by a non-empty `custom_title_prompt`.
 *
 * @param array $cw_config The specific configuration for the content writing item.
 * @param AIPKit_AI_Caller $ai_caller An instance of the AI Caller.
 * @return array|WP_Error On success, returns ['title' => string, 'usage' => array|null]. On failure, returns WP_Error.
 */
function generate_title_logic(array $cw_config, AIPKit_AI_Caller $ai_caller): array|WP_Error
{
    $final_title = $cw_config['content_title'] ?? 'AI Generated Content';

    // Title generation is now only triggered if a custom title prompt exists and is not empty.
    $should_generate = !empty($cw_config['custom_title_prompt']);

    if (!$should_generate) {
        return ['title' => $final_title, 'usage' => null]; // Use the topic line as the title, no usage
    }

    $system_instruction_for_title = "You are an expert copywriter specializing in crafting engaging headlines.";

    // Use the custom prompt, falling back to default if for some reason it's empty but generation was triggered.
    $user_prompt_template = $cw_config['custom_title_prompt'] ?: AIPKit_Content_Writer_Prompts::get_default_title_prompt();
    $user_prompt_for_title = str_replace('{topic}', $final_title, $user_prompt_template);
    // Also replace the keywords placeholder
    $final_keywords_for_prompt = !empty($cw_config['inline_keywords']) ? $cw_config['inline_keywords'] : ($cw_config['content_keywords'] ?? '');
    $user_prompt_for_title = str_replace('{keywords}', $final_keywords_for_prompt, $user_prompt_for_title);

    // --- NEW: Add URL Scraped Content and Source URL placeholders for Title Prompt ---
    $url_content = $cw_config['url_content_context'] ?? '';
    if (!empty($url_content) && strpos($user_prompt_for_title, '{url_content}') !== false) {
        $user_prompt_for_title = str_replace('{url_content}', trim($url_content), $user_prompt_for_title);
    }
    $source_url = $cw_config['source_url'] ?? '';
    if (!empty($source_url) && strpos($user_prompt_for_title, '{source_url}') !== false) {
        $user_prompt_for_title = str_replace('{source_url}', trim($source_url), $user_prompt_for_title);
    }
    // --- END NEW ---

    // Use the max tokens from task configuration, with a fallback to 60 for title generation
    $max_tokens_for_title = isset($cw_config['content_max_tokens']) && $cw_config['content_max_tokens'] > 0 
                            ? $cw_config['content_max_tokens'] // Use the configured value
                            : 4000; // Default fallback

    $title_ai_params = [
        'temperature' => floatval($cw_config['ai_temperature'] ?? 1),
        'max_completion_tokens' => $max_tokens_for_title,
    ];

    $title_result = $ai_caller->make_standard_call(
        $cw_config['ai_provider'],
        $cw_config['ai_model'],
        [['role' => 'user', 'content' => $user_prompt_for_title]],
        $title_ai_params,
        $system_instruction_for_title
    );

    if (is_wp_error($title_result)) {
        // Enhanced error message with token information for debugging
        $error_msg = $title_result->get_error_message();
        $debug_info = " (max_tokens used: {$max_tokens_for_title})";
        return new WP_Error('title_generation_failed', 'Title generation failed: ' . $error_msg . $debug_info);
    }

    $generated_title_raw = trim($title_result['content'] ?? '');
    if (preg_match('/^"(.*)"$/', $generated_title_raw, $matches)) {
        $generated_title_raw = $matches[1];
    }
    $generated_title = trim(str_replace(["\n", "\r"], ' ', $generated_title_raw));
    $generated_title = preg_replace('/\s+/', ' ', $generated_title);

    return [
        'title' => !empty($generated_title) ? $generated_title : $final_title,
        'usage' => $title_result['usage'] ?? null
    ];
}
<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/processor/content-writing/build-content-prompt.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Cron\EventProcessor\Processor\ContentWriting;

use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_System_Instruction_Builder;
use WPAICG\ContentWriter\Prompt\AIPKit_Content_Writer_User_Prompt_Builder;
// --- ADDED: Dependencies for vector context ---
use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Core\Stream\Vector as VectorContextBuilder;

// --- END ADDED ---

if (!defined('ABSPATH')) {
    exit;
}

// --- ADDED: Dependency loader for vector context functions ---
$vector_logic_base_path = WPAICG_PLUGIN_DIR . 'classes/core/stream/vector/';
if (file_exists($vector_logic_base_path . 'fn-build-vector-search-context.php')) {
    require_once $vector_logic_base_path . 'fn-build-vector-search-context.php';
}
// --- END ADDED ---

/**
 * Builds the system instruction and user prompt for a content writing task.
 * UPDATED: Simplified to only use custom prompts, as Guided Mode has been removed. Placeholders are still replaced.
 * UPDATED: Handles new {url_content} and {source_url} placeholders.
 *
 * @param array $cw_config The specific configuration for the content writing item.
 *                         It's expected to have 'content_title' which is the *final* title,
 *                         and potentially 'inline_keywords'.
 * @return array ['system_instruction' => string, 'user_prompt' => string]
 */
function build_content_prompts_logic(array $cw_config): array
{
    // System instruction is now simpler as it doesn't need to reference guided fields.
    $system_instruction = "You are an expert content writer specializing in creating high-quality, engaging content.";
    $final_title = $cw_config['content_title'] ?? 'AI Generated Content';
    $final_keywords = !empty($cw_config['inline_keywords']) ? $cw_config['inline_keywords'] : ($cw_config['content_keywords'] ?? '');

    // --- START: NEW Vector Store Logic ---
    $vector_store_enabled = ($cw_config['enable_vector_store'] ?? '0') === '1';

    if ($vector_store_enabled) {
        $ai_caller = class_exists(AIPKit_AI_Caller::class) ? new AIPKit_AI_Caller() : null;
        $vector_store_manager = class_exists(AIPKit_Vector_Store_Manager::class) ? new AIPKit_Vector_Store_Manager() : null;

        if ($ai_caller && $vector_store_manager && function_exists('\WPAICG\Core\Stream\Vector\build_vector_search_context_logic')) {
            $vector_context = VectorContextBuilder\build_vector_search_context_logic(
                $ai_caller,
                $vector_store_manager,
                $final_title, // Use the final title as the primary query text
                $cw_config, // Pass the whole config as it contains all vector settings
                $cw_config['ai_provider'],
                null,
                $cw_config['pinecone_index_name'] ?? null,
                null,
                $cw_config['qdrant_collection_name'] ?? null,
                null
            );
            if (!empty($vector_context)) {
                $system_instruction = "## Relevant information from knowledge base:\n" . trim($vector_context) . "\n##\n\n" . $system_instruction;
            }
        }
    }
    // --- END: NEW Vector Store Logic ---


    // Get the user prompt template. The builder now only returns the custom prompt.
    $user_prompt_template = AIPKit_Content_Writer_User_Prompt_Builder::build($cw_config);

    // Add RSS description as context if it exists
    $rss_description = $cw_config['rss_description'] ?? '';
    if (!empty($rss_description)) {
        $user_prompt_template = str_replace('{description}', trim($rss_description), $user_prompt_template);
    }

    // Add URL Scraped Content as context if it exists
    $url_content = $cw_config['url_content_context'] ?? '';
    if (!empty($url_content)) {
        $user_prompt_template = str_replace('{url_content}', trim($url_content), $user_prompt_template);
    }
    $source_url = $cw_config['source_url'] ?? '';
    if (!empty($source_url)) {
        $user_prompt_template = str_replace('{source_url}', trim($source_url), $user_prompt_template);
    }

    // Replace the {topic} placeholder with the final title for the content generation step
    $user_prompt = str_replace('{topic}', $final_title, $user_prompt_template);

    // Replace the {keywords} placeholder with inline keywords if present, otherwise global keywords
    $user_prompt = str_replace('{keywords}', $final_keywords, $user_prompt);

    // Final cleanup in case placeholders were not provided for custom prompt
    $user_prompt = str_replace('{description}', '', $user_prompt);
    $user_prompt = str_replace('{url_content}', '', $user_prompt);
    $user_prompt = str_replace('{source_url}', '', $user_prompt);

    return [
        'system_instruction' => $system_instruction,
        'user_prompt' => $user_prompt,
    ];
}

<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/processor/content-writing/generate-post-helper.php
// Status: MODIFIED
// I have added the logic to include the `reasoning_effort` parameter for compatible OpenAI models.

namespace WPAICG\AutoGPT\Cron\EventProcessor\Processor\ContentWriting;

use WPAICG\Core\AIPKit_AI_Caller;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates the main post content using the AI Caller.
 *
 * @param array $prompts The array containing system_instruction and user_prompt.
 * @param array $cw_config The specific configuration for the content writing item.
 * @param AIPKit_AI_Caller $ai_caller An instance of the AI Caller.
 * @return array|WP_Error On success, returns ['content' => string, 'usage' => array|null]. On failure, returns WP_Error.
 */
function generate_post_logic(array $prompts, array $cw_config, AIPKit_AI_Caller $ai_caller): array|WP_Error
{
    $provider = $cw_config['ai_provider'];
    $model = $cw_config['ai_model'];

    $content_ai_params = [
        'temperature' => floatval($cw_config['ai_temperature'] ?? 1),
        'max_completion_tokens' => intval($cw_config['content_max_tokens'] ?? 4000)
    ];

    if (($provider ?? '') === 'OpenAI' && isset($cw_config['reasoning_effort']) && !empty($cw_config['reasoning_effort'])) {
        $model_lower = strtolower($model ?? '');
        if (strpos($model_lower, 'gpt-5') !== false || strpos($model_lower, 'o1') !== false || strpos($model_lower, 'o3') !== false || strpos($model_lower, 'o4') !== false) {
            $content_ai_params['reasoning'] = ['effort' => sanitize_key($cw_config['reasoning_effort'])];
        }
    }

    // --- ADDED: Add OpenAI vector tool configuration if applicable ---
    if ($provider === 'OpenAI' &&
        ($cw_config['enable_vector_store'] ?? '0') === '1' &&
        ($cw_config['vector_store_provider'] ?? '') === 'openai' &&
        !empty($cw_config['openai_vector_store_ids']) && is_array($cw_config['openai_vector_store_ids'])) {

        $vector_top_k = absint($cw_config['vector_store_top_k'] ?? 3);

        $content_ai_params['vector_store_tool_config'] = [
            'type'             => 'file_search',
            'vector_store_ids' => $cw_config['openai_vector_store_ids'],
            'max_num_results'  => max(1, min($vector_top_k, 20)),
        ];
    }
    // --- END ADDED ---

    $content_result = $ai_caller->make_standard_call(
        $provider,
        $model,
        [['role' => 'user', 'content' => $prompts['user_prompt']]],
        $content_ai_params,
        $prompts['system_instruction']
    );

    if (is_wp_error($content_result)) {
        return new WP_Error('content_generation_failed', 'Content generation failed: ' . $content_result->get_error_message());
    }

    $generated_content = $content_result['content'] ?? '';
    if (empty($generated_content)) {
        return new WP_Error('empty_content_response', 'AI returned empty content.');
    }

    return [
        'content' => $generated_content,
        'usage'   => $content_result['usage'] ?? null
    ];
}
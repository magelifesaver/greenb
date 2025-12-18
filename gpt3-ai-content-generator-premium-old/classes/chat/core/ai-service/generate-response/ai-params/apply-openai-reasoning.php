<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/core/ai-service/generate-response/ai-params/apply-openai-reasoning.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse\AiParams;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Applies OpenAI Reasoning parameters if the model is compatible.
 *
 * @param array &$final_ai_params Reference to the final AI parameters array to be modified.
 * @param array $bot_settings Bot settings.
 * @param string $model The selected AI model name.
 */
function apply_openai_reasoning_logic(
    array &$final_ai_params,
    array $bot_settings,
    string $model
): void {
    $reasoning_effort = $bot_settings['reasoning_effort'] ?? 'low';
    $model_lower = strtolower($model);

    // Check if the model is an o-series or gpt-5 model and the setting is not empty
    if (!empty($reasoning_effort) && (strpos($model_lower, 'gpt-5') !== false || strpos($model_lower, 'o1') !== false || strpos($model_lower, 'o3') !== false || strpos($model_lower, 'o4') !== false)) {
        $final_ai_params['reasoning'] = ['effort' => $reasoning_effort];
    }
}
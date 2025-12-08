<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/generate-title/prepare-ai-params.php
// Status: MODIFIED
// I have added a conditional check to ensure the `reasoning_effort` parameter is only added for compatible OpenAI models (gpt-5, o-series).

namespace WPAICG\ContentWriter\Ajax\Actions\GenerateTitle;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares the final AI parameters by merging global settings with form-specific overrides for title generation.
 *
 * @param array $validated_params The validated settings from the request.
 * @return array The array of AI parameter overrides.
 */
function prepare_ai_params_logic(array $validated_params): array
{
    // Use the max tokens from template/form settings, or default to 200 for title generation
    $max_tokens = isset($validated_params['content_max_tokens']) && $validated_params['content_max_tokens'] > 0 
                  ? $validated_params['content_max_tokens'] 
                  : 4000;
    
    $ai_params_override = [
        'max_completion_tokens' => $max_tokens,
    ];

    if (isset($validated_params['ai_temperature'])) {
        $ai_params_override['temperature'] = floatval($validated_params['ai_temperature']);
    }

    // Add reasoning effort to AI params if present and model is compatible
    if (($validated_params['provider'] ?? '') === 'OpenAI' && isset($validated_params['reasoning_effort']) && !empty($validated_params['reasoning_effort'])) {
        $model_lower = strtolower($validated_params['ai_model'] ?? '');
        if (strpos($model_lower, 'gpt-5') !== false || strpos($model_lower, 'o1') !== false || strpos($model_lower, 'o3') !== false || strpos($model_lower, 'o4') !== false) {
             $ai_params_override['reasoning'] = ['effort' => sanitize_key($validated_params['reasoning_effort'])];
        }
    }

    return $ai_params_override;
}
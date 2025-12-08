<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/shared/prepare-ai-params.php
// Status: MODIFIED
// I have added a conditional check to ensure the `reasoning_effort` parameter is only added for compatible OpenAI models (gpt-5, o-series).

namespace WPAICG\ContentWriter\Ajax\Actions\Shared;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prepares an array of AI parameter overrides from the submitted settings.
 * This does NOT merge with global defaults; it only prepares the override values.
 *
 * @param array $settings The validated settings from the request.
 * @return array The array of AI parameter overrides.
 */
function prepare_ai_params_logic(array $settings): array
{
    $ai_params_override = [];

    if (isset($settings['ai_temperature'])) {
        $ai_params_override['temperature'] = floatval($settings['ai_temperature']);
    }
    if (isset($settings['content_max_tokens']) && is_numeric($settings['content_max_tokens'])) {
        $ai_params_override['max_completion_tokens'] = absint($settings['content_max_tokens']);
    }
    // Add reasoning effort to AI params if present and model is compatible
    if (($settings['provider'] ?? '') === 'OpenAI' && isset($settings['reasoning_effort']) && !empty($settings['reasoning_effort'])) {
        $model_lower = strtolower($settings['ai_model'] ?? '');
        if (strpos($model_lower, 'gpt-5') !== false || strpos($model_lower, 'o1') !== false || strpos($model_lower, 'o3') !== false || strpos($model_lower, 'o4') !== false) {
            $ai_params_override['reasoning'] = ['effort' => sanitize_key($settings['reasoning_effort'])];
        }
    }


    return $ai_params_override;
}
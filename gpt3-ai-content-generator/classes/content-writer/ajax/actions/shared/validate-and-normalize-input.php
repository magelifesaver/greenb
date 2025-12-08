<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/shared/validate-and-normalize-input.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions\Shared;

use WPAICG\ContentWriter\Ajax\AIPKit_Content_Writer_Base_Ajax_Action;
use WPAICG\AIPKit_Providers;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validates the input for content generation AJAX actions.
 * UPDATED: Simplified to remove guided mode fields.
 *
 * @param AIPKit_Content_Writer_Base_Ajax_Action $handler The handler instance.
 * @param array $settings The raw POST data.
 * @return array|WP_Error An array of validated parameters or a WP_Error on failure.
 */
function validate_and_normalize_input_logic(AIPKit_Content_Writer_Base_Ajax_Action $handler, array $settings): array|WP_Error
{
    $permission_check = $handler->check_module_access_permissions('content-writer', 'aipkit_content_writer_nonce');
    if (is_wp_error($permission_check)) {
        return $permission_check;
    }

    $content_title_raw = isset($settings['content_title']) ? sanitize_text_field(wp_unslash($settings['content_title'])) : '';
    if (empty($content_title_raw)) {
        return new WP_Error('missing_title', __('Content title/topic is required.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    // --- START: Parse title and keywords ---
    $topic = $content_title_raw;
    $inline_keywords = '';
    if (strpos($content_title_raw, '|') !== false) {
        $parts = explode('|', $content_title_raw, 2);
        $topic = trim($parts[0]);
        $inline_keywords = isset($parts[1]) ? trim($parts[1]) : ''; // Only take the second part as keywords
    }
    // --- END: Parse ---

    $provider_raw = isset($settings['ai_provider']) && !empty($settings['ai_provider'])
                   ? sanitize_text_field($settings['ai_provider'])
                   : AIPKit_Providers::get_current_provider();

    $provider = match(strtolower($provider_raw)) {
        'openai' => 'OpenAI',
        'openrouter' => 'OpenRouter',
        'google' => 'Google',
        'azure' => 'Azure',
        'deepseek' => 'DeepSeek',
        'ollama' => 'Ollama',
        default => $provider_raw
    };

    $model_data = AIPKit_Providers::get_provider_data($provider);
    $model = isset($settings['ai_model']) && !empty($settings['ai_model'])
             ? sanitize_text_field($settings['ai_model'])
             : ($model_data['model'] ?? '');

    if (empty($model)) {
        return new WP_Error('missing_model', __('AI model selection is required.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    $validated_params = $settings;
    $validated_params['content_title'] = $topic; // Use parsed topic
    $validated_params['inline_keywords'] = $inline_keywords; // Add parsed keywords
    $validated_params['provider'] = $provider;
    $validated_params['model'] = $model;

    return $validated_params;
}

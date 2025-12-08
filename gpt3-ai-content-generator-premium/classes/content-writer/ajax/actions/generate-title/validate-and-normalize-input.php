<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/generate-title/validate-and-normalize-input.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions\GenerateTitle;

use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Generate_Title_Action;
use WPAICG\AIPKit_Providers;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validates the input for the title generation AJAX action.
 *
 * @param AIPKit_Content_Writer_Generate_Title_Action $handler The handler instance.
 * @return array|WP_Error An array of validated parameters or a WP_Error on failure.
 */
function validate_and_normalize_input_logic(AIPKit_Content_Writer_Generate_Title_Action $handler): array|WP_Error
{
    $permission_check = $handler->check_module_access_permissions('content-writer', 'aipkit_content_writer_nonce');
    if (is_wp_error($permission_check)) {
        return $permission_check;
    }

    if (!$handler->get_ai_caller()) {
        return new WP_Error('ai_caller_missing', __('AI processing component is unavailable.', 'gpt3-ai-content-generator'), ['status' => 500]);
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in check_module_access_permissions method.
    $settings = isset($_POST) ? wp_unslash($_POST) : [];
    $original_title = isset($settings['content_title']) ? sanitize_text_field(wp_unslash($settings['content_title'])) : '';

    if (empty($original_title)) {
        return new WP_Error('missing_original_title', __('Original title/topic is required to generate a new title.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    // --- START: Parse title and keywords ---
    $topic = $original_title;
    $inline_keywords = '';
    if (strpos($original_title, '|') !== false) {
        $parts = explode('|', $original_title, 2);
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
        return new WP_Error('missing_model_title_gen', __('AI model selection is required for title generation.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    // Sanitize other used fields that might be passed in
    $settings['ai_temperature'] = isset($settings['ai_temperature']) ? floatval($settings['ai_temperature']) : 1.0;
    $settings['custom_title_prompt'] = isset($settings['custom_title_prompt']) ? sanitize_textarea_field($settings['custom_title_prompt']) : '';
    
    // Extract max tokens parameter for title generation
    $settings['content_max_tokens'] = isset($settings['content_max_tokens']) ? intval($settings['content_max_tokens']) : null;


    // Return the full set of validated and normalized parameters
    $validated_params = $settings;
    $validated_params['content_title'] = $topic; // Use parsed topic
    $validated_params['inline_keywords'] = $inline_keywords; // Add parsed keywords
    $validated_params['provider'] = $provider;
    $validated_params['model'] = $model;

    return $validated_params;
}

<?php

// File: classes/chat/core/ai-service/generate-response/validate-request.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Validates the initial request parameters for generate_response.
 *
 * @param \WPAICG\Core\AIPKit_AI_Caller|null $ai_caller Instance of AI Caller.
 * @param string $user_message The user's input message.
 * @param array|null $image_inputs_for_service Optional array of image data.
 * @param array $bot_settings Settings for the specific bot.
 * @return true|WP_Error True if valid, WP_Error otherwise.
 */
function validate_request_logic(
    ?\WPAICG\Core\AIPKit_AI_Caller $ai_caller,
    string $user_message,
    ?array $image_inputs_for_service,
    array $bot_settings
): bool|WP_Error {
    if (!$ai_caller) {
        return new WP_Error('ai_caller_missing_validation', 'AI Caller component is not available for request validation.');
    }
    if (empty($user_message) && empty($image_inputs_for_service)) {
        return new WP_Error('empty_content_validation', __('User message or image cannot be empty.', 'gpt3-ai-content-generator'));
    }
    if (empty($bot_settings['bot_id'])) {
        return new WP_Error('missing_bot_id_validation', __('Bot ID is missing in settings for request validation.', 'gpt3-ai-content-generator'));
    }
    return true;
}

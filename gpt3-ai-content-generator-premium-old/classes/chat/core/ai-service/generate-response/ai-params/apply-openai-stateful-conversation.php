<?php

// File: classes/chat/core/ai-service/generate-response/ai-params/apply-openai-stateful-conversation.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse\AiParams;

use WPAICG\AIPKit_Providers; // For checking provider data existence if needed, though not used in this specific logic.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Applies OpenAI stateful conversation parameters.
 *
 * @param array &$final_ai_params Reference to the final AI parameters array to be modified.
 * @param array &$messages_payload_ref Reference to the messages payload array (can be modified).
 * @param array $bot_settings Bot settings.
 * @param string|null $frontend_previous_openai_response_id Previous OpenAI response ID from frontend.
 * @param string|null $last_openai_response_id_from_history Last OpenAI response ID from history.
 * @return string|null The actual previous response ID used, or null.
 */
function apply_openai_stateful_conversation_logic(
    array &$final_ai_params,
    array &$messages_payload_ref,
    array $bot_settings,
    ?string $frontend_previous_openai_response_id,
    ?string $last_openai_response_id_from_history
): ?string {
    $actual_previous_response_id_to_use = null;
    $use_openai_conv_state = ($bot_settings['openai_conversation_state_enabled'] ?? '0') === '1';

    if ($use_openai_conv_state) {
        $final_ai_params['use_openai_conversation_state'] = true;
        if (!empty($frontend_previous_openai_response_id)) {
            $actual_previous_response_id_to_use = $frontend_previous_openai_response_id;
        } elseif (!empty($last_openai_response_id_from_history)) {
            $actual_previous_response_id_to_use = $last_openai_response_id_from_history;
        }

        if ($actual_previous_response_id_to_use !== null) {
            $final_ai_params['previous_response_id'] = $actual_previous_response_id_to_use;
            $latest_user_message_obj = end($messages_payload_ref);
            if ($latest_user_message_obj && ($latest_user_message_obj['role'] === 'user')) {
                $messages_payload_ref = [$latest_user_message_obj];
            }
        }
    }
    return $actual_previous_response_id_to_use;
}

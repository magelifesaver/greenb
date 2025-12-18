<?php

// File: classes/chat/core/ai-service/generate-response/prepare-messages-for-api.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares the messages array for the API call and extracts relevant info for stateful OpenAI.
 *
 * @param array $history Conversation history.
 * @param string $user_message_text The latest user message.
 * @return array Contains 'messages_payload', 'latest_user_message_obj_for_stateful', 'last_openai_response_id_from_history'.
 */
function prepare_messages_for_api_logic(array $history, string $user_message_text): array
{
    $messages_payload = [];
    $latest_user_message_obj_for_stateful = null;
    $last_openai_response_id_from_history = null;

    foreach ($history as $msg) {
        $role = ($msg['role'] === 'bot') ? 'assistant' : $msg['role'];
        $content = isset($msg['content']) ? trim($msg['content']) : '';
        if ($content !== '' && in_array($role, ['system', 'user', 'assistant'])) {
            $messages_payload[] = ['role' => $role, 'content' => $content];
            if ($role === 'user' && $msg['content'] === $user_message_text) { // Assuming history includes the current user message for this logic
                $latest_user_message_obj_for_stateful = ['role' => 'user', 'content' => $content];
            }
        }
        if (($role === 'assistant' || $role === 'bot') && isset($msg['openai_response_id']) && !empty($msg['openai_response_id'])) {
            $last_openai_response_id_from_history = $msg['openai_response_id'];
        }
    }
    // Note: The original AIService::generate_response adds the current user message to history
    // *after* limiting it, then loops through this potentially modified history.
    // For this refactor, this function receives the already limited $history.
    // The current $user_message_text is the latest message from the user.
    // The AI_Caller will typically add the latest user message to the final payload.
    // This function here primarily formats the existing $history for the API.
    // Let's adjust to assume $history is the *previous* history, and add $user_message_text here.
    // No, the original AIService directly adds $user_message as the last item to the history array for some providers,
    // and for OpenAI stateful, it uses only the latest user message.
    // The AIPKit_AI_Caller's format_chat_completions_payload actually expects the latest user message separately.
    // Let's stick to what AIPKit_AI_Caller expects for $messages_payload (which is history + latest user message).
    // This means this function should append the current user message to the $messages_payload.
    if (!empty($user_message_text)) {
        $messages_payload[] = ['role' => 'user', 'content' => $user_message_text];
        $latest_user_message_obj_for_stateful = ['role' => 'user', 'content' => $user_message_text];
    }


    return [
        'messages_payload' => $messages_payload,
        'latest_user_message_obj_for_stateful' => $latest_user_message_obj_for_stateful,
        'last_openai_response_id_from_history' => $last_openai_response_id_from_history
    ];
}

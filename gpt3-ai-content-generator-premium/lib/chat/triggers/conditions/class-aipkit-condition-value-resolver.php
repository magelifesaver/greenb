<?php

namespace WPAICG\Lib\Chat\Triggers\Conditions; // UPDATED Namespace

use WP_Error; // Not strictly needed as it returns bool, but good for context.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Condition_Value_Resolver
 *
 * Retrieves the actual value from context data based on condition type and field.
 */
class AIPKit_Condition_Value_Resolver {

    /**
     * Retrieves the actual value from the context data.
     *
     * @param string $condition_type Condition type (e.g., 'user_context', 'text_content').
     * @param string $field          Field name (e.g., 'is_logged_in', 'user_message_text').
     * @param array  $context_data   The context data array.
     * @return mixed|WP_Error The actual value from context, or WP_Error if field not found/supported.
     */
    public function get_value(string $condition_type, string $field, array $context_data) {
        switch ($condition_type) {
            case 'user_context':
                if ($field === 'is_logged_in') {
                    return isset($context_data['user_id']) && (int)$context_data['user_id'] > 0;
                } elseif ($field === 'user_role') {
                    return $context_data['user_roles'] ?? ['guest'];
                } elseif ($field === 'user_message_text') { // MODIFIED: Allow user_message_text under user_context
                    return $context_data['user_message_text'] ?? '';
                }
                break;
            case 'text_content':
                if ($field === 'user_message_text') { // MODIFIED: Keep original behavior
                    return $context_data['user_message_text'] ?? '';
                }
                break;
            case 'conversation_state':
                if ($field === 'message_count') { // MODIFIED: Change logic to count user messages and handle history inconsistency
                    $history = $context_data['current_history'] ?? [];
                    $user_message_text = $context_data['user_message_text'] ?? null;
                    $history_for_counting = $history;
                    if ($user_message_text !== null && !empty($history_for_counting)) {
                        $last_message = end($history_for_counting);
                        if (($last_message['role'] ?? '') === 'user' && ($last_message['content'] ?? '') === $user_message_text) {
                            array_pop($history_for_counting);
                        }
                    }
                    $user_message_count = 0;
                    foreach ($history_for_counting as $msg) {
                        if (isset($msg['role']) && $msg['role'] === 'user') {
                            $user_message_count++;
                        }
                    }
                    return $user_message_count;
                }
                break;
            case 'ai_model_context':
                if ($field === 'current_provider') {
                    return $context_data['current_provider'] ?? null;
                } elseif ($field === 'current_model_id') {
                    return $context_data['current_model_id'] ?? null;
                }
                break;
            default:
                return new WP_Error('unknown_condition_type', "Unknown condition type: {$condition_type}");
        }
        return new WP_Error('unknown_condition_field', "Unknown field '{$field}' for condition type '{$condition_type}'");
    }
}
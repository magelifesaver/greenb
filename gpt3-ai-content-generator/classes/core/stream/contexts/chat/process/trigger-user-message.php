<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/chat/process/trigger-user-message.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Contexts\Chat\Process;

use WPAICG\Chat\Storage\LogStorage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Processes 'user_message_received' triggers.
 *
 * @param array $context Context data including bot_id, bot_settings, user_id, session_id, etc.
 *                       Also includes `user_message_text`, `final_history_for_ai`,
 *                       and `system_instruction_for_ai` which may have been modified
 *                       by `session_started` triggers.
 * @return array|WP_Error Result of trigger processing or WP_Error if blocked/direct reply.
 */
function process_user_message_trigger_logic(array $context): array|WP_Error
{
    $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
    $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';
    $trigger_function_name = '\WPAICG\Lib\Chat\Triggers\process_chat_triggers'; // This function is in lib/chat/triggers/trigger_handler.php
    $triggers_addon_active = false;

    if (class_exists('\WPAICG\aipkit_dashboard')) {
        $triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
    }

    // Prepare a neutral result that reflects the input context if no triggers modify it.
    // --- MODIFIED: Use correct keys from $context ---
    $neutral_result = [
        'status' => 'processed',
        'message_to_user' => null,
        'message_id' => null,
        'modified_context_data' => [
            // These are the keys expected by the AI request builder later
            'system_instruction' => $context['system_instruction_for_ai'],
            'user_message_text'  => $context['user_message_text'],
            'current_history'    => $context['final_history_for_ai'], // Use final_history_for_ai from input context
        ],
        'stop_ai_processing' => false,
        'display_form_event_data' => null,
    ];
    // --- END MODIFICATION ---

    if (!$triggers_addon_active || !class_exists($trigger_storage_class) || !class_exists($trigger_manager_class) || !function_exists($trigger_function_name)) {
        return $neutral_result; // No triggers to run or components missing
    }

    // Data to pass to the global trigger processor `process_chat_triggers`
    // This uses specific keys that `process_chat_triggers` expects for 'user_message_received'
    // --- MODIFIED: Use correct keys from $context and calculate message_count ---
    $message_count_for_trigger = count($context['final_history_for_ai']);
    $user_message_trigger_context_data = [
        'event_type'        => 'user_message_received',
        'user_id'           => $context['user_id'],
        'session_id'        => $context['session_id'],
        'bot_id'            => $context['bot_id'],
        'bot_settings'      => $context['bot_settings'],
        'user_message_text' => $context['user_message_text'],
        'current_history'   => $context['final_history_for_ai'], // Use final_history_for_ai
        'client_ip'         => $context['client_ip'],
        'post_id'           => $context['post_id'],
        'message_count'     => $message_count_for_trigger, // Calculated count
        'system_instruction'=> $context['system_instruction_for_ai'],
        'user_roles'        => $context['user_wp_role'] ? array_map('trim', explode(',', $context['user_wp_role'])) : ['guest'],
        'current_provider'  => $context['current_provider'],
        'current_model_id'  => $context['current_model_id'],
        'base_log_data'     => $context['base_log_data'],
        'log_storage'       => $context['log_storage']
    ];
    // --- END MODIFICATION ---

    // $trigger_function_name is \WPAICG\Lib\Chat\Triggers\process_chat_triggers
    $user_message_trigger_result = $trigger_function_name($user_message_trigger_context_data);

    // Handle blocking actions or direct replies from triggers
    if ($user_message_trigger_result['status'] === 'blocked') {
        $block_message_id = $user_message_trigger_result['message_id'] ?? ('trigger-block-' . uniqid());
        return new WP_Error('trigger_blocked', $user_message_trigger_result['message_to_user'] ?? __('Message blocked.', 'gpt3-ai-content-generator'), ['status' => 400, 'is_trigger_response' => true, 'message_id' => $block_message_id]);
    }
    if (isset($user_message_trigger_result['display_form_event_data']) && is_array($user_message_trigger_result['display_form_event_data'])) {
        return new WP_Error('trigger_display_form', __('Form display requested by trigger.', 'gpt3-ai-content-generator'), ['status' => 200, 'display_form_event_data' => $user_message_trigger_result['display_form_event_data']]);
    }
    if (isset($user_message_trigger_result['message_to_user']) && ($user_message_trigger_result['stop_ai_processing'] ?? false)) {
        $trigger_reply_message_id_user = $user_message_trigger_result['message_id'] ?? ('trigger-reply-' . uniqid());
        return new WP_Error('trigger_direct_reply', $user_message_trigger_result['message_to_user'], ['status' => 200, 'is_trigger_response' => true, 'message_id' => $trigger_reply_message_id_user]);
    }

    // If not blocked or direct replied, update the neutral_result with the outcome
    $neutral_result['status'] = $user_message_trigger_result['status']; // Should still be 'processed'
    $neutral_result['message_to_user'] = $user_message_trigger_result['message_to_user'] ?? $neutral_result['message_to_user'];
    $neutral_result['message_id'] = $user_message_trigger_result['message_id'] ?? $neutral_result['message_id'];
    $neutral_result['stop_ai_processing'] = $user_message_trigger_result['stop_ai_processing'] ?? $neutral_result['stop_ai_processing'];
    $neutral_result['display_form_event_data'] = $user_message_trigger_result['display_form_event_data'] ?? $neutral_result['display_form_event_data'];

    // Apply modifications from triggers to the context that will be passed to the AI
    // The keys in $user_message_trigger_result['modified_context_data'] are 'system_instruction', 'user_message_text', 'current_history'
    // --- MODIFIED: Use correct keys from input $context for fallbacks ---
    $neutral_result['modified_context_data']['system_instruction'] = $user_message_trigger_result['modified_context_data']['system_instruction'] ?? $context['system_instruction_for_ai'];
    $neutral_result['modified_context_data']['user_message_text']  = $user_message_trigger_result['modified_context_data']['user_message_text'] ?? $context['user_message_text'];
    $neutral_result['modified_context_data']['current_history']    = $user_message_trigger_result['modified_context_data']['current_history'] ?? $context['final_history_for_ai'];
    // --- END MODIFICATION ---

    return $neutral_result;
}
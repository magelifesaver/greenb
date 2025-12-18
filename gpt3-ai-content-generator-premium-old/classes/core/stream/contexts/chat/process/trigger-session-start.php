<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/chat/process/trigger-session-start.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Contexts\Chat\Process;

use WP_Error;

// Dependencies like Trigger_Storage, Trigger_Manager, process_chat_triggers
// are checked for existence within this logic file before use.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the 'session_started' trigger event processing.
 *
 * @param array $context An associative array containing all necessary parameters:
 *    'user_id', 'session_id', 'bot_id', 'bot_settings', 'client_ip', 'post_id',
 *    'user_message_text' (first user message), 'system_instruction_for_ai' (current system instruction),
 *    'user_wp_role', 'current_provider', 'current_model_id', 'base_log_data', 'log_storage'.
 * @return array|WP_Error The result of trigger processing, which could be a WP_Error
 *                        if the trigger blocks or sends a direct reply, or an array
 *                        with potentially modified context data.
 *                        Structure of array: ['status', 'message_to_user', 'message_id', 'modified_context_data', 'stop_ai_processing', 'display_form_event_data']
 */
function process_session_start_trigger_logic(array $context): array|WP_Error
{
    $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
    $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';
    $trigger_function_name = '\WPAICG\Lib\Chat\Triggers\process_chat_triggers';
    $triggers_addon_active = false;

    if (class_exists('\WPAICG\aipkit_dashboard')) {
        $triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
    }

    $neutral_result = [
        'status' => 'processed',
        'message_to_user' => null,
        'message_id' => null,
        'modified_context_data' => [
            'system_instruction' => $context['system_instruction_for_ai'],
            'user_message_text' => $context['user_message_text'],
            'current_history' => [], // History is empty for session_started
        ],
        'stop_ai_processing' => false,
        'display_form_event_data' => null,
    ];

    if (!$triggers_addon_active || !class_exists($trigger_storage_class) || !class_exists($trigger_manager_class) || !function_exists($trigger_function_name)) {
        return $neutral_result; // No triggers to run or components missing
    }

    $session_trigger_context_data = [
        'event_type'        => 'session_started',
        'user_id'           => $context['user_id'],
        'session_id'        => $context['session_id'],
        'bot_id'            => $context['bot_id'],
        'bot_settings'      => $context['bot_settings'],
        'client_ip'         => $context['client_ip'],
        'post_id'           => $context['post_id'],
        'user_message_text' => $context['user_message_text'], // First user message
        'current_history'   => [], // History is empty for session_started event itself
        'message_count'     => 0,
        'system_instruction' => $context['system_instruction_for_ai'],
        'user_roles'        => $context['user_wp_role'] ? array_map('trim', explode(',', $context['user_wp_role'])) : ['guest'],
        'current_provider'  => $context['current_provider'],
        'current_model_id'  => $context['current_model_id'],
        'base_log_data'     => $context['base_log_data'],
        'log_storage'       => $context['log_storage']
    ];

    // Call the global trigger processing function
    $session_trigger_result = $trigger_function_name($session_trigger_context_data);

    // If trigger blocks or sends a direct reply, return that result as WP_Error
    if ($session_trigger_result['status'] === 'blocked') {
        $block_message_id = $session_trigger_result['message_id'] ?? ('trigger-session-block-' . uniqid());
        return new WP_Error('trigger_blocked', $session_trigger_result['message_to_user'] ?? __('Session blocked by system.', 'gpt3-ai-content-generator'), ['status' => 400, 'is_trigger_response' => true, 'message_id' => $block_message_id]);
    }
    if (isset($session_trigger_result['display_form_event_data']) && is_array($session_trigger_result['display_form_event_data'])) {
        return new WP_Error('trigger_display_form', __('Form display requested by trigger.', 'gpt3-ai-content-generator'), ['status' => 200, 'display_form_event_data' => $session_trigger_result['display_form_event_data']]);
    }
    if (isset($session_trigger_result['message_to_user']) && ($session_trigger_result['stop_ai_processing'] ?? false)) {
        $trigger_reply_message_id_session = $session_trigger_result['message_id'] ?? ('trigger-session-reply-' . uniqid());
        return new WP_Error('trigger_direct_reply', $session_trigger_result['message_to_user'], ['status' => 200, 'is_trigger_response' => true, 'message_id' => $trigger_reply_message_id_session]);
    }

    // Return the full result from trigger processing, including modified context
    return $session_trigger_result;
}

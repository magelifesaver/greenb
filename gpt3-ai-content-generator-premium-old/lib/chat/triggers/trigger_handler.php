<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/triggers/trigger_handler.php
// Status: MODIFIED

namespace WPAICG\Lib\Chat\Triggers; // UPDATED Namespace

use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager;
use WPAICG\Chat\Storage\LogStorage; // ADDED: Import LogStorage
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handle trigger processing for chatbot conversations.
 * This function was moved from classes/chat/core/ajax-processor/trigger_handler.php
 *
 * @param array $params Associative array containing all necessary parameters for trigger processing.
 * @return array Result data with AI input values and status information.
 */
function process_chat_triggers(array $params): array {
    // Extract parameters from the passed array
    $event_type          = $params['event_type'] ?? 'user_message_received';
    $user_id             = $params['user_id'] ?? null;
    $session_id          = $params['session_id'] ?? '';
    $bot_id              = $params['bot_id'] ?? 0;
    $bot_settings        = $params['bot_settings'] ?? [];
    $client_ip           = $params['client_ip'] ?? null;
    $post_id             = $params['post_id'] ?? 0;
    $user_message_text   = $params['user_message_text'] ?? '';
    $system_instruction  = $params['system_instruction'] ?? '';
    $current_history     = $params['current_history'] ?? [];
    $message_count       = $params['message_count'] ?? 0;
    $user_roles          = $params['user_roles'] ?? ['guest'];
    $current_provider    = $params['current_provider'] ?? null;
    $current_model_id    = $params['current_model_id'] ?? null;
    $base_log_data       = $params['base_log_data'] ?? [];
    $log_storage         = $params['log_storage'] ?? null; // Get LogStorage instance
    $is_new_session      = $params['is_new_session'] ?? false; // Added for session_started trigger

    // Initialize the structure that will be returned by this function
    $final_result_from_triggers = [
        'status' => 'processed', // Default status
        'modified_context_data' => [ // Initialize with incoming context
            'user_message_text' => $user_message_text,
            'system_instruction' => $system_instruction,
            'current_history' => $current_history
        ],
        'stop_ai_processing' => false,
        'message_to_user' => null,
        'message_id' => null,
        'display_form_event_data' => null, // Ensure this key is present
    ];

    $trigger_manager = null;
    $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
    $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';

    $triggers_addon_active = false;
    if (class_exists('\WPAICG\aipkit_dashboard')) {
        $triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
    }

    if (!$triggers_addon_active || !class_exists($trigger_storage_class) || !class_exists($trigger_manager_class)) {
        return $final_result_from_triggers; // Triggers not active or classes not found
    }
    
    $trigger_storage = new $trigger_storage_class();
    // --- MODIFIED: Pass $log_storage to TriggerManager constructor ---
    $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage);
    // --- END MODIFICATION ---

    // --- Start Trigger Processing ---
    if ($is_new_session) {
        $session_trigger_context_data = [
            'user_id'           => $user_id,
            'session_id'        => $session_id,
            'bot_id'            => $bot_id,
            'bot_settings'      => $bot_settings,
            'user_message_text' => $user_message_text, 
            'current_history'   => [], 
            'client_ip'         => $client_ip,
            'post_id'           => $post_id,
            'message_count'     => 0,
            'system_instruction'=> $final_result_from_triggers['modified_context_data']['system_instruction'],
            'user_roles'        => $user_roles,
            'current_provider'  => $current_provider,
            'current_model_id'  => $current_model_id,
            'base_log_data'     => $base_log_data,
            'log_storage'       => $log_storage,
        ];
        $session_event_result = $trigger_manager->process_event($bot_id, 'session_started', $session_trigger_context_data);
        $final_result_from_triggers = array_merge($final_result_from_triggers, $session_event_result);

        if ($final_result_from_triggers['status'] === 'blocked' || 
            (isset($final_result_from_triggers['message_to_user']) && ($final_result_from_triggers['stop_ai_processing'] ?? false)) ||
            (isset($final_result_from_triggers['display_form_event_data']) && is_array($final_result_from_triggers['display_form_event_data']))
        ) {
            return $final_result_from_triggers;
        }
    }

    $user_message_trigger_context_data = [
        'user_id'           => $user_id,
        'session_id'        => $session_id,
        'bot_id'            => $bot_id,
        'bot_settings'      => $bot_settings,
        'user_message_text' => $final_result_from_triggers['modified_context_data']['user_message_text'], 
        'current_history'   => $final_result_from_triggers['modified_context_data']['current_history'],   
        'client_ip'         => $client_ip,
        'post_id'           => $post_id,
        'message_count'     => $message_count, 
        'system_instruction'=> $final_result_from_triggers['modified_context_data']['system_instruction'], 
        'user_roles'        => $user_roles,
        'current_provider'  => $current_provider,
        'current_model_id'  => $current_model_id,
        'base_log_data'     => $base_log_data,
        'log_storage'       => $log_storage,
        'form_id'           => $params['form_id'] ?? null, 
        'submitted_data'    => $params['submitted_data'] ?? null, 
    ];
    $user_message_event_result = $trigger_manager->process_event($bot_id, 'user_message_received', $user_message_trigger_context_data);
    $final_result_from_triggers = array_merge($final_result_from_triggers, $user_message_event_result);
    // --- End Trigger Processing ---

    return $final_result_from_triggers;
}
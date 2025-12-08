<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/triggers/manager/event-processor/log_trigger_event.php
// Status: NEW FILE

namespace WPAICG\Lib\Chat\Triggers\Manager\EventProcessorMethods;

use WPAICG\Chat\Storage\LogStorage;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logs a trigger processing event.
 * This logic was moved from AIPKit_Trigger_Event_Processor::log_trigger_event().
 *
 * @param LogStorage|null $log_storage Instance of LogStorage for logging.
 * @param array $base_log_data Base data for the log entry.
 * @param string $log_subtype Specific subtype for trigger_log_details.
 * @param string $content Human-readable summary.
 * @param array $details Specific details for trigger_log_details.
 */
function log_trigger_event_logic(?LogStorage $log_storage, array $base_log_data, string $log_subtype, string $content, array $details): void {
    if (!$log_storage) {
        return;
    }

    // Ensure essential base log data is present for ConversationLogger
    $required_base_keys = ['bot_id', 'user_id', 'session_id', 'conversation_uuid', 'module', 'is_guest', 'ip_address', 'role'];
    foreach ($required_base_keys as $key) {
        if (!array_key_exists($key, $base_log_data)) {
            // Provide a default or null if a key is missing to prevent PHP notices
            $base_log_data[$key] = ($key === 'is_guest') ? 1 : null;
        }
    }
    
    $log_entry = array_merge($base_log_data, [
        'message_role'   => 'system',
        'event_sub_type' => 'trigger_log',
        'message_content'=> $content, // Human-readable summary
        'timestamp'      => time(),
        'trigger_log_details' => array_merge(['log_subtype' => $log_subtype], $details)
    ]);
    
    $log_storage->log_message($log_entry);
}
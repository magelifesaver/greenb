<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/logger/build-message-object.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage\LoggerMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Builds the new message object for logging.
 * UPDATED: Moved request_payload and response_data handling to be general.
 * UPDATED: Add provider, model, usage, feedback, and OpenAI/Google specific fields if present in log_data, regardless of role.
 * ADDED: Handling for 'system' role with 'trigger_log' event_sub_type to store detailed trigger log data.
 * FIXED: Ensure 'form_submission_stored' subtype correctly logs 'form_id' and 'submitted_data_snapshot'.
 *
 * @param array $log_data Associative array containing message details.
 * @param string $message_id The generated or provided message ID.
 * @param int $current_timestamp The current timestamp for the message.
 * @return array The structured message object.
 */
function build_message_object_logic(array $log_data, string $message_id, int $current_timestamp): array {
    $new_message = [
        'message_id'=> $message_id,
        'role'      => sanitize_key($log_data['message_role']),
        'content'   => wp_kses_post($log_data['message_content']), // wp_kses_post for message content
        'timestamp' => $current_timestamp,
    ];

    // --- NEW: Handle 'trigger_log' event_sub_type ---
    if ($new_message['role'] === 'system' && isset($log_data['event_sub_type']) && $log_data['event_sub_type'] === 'trigger_log') {
        $new_message['event_sub_type'] = 'trigger_log'; // Explicitly store this sub-type

        // Populate trigger_log_details if provided in $log_data
        if (isset($log_data['trigger_log_details']) && is_array($log_data['trigger_log_details'])) {
            $trigger_details = $log_data['trigger_log_details'];
            $sanitized_details = [];
            // Always copy common fields
            $sanitized_details['log_subtype'] = isset($trigger_details['log_subtype']) ? sanitize_key($trigger_details['log_subtype']) : 'unknown';
            $sanitized_details['trigger_id'] = isset($trigger_details['trigger_id']) ? sanitize_key($trigger_details['trigger_id']) : 'unknown';
            $sanitized_details['trigger_name'] = isset($trigger_details['trigger_name']) ? sanitize_text_field($trigger_details['trigger_name']) : 'Unnamed Trigger';
            $sanitized_details['event_name_processed'] = isset($trigger_details['event_name_processed']) ? sanitize_key($trigger_details['event_name_processed']) : 'unknown_event';

            // Subtype-specific fields
            if ($sanitized_details['log_subtype'] === 'trigger_evaluation') {
                $sanitized_details['conditions_met'] = isset($trigger_details['conditions_met']) ? (bool)$trigger_details['conditions_met'] : false;
                if (isset($trigger_details['conditions_summary'])) {
                    $sanitized_details['conditions_summary'] = sanitize_text_field($trigger_details['conditions_summary']);
                }
            } elseif ($sanitized_details['log_subtype'] === 'action_execution_start') {
                $sanitized_details['action_type'] = isset($trigger_details['action_type']) ? sanitize_key($trigger_details['action_type']) : 'unknown_action';
                if (isset($trigger_details['action_payload_summary'])) {
                    // Keep payload summary as an array/object, it will be JSON encoded later
                    $sanitized_details['action_payload_summary'] = $trigger_details['action_payload_summary'];
                }
            } elseif ($sanitized_details['log_subtype'] === 'action_execution_result') {
                $sanitized_details['action_type'] = isset($trigger_details['action_type']) ? sanitize_key($trigger_details['action_type']) : 'unknown_action';
                $sanitized_details['status'] = isset($trigger_details['status']) ? sanitize_key($trigger_details['status']) : 'unknown';
                if (isset($trigger_details['result_summary'])) {
                    $sanitized_details['result_summary'] = sanitize_text_field($trigger_details['result_summary']);
                }
                if (isset($trigger_details['error_details'])) {
                    $sanitized_details['error_details'] = sanitize_text_field($trigger_details['error_details']);
                }
            } elseif ($sanitized_details['log_subtype'] === 'form_submission_stored') {
                if (isset($trigger_details['form_id'])) {
                    $sanitized_details['form_id'] = sanitize_text_field($trigger_details['form_id']);
                }
                if (isset($trigger_details['submitted_data_snapshot'])) {
                    // submitted_data_snapshot is an array, it will be JSON encoded by the logger.
                    // No complex sanitization needed here as it's structured data for logging.
                    $sanitized_details['submitted_data_snapshot'] = $trigger_details['submitted_data_snapshot'];
                }
            }
            $new_message['trigger_log_details'] = $sanitized_details;
        }
        return $new_message; // Return early for trigger logs
    }
    // --- END NEW ---


    // Add these fields if they exist in log_data, regardless of role (for user/bot messages)
    if (isset($log_data['ai_provider']) && !empty($log_data['ai_provider'])) {
        $new_message['provider'] = sanitize_text_field($log_data['ai_provider']);
    }
    if (isset($log_data['ai_model']) && !empty($log_data['ai_model'])) {
        $new_message['model'] = sanitize_text_field($log_data['ai_model']);
    }
    if (isset($log_data['usage']) && is_array($log_data['usage'])) {
        $new_message['usage'] = $log_data['usage']; // Assume usage data is safe
    }
    if (isset($log_data['feedback'])) {
        $new_message['feedback'] = sanitize_key($log_data['feedback']);
    }
    if (isset($log_data['request_payload'])) {
        $new_message['request_payload'] = $log_data['request_payload'];
    }
    if (isset($log_data['response_data'])) {
        $new_message['response_data'] = $log_data['response_data'];
    }
    // Store OpenAI specific IDs
    if (isset($log_data['openai_response_id']) && !empty($log_data['openai_response_id'])) {
        $new_message['openai_response_id'] = sanitize_text_field($log_data['openai_response_id']);
    }
    if (isset($log_data['used_previous_response_id']) && $log_data['used_previous_response_id'] === true) {
        $new_message['used_previous_response_id'] = true;
    }
    // Store Google Grounding Metadata
    if (isset($log_data['grounding_metadata']) && is_array($log_data['grounding_metadata'])) {
        $new_message['grounding_metadata'] = $log_data['grounding_metadata'];
    }
    // Store Vector Search Scores
    if (isset($log_data['vector_search_scores']) && is_array($log_data['vector_search_scores']) && !empty($log_data['vector_search_scores'])) {
        $new_message['vector_search_scores'] = $log_data['vector_search_scores'];
    }

    return $new_message;
}
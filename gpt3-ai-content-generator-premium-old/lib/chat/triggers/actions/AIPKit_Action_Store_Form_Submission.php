<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/triggers/actions/AIPKit_Action_Store_Form_Submission.php
// Status: MODIFIED

namespace WPAICG\Lib\Chat\Triggers\Actions; // UPDATED Namespace

use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor;
use WPAICG\Chat\Storage\LogStorage; // Use the LogStorage facade from the main plugin
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Action_Store_Form_Submission
 *
 * Handles the 'store_form_submission' trigger action.
 * This action logs the submitted form data.
 * MODIFIED: Changed default log message template and processing to use summaries for sensitive data.
 * MODIFIED: Updated form_submission_summary to include submitted values.
 */
class AIPKit_Action_Store_Form_Submission {

    /**
     * Executes the 'store_form_submission' action.
     *
     * @param array $payload Payload for the action, e.g., ['log_message_format' => string].
     * @param array $context_data Contextual data from the event (e.g., form_id, submitted_data).
     * @param int   $bot_id Bot ID associated with the trigger.
     * @param LogStorage $log_storage Instance of LogStorage for logging.
     * @return array|WP_Error Action result or WP_Error on failure.
     */
    public function execute(array $payload, array $context_data, int $bot_id, LogStorage $log_storage): array|WP_Error {

        $form_id = $context_data['form_id'] ?? null;
        $submitted_data = $context_data['submitted_data'] ?? []; // This is the key-value array of submitted data

        if (empty($form_id)) {
            return new WP_Error('missing_form_id_in_context', __('Form ID is missing in the trigger context for store_form_submission.', 'gpt3-ai-content-generator'));
        }

        $log_message_format_template = $payload['log_message_format'] ?? "Form '{{form_id}}' submitted by {{user_name}}. Data: {{form_submission_summary}}";

        // --- MODIFIED: Create a summary string with key-value pairs ---
        $form_submission_summary_parts = [];
        if (empty($submitted_data)) {
            $form_submission_summary = "No data submitted.";
        } else {
            foreach ($submitted_data as $key => $value) {
                // Sanitize key and value for display in the log message
                $display_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                $display_value = is_array($value) ? implode(', ', array_map(function($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }, $value)) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                // Truncate long values for the summary message
                if (mb_strlen($display_value) > 50) {
                    $display_value = mb_substr($display_value, 0, 47) . '...';
                }
                $form_submission_summary_parts[] = "{$display_key}: \"{$display_value}\"";
            }
            $form_submission_summary = implode('; ', $form_submission_summary_parts);
        }
        // --- END MODIFICATION ---

        $context_data_for_placeholder = $context_data;
        $context_data_for_placeholder['form_submission_summary'] = $form_submission_summary;
        unset($context_data_for_placeholder['submitted_data_json']);


        $processed_log_message = AIPKit_Placeholder_Processor::process($log_message_format_template, $context_data_for_placeholder, 'log_summary');

        $base_log_data = [
            'bot_id'            => $context_data['bot_id'] ?? null,
            'user_id'           => $context_data['user_id'] ?? (get_current_user_id() ?: null),
            'session_id'        => $context_data['session_id'] ?? null,
            'conversation_uuid' => $context_data['conversation_uuid'] ?? null,
            'module'            => 'chat', 
            'is_guest'          => !(isset($context_data['user_id']) && $context_data['user_id'] > 0),
            'role'              => $context_data['user_wp_role'] ?? ($context_data['user_roles'] ?? null),
            'ip_address'        => $context_data['client_ip'] ?? null,
        ];
        
        if (empty($base_log_data['bot_id']) || empty($base_log_data['conversation_uuid'])) {
            return new WP_Error('missing_chat_identifiers', __('Cannot associate form submission with a chat session.', 'gpt3-ai-content-generator'));
        }

        $log_data_for_storage = array_merge($base_log_data, [
            'message_role'    => 'system',
            'event_sub_type'  => 'trigger_log', 
            'message_content' => $processed_log_message,
            'timestamp'       => time(),
            'ai_provider'     => 'TriggerSystem',
            'ai_model'        => 'StoreFormAction',
            'usage'           => null,
            'trigger_log_details' => [
                'log_subtype' => 'form_submission_stored',
                'trigger_id' => $context_data['current_trigger_id'] ?? 'unknown', 
                'trigger_name' => $context_data['current_trigger_name'] ?? 'unknown',
                'event_name_processed' => $context_data['event_type'] ?? 'form_submitted', 
                'form_id' => $form_id,
                'submitted_data_snapshot' => $submitted_data, 
            ],
            'response_data'   => ['status' => 'form_data_logged_to_chat_session'],
        ]);
        
        $log_result = $log_storage->log_message($log_data_for_storage);

        if ($log_result === false) {
            return new WP_Error('form_log_failed', __('Failed to log form submission to chat.', 'gpt3-ai-content-generator'));
        }

        $return_value = [
            'type'    => 'form_data_logged_to_chat', 
            'status'  => 'success',
            'form_id' => $form_id,
            'log_id'  => $log_result['log_id'] ?? null,
            'message_id' => $log_result['message_id'] ?? null, 
        ];
        return $return_value;
    }
}
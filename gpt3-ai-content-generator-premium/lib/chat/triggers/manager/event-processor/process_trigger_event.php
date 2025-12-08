<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/triggers/manager/event-processor/process_trigger_event.php
// Status: MODIFIED

namespace WPAICG\Lib\Chat\Triggers\Manager\EventProcessorMethods;

use WPAICG\Lib\Chat\Triggers\Manager\AIPKit_Trigger_Event_Processor; // For type hinting $processorInstance
use WP_Error; // For consistency with WP standards, though not directly returned here

// --- NEW: Require the new method logic files ---
$event_processor_methods_path = __DIR__ . '/';
require_once $event_processor_methods_path . 'process_trigger_event.php';
// log_trigger_event.php and summarize_payload_for_log.php are called by process_trigger_event.php
// and will be required by process_trigger_event.php itself.
// --- END NEW ---

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load helper functions for this specific processing logic if not already loaded.
// These are called from within this function.
if (!function_exists(__NAMESPACE__ . '\log_trigger_event_logic')) { // Add check before requiring
    require_once __DIR__ . '/log_trigger_event.php';
}
if (!function_exists(__NAMESPACE__ . '\summarize_payload_for_log_logic')) { // Add check before requiring
    require_once __DIR__ . '/summarize_payload_for_log.php';
}


/**
 * Processes all active triggers for a given event and context.
 * This logic was moved from AIPKit_Trigger_Event_Processor::process().
 * MODIFIED: Only log 'trigger_evaluation' when conditions are met.
 *
 * @param AIPKit_Trigger_Event_Processor $processorInstance The instance of the main processor class.
 * @param int $bot_id The ID of the chatbot.
 * @param string $event_name The name of the event being processed.
 * @param array $context_data Contextual data for evaluating conditions and executing actions.
 * @return array An array containing the processing status and results.
 */
function process_trigger_event_logic(AIPKit_Trigger_Event_Processor $processorInstance, int $bot_id, string $event_name, array $context_data): array {
    $result_accumulator = [
        'status'                   => 'processed',
        'message_to_user'          => null,
        'actions_executed'         => [],
        'modified_context_data'    => $context_data,
        'block_further_triggers'   => false,
        'stop_ai_processing'       => false,
        'display_form_event_data'  => null,
        'message_id'               => null,
    ];

    // Get dependencies from the processor instance
    $fetcher = $processorInstance->get_fetcher();
    $condition_evaluator = $processorInstance->get_condition_evaluator();
    $action_executor = $processorInstance->get_action_executor();
    $context_updater = $processorInstance->get_context_updater();
    $log_storage = $processorInstance->get_log_storage();

    // --- Fetch active triggers FIRST ---
    $active_triggers = $fetcher->get_active_triggers_for_event($bot_id, $event_name);
    if (empty($active_triggers)) {
        // If no active triggers for this event, don't log "start" and return immediately
        return $result_accumulator;
    }

    // --- MODIFIED: Correctly build base_log_data_for_processing ---
    // Prioritize direct context values, fallback to base_log_data sub-array if context is nested (e.g., from chat AJAX)
    // For system_error_occurred, conversation_uuid and module should come directly from $context_data.
    $base_log_data_for_processing = [
        'conversation_uuid' => $context_data['conversation_uuid'] ?? ($context_data['base_log_data']['conversation_uuid'] ?? null),
        'module'            => $context_data['module'] ?? ($context_data['base_log_data']['module'] ?? 'chat_trigger_system_unknown_origin'), // More descriptive default
        'bot_id'            => $context_data['bot_id'] ?? ($context_data['base_log_data']['bot_id'] ?? $bot_id), // Use passed $bot_id if available
        'user_id'           => $context_data['user_id'] ?? ($context_data['base_log_data']['user_id'] ?? null),
        'session_id'        => $context_data['session_id'] ?? ($context_data['base_log_data']['session_id'] ?? null),
        'is_guest'          => $context_data['is_guest'] ?? ($context_data['base_log_data']['is_guest'] ?? (!(isset($context_data['user_id']) && $context_data['user_id'] > 0))),
        'ip_address'        => $context_data['client_ip'] ?? ($context_data['ip_address'] ?? ($context_data['base_log_data']['ip_address'] ?? null)),
        'role'              => $context_data['user_wp_role'] ?? ($context_data['role'] ?? ((isset($context_data['user_roles']) && is_array($context_data['user_roles'])) ? implode(', ', $context_data['user_roles']) : ($context_data['base_log_data']['role'] ?? null))),
    ];
    // --- END MODIFICATION ---


    // --- Log start of processing only if there are active triggers ---
    log_trigger_event_logic(
        $log_storage,
        $base_log_data_for_processing,
        'trigger_processing_start',
        sprintf("Trigger processing started for event: %s", $event_name),
        [
            'trigger_id' => 'N/A',
            'trigger_name' => 'N/A',
            'event_name_processed' => $event_name,
        ]
    );


    foreach ($active_triggers as $trigger) {
        $conditions = $trigger['conditions'] ?? [];
        $action_config = $trigger['action'] ?? null;
        $trigger_id = $trigger['id'] ?? 'unknown_trigger_id';
        $trigger_name = $trigger['name'] ?? 'Unnamed Trigger';

        if (empty($action_config)) {
            log_trigger_event_logic(
                $log_storage,
                $base_log_data_for_processing,
                'trigger_skipped',
                sprintf("Trigger '%s' (ID: %s) skipped: No action configured.", $trigger_name, $trigger_id),
                [
                    'trigger_id' => $trigger_id,
                    'trigger_name' => $trigger_name,
                    'event_name_processed' => $event_name,
                    'reason' => 'No action configured',
                ]
            );
            continue;
        }

        $conditions_met = $condition_evaluator->are_conditions_met($conditions, $result_accumulator['modified_context_data']);

        if ($conditions_met) {
            $action_type_to_execute = $action_config['type'] ?? 'unknown';
            $action_payload_for_log = $action_config['payload'] ?? [];
            if ($action_type_to_execute === 'set_variable' && isset($action_config['value'])) {
                $action_payload_for_log['value'] = $action_config['value'];
            }

            log_trigger_event_logic(
                $log_storage,
                $base_log_data_for_processing,
                'action_execution_start',
                sprintf("Trigger '%s': Action '%s' started.", $trigger_name, $action_type_to_execute),
                [
                    'trigger_id' => $trigger_id,
                    'trigger_name' => $trigger_name,
                    'event_name_processed' => $event_name,
                    'action_type' => $action_type_to_execute,
                    'action_payload_summary' => summarize_payload_for_log_logic($action_type_to_execute, $action_payload_for_log) // Call namespaced function
                ]
            );

            $action_result = $action_executor->execute_action($action_config, $result_accumulator['modified_context_data'], $bot_id);

            $action_status = 'success';
            $action_result_summary = "Action completed.";
            $action_error_details = null;

            if (is_wp_error($action_result)) {
                $action_status = 'error';
                $action_result_summary = "Error: " . $action_result->get_error_message();
                $action_error_details = $action_result->get_error_code();
                $result_accumulator['status'] = 'error';
                $result_accumulator['message_to_user'] = $action_result->get_error_message();
                $result_accumulator['block_further_triggers'] = true;
            } else if (is_array($action_result)) {
                $executed_action_type = $action_result['type'] ?? $action_type_to_execute;
                $action_result_summary = "Action '{$executed_action_type}' completed.";

                if ($executed_action_type === 'bot_reply') {
                    $action_result_summary .= " Reply Snippet: '" . esc_html(mb_substr($action_result['message'] ?? '', 0, 50)) . "...'. Stop AI: " . (($action_result['stop_ai'] ?? false) ? 'Yes' : 'No') . ".";
                } elseif ($executed_action_type === 'block_message') {
                    $action_result_summary .= " Reason: '" . esc_html($action_result['reason'] ?? 'N/A') . "'.";
                } elseif ($executed_action_type === 'call_webhook') {
                    $action_result_summary .= " Status: " . esc_html($action_result['status'] ?? 'unknown') . ". HTTP: " . esc_html($action_result['http_code'] ?? 'N/A') . ".";
                    if (isset($action_result['response_body_snippet'])) {
                        $action_result_summary .= " Response Snippet: '" . esc_html(mb_substr($action_result['response_body_snippet'], 0, 30)) . "...'.";
                    }
                } elseif ($executed_action_type === 'display_form') {
                    $form_id_display = isset($action_result['form_definition']['form_id']) ? $action_result['form_definition']['form_id'] : 'N/A';
                    $action_result_summary .= " Form ID: '" . esc_html($form_id_display) . "'.";
                } elseif ($executed_action_type === 'form_data_logged_to_chat'){
                    $action_result_summary .= " Form ID: '" . esc_html($action_result['form_id'] ?? 'N/A') . "' logged to message ID: " . esc_html($action_result['message_id'] ?? 'N/A') . ".";
                } elseif ($executed_action_type === 'inject_context') {
                     $action_result_summary .= " Placement: " . esc_html($action_result['placement'] ?? 'unknown') . ". Content Snippet: '" . esc_html(mb_substr($action_result['content'] ?? '', 0, 30)) . "...'.";
                } elseif ($executed_action_type === 'set_variable') {
                    $action_result_summary .= " Scope: " . esc_html($action_config['payload']['scope'] ?? 'N/A') . ", Key: '" . esc_html($action_config['payload']['key'] ?? 'N/A') . "'.";
                }
            }

            log_trigger_event_logic(
                $log_storage,
                $base_log_data_for_processing,
                'action_execution_result',
                sprintf("Trigger '%s': Action '%s' completed. Status: %s.", $trigger_name, $action_type_to_execute, $action_status),
                [
                    'trigger_id' => $trigger_id,
                    'trigger_name' => $trigger_name,
                    'event_name_processed' => $event_name,
                    'action_type' => $action_type_to_execute,
                    'status' => $action_status,
                    'result_summary' => $action_result_summary,
                    'error_details' => $action_error_details
                ]
            );

            if (is_wp_error($action_result)) {
                break;
            }
            if (is_array($action_result)) {
                $result_accumulator['actions_executed'][] = $action_result;
                $result_accumulator['modified_context_data'] = $context_updater->update_context_from_action(
                    $result_accumulator['modified_context_data'],
                    $action_result
                );

                $action_type_executed = $action_result['type'] ?? '';
                switch ($action_type_executed) {
                    case 'bot_reply':
                        $result_accumulator['message_to_user'] = $action_result['message'] ?? null;
                        $result_accumulator['message_id'] = $action_result['message_id'] ?? ('trigger-reply-' . uniqid());
                        if ($action_result['stop_ai'] ?? false) {
                            $result_accumulator['stop_ai_processing'] = true;
                        }
                        break;
                    case 'block_message':
                        $result_accumulator['status'] = 'blocked';
                        $result_accumulator['message_to_user'] = $action_result['reason'] ?? __('Your message could not be processed at this time.', 'gpt3-ai-content-generator');
                        $result_accumulator['message_id'] = $action_result['message_id'] ?? ('trigger-block-' . uniqid());
                        $result_accumulator['stop_ai_processing'] = $action_result['stop_ai'] ?? true;
                        $result_accumulator['block_further_triggers'] = $action_result['block_further_triggers'] ?? true;
                        break;
                    case 'display_form':
                        $result_accumulator['display_form_event_data'] = $action_result;
                        $result_accumulator['stop_ai_processing'] = true;
                        $result_accumulator['block_further_triggers'] = $action_result['block_further_triggers'] ?? true;
                        break;
                }
            }

            if ($result_accumulator['block_further_triggers']) {
                break;
            }
        } // End if ($conditions_met)
    } // End foreach ($active_triggers as $trigger)

    if ($result_accumulator['status'] === 'processed' && $result_accumulator['stop_ai_processing']) {
        if ($result_accumulator['display_form_event_data']) {
            // Status remains 'processed', but 'stop_ai_processing' is true.
        } elseif ($result_accumulator['message_to_user']) {
            $result_accumulator['status'] = 'ai_stopped';
        }
    }
    return $result_accumulator;
}
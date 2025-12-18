<?php

namespace WPAICG\Lib\Chat\Triggers; // UPDATED Namespace

// --- MODIFIED: Use statements for the new action handler class locations ---
use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Bot_Reply;
use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Inject_Context;
use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Block_Message;
use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Call_Webhook;
use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Store_Form_Submission;
use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Display_Form; // ADDED
// The Placeholder Processor is used by the individual action classes and should also be in the new namespace.
// It's not directly used here, but action handlers will use \WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor
// --- END MODIFICATION ---
use WPAICG\Chat\Storage\LogStorage; // ADDED for dependency injection

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Action_Executor
 *
 * Executes defined actions based on trigger configurations by delegating to specific action classes.
 */
class AIPKit_Trigger_Action_Executor {

    private $action_handlers = [];
    private $log_storage; // ADDED

    public function __construct(LogStorage $log_storage) { // MODIFIED: Accept LogStorage
        $this->log_storage = $log_storage; // ADDED: Store LogStorage

        // Instantiate action handlers. These classes should be loaded by the Triggers_Dependencies_Loader.
        // --- MODIFIED: Check for classes in their new Pro namespace ---
        if (class_exists(\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Bot_Reply::class)) {
            $this->action_handlers['bot_reply'] = new \WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Bot_Reply();
        }
        if (class_exists(\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Inject_Context::class)) {
            $this->action_handlers['inject_context'] = new \WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Inject_Context();
        }
        if (class_exists(\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Block_Message::class)) {
            $this->action_handlers['block_message'] = new \WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Block_Message();
        }
        if (class_exists(\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Call_Webhook::class)) {
            $this->action_handlers['call_webhook'] = new \WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Call_Webhook();
        }
        // --- ADDED: Instantiate new action handler ---
        if (class_exists(\WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Store_Form_Submission::class)) {
            $this->action_handlers['store_form_submission'] = new \WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Action_Store_Form_Submission();
        }
        // --- ADDED: Instantiate display_form action handler ---
        if (class_exists(AIPKit_Action_Display_Form::class)) { // Use the imported class name
            $this->action_handlers['display_form'] = new AIPKit_Action_Display_Form();
        }
        // --- END MODIFICATION ---
    }

    /**
     * Executes a given action object by delegating to the appropriate handler.
     *
     * @param array $action_object The action object from the trigger configuration.
     *                             Expected structure: ['type' => string, 'payload' => array]
     * @param array $context_data  Contextual data for the current event (e.g., user message, bot settings).
     * @param int   $bot_id        The ID of the current chatbot.
     * @return array|WP_Error An array describing the result or action to be taken, or WP_Error on failure.
     */
    public function execute_action(array $action_object, array $context_data, int $bot_id): array|WP_Error {
        $action_type = $action_object['type'] ?? null;
        $action_payload = $action_object['payload'] ?? [];
        $action_value = $action_object['value'] ?? null; // For set_variable, value is top-level in action_object

        if (empty($action_type)) {
            return new WP_Error('missing_action_type', __('Action type is missing.', 'gpt3-ai-content-generator'));
        }

        if (isset($this->action_handlers[$action_type])) {
            // For set_variable, the value is passed directly in the action_object, not its payload.
            // So, we merge it into the payload for the handler if it's a set_variable action.
            if ($action_type === 'set_variable' && $action_value !== null) {
                $action_payload['value'] = $action_value;
            }
            // --- MODIFIED: Pass LogStorage to store_form_submission handler ---
            if ($action_type === 'store_form_submission') {
                 if (!$this->log_storage) {
                    return new WP_Error('missing_log_storage', __('Log storage service is unavailable for storing form submission.', 'gpt3-ai-content-generator'));
                 }
                return $this->action_handlers[$action_type]->execute($action_payload, $context_data, $bot_id, $this->log_storage);
            }
            // --- END MODIFICATION ---
            return $this->action_handlers[$action_type]->execute($action_payload, $context_data, $bot_id);
        } else {
            /* translators: %s is the action type */
            return new WP_Error('unknown_action_type', sprintf(__('Unknown or unhandled action type: %s', 'gpt3-ai-content-generator'), $action_type));
        }
    }
}
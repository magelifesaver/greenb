<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/triggers/class-aipkit-trigger-manager.php
// Status: MODIFIED

namespace WPAICG\Lib\Chat\Triggers; // UPDATED Namespace

use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage;
// Use new manager components
use WPAICG\Lib\Chat\Triggers\Manager\AIPKit_Trigger_Fetcher;
use WPAICG\Lib\Chat\Triggers\Manager\AIPKit_Trigger_Event_Processor;
use WPAICG\Lib\Chat\Triggers\Manager\AIPKit_Trigger_Context_Updater;
// Existing dependencies (facades) that EventProcessor will need
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Condition_Evaluator;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Action_Executor;
use WPAICG\Chat\Storage\LogStorage;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Manager (Revised Facade)
 *
 * Orchestrates trigger evaluation and execution by delegating to specialized manager classes.
 */
class AIPKit_Trigger_Manager
{
    private $event_processor;
    private $log_storage; // ADDED: Store LogStorage instance

    /**
     * Constructor.
     *
     * @param AIPKit_Trigger_Storage $trigger_storage Instance of the trigger storage handler.
     * @param LogStorage|null $log_storage Instance of LogStorage for actions that need logging. Can be null for tests.
     */
    public function __construct(AIPKit_Trigger_Storage $trigger_storage, ?LogStorage $log_storage = null)
    {
        $this->log_storage = $log_storage;

        // Ensure components are loaded
        $manager_components_path = __DIR__ . '/manager/';
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Manager\AIPKit_Trigger_Fetcher::class)) {
            $fetcher_path = $manager_components_path . 'class-aipkit-trigger-fetcher.php';
            if (file_exists($fetcher_path)) {
                require_once $fetcher_path;
            }
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Condition_Evaluator::class)) {
            $evaluator_path = __DIR__ . '/class-aipkit-trigger-condition-evaluator.php';
            if (file_exists($evaluator_path)) {
                require_once $evaluator_path;
            }
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Action_Executor::class)) {
            $executor_path = __DIR__ . '/class-aipkit-trigger-action-executor.php';
            if (file_exists($executor_path)) {
                require_once $executor_path;
            }
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Manager\AIPKit_Trigger_Context_Updater::class)) {
            $updater_path = $manager_components_path . 'class-aipkit-trigger-context-updater.php';
            if (file_exists($updater_path)) {
                require_once $updater_path;
            }
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Manager\AIPKit_Trigger_Event_Processor::class)) {
            $processor_path = $manager_components_path . 'class-aipkit-trigger-event-processor.php';
            if (file_exists($processor_path)) {
                require_once $processor_path;
            }
        }


        // Instantiate the new manager components
        $fetcher = class_exists(AIPKit_Trigger_Fetcher::class) ? new AIPKit_Trigger_Fetcher($trigger_storage) : null;
        $condition_evaluator = class_exists(AIPKit_Trigger_Condition_Evaluator::class) ? new AIPKit_Trigger_Condition_Evaluator() : null;
        // --- MODIFIED: Pass LogStorage to ActionExecutor ---
        $action_executor = (class_exists(AIPKit_Trigger_Action_Executor::class) && $this->log_storage)
                            ? new AIPKit_Trigger_Action_Executor($this->log_storage)
                            : (class_exists(AIPKit_Trigger_Action_Executor::class) ? new AIPKit_Trigger_Action_Executor(null) : null);

        // --- END MODIFICATION ---
        $context_updater = class_exists(AIPKit_Trigger_Context_Updater::class) ? new AIPKit_Trigger_Context_Updater() : null;

        // Instantiate the main event processor, passing LogStorage
        if ($fetcher && $condition_evaluator && $action_executor && $context_updater && class_exists(AIPKit_Trigger_Event_Processor::class)) {
            $this->event_processor = new AIPKit_Trigger_Event_Processor(
                $fetcher,
                $condition_evaluator,
                $action_executor,
                $context_updater,
                $this->log_storage // Pass LogStorage to EventProcessor
            );
        } else {
            $this->event_processor = null;
        }
    }

    /**
     * Processes all active triggers for a given event and context.
     * Delegates to AIPKit_Trigger_Event_Processor.
     *
     * @param int $bot_id The ID of the chatbot.
     * @param string $event_name The name of the event being processed.
     * @param array $context_data Contextual data for evaluating conditions and executing actions.
     * @return array An array containing the processing status and results:
     *               [
     *                  'status' => 'processed' | 'blocked' | 'ai_stopped' | 'error',
     *                  'message_to_user' => string|null,
     *                  'actions_executed' => array,
     *                  'modified_context_data' => array,
     *                  'block_further_triggers' => bool,
     *                  'stop_ai_processing' => bool,
     *                  'display_form_event_data' => array|null,
     *                  'message_id' => string|null,
     *               ]
     */
    public function process_event(int $bot_id, string $event_name, array $context_data): array
    {
        if (!$this->event_processor) {
            return [
                'status'                   => 'error',
                'message_to_user'          => __('Trigger system internal error.', 'gpt3-ai-content-generator'),
                'actions_executed'         => [],
                'modified_context_data'    => $context_data,
                'block_further_triggers'   => true,
                'stop_ai_processing'       => true,
                'display_form_event_data'  => null,
                'message_id'               => null,
            ];
        }
        return $this->event_processor->process($bot_id, $event_name, $context_data);
    }
}

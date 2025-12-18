<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/triggers/manager/class-aipkit-trigger-event-processor.php
// Status: MODIFIED

namespace WPAICG\Lib\Chat\Triggers\Manager;

use WPAICG\Lib\Chat\Triggers\Manager\AIPKit_Trigger_Fetcher;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Condition_Evaluator;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Action_Executor;
use WPAICG\Lib\Chat\Triggers\Manager\AIPKit_Trigger_Context_Updater;
use WPAICG\Chat\Storage\LogStorage;
use WP_Error;

// --- NEW: Require the new method logic files ---
$event_processor_methods_path = __DIR__ . '/event-processor/';
require_once $event_processor_methods_path . 'process_trigger_event.php';
// log_trigger_event.php and summarize_payload_for_log.php are called by process_trigger_event.php
// and will be required by process_trigger_event.php itself.
// --- END NEW ---

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Event_Processor (Revised Facade)
 *
 * Orchestrates trigger evaluation and execution by delegating to specialized manager classes.
 * The core processing logic is now in EventProcessorMethods\process_trigger_event_logic.
 */
class AIPKit_Trigger_Event_Processor
{
    private $fetcher;
    private $condition_evaluator;
    private $action_executor;
    private $context_updater;
    private $log_storage;

    /**
     * Constructor.
     *
     * @param AIPKit_Trigger_Fetcher $fetcher
     * @param AIPKit_Trigger_Condition_Evaluator $condition_evaluator
     * @param AIPKit_Trigger_Action_Executor $action_executor
     * @param AIPKit_Trigger_Context_Updater $context_updater
     * @param LogStorage|null $log_storage
     */
    public function __construct(
        AIPKit_Trigger_Fetcher $fetcher,
        AIPKit_Trigger_Condition_Evaluator $condition_evaluator,
        AIPKit_Trigger_Action_Executor $action_executor,
        AIPKit_Trigger_Context_Updater $context_updater,
        ?LogStorage $log_storage = null
    ) {
        $this->fetcher = $fetcher;
        $this->condition_evaluator = $condition_evaluator;
        $this->action_executor = $action_executor;
        $this->context_updater = $context_updater;
        $this->log_storage = $log_storage;

        // Ensure dependencies for externalized functions are loaded (they will require their own files)
        $sub_dir_path = __DIR__ . '/event-processor/';
        if (!function_exists('\WPAICG\Lib\Chat\Triggers\Manager\EventProcessorMethods\log_trigger_event_logic')) {
            $log_event_path = $sub_dir_path . 'log_trigger_event.php';
            if (file_exists($log_event_path)) {
                require_once $log_event_path;
            }
        }
        if (!function_exists('\WPAICG\Lib\Chat\Triggers\Manager\EventProcessorMethods\summarize_payload_for_log_logic')) {
            $summarize_path = $sub_dir_path . 'summarize_payload_for_log.php';
            if (file_exists($summarize_path)) {
                require_once $summarize_path;
            }
        }
    }

    // --- NEW: Getters for dependencies needed by the externalized logic ---
    public function get_fetcher(): AIPKit_Trigger_Fetcher
    {
        return $this->fetcher;
    }
    public function get_condition_evaluator(): AIPKit_Trigger_Condition_Evaluator
    {
        return $this->condition_evaluator;
    }
    public function get_action_executor(): AIPKit_Trigger_Action_Executor
    {
        return $this->action_executor;
    }
    public function get_context_updater(): AIPKit_Trigger_Context_Updater
    {
        return $this->context_updater;
    }
    public function get_log_storage(): ?LogStorage
    {
        return $this->log_storage;
    }
    // --- END NEW ---

    /**
     * Processes all active triggers for a given event and context.
     * Delegates to EventProcessorMethods\process_trigger_event_logic.
     *
     * @param int $bot_id The ID of the chatbot.
     * @param string $event_name The name of the event being processed.
     * @param array $context_data Contextual data for evaluating conditions and executing actions.
     * @return array An array containing the processing status and results.
     */
    public function process(int $bot_id, string $event_name, array $context_data): array
    {
        // Delegate to the namespaced function
        return \WPAICG\Lib\Chat\Triggers\Manager\EventProcessorMethods\process_trigger_event_logic(
            $this, // Pass the current instance
            $bot_id,
            $event_name,
            $context_data
        );
    }

    // Private methods log_trigger_event and summarize_payload_for_log are now moved
    // to their respective files and will be called by process_trigger_event_logic.
}

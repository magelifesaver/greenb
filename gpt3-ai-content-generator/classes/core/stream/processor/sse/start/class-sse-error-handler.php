<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/processor/sse/start/class-sse-error-handler.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Processor\SSE\Start;

use WPAICG\Core\Stream\Processor\SSEStreamProcessor;
use WPAICG\Core\Stream\Formatter\SSEResponseFormatter;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager;
use WPAICG\Chat\Storage\LogStorage; // Added for passing to TriggerManager
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles SSE errors, logs them, sends SSE error events, and dispatches system error triggers.
 */
class SSEErrorHandler
{
    private $processorInstance;
    private $formatter;
    private $botIdForTrigger;
    private $log_storage; // Added LogStorage instance

    public function __construct(SSEStreamProcessor $processorInstance, SSEResponseFormatter $formatter, ?int $botIdForTrigger = 0, ?LogStorage $log_storage = null)
    {
        $this->processorInstance = $processorInstance;
        $this->formatter = $formatter;
        $this->botIdForTrigger = $botIdForTrigger ?: ($processorInstance->get_log_base_data()['bot_id'] ?? 0);
        $this->log_storage = $log_storage; // Store LogStorage

        // Ensure Trigger classes are loaded if the addon is active
        $triggers_addon_active = false;
        if (class_exists('\WPAICG\aipkit_dashboard')) {
            $triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
        }
        if ($triggers_addon_active) {
            if (!class_exists('\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage')) {
                $path = WPAICG_LIB_DIR . 'chat/triggers/class-aipkit-trigger-storage.php';
                if (file_exists($path)) {
                    require_once $path;
                }
            }
            if (!class_exists('\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager')) {
                $path = WPAICG_LIB_DIR . 'chat/triggers/class-aipkit-trigger-manager.php';
                if (file_exists($path)) {
                    require_once $path;
                }
            }
        }
    }

    /**
     * Handles an error by logging it, sending an SSE error event, and dispatching a system trigger.
     *
     * @param WP_Error $error The WP_Error object.
     * @param array $trigger_context Additional context for the system_error_occurred trigger.
     *                               Should include 'provider', 'model', 'operation', 'module'.
     *                               Optional: 'http_code'.
     */
    public function handle_error(WP_Error $error, array $trigger_context): void
    {
        $error_message = $error->get_error_message();
        $error_code = $error->get_error_code();
        $error_data_payload = $error->get_error_data();
        
        if ($this->formatter && !$this->processorInstance->get_error_occurred_status()) {
            $this->formatter->send_sse_error($error_message, false);
            $this->processorInstance->set_error_occurred_status(true);
        }

        if ($this->processorInstance->get_current_bot_message_id() && function_exists('\WPAICG\Core\Stream\Processor\log_bot_error_logic')) {
            \WPAICG\Core\Stream\Processor\log_bot_error_logic($this->processorInstance, $error_message);
        }

        $triggers_addon_active = class_exists('\WPAICG\aipkit_dashboard') && \WPAICG\aipkit_dashboard::is_addon_active('triggers');
        $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
        $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';

        if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class)) {
            $base_log_data = $this->processorInstance->get_log_base_data();
            $error_event_context = [
                'error_code'    => $error_code,
                'error_message' => $error_message,
                'bot_id'        => $this->botIdForTrigger,
                'user_id'       => $base_log_data['user_id'] ?? (get_current_user_id() ?: null),
                'session_id'    => $base_log_data['session_id'] ?? ($base_log_data['is_guest'] ? session_id() : null), // Ensure session_id is passed
                'module'        => $trigger_context['module'] ?? ($base_log_data['module'] ?? 'unknown_stream_error'),
                'operation'     => $trigger_context['operation'] ?? 'unknown_operation',
                'failed_provider' => $trigger_context['provider'] ?? ($this->processorInstance->get_current_provider() ?? null),
                'failed_model'    => $trigger_context['model'] ?? ($this->processorInstance->get_current_model() ?? null),
                'http_code'     => $trigger_context['http_code'] ?? ($error_data_payload['status_code'] ?? ($error_data_payload['status'] ?? null)),
            ];
            try {
                $trigger_storage = new $trigger_storage_class();
                // --- MODIFIED: Pass $this->log_storage to TriggerManager constructor ---
                $trigger_manager = new $trigger_manager_class($trigger_storage, $this->log_storage);
                // --- END MODIFICATION ---
                $trigger_manager->process_event($this->botIdForTrigger, 'system_error_occurred', $error_event_context);
            } catch (\Exception $e) {
                // AIPKit SSE Error Handler: Exception while processing system_error_occurred trigger: " . $e->getMessage());
            }
        }
    }
}

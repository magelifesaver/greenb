<?php

// File: classes/chat/core/ai-service/generate-response/handle-ai-call-error.php
// Status: MODIFIED

namespace WPAICG\Chat\Core\AIService\GenerateResponse;

use WP_Error;
use WPAICG\Chat\Storage\LogStorage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles logging and trigger dispatch for AI call errors.
 *
 * @param WP_Error $ai_call_error_result The WP_Error object from the AI call.
 * @param bool $triggers_addon_active Whether the triggers addon is active.
 * @param \WPAICG\Chat\Storage\LogStorage|null $log_storage_for_triggers Instance of LogStorage for triggers.
 * @param array $base_log_data Base log data (may be empty if not passed down).
 * @param string $main_provider The main AI provider.
 * @param string $model The selected AI model.
 * @param int|null $bot_id The ID of the bot.
 */
function handle_ai_call_error_logic(
    WP_Error $ai_call_error_result,
    bool $triggers_addon_active,
    ?LogStorage $log_storage_for_triggers,
    array $base_log_data, // This might be empty depending on the orchestrator
    string $main_provider,
    string $model,
    ?int $bot_id
): void {

    $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
    $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';

    if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class)) {
        // Only proceed if log storage is available for the trigger manager
        if ($log_storage_for_triggers) {
            $error_data_from_caller = $ai_call_error_result->get_error_data() ?? [];
            $error_event_context = [
                'error_code'    => $ai_call_error_result->get_error_code(),
                'error_message' => $ai_call_error_result->get_error_message(),
                'bot_id'        => $bot_id ?? ($base_log_data['bot_id'] ?? null),
                'user_id'       => $base_log_data['user_id'] ?? null,
                'session_id'    => $base_log_data['session_id'] ?? null,
                'module'        => 'chat_ai_service',
                'operation'     => 'make_standard_call_in_generate_response',
                'failed_provider' => $error_data_from_caller['provider'] ?? $main_provider,
                'failed_model'    => $error_data_from_caller['model'] ?? $model,
                'status_code'   => $error_data_from_caller['status_code'] ?? null,
            ];
            $trigger_storage = new $trigger_storage_class();
            $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage_for_triggers);
            $trigger_manager->process_event($bot_id ?? 0, 'system_error_occurred', $error_event_context);
        }
    }
}
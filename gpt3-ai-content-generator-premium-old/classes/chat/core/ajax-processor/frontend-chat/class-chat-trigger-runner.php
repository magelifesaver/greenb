<?php
// File: classes/chat/core/ajax-processor/frontend-chat/class-chat-trigger-runner.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AjaxProcessor\FrontendChat;

use WPAICG\Chat\Storage\LogStorage; // For logging trigger actions
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatTriggerRunner {

    private $log_storage;

    public function __construct(LogStorage $log_storage) {
        $this->log_storage = $log_storage;
    }

    /**
     * Runs session_started and user_message_received triggers.
     *
     * @param array $context Context data from ChatContextBuilder and ChatHistoryManager.
     *                       Includes: bot_id, bot_settings, user_id, session_id, client_ip, post_id,
     *                       user_message_text, current_history (already limited), system_instruction,
     *                       user_wp_role, current_provider, current_model_id, base_log_data.
     * @param bool $is_new_session Whether this is a new session.
     * @return array Results of trigger processing:
     *               [
     *                  'status' => 'processed' | 'blocked' | 'ai_stopped',
     *                  'message_to_user' => string|null (if action was bot_reply or block_message),
     *                  'message_id' => string|null (if action was bot_reply or block_message),
     *                  'final_user_message_for_ai' => string,
     *                  'final_system_instruction_for_ai' => string,
     *                  'final_history_for_ai' => array
     *               ]
     */
    public function run_triggers(array $context, bool $is_new_session): array {
        $final_user_message_for_ai = $context['user_message_text'];
        $final_system_instruction_for_ai = $context['system_instruction'];
        $final_history_for_ai = $context['current_history']; // History manager has already limited it

        $trigger_result_accumulator = [
            'status' => 'processed',
            'message_to_user' => null,
            'message_id' => null,
            'final_user_message_for_ai' => $final_user_message_for_ai,
            'final_system_instruction_for_ai' => $final_system_instruction_for_ai,
            'final_history_for_ai' => $final_history_for_ai,
        ];

        $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
        $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';
        $trigger_function_name = '\WPAICG\Lib\Chat\Triggers\process_chat_triggers';

        $triggers_addon_active = false;
        if (class_exists('\WPAICG\aipkit_dashboard')) {
            $triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
        }

        if (!$triggers_addon_active || !class_exists($trigger_storage_class) || !class_exists($trigger_manager_class) || !function_exists($trigger_function_name)) {
            return $trigger_result_accumulator; // No triggers to run or components missing
        }

        // Process 'session_started' triggers if it's a new session
        if ($is_new_session) {
            $session_trigger_params = [
                'event_type'        => 'session_started',
                'user_id'           => $context['user_id'], 'session_id' => $context['session_id'], 'bot_id' => $context['bot_id'],
                'bot_settings'      => $context['bot_settings'], 'client_ip' => $context['client_ip'], 'post_id' => $context['post_id'],
                'user_message_text' => $context['user_message_text'], // First user message
                'current_history'   => [], // History is empty for session_started
                'message_count'     => 0,
                'system_instruction'=> $final_system_instruction_for_ai, // Use potentially already modified instruction
                'user_roles'        => $context['user_wp_role'] ? array_map('trim', explode(',', $context['user_wp_role'])) : ['guest'],
                'current_provider'  => $context['current_provider'], 'current_model_id' => $context['current_model_id'],
                'base_log_data'     => $context['base_log_data'], 'log_storage' => $this->log_storage
            ];
            $session_trigger_result = $trigger_function_name($session_trigger_params);

            if ($session_trigger_result['status'] === 'blocked') {
                $trigger_result_accumulator['status'] = 'blocked';
                $trigger_result_accumulator['message_to_user'] = $session_trigger_result['message_to_user'] ?? __('Session blocked by system.', 'gpt3-ai-content-generator');
                $trigger_result_accumulator['message_id'] = $session_trigger_result['message_id'] ?? ('trigger-session-block-' . uniqid());
                return $trigger_result_accumulator;
            }
            if (isset($session_trigger_result['message_to_user']) && ($session_trigger_result['stop_ai_processing'] ?? false)) {
                $trigger_result_accumulator['status'] = 'ai_stopped';
                $trigger_result_accumulator['message_to_user'] = $session_trigger_result['message_to_user'];
                $trigger_result_accumulator['message_id'] = $session_trigger_result['message_id'] ?? ('trigger-session-reply-' . uniqid());
                // Note: if session_started stops AI, the user_message_received might not run if this function returns early.
                // This is acceptable as a bot_reply from session_started should be the end of the exchange.
                return $trigger_result_accumulator;
            }
            // Apply context modifications from session triggers before user_message triggers
            $final_system_instruction_for_ai = $session_trigger_result['modified_context_data']['system_instruction'] ?? $final_system_instruction_for_ai;
            $final_user_message_for_ai       = $session_trigger_result['modified_context_data']['user_message_text'] ?? $final_user_message_for_ai;
            // History is not typically modified by session_started in a way that impacts user_message_received (which uses its own history load)
        }

        // Process 'user_message_received' triggers
        $message_count_for_trigger = count($final_history_for_ai);
        $user_message_trigger_params = [
            'event_type'        => 'user_message_received',
            'user_id'           => $context['user_id'], 'session_id' => $context['session_id'], 'bot_id' => $context['bot_id'],
            'bot_settings'      => $context['bot_settings'], 'user_message_text' => $final_user_message_for_ai, // Use potentially modified user message
            'current_history'   => $final_history_for_ai, 'client_ip' => $context['client_ip'], 'post_id' => $context['post_id'],
            'message_count'     => $message_count_for_trigger,
            'system_instruction'=> $final_system_instruction_for_ai, // Use potentially modified system instruction
            'user_roles'        => $context['user_wp_role'] ? array_map('trim', explode(',', $context['user_wp_role'])) : ['guest'],
            'current_provider'  => $context['current_provider'], 'current_model_id' => $context['current_model_id'],
            'base_log_data'     => $context['base_log_data'], 'log_storage'       => $this->log_storage
        ];
        $user_message_trigger_result = $trigger_function_name($user_message_trigger_params);

        if ($user_message_trigger_result['status'] === 'blocked') {
            $trigger_result_accumulator['status'] = 'blocked';
            $trigger_result_accumulator['message_to_user'] = $user_message_trigger_result['message_to_user'] ?? __('Message blocked.', 'gpt3-ai-content-generator');
            $trigger_result_accumulator['message_id'] = $user_message_trigger_result['message_id'] ?? ('trigger-block-' . uniqid());
            return $trigger_result_accumulator;
        }
        if (isset($user_message_trigger_result['message_to_user']) && ($user_message_trigger_result['stop_ai_processing'] ?? false)) {
            $trigger_result_accumulator['status'] = 'ai_stopped';
            $trigger_result_accumulator['message_to_user'] = $user_message_trigger_result['message_to_user'];
            $trigger_result_accumulator['message_id'] = $user_message_trigger_result['message_id'] ?? ('trigger-reply-' . uniqid());
            return $trigger_result_accumulator;
        }

        // Update final context based on user_message_received triggers
        $trigger_result_accumulator['final_user_message_for_ai']       = $user_message_trigger_result['modified_context_data']['user_message_text'] ?? $final_user_message_for_ai;
        $trigger_result_accumulator['final_history_for_ai']            = $user_message_trigger_result['modified_context_data']['current_history'] ?? $final_history_for_ai;
        $trigger_result_accumulator['final_system_instruction_for_ai'] = $user_message_trigger_result['modified_context_data']['system_instruction'] ?? $final_system_instruction_for_ai;

        return $trigger_result_accumulator;
    }
}
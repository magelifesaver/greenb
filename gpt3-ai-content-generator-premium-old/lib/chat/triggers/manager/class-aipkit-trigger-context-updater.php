<?php

namespace WPAICG\Lib\Chat\Triggers\Manager; // UPDATED Namespace

// No direct class dependencies in constructor for now

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Context_Updater
 *
 * Handles modifying the context data based on executed trigger actions.
 */
class AIPKit_Trigger_Context_Updater {

    public function __construct() {
        // Placeholder_Processor is used by individual action handlers before this updater is called.
    }

    /**
     * Updates the context data based on the result of an executed action.
     *
     * @param array $current_context_data The current context data.
     * @param array $action_result The result array from an executed action.
     * @return array The potentially modified context data.
     */
    public function update_context_from_action(array $current_context_data, array $action_result): array {
        $modified_context = $current_context_data;
        $action_type = $action_result['type'] ?? null;

        switch ($action_type) {
            case 'inject_context':
                $placement = $action_result['placement'] ?? null;
                $content_to_inject = $action_result['content'] ?? ''; // Content is already processed by placeholder processor

                if ($placement === 'system_instruction_prepend') {
                    $modified_context['system_instruction'] =
                        $content_to_inject . "\n" . ($modified_context['system_instruction'] ?? '');
                } elseif ($placement === 'system_instruction_append') {
                    $modified_context['system_instruction'] =
                        ($modified_context['system_instruction'] ?? '') . "\n" . $content_to_inject;
                } elseif ($placement === 'history_prepend' && isset($modified_context['current_history'])) {
                    // Assuming content_to_inject is a simple string to be added as a user message
                    // This structure should match the history format used by the AI_Service
                    array_unshift($modified_context['current_history'], ['role' => 'user', 'content' => $content_to_inject]);
                }
                break;

            case 'set_variable':
                $scope = $action_result['scope'] ?? null;
                $key   = $action_result['key'] ?? null;
                $value = $action_result['value'] ?? null; // Value is already processed by placeholder processor

                if ($scope === 'bot_context' && $key !== null) {
                    $modified_context[$key] = $value;
                }
                // 'user_meta' scope is handled directly by the AIPKit_Action_Set_Variable class,
                // so it does not directly modify the $modified_context array here.
                break;

            // Other action types might not modify the context in this way
        }

        return $modified_context;
    }
}
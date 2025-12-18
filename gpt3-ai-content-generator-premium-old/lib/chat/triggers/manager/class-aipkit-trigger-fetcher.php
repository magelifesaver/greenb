<?php

namespace WPAICG\Lib\Chat\Triggers\Manager; // UPDATED Namespace

use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage; // Use new namespace

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Fetcher
 *
 * Responsible for fetching and filtering active triggers for a specific event.
 */
class AIPKit_Trigger_Fetcher {

    private $trigger_storage;

    public function __construct(AIPKit_Trigger_Storage $trigger_storage) {
        $this->trigger_storage = $trigger_storage;
    }

    /**
     * Retrieves all active triggers for a given bot and event, sorted by priority.
     * This logic was moved from AIPKit_Trigger_Manager::get_active_triggers_for_event().
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @param string $event_name The name of the event to retrieve triggers for (e.g., "user_message_received").
     * @return array An array of active trigger objects, sorted by priority (ascending). Returns empty array on failure or if no triggers found.
     */
    public function get_active_triggers_for_event(int $bot_id, string $event_name): array {
        if (empty($bot_id) || empty($event_name)) {
            return [];
        }

        $all_triggers_for_bot = $this->trigger_storage->get_triggers($bot_id);

        if (empty($all_triggers_for_bot)) {
            return [];
        }

        $active_event_triggers = [];
        foreach ($all_triggers_for_bot as $trigger) {
            if (!is_array($trigger) ||
                !isset($trigger['is_active']) ||
                !isset($trigger['event_name']) ||
                !isset($trigger['priority'])) {
                continue;
            }

            if ($trigger['is_active'] === true && $trigger['event_name'] === $event_name) {
                $active_event_triggers[] = $trigger;
            }
        }

        usort($active_event_triggers, function ($a, $b) {
            $priority_a = $a['priority'] ?? 10;
            $priority_b = $b['priority'] ?? 10;
            return $priority_a <=> $priority_b;
        });

        return $active_event_triggers;
    }
}
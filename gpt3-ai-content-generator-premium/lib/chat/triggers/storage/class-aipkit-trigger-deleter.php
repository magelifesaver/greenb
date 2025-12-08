<?php

namespace WPAICG\Lib\Chat\Triggers\Storage; // UPDATED Namespace

// No WP_Error needed here as it returns bool
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Deleter
 *
 * Handles deleting a trigger from a bot's configuration.
 */
class AIPKit_Trigger_Deleter {

    private $loader;
    private $saver;

    public function __construct(AIPKit_Trigger_Loader $loader, AIPKit_Trigger_Saver $saver) {
        $this->loader = $loader;
        $this->saver = $saver;
    }

    /**
     * Deletes a trigger object from a bot's configuration by its ID.
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @param string $trigger_id The ID of the trigger to delete.
     * @return bool True if deletion was successful or if the trigger didn't exist. False on save error.
     */
    public function delete_trigger(int $bot_id, string $trigger_id): bool {
        if (empty($trigger_id)) {
            return true; // Or false, depending on strictness. True implies "it's gone".
        }

        $triggers = $this->loader->get_triggers($bot_id);
        $original_count = count($triggers);

        $updated_triggers = array_filter($triggers, function ($trigger) use ($trigger_id) {
            return !(isset($trigger['id']) && $trigger['id'] === $trigger_id);
        });

        // Only save if something actually changed (trigger was found and removed)
        if (count($updated_triggers) < $original_count) {
            return $this->saver->save_triggers($bot_id, array_values($updated_triggers)); // Re-index array
        }
        return true; // Trigger not found, so it's effectively "deleted"
    }
}
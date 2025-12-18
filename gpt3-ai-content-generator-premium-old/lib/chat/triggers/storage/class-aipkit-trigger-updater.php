<?php

namespace WPAICG\Lib\Chat\Triggers\Storage; // UPDATED Namespace

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Updater
 *
 * Handles updating an existing trigger in a bot's configuration.
 */
class AIPKit_Trigger_Updater {

    private $loader;
    private $saver;

    public function __construct(AIPKit_Trigger_Loader $loader, AIPKit_Trigger_Saver $saver) {
        $this->loader = $loader;
        $this->saver = $saver;
    }

    /**
     * Updates an existing trigger object for a bot.
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @param string $trigger_id The ID of the trigger to update.
     * @param array $updated_trigger_object The new trigger object data.
     * @return bool|WP_Error True on success, WP_Error on failure or if trigger not found.
     */
    public function update_trigger(int $bot_id, string $trigger_id, array $updated_trigger_object): bool|WP_Error {
        if (empty($trigger_id)) {
            return new WP_Error('missing_trigger_id_for_update', __('Trigger ID is required for update.', 'gpt3-ai-content-generator'));
        }
        // Ensure the ID in the object matches the trigger_id being updated
        $updated_trigger_object['id'] = $trigger_id;

        $triggers = $this->loader->get_triggers($bot_id);
        $found_index = -1;

        foreach ($triggers as $index => $existing_trigger) {
            if (isset($existing_trigger['id']) && $existing_trigger['id'] === $trigger_id) {
                $found_index = $index;
                break;
            }
        }

        if ($found_index === -1) {
            /* translators: %s is the trigger ID */
            return new WP_Error('trigger_not_found_for_update', sprintf(__('Trigger with ID "%s" not found for this bot.', 'gpt3-ai-content-generator'), esc_html($trigger_id)));
        }

        $triggers[$found_index] = $updated_trigger_object;
        return $this->saver->save_triggers($bot_id, $triggers);
    }
}
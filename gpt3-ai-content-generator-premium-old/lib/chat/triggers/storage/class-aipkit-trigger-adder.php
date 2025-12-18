<?php

namespace WPAICG\Lib\Chat\Triggers\Storage; // UPDATED Namespace

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Adder
 *
 * Handles adding a new trigger to a bot's configuration.
 */
class AIPKit_Trigger_Adder {

    private $loader;
    private $saver;

    public function __construct(AIPKit_Trigger_Loader $loader, AIPKit_Trigger_Saver $saver) {
        $this->loader = $loader;
        $this->saver = $saver;
    }

    /**
     * Adds a new trigger object to a bot's trigger configuration.
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @param array $trigger_object The trigger object to add. Must include a unique 'id'.
     * @return bool|WP_Error True on success, WP_Error on failure or if ID conflicts.
     */
    public function add_trigger(int $bot_id, array $trigger_object): bool|WP_Error {
        if (!isset($trigger_object['id']) || empty(trim($trigger_object['id']))) {
            return new WP_Error('missing_trigger_id', __('Trigger object must have a unique "id".', 'gpt3-ai-content-generator'));
        }

        $triggers = $this->loader->get_triggers($bot_id);

        // Check for ID conflict
        foreach ($triggers as $existing_trigger) {
            if (isset($existing_trigger['id']) && $existing_trigger['id'] === $trigger_object['id']) {
                /* translators: %s is the trigger ID */
                return new WP_Error('duplicate_trigger_id', sprintf(__('A trigger with ID "%s" already exists for this bot.', 'gpt3-ai-content-generator'), esc_html($trigger_object['id'])));
            }
        }

        $triggers[] = $trigger_object;
        return $this->saver->save_triggers($bot_id, $triggers);
    }
}
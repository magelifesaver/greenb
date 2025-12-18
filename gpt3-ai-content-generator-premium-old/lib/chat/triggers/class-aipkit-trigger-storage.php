<?php

namespace WPAICG\Lib\Chat\Triggers; // UPDATED Namespace

// Import the new storage handler classes with their correct Lib namespace
use WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Loader;
use WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Saver;
use WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Adder;
use WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Updater;
use WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Deleter;
use WP_Error; // Keep for return type hinting

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Storage (Delegator)
 *
 * Handles saving and retrieving trigger configurations for chatbots by delegating
 * to specialized storage handler classes.
 * Triggers are stored as a JSON array in post meta for the chatbot CPT.
 */
class AIPKit_Trigger_Storage {

    const META_KEY = '_aipkit_chatbot_triggers';

    private $loader;
    private $saver;
    private $adder;
    private $updater;
    private $deleter;

    public function __construct() {
        // Define paths to new storage handler classes within /lib/
        $storage_handlers_path = __DIR__ . '/storage/';

        // Ensure handler classes are loaded
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Loader::class)) {
            require_once $storage_handlers_path . 'class-aipkit-trigger-loader.php';
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Saver::class)) {
            require_once $storage_handlers_path . 'class-aipkit-trigger-saver.php';
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Adder::class)) {
            require_once $storage_handlers_path . 'class-aipkit-trigger-adder.php';
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Updater::class)) {
            require_once $storage_handlers_path . 'class-aipkit-trigger-updater.php';
        }
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\Storage\AIPKit_Trigger_Deleter::class)) {
            require_once $storage_handlers_path . 'class-aipkit-trigger-deleter.php';
        }

        // Instantiate dependencies
        $this->loader = new AIPKit_Trigger_Loader();
        $this->saver = new AIPKit_Trigger_Saver();
        $this->adder = new AIPKit_Trigger_Adder($this->loader, $this->saver);
        $this->updater = new AIPKit_Trigger_Updater($this->loader, $this->saver);
        $this->deleter = new AIPKit_Trigger_Deleter($this->loader, $this->saver);
    }

    /**
     * Retrieves the array of trigger objects for a given bot ID.
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @return array An array of trigger objects, or an empty array if none found or on error.
     */
    public function get_triggers(int $bot_id): array {
        if (!$this->loader) return [];
        return $this->loader->get_triggers($bot_id);
    }

    /**
     * Saves an array of trigger objects for a given bot ID.
     * Overwrites any existing triggers for that bot.
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @param array $triggers_array An array of trigger objects to save.
     * @return bool True on success, false on failure.
     */
    public function save_triggers(int $bot_id, array $triggers_array): bool {
        if (!$this->saver) return false;
        return $this->saver->save_triggers($bot_id, $triggers_array);
    }

    /**
     * Adds a new trigger object to a bot's trigger configuration.
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @param array $trigger_object The trigger object to add. Must include a unique 'id'.
     * @return bool|WP_Error True on success, WP_Error on failure or if ID conflicts.
     */
    public function add_trigger(int $bot_id, array $trigger_object): bool|WP_Error {
        if (!$this->adder) return new WP_Error('handler_not_initialized', 'Trigger Adder not available.');
        return $this->adder->add_trigger($bot_id, $trigger_object);
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
        if (!$this->updater) return new WP_Error('handler_not_initialized', 'Trigger Updater not available.');
        return $this->updater->update_trigger($bot_id, $trigger_id, $updated_trigger_object);
    }

    /**
     * Deletes a trigger object from a bot's configuration by its ID.
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @param string $trigger_id The ID of the trigger to delete.
     * @return bool True if deletion was successful or if the trigger didn't exist. False on save error.
     */
    public function delete_trigger(int $bot_id, string $trigger_id): bool {
        if (!$this->deleter) return false;
        return $this->deleter->delete_trigger($bot_id, $trigger_id);
    }
}
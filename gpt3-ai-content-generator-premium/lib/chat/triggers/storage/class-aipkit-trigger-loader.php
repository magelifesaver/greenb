<?php

namespace WPAICG\Lib\Chat\Triggers\Storage; // UPDATED Namespace

use WPAICG\Chat\Admin\AdminSetup; // This path is from /classes/
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage; // For META_KEY from /lib/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Loader
 *
 * Handles retrieving trigger configurations for chatbots.
 */
class AIPKit_Trigger_Loader {

    /**
     * Retrieves the array of trigger objects for a given bot ID.
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @return array An array of trigger objects, or an empty array if none found or on error.
     */
    public function get_triggers(int $bot_id): array {
        // Ensure AdminSetup class is loaded for POST_TYPE constant
        if (!class_exists(\WPAICG\Chat\Admin\AdminSetup::class)) {
            $admin_setup_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/chat_admin_setup.php';
            if (file_exists($admin_setup_path)) {
                require_once $admin_setup_path;
            } else {
                return [];
            }
        }
        // Ensure AIPKit_Trigger_Storage is loaded for META_KEY constant (it's in /lib/chat/triggers/)
        if (!class_exists(\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage::class)) {
             $storage_class_path = dirname(__DIR__) . '/class-aipkit-trigger-storage.php'; // Relative to this file's directory
             if (file_exists($storage_class_path)) {
                 require_once $storage_class_path;
             } else {
                 return []; // Cannot proceed without META_KEY
             }
        }


        if (get_post_type($bot_id) !== \WPAICG\Chat\Admin\AdminSetup::POST_TYPE) {
            return [];
        }

        $triggers_json = get_post_meta($bot_id, \WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage::META_KEY, true);
        if (empty($triggers_json) || !is_string($triggers_json)) {
            return [];
        }

        $triggers_array = json_decode($triggers_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($triggers_array)) {
            return [];
        }

        return $triggers_array;
    }
}
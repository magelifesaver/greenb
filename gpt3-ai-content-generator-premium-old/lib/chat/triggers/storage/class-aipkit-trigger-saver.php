<?php

namespace WPAICG\Lib\Chat\Triggers\Storage; // UPDATED Namespace

use WPAICG\Chat\Admin\AdminSetup; // This path is from /classes/
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage; // For META_KEY from /lib/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Trigger_Saver
 *
 * Handles saving trigger configurations for chatbots.
 */
class AIPKit_Trigger_Saver {

    /**
     * Saves an array of trigger objects for a given bot ID.
     * Overwrites any existing triggers for that bot.
     *
     * @param int $bot_id The ID of the chatbot CPT.
     * @param array $triggers_array An array of trigger objects to save.
     * @return bool True on success, false on failure.
     */
    public function save_triggers(int $bot_id, array $triggers_array): bool {
        // Ensure AdminSetup class is loaded for POST_TYPE constant
        if (!class_exists(\WPAICG\Chat\Admin\AdminSetup::class)) {
            $admin_setup_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/chat_admin_setup.php';
            if (file_exists($admin_setup_path)) {
                require_once $admin_setup_path;
            } else {
                return false;
            }
        }
         // Ensure AIPKit_Trigger_Storage is loaded for META_KEY constant
         if (!class_exists(\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage::class)) {
             $storage_class_path = dirname(__DIR__) . '/class-aipkit-trigger-storage.php'; // Relative to this file's directory
             if (file_exists($storage_class_path)) {
                 require_once $storage_class_path;
             } else {
                 return false; // Cannot proceed without META_KEY
             }
        }

        if (get_post_type($bot_id) !== \WPAICG\Chat\Admin\AdminSetup::POST_TYPE) {
            return false;
        }

        $triggers_json = wp_json_encode($triggers_array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return update_post_meta($bot_id, \WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage::META_KEY, $triggers_json);
    }
}
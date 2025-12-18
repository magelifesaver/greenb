<?php

namespace WPAICG\Chat\Admin\Ajax;

use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Chat\Storage\BotLifecycleManager;
use WPAICG\Chat\Storage\BotSettingsManager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles AJAX requests for importing Chatbot data from a JSON file.
 */
class ChatbotImportAjaxHandler extends BaseAjaxHandler {

    private $bot_storage;
    private $lifecycle_manager;
    private $settings_manager;

    public function __construct() {
        // Ensure dependencies exist and instantiate
        if (!class_exists(\WPAICG\Chat\Storage\BotStorage::class)) {
            return;
        }
        if (!class_exists(\WPAICG\Chat\Storage\BotLifecycleManager::class)) {
            return;
        }
         if (!class_exists(\WPAICG\Chat\Storage\BotSettingsManager::class)) {
            return;
        }
        $this->bot_storage = new BotStorage();
        $this->lifecycle_manager = new BotLifecycleManager();
        $this->settings_manager = new BotSettingsManager();
    }

    /**
     * AJAX: Imports chatbots from an uploaded JSON file.
     */
    public function ajax_import_chatbots() {
        $permission_check = $this->check_module_access_permissions('chatbot');
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        if (!$this->lifecycle_manager || !$this->settings_manager) {
             $this->send_wp_error(new WP_Error('dependency_missing', __('Required components for import are missing.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        // Check if file was uploaded
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification is handled in check_module_access_permissions method.
        $files_data = wp_unslash($_FILES);
        if (!isset($files_data['aipkit_import_file']) || empty($files_data['aipkit_import_file']['tmp_name'])) {
            $this->send_wp_error(new WP_Error('no_file', __('No import file provided.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        $file = $files_data['aipkit_import_file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
             $this->send_wp_error(new WP_Error('upload_error', __('Error uploading file: Code ', 'gpt3-ai-content-generator') . $file['error'], ['status' => 400]));
            return;
        }

        // *** REVISED FILE TYPE CHECK ***
        // Instead of wp_check_filetype, check the extension directly.
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'json') {
             $this->send_wp_error(new WP_Error('invalid_file_type', __('Invalid file type. Please upload a JSON file ending with .json.', 'gpt3-ai-content-generator'), ['status' => 400]));
             return;
        }
        // *** END REVISED CHECK ***

        // Read file content
        $json_content = file_get_contents($file['tmp_name']);
        if ($json_content === false) {
            $this->send_wp_error(new WP_Error('read_error', __('Could not read import file.', 'gpt3-ai-content-generator'), ['status' => 500]));
            return;
        }

        // Decode JSON (This is the primary validation for content format)
        $import_data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->send_wp_error(new WP_Error('json_decode_error', __('Invalid JSON file. Error: ', 'gpt3-ai-content-generator') . json_last_error_msg(), ['status' => 400]));
            return;
        }

        // Validate structure (should be an array of objects with 'title' and 'settings')
        if (!is_array($import_data)) {
            $this->send_wp_error(new WP_Error('invalid_json_structure', __('Invalid JSON structure: Expected an array of bots.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }

        $imported_count = 0;
        $failed_count = 0;
        $errors = [];

        foreach ($import_data as $index => $bot_data) {
            if (!is_array($bot_data) || !isset($bot_data['title']) || !isset($bot_data['settings']) || !is_array($bot_data['settings'])) {
                $failed_count++;
                /* translators: %d: The numerical index of the bot in the import file. */
                $errors[] = sprintf(__('Skipped bot at index %d: Invalid data structure.', 'gpt3-ai-content-generator'), $index);
                continue;
            }

            $bot_title = sanitize_text_field($bot_data['title']);
            $bot_settings = $bot_data['settings']; // Settings will be sanitized during save

            if (empty($bot_title)) {
                 $failed_count++;
                 /* translators: %d: The numerical index of the bot in the import file. */
                 $errors[] = sprintf(__('Skipped bot at index %d: Title cannot be empty.', 'gpt3-ai-content-generator'), $index);
                 continue;
            }

            // --- REMOVED: Appending "(imported)" suffix ---
            $import_title = $bot_title; // Use the original title
            // --- END REMOVAL ---

            // Create the bot using Lifecycle Manager
            $create_result = $this->lifecycle_manager->create_bot($import_title);

            if (is_wp_error($create_result)) {
                $failed_count++;
                /* translators: %1$s: The title of the chatbot. %2$s: The error message. */
                $errors[] = sprintf(__('Failed to create bot "%1$s": %2$s', 'gpt3-ai-content-generator'), $import_title, $create_result->get_error_message());
                continue;
            }

            $new_bot_id = $create_result['bot_id'];

            // Save the imported settings using Settings Manager
            $save_result = $this->settings_manager->save_bot_settings($new_bot_id, $bot_settings);

            if (is_wp_error($save_result)) {
                 $failed_count++;
                 /* translators: %1$s: The title of the chatbot. %2$d: The new bot's ID. %3$s: The error message. */
                 $errors[] = sprintf(__('Created bot "%1$s" (ID: %2$d) but failed to save settings: %3$s', 'gpt3-ai-content-generator'), $import_title, $new_bot_id, $save_result->get_error_message());
                 // Optionally delete the partially created bot? For now, leave it.
            } else {
                 $imported_count++;
            }
        } // End foreach loop
        /* translators: %1$d: The number of chatbots imported. */
        $message = sprintf(_n('%1$d chatbot imported successfully.', '%1$d chatbots imported successfully.', $imported_count, 'gpt3-ai-content-generator'), $imported_count);
        if ($failed_count > 0) {
            /* translators: %d: The number of chatbots that failed to import. */
            $message .= ' ' . sprintf(_n('%d bot failed.', '%d bots failed.', $failed_count, 'gpt3-ai-content-generator'), $failed_count);
             // Add detailed errors to the main message if any occurred
             if (!empty($errors)) {
                 $message .= ' ' . __('Details:', 'gpt3-ai-content-generator') . ' ' . implode('; ', $errors);
             }
        }

        wp_send_json_success([
            'message' => $message,
            'imported' => $imported_count,
            'failed' => $failed_count,
            'errors' => $errors, // Send back detailed errors if any (for console/debug)
        ]);
    }
}
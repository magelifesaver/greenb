<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/class-aipkit-content-writer-create-task-action.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions;

use WPAICG\ContentWriter\Ajax\AIPKit_Content_Writer_Base_Ajax_Action;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

// Load the new modular logic files
$logic_path = __DIR__ . '/create-task/';
require_once $logic_path . 'validate-permissions.php';
require_once $logic_path . 'normalize-task-settings.php';
require_once $logic_path . 'build-content-writer-config.php';
require_once $logic_path . 'validate-task-requirements.php';
require_once $logic_path . 'insert-task-into-db.php';
require_once $logic_path . 'schedule-task-if-active.php';

/**
* Handles the AJAX action for creating an Automated Task from the Content Writer UI.
* This class now orchestrates calls to modularized logic functions.
*/
class AIPKit_Content_Writer_Create_Task_Action extends AIPKit_Content_Writer_Base_Ajax_Action
{
    public function handle()
    {
        // 1. Validate permissions
        $permission_check = CreateTask\validate_permissions_logic($this);
        if (is_wp_error($permission_check)) {
            $this->send_wp_error($permission_check);
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce is checked in validate_permissions_logic.
        $raw_settings = isset($_POST) ? wp_unslash($_POST) : [];

        // --- NEW: Google Sheets Logic ---
        $generation_mode = $raw_settings['cw_generation_mode'] ?? 'single';
        if ($generation_mode === 'gsheets') {
            if (class_exists('\WPAICG\Lib\ContentWriter\AIPKit_Google_Sheets_Parser')) {
                $sheet_id = $raw_settings['gsheets_sheet_id'] ?? '';
                $credentials_json = $raw_settings['gsheets_credentials'] ?? '';

                if (empty($sheet_id) || empty($credentials_json)) {
                    $this->send_wp_error(new WP_Error('missing_gsheets_info', __('Google Sheet ID and Credentials are required.', 'gpt3-ai-content-generator')), 400);
                    return;
                }
                try {
                    // --- MODIFICATION: The parser now expects an array. Decode the JSON string from the form. ---
                    $credentials_array = json_decode($credentials_json, true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($credentials_array)) {
                        throw new \Exception('Invalid JSON format for Google credentials.');
                    }
                    $sheets_parser = new \WPAICG\Lib\ContentWriter\AIPKit_Google_Sheets_Parser($credentials_array);
                    // --- END MODIFICATION ---

                    $verification_result = $sheets_parser->verify_access($sheet_id);
                    if (is_wp_error($verification_result)) {
                        $this->send_wp_error($verification_result);
                        return;
                    }
                } catch (\Exception $e) {
                    $this->send_wp_error(new WP_Error('gsheets_parser_error', 'Failed to process Google Sheet: ' . $e->getMessage()), 500);
                    return;
                }
            } else {
                $this->send_wp_error(new WP_Error('gsheets_pro_feature_missing', __('Google Sheets integration is a Pro feature or its components are missing.', 'gpt3-ai-content-generator')), 403);
                return;
            }
        }
        // --- END NEW ---

        // 2. Normalize basic task settings (name, frequency, status)
        $normalized_task_settings = CreateTask\normalize_task_settings_logic($raw_settings);
        $task_name = $normalized_task_settings['task_name'];
        $task_frequency = $normalized_task_settings['task_frequency'];
        $task_status = $normalized_task_settings['task_status'];

        // 3. Build the specific config for the content writer task
        $content_writer_config = CreateTask\build_content_writer_config_logic($raw_settings, $task_frequency, $task_status);

        // 4. Validate that the built config has all requirements
        $requirements_check = CreateTask\validate_task_requirements_logic($content_writer_config);
        if (is_wp_error($requirements_check)) {
            $this->send_wp_error($requirements_check);
            return;
        }

        // --- START FIX: Determine task type based on generation mode sent from JS ---
        $mode_map = [
            'task'    => 'content_writing_bulk',
            'csv'     => 'content_writing_csv',
            'rss'     => 'content_writing_rss',
            'url'     => 'content_writing_url',
            'gsheets' => 'content_writing_gsheets',
        ];
        $task_type = $mode_map[$generation_mode] ?? 'content_writing_bulk'; // Fallback to bulk for safety.
        // --- END FIX ---

        // 5. Insert the task into the database
        $insert_result = CreateTask\insert_task_into_db_logic($task_name, $task_type, $content_writer_config, $task_status);
        if (is_wp_error($insert_result)) {
            $this->send_wp_error($insert_result);
            return;
        }
        $new_task_id = $insert_result;

        // 6. Schedule the cron event if the task is active
        CreateTask\schedule_task_if_active_logic($new_task_id, $task_status, $task_frequency);

        // 7. Send success response
        wp_send_json_success([
            'message' => __('Your content writing task is queued. You can track it under the Automated Tasks tab.', 'gpt3-ai-content-generator'),
            'task_id' => $new_task_id
        ]);
    }
}

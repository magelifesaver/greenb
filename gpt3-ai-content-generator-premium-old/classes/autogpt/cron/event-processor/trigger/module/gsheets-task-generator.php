<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/trigger/module/gsheets-task-generator.php
// Status: NEW FILE

namespace WPAICG\AutoGPT\Cron\EventProcessor\Trigger\Modules;

use WPAICG\aipkit_dashboard;
use WPAICG\Lib\ContentWriter\AIPKit_Google_Sheets_Parser;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates items to be queued from a Google Sheet.
 *
 * @param int $task_id The ID of the task.
 * @param array $task_config The configuration of the task.
 * @return array|WP_Error An array of items or WP_Error on failure.
 */
function gsheets_mode_generate_items_logic(int $task_id, array $task_config): array|WP_Error
{
    if (!aipkit_dashboard::is_pro_plan() || !class_exists(AIPKit_Google_Sheets_Parser::class)) {
        return new WP_Error('gsheets_feature_unavailable', __('Google Sheets integration is a Pro feature or its components are missing.', 'gpt3-ai-content-generator'), ['status' => 403]);
    }

    $sheet_id = $task_config['gsheets_sheet_id'] ?? '';
    $credentials_array = $task_config['gsheets_credentials'] ?? [];

    if (empty($sheet_id) || empty($credentials_array) || !is_array($credentials_array)) {
        return new WP_Error('missing_gsheets_config', __('Google Sheets task is missing Sheet ID or credentials.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    try {
        $sheets_parser = new AIPKit_Google_Sheets_Parser($credentials_array);
        $topics_from_sheet = $sheets_parser->get_rows_from_sheet($sheet_id);

        if (is_wp_error($topics_from_sheet)) {
            return $topics_from_sheet;
        }

        return $topics_from_sheet;
    } catch (\Exception $e) {
        return new WP_Error('gsheets_parser_exception', 'Error processing Google Sheet: ' . $e->getMessage(), ['status' => 500]);
    }
}
<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/template-manager/get-template.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\TemplateManagerMethods;

use WPAICG\AIPKIT_AI_Settings;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* Logic for retrieving a single template's data.
*
* @param \WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance The instance of the template manager.
* @param int $template_id The ID of the template to retrieve.
* @param int|null $user_id_override Optional user ID to override the current user.
* @return array|WP_Error The template data array or a WP_Error on failure.
*/
function get_template_logic(\WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance, int $template_id, ?int $user_id_override = null): array|WP_Error
{
    $wpdb = $managerInstance->get_wpdb();
    $table_name = $managerInstance->get_table_name();

    $user_id = $user_id_override ?? get_current_user_id();
    if (!$user_id && $user_id_override !== 0) {
        return new WP_Error('not_logged_in_get', __('User must be logged in to get templates.', 'gpt3-ai-content-generator'));
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
    $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d AND user_id = %d", $template_id, $user_id), ARRAY_A);

    if (!$template) {
        return new WP_Error('template_not_found', __('Template not found or access denied.', 'gpt3-ai-content-generator'));
    }

    // Decode the config JSON
    $config = json_decode($template['config'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $template['config'] = [];
    } else {
        // Handle Google Sheets credentials if they exist
        if (isset($config['gsheets_credentials'])) {
            $creds = $config['gsheets_credentials'];

            // If it's a string, it might be double-encoded JSON from a previous bug. Try to decode it.
            if (is_string($creds)) {
                $decoded_creds = json_decode($creds, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_creds)) {
                    // It was a JSON string, replace it with the decoded array.
                    $config['gsheets_credentials'] = $decoded_creds;
                }
            }
        }
        $template['config'] = $config;
    }

    if (isset($template['post_categories'])) {
        $cat_ids_from_db = json_decode($template['post_categories'], true);
        $template['config']['post_categories'] = is_array($cat_ids_from_db) ? array_map('absint', $cat_ids_from_db) : [];
    } elseif (isset($template['config']['post_categories'])) {
        if (is_string($template['config']['post_categories'])) {
            $cat_input = array_map('trim', explode(',', $template['config']['post_categories']));
            $cat_ids = array_filter(array_map('absint', $cat_input), function ($id) {
                return $id > 0;
            });
            $template['config']['post_categories'] = array_values(array_unique($cat_ids));
        } elseif (!is_array($template['config']['post_categories'])) {
            $template['config']['post_categories'] = [];
        } else {
            $template['config']['post_categories'] = array_values(array_unique(array_map('absint', $template['config']['post_categories'])));
        }
    } else {
        $template['config']['post_categories'] = [];
    }
    unset($template['config']['post_tags']);

    $db_config_keys = ['post_type', 'post_author', 'post_status'];
    foreach ($db_config_keys as $key) {
        if (!isset($template['config'][$key]) && isset($template[$key])) {
            $template['config'][$key] = $template[$key];
        }
    }
    if (isset($template['post_schedule']) && $template['post_schedule'] !== null && $template['post_schedule'] !== '0000-00-00 00:00:00') {
        $schedule_timestamp = strtotime($template['post_schedule']);
        $template['config']['post_schedule_date'] = wp_date('Y-m-d', $schedule_timestamp);
        $template['config']['post_schedule_time'] = wp_date('H:i', $schedule_timestamp);
    } else {
        $template['config']['post_schedule_date'] = '';
        $template['config']['post_schedule_time'] = '';
    }

    if (!isset($template['config']['content_max_tokens']) && class_exists(AIPKIT_AI_Settings::class)) {
        $ai_parameters = AIPKIT_AI_Settings::get_ai_parameters();
        $template['config']['content_max_tokens'] = (string)($ai_parameters['max_completion_tokens'] ?? 4000);
    }

    return $template;
}

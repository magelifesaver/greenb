<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/template-manager/get-templates-for-user.php
// Status: MODIFIED
// I have updated this file to allow users with the 'administrator' role to view all templates created by any user, grouped by user.

namespace WPAICG\ContentWriter\TemplateManagerMethods;

use WPAICG\AIPKIT_AI_Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Logic for retrieving all templates for the current user.
*
* @param \WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance The instance of the template manager.
* @param string $type The type of template to retrieve.
* @return array An array of template objects.
*/
function get_templates_for_user_logic(\WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance, string $type = 'content_writer'): array
{
    $wpdb = $managerInstance->get_wpdb();
    $table_name = $managerInstance->get_table_name();

    $user_id = get_current_user_id();
    if (!$user_id) {
        return [];
    }

    $all_templates_raw = [];

    // All users (including admins) get their own templates first.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
    $user_templates_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT t.*, u.display_name FROM {$table_name} t LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID WHERE t.user_id = %d AND t.template_type = %s ORDER BY t.is_default DESC, t.template_name ASC",
        $user_id,
        $type
    ), ARRAY_A);

    if (!empty($user_templates_raw)) {
        $all_templates_raw = array_merge($all_templates_raw, $user_templates_raw);
    }

    // If user is an admin, get templates from all OTHER users.
    if (current_user_can('manage_options')) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
        $other_users_templates_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.display_name FROM {$table_name} t LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID WHERE t.user_id != %d AND t.template_type = %s AND t.is_default = 0 ORDER BY u.display_name ASC, t.template_name ASC",
            $user_id,
            $type
        ), ARRAY_A);

        if (!empty($other_users_templates_raw)) {
            $all_templates_raw = array_merge($all_templates_raw, $other_users_templates_raw);
        }
    }

    $templates = [];
    $process_raw_template = function ($raw_template) {
        if (!$raw_template) {
            return null;
        }
        $config = json_decode($raw_template['config'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $config = [];
        } else {
            if (isset($config['gsheets_credentials'])) {
                $creds = $config['gsheets_credentials'];
                if (is_string($creds)) {
                    $decoded_creds = json_decode($creds, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_creds)) {
                        $config['gsheets_credentials'] = $decoded_creds;
                    }
                }
            }
        }
        $raw_template['config'] = $config;

        if (isset($raw_template['post_categories'])) {
            $cat_ids_from_db = json_decode($raw_template['post_categories'], true);
            $raw_template['config']['post_categories'] = is_array($cat_ids_from_db) ? array_map('absint', $cat_ids_from_db) : [];
        } elseif (isset($raw_template['config']['post_categories'])) {
            if (is_string($raw_template['config']['post_categories'])) {
                $cat_input = array_map('trim', explode(',', $raw_template['config']['post_categories']));
                $cat_ids = array_filter(array_map('absint', $cat_input), fn ($id) => $id > 0);
                $raw_template['config']['post_categories'] = array_values(array_unique($cat_ids));
            } elseif (!is_array($raw_template['config']['post_categories'])) {
                $raw_template['config']['post_categories'] = [];
            } else {
                $raw_template['config']['post_categories'] = array_values(array_unique(array_map('absint', $raw_template['config']['post_categories'])));
            }
        } else {
            $raw_template['config']['post_categories'] = [];
        }
        unset($raw_template['config']['post_tags']);

        $db_config_keys = ['post_type', 'post_author', 'post_status'];
        foreach ($db_config_keys as $key) {
            if (!isset($raw_template['config'][$key]) && isset($raw_template[$key])) {
                $raw_template['config'][$key] = $raw_template[$key];
            }
        }
        if (isset($raw_template['post_schedule']) && $raw_template['post_schedule'] !== null && $raw_template['post_schedule'] !== '0000-00-00 00:00:00') {
            $ts = strtotime($raw_template['post_schedule']);
            $raw_template['config']['post_schedule_date'] = wp_date('Y-m-d', $ts);
            $raw_template['config']['post_schedule_time'] = wp_date('H:i', $ts);
        } else {
            $raw_template['config']['post_schedule_date'] = '';
            $raw_template['config']['post_schedule_time'] = '';
        }

        if (!isset($raw_template['config']['content_max_tokens']) && class_exists(AIPKIT_AI_Settings::class)) {
            $ai_parameters = AIPKIT_AI_Settings::get_ai_parameters();
            $raw_template['config']['content_max_tokens'] = (string)($ai_parameters['max_completion_tokens'] ?? 4000);
        }

        return $raw_template;
    };

    if (is_array($all_templates_raw)) {
        foreach ($all_templates_raw as $template_raw) {
            $processed_template = $process_raw_template($template_raw);
            if ($processed_template) {
                $templates[] = $processed_template;
            }
        }
    }
    return $templates;
}
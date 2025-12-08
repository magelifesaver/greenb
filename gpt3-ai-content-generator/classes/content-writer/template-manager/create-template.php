<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/template-manager/create-template.php
// Status: MODIFIED
// I have added a `template_type` parameter to differentiate between template types (e.g. content_writer, enhancer).

namespace WPAICG\ContentWriter\TemplateManagerMethods;

use WP_Error;

// Load dependencies
require_once __DIR__ . '/sanitize-config.php';
require_once __DIR__ . '/calculate-schedule-datetime.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* Logic for creating a new template.
*
* @param \WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance The instance of the template manager.
* @param string $template_name The name for the new template.
* @param array $config The configuration array for the template.
* @param string $template_type The type of template (e.g., 'content_writer').
* @return int|WP_Error The new post ID or a WP_Error on failure.
*/
function create_template_logic(\WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance, string $template_name, array $config, string $template_type = 'content_writer'): int|WP_Error
{
    $wpdb = $managerInstance->get_wpdb();
    $table_name = $managerInstance->get_table_name();

    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_Error('not_logged_in', __('User must be logged in to create templates.', 'gpt3-ai-content-generator'));
    }
    if (empty($template_name)) {
        return new WP_Error('empty_template_name', __('Template name cannot be empty.', 'gpt3-ai-content-generator'));
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_name} WHERE user_id = %d AND template_name = %s AND template_type = %s",
        $user_id,
        $template_name,
        $template_type
    ));
    if ($existing) {
        return new WP_Error('duplicate_template_name', __('A template with this name already exists for your account.', 'gpt3-ai-content-generator'));
    }

    $sanitized_config = sanitize_config_logic($managerInstance, $config);
    $post_schedule_datetime = calculate_schedule_datetime_logic($sanitized_config['post_schedule_date'] ?? '', $sanitized_config['post_schedule_time'] ?? '');
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct insert to a custom table. Caches will be invalidated.
    $result = $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'template_name' => sanitize_text_field($template_name),
            'template_type' => sanitize_key($template_type),
            'config' => wp_json_encode($sanitized_config),
            'is_default' => 0,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1),
            'post_type' => $sanitized_config['post_type'] ?? 'post',
            'post_author' => $sanitized_config['post_author'] ?? $user_id,
            'post_status' => $sanitized_config['post_status'] ?? 'draft',
            'post_schedule' => $post_schedule_datetime,
            'post_categories' => wp_json_encode($sanitized_config['post_categories'] ?? []),
            ],
        ['%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
    );

    if ($result === false) {
        return new WP_Error('db_insert_error', __('Failed to save template.', 'gpt3-ai-content-generator'));
    }
    return $wpdb->insert_id;
}
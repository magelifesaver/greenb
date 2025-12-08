<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/template-manager/update-template.php
// Status: MODIFIED
// I have updated this file to allow administrators to update or rename templates belonging to any user.

namespace WPAICG\ContentWriter\TemplateManagerMethods;

use WP_Error;

// Load dependencies
require_once __DIR__ . '/sanitize-config.php';
require_once __DIR__ . '/calculate-schedule-datetime.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* Logic for updating an existing template.
*
* @param \WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance The instance of the template manager.
* @param int $template_id The ID of the template to update.
* @param string $template_name The new name for the template.
* @param array $config The new configuration for the template.
* @return bool|WP_Error True on success, or a WP_Error on failure.
*/
function update_template_logic(\WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance, int $template_id, string $template_name, array $config): bool|WP_Error
{
    $wpdb = $managerInstance->get_wpdb();
    $table_name = $managerInstance->get_table_name();

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        return new WP_Error('not_logged_in', __('User must be logged in to update templates.', 'gpt3-ai-content-generator'));
    }
    if (empty($template_name)) {
        return new WP_Error('empty_template_name', __('Template name cannot be empty.', 'gpt3-ai-content-generator'));
    }

    // Get the original template to check ownership and type
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $original_template = $wpdb->get_row($wpdb->prepare("SELECT user_id, template_type, is_default FROM {$table_name} WHERE id = %d", $template_id), ARRAY_A);

    if (!$original_template) {
        return new WP_Error('template_not_found', __('Template not found.', 'gpt3-ai-content-generator'));
    }

    $is_admin = current_user_can('manage_options');
    $is_owner = ((int) $original_template['user_id'] === $current_user_id);

    // Only owners or administrators can update a template.
    if (!$is_owner && !$is_admin) {
        return new WP_Error('permission_denied', __('You do not have permission to update this template.', 'gpt3-ai-content-generator'));
    }

    // Check for duplicate name for the original owner of the template.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name} WHERE user_id = %d AND template_name = %s AND template_type = %s AND id != %d",
        $original_template['user_id'],
        $template_name,
        $original_template['template_type'],
        $template_id
    ));
    if ($existing) {
        return new WP_Error('duplicate_template_name_update', __('Another template with this name already exists for the owner of this template.', 'gpt3-ai-content-generator'));
    }

    $sanitized_config = sanitize_config_logic($managerInstance, $config);
    $post_schedule_datetime = calculate_schedule_datetime_logic($sanitized_config['post_schedule_date'] ?? '', $sanitized_config['post_schedule_time'] ?? '');

    $data_to_update = [
        'template_name' => sanitize_text_field($template_name),
        'config' => wp_json_encode($sanitized_config),
        'updated_at' => current_time('mysql', 1),
        'post_type' => $sanitized_config['post_type'] ?? 'post',
        'post_author' => $sanitized_config['post_author'] ?? $original_template['user_id'], // Keep original author if not specified
        'post_status' => $sanitized_config['post_status'] ?? 'draft',
        'post_schedule' => $post_schedule_datetime,
        'post_categories' => wp_json_encode($sanitized_config['post_categories'] ?? []),
    ];
    $data_formats = ['%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s'];

    $where = ['id' => $template_id];
    $where_formats = ['%d'];
    if (!$is_admin) {
        $where['user_id'] = $current_user_id;
        $where_formats[] = '%d';
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $result = $wpdb->update($table_name, $data_to_update, $where, $data_formats, $where_formats);

    if ($result === false) {
        return new WP_Error('db_update_error', __('Failed to update template.', 'gpt3-ai-content-generator'));
    }
    return true;
}
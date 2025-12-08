<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/template-manager/delete-template.php
// Status: MODIFIED
// I have updated this file to allow administrators to delete any user's template, while standard users can only delete their own.

namespace WPAICG\ContentWriter\TemplateManagerMethods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* Logic for deleting a template.
*
* @param \WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance The instance of the template manager.
* @param int $template_id The ID of the template to delete.
* @return bool|WP_Error True on success, or a WP_Error on failure.
*/
function delete_template_logic(\WPAICG\ContentWriter\AIPKit_Content_Writer_Template_Manager $managerInstance, int $template_id): bool|WP_Error
{
    $wpdb = $managerInstance->get_wpdb();
    $table_name = $managerInstance->get_table_name();
    $user_id = get_current_user_id();

    if (!$user_id) {
        return new WP_Error('not_logged_in', __('User must be logged in to delete templates.', 'gpt3-ai-content-generator'));
    }

    // Prepare the WHERE clause for the delete operation.
    $where = ['id' => $template_id];
    $where_formats = ['%d'];

    // If the current user is NOT an administrator, they can only delete their own templates.
    if (!current_user_can('manage_options')) {
        $where['user_id'] = $user_id;
        $where_formats[] = '%d';
    }
    // Administrators do not have the user_id constraint, allowing them to delete any template by its ID.

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct query to a custom table.
    $result = $wpdb->delete($table_name, $where, $where_formats);

    if ($result === false) {
        return new WP_Error('db_delete_error', __('Failed to delete template from the database.', 'gpt3-ai-content-generator'));
    }
    if ($result === 0) {
        // This can happen if a non-admin tries to delete another user's template.
        return new WP_Error('delete_permission_denied', __('Template not found or you do not have permission to delete it.', 'gpt3-ai-content-generator'));
    }

    return true;
}
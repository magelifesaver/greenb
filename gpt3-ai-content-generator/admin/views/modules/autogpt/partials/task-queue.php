<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/task-queue.php
// Status: MODIFIED
/**
 * Partial: Automated Task Queue Viewer
 * Displays items currently in the task queue.
 * MODIFIED: Changed the "Delete Filtered" button to an icon-only button for a cleaner UI.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="aipkit_automated_task_queue_wrapper">
    <div class="aipkit_task_queue_controls">
        <h4 style="margin:0;"><?php esc_html_e('Task Queue', 'gpt3-ai-content-generator'); ?></h4>
        <div class="aipkit_filter_group">
            <input type="search" id="aipkit_task_queue_search_input" class="aipkit_form-input" placeholder="<?php esc_attr_e('Search by item or task name...', 'gpt3-ai-content-generator'); ?>">
            <select id="aipkit_task_queue_status_filter" class="aipkit_form-input">
                <option value="all"><?php esc_html_e('All Statuses', 'gpt3-ai-content-generator'); ?></option>
                <option value="pending"><?php esc_html_e('Pending', 'gpt3-ai-content-generator'); ?></option>
                <option value="processing"><?php esc_html_e('Processing', 'gpt3-ai-content-generator'); ?></option>
                <option value="completed"><?php esc_html_e('Completed', 'gpt3-ai-content-generator'); ?></option>
                <option value="failed"><?php esc_html_e('Failed', 'gpt3-ai-content-generator'); ?></option>
            </select>
            <button id="aipkit_delete_queue_by_status_btn" class="aipkit_btn aipkit_btn-danger" title="<?php esc_attr_e('Delete items matching filter', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-trash"></span>
                <span class="aipkit_spinner" style="display:none;"></span>
            </button>
            <button id="aipkit_refresh_task_queue_btn" class="aipkit_btn aipkit_btn-secondary" title="<?php esc_attr_e('Refresh Queue', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-update-alt"></span>
                <span class="aipkit_spinner" style="display:none;"></span>
            </button>
        </div>
    </div>
    <div id="aipkit_automated_task_queue_viewer_area" class="aipkit_data-table">
        <table>
            <thead>
                <tr>
                    <th class="aipkit-sortable-col" data-sort-by="q.target_identifier"><?php esc_html_e('Item', 'gpt3-ai-content-generator'); ?></th>
                    <th class="aipkit-sortable-col" data-sort-by="t.task_name"><?php esc_html_e('Name', 'gpt3-ai-content-generator'); ?></th>
                    <th class="aipkit-sortable-col" data-sort-by="q.task_type"><?php esc_html_e('Type', 'gpt3-ai-content-generator'); ?></th>
                    <th class="aipkit-sortable-col" data-sort-by="q.status"><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></th>
                    <th class="aipkit-sortable-col" data-sort-by="q.attempts"><?php esc_html_e('Attempts', 'gpt3-ai-content-generator'); ?></th>
                    <th class="aipkit-sortable-col" data-sort-by="q.added_at"><?php esc_html_e('Added At', 'gpt3-ai-content-generator'); ?></th>
                    <th style="text-align: right;"><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                </tr>
            </thead>
            <tbody id="aipkit_automated_task_queue_tbody">
                <tr><td colspan="7" class="aipkit_text-center">Loading queue...</td></tr>
            </tbody>
        </table>
         <div id="aipkit_automated_task_queue_pagination" class="aipkit_pagination" style="margin-top:10px;"></div>
    </div>
    <div id="aipkit_automated_task_queue_status" class="aipkit_form-help" style="margin-top: 10px; min-height: 1.5em;"></div>
</div>
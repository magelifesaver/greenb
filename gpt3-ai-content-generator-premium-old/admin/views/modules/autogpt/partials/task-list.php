<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/task-list.php
// Status: MODIFIED
/**
 * Partial: Automated Task List
 * Displays the table of existing automated tasks.
 * MODIFIED: Added search input and sortable table headers.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="aipkit_automated_task_list_wrapper">
    <div class="aipkit_task_list_controls">
        <h4 style="margin:0;"><?php esc_html_e('Automated Tasks', 'gpt3-ai-content-generator'); ?></h4>
        <div class="aipkit_filter_group">
            <input type="search" id="aipkit_task_list_search_input" class="aipkit_form-input" placeholder="<?php esc_attr_e('Search tasks by name...', 'gpt3-ai-content-generator'); ?>">
        </div>
    </div>

    <div class="aipkit_data-table">
        <table>
            <thead>
                <tr>
                    <th class="aipkit-sortable-col" data-sort-by="task_name"><?php esc_html_e('Name', 'gpt3-ai-content-generator'); ?></th>
                    <th class="aipkit-sortable-col" data-sort-by="task_type"><?php esc_html_e('Type', 'gpt3-ai-content-generator'); ?></th>
                    <th><?php esc_html_e('Frequency', 'gpt3-ai-content-generator'); ?></th>
                    <th class="aipkit-sortable-col" data-sort-by="status"><?php esc_html_e('Status', 'gpt3-ai-content-generator'); ?></th>
                    <th class="aipkit-sortable-col" data-sort-by="last_run_time"><?php esc_html_e('Last Run', 'gpt3-ai-content-generator'); ?></th>
                    <th class="aipkit-sortable-col" data-sort-by="next_run_time"><?php esc_html_e('Next Run', 'gpt3-ai-content-generator'); ?></th>
                    <th style="text-align: right;"><?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                </tr>
            </thead>
            <tbody id="aipkit_automated_tasks_tbody">
                <tr><td colspan="7" class="aipkit_text-center">Loading...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="aipkit_automated_task_list_pagination" class="aipkit_pagination" style="margin-top:10px;"></div>
     <div id="aipkit_automated_task_status" class="aipkit_form-help" style="min-height: 1.5em;"></div>
</div>
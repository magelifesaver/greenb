<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/content-indexing/source-settings.php
// Status: MODIFIED

/**
 * Partial: Content Indexing Automated Task - Source Settings
 * @since 2.2
 */

if (!defined('ABSPATH')) {
    exit;
}
// Variables from parent: $all_selectable_post_types
?>
<div id="aipkit_task_ci_source_settings">
    <div class="aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_task_content_indexing_post_types"><?php esc_html_e('Post Types to Index', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_task_content_indexing_post_types" name="post_types[]" class="aipkit_form-input" multiple size="5" style="min-height: 80px;">
            <?php foreach ($all_selectable_post_types as $slug => $pt_obj): ?>
                <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($pt_obj->label); ?></option>
            <?php endforeach; ?>
        </select>
        <div class="aipkit_form-help"><?php esc_html_e('Select one or more post types. Ctrl/Cmd + click to select multiple.', 'gpt3-ai-content-generator'); ?></div>
    </div>
     <div class="aipkit_auto_indexer_content_options">
        <label class="aipkit_checkbox-label">
            <input type="checkbox" name="index_existing_now_flag" id="aipkit_task_content_indexing_index_existing" value="1" checked>
            <?php esc_html_e('Queue all existing content for indexing (one-time action).', 'gpt3-ai-content-generator'); ?>
        </label>
        <p class="aipkit_form-help" style="margin-left: 20px; margin-top: -5px; margin-bottom: 10px;">
            <?php esc_html_e('Use this to build your initial knowledge base. The task will run once for all existing content and then this option will be automatically disabled for this task.', 'gpt3-ai-content-generator'); ?>
        </p>
        <label class="aipkit_checkbox-label">
            <input type="checkbox" name="only_new_updated_flag" id="aipkit_task_content_indexing_only_new_updated" value="1">
            <?php esc_html_e('Automatically index new & updated content on a schedule.', 'gpt3-ai-content-generator'); ?>
        </label>
        <p class="aipkit_form-help" style="margin-left: 20px; margin-top: -5px;">
            <?php esc_html_e('Keeps your knowledge base up-to-date by indexing content that is published or updated after the task\'s last run.', 'gpt3-ai-content-generator'); ?>
        </p>
    </div>
</div>
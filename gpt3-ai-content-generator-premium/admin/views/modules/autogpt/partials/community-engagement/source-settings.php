<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/community-engagement/source-settings.php
// Status: NEW FILE

/**
 * Partial: Community Engagement Automated Task - Source Settings
 * This is included in the main "Setup" step of the wizard.
 *
 * @since 2.2.0
 */
if (!defined('ABSPATH')) {
    exit;
}
// Variables from parent: $all_selectable_post_types
?>
<div class="aipkit_form-group aipkit_form-col">
    <label class="aipkit_form-label" for="aipkit_task_comment_reply_post_types"><?php esc_html_e('Post Types to Monitor', 'gpt3-ai-content-generator'); ?></label>
    <select id="aipkit_task_comment_reply_post_types" name="post_types_for_comments[]" class="aipkit_form-input" multiple size="4" style="min-height: 70px;">
        <?php foreach ($all_selectable_post_types as $slug => $pt_obj): ?>
            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($pt_obj->label); ?></option>
        <?php endforeach; ?>
    </select>
    <div class="aipkit_form-help"><?php esc_html_e('The task will only reply to comments on these post types.', 'gpt3-ai-content-generator'); ?></div>
</div>
<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/content-enhancement/source-settings.php
// Status: MODIFIED
// I have added a 'ce_' prefix to all 'name' attributes to prevent collisions with other task forms.
/**
 * Partial: Automated Task Form - Content Enhancement Source Settings
 * This is included in the main "Setup" step of the wizard.
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}
// Variables available from parent: $all_selectable_post_types, $cw_wp_categories, $cw_users_for_author, $cw_post_statuses, $is_pro
?>
<div class="aipkit_form-row">
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_task_ce_post_types"><?php esc_html_e('Post Types to Update', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_task_ce_post_types" name="ce_post_types[]" class="aipkit_form-input" multiple size="4" style="min-height: 105px;" <?php disabled(!$is_pro); ?>>
            <?php foreach ($all_selectable_post_types as $slug => $pt_obj): ?>
                <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($pt_obj->label); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="aipkit_form-help"><?php esc_html_e('Select one or more post types. Ctrl/Cmd + click to select multiple.', 'gpt3-ai-content-generator'); ?></p>
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_task_ce_post_categories"><?php esc_html_e('Categories (Optional)', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_task_ce_post_categories" name="ce_post_categories[]" class="aipkit_form-input" multiple size="4" style="min-height: 105px;" <?php disabled(!$is_pro); ?>>
            <?php foreach ($cw_wp_categories as $category): ?>
                <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="aipkit_form-help"><?php esc_html_e('Leave empty to include all categories.', 'gpt3-ai-content-generator'); ?></p>
    </div>
</div>

<div class="aipkit_form-row">
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_task_ce_post_authors"><?php esc_html_e('Authors (Optional)', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_task_ce_post_authors" name="ce_post_authors[]" class="aipkit_form-input" multiple size="4" style="min-height: 105px;" <?php disabled(!$is_pro); ?>>
            <?php foreach ($cw_users_for_author as $user): ?>
                <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="aipkit_form-help"><?php esc_html_e('Leave empty to include all authors.', 'gpt3-ai-content-generator'); ?></p>
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_task_ce_post_status"><?php esc_html_e('Post Statuses', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipkit_task_ce_post_status" name="ce_post_statuses[]" class="aipkit_form-input" multiple size="4" style="min-height: 105px;" <?php disabled(!$is_pro); ?>>
            <option value="publish" selected><?php esc_html_e('Published', 'gpt3-ai-content-generator'); ?></option>
            <option value="draft"><?php esc_html_e('Draft', 'gpt3-ai-content-generator'); ?></option>
            <option value="pending"><?php esc_html_e('Pending Review', 'gpt3-ai-content-generator'); ?></option>
        </select>
         <p class="aipkit_form-help"><?php esc_html_e('The task will only enhance posts with the selected statuses.', 'gpt3-ai-content-generator'); ?></p>
    </div>
</div>


<div class="aipkit_form-group">
    <label class="aipkit_form-label"><?php esc_html_e('Fields to Update', 'gpt3-ai-content-generator'); ?></label>
    <div class="aipkit_checkbox-group">
        <label class="aipkit_checkbox-label" for="aipkit_task_ce_update_title">
            <input type="checkbox" id="aipkit_task_ce_update_title" name="ce_update_title" value="1" <?php disabled(!$is_pro); ?>>
            <?php esc_html_e('Title', 'gpt3-ai-content-generator'); ?>
        </label>
        <label class="aipkit_checkbox-label" for="aipkit_task_ce_update_excerpt">
            <input type="checkbox" id="aipkit_task_ce_update_excerpt" name="ce_update_excerpt" value="1" <?php disabled(!$is_pro); ?>>
            <?php esc_html_e('Excerpt', 'gpt3-ai-content-generator'); ?>
        </label>
        <label class="aipkit_checkbox-label" for="aipkit_task_ce_update_content">
            <input type="checkbox" id="aipkit_task_ce_update_content" name="ce_update_content" value="1" <?php disabled(!$is_pro); ?>>
            <?php esc_html_e('Content', 'gpt3-ai-content-generator'); ?>
        </label>
        <label class="aipkit_checkbox-label" for="aipkit_task_ce_update_meta">
            <input type="checkbox" id="aipkit_task_ce_update_meta" name="ce_update_meta" value="1" <?php disabled(!$is_pro); ?>>
            <?php esc_html_e('Meta Description', 'gpt3-ai-content-generator'); ?>
        </label>
    </div>
</div>
<hr class="aipkit_hr">
<div class="aipkit_form-group">
    <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_task_ce_enhance_existing_now_flag">
        <input type="checkbox" id="aipkit_task_ce_enhance_existing_now_flag" name="ce_enhance_existing_now_flag" value="1" checked <?php disabled(!$is_pro); ?>>
        <?php esc_html_e('Queue all matching content for enhancement (one-time action).', 'gpt3-ai-content-generator'); ?>
    </label>
    <p class="aipkit_form-help" style="margin-left: 20px; margin-top: -5px;">
        <?php esc_html_e('Use this to enhance all existing content at once. Scheduled runs will only process newly modified content.', 'gpt3-ai-content-generator'); ?>
    </p>
</div>
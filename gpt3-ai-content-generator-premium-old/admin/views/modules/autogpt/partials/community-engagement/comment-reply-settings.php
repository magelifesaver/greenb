<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/community-engagement/comment-reply-settings.php
// Status: MODIFIED

/**
 * Partial: Community Engagement Automated Task - Comment Reply Settings
 *
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent:
// $all_selectable_post_types (this is no longer passed here, it's used in source-settings.php)

$reply_actions = [
    'approve' => __('Approve Immediately', 'gpt3-ai-content-generator'),
    'hold' => __('Hold for Moderation', 'gpt3-ai-content-generator'),
];
?>
<div id="aipkit_task_config_comment_reply_settings">
    <div class="aipkit_form-row">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_task_comment_reply_action"><?php esc_html_e('Action on Reply', 'gpt3-ai-content-generator'); ?></label>
            <select id="aipkit_task_comment_reply_action" name="reply_action" class="aipkit_form-input">
                <?php foreach ($reply_actions as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($value, 'approve'); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="aipkit_form-group">
            <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_task_comment_reply_no_replies" style="margin-top: 25px;">
                <input type="checkbox" id="aipkit_task_comment_reply_no_replies" name="no_reply_to_replies" value="1" checked>
                <?php esc_html_e('Do not reply to other replies', 'gpt3-ai-content-generator'); ?>
            </label>
        </div>
    </div>
    <hr class="aipkit_hr">
    <h5><?php esc_html_e('Comment Filters', 'gpt3-ai-content-generator'); ?></h5>
    <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_comment_reply_include_keywords"><?php esc_html_e('Only reply if comment contains (optional)', 'gpt3-ai-content-generator'); ?></label>
            <textarea id="aipkit_task_comment_reply_include_keywords" name="include_keywords" class="aipkit_form-input" rows="2" placeholder="<?php esc_attr_e('e.g., question, help, how to', 'gpt3-ai-content-generator'); ?>"></textarea>
            <div class="aipkit_form-help"><?php esc_html_e('Comma-separated. The AI will only reply if the comment contains at least one of these words/phrases.', 'gpt3-ai-content-generator'); ?></div>
        </div>
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_comment_reply_exclude_keywords"><?php esc_html_e('Do not reply if comment contains (optional)', 'gpt3-ai-content-generator'); ?></label>
            <textarea id="aipkit_task_comment_reply_exclude_keywords" name="exclude_keywords" class="aipkit_form-input" rows="2" placeholder="<?php esc_attr_e('e.g., spam, offer, http', 'gpt3-ai-content-generator'); ?>"></textarea>
            <div class="aipkit_form-help"><?php esc_html_e('Comma-separated. The AI will ignore comments containing any of these words/phrases.', 'gpt3-ai-content-generator'); ?></div>
        </div>
    </div>
</div>
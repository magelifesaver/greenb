<?php
/**
 * Partial: Content Writing Automated Task - RSS Input Mode
 */

if (!defined('ABSPATH')) {
    exit;
}
// $is_pro is available from the parent scope
?>
<div id="aipkit_task_cw_input_mode_rss" class="aipkit_task_cw_input_mode_section" style="display:none;">
    <div class="aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_task_cw_rss_feeds"><?php esc_html_e('RSS Feed URLs (one per line)', 'gpt3-ai-content-generator'); ?></label>
        <textarea id="aipkit_task_cw_rss_feeds" name="rss_feeds" class="aipkit_form-input" rows="6" placeholder="<?php esc_attr_e('Enter one RSS feed URL per line...', 'gpt3-ai-content-generator'); ?>" <?php disabled(!$is_pro); ?>></textarea>
    </div>
    <div class="aipkit_form-row">
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_cw_rss_include_keywords"><?php esc_html_e('Include Keywords (optional)', 'gpt3-ai-content-generator'); ?></label>
            <input type="text" id="aipkit_task_cw_rss_include_keywords" name="rss_include_keywords" class="aipkit_form-input" placeholder="<?php esc_attr_e('e.g., wordpress, ai', 'gpt3-ai-content-generator'); ?>" <?php disabled(!$is_pro); ?>>
        </div>
        <div class="aipkit_form-group aipkit_form-col">
            <label class="aipkit_form-label" for="aipkit_task_cw_rss_exclude_keywords"><?php esc_html_e('Exclude Keywords (optional)', 'gpt3-ai-content-generator'); ?></label>
            <input type="text" id="aipkit_task_cw_rss_exclude_keywords" name="rss_exclude_keywords" class="aipkit_form-input" placeholder="<?php esc_attr_e('e.g., review, update', 'gpt3-ai-content-generator'); ?>" <?php disabled(!$is_pro); ?>>
        </div>
    </div>
</div>
<?php
/**
 * Partial: Content Writer & AutoGPT Form - RSS Input Mode (Shared)
 * @since NEXT_VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}
// $is_pro variable is expected to be available from the parent scope
?>
<div class="aipkit_form-group">
    <label class="aipkit_form-label" for="aipkit_cw_rss_feeds"><?php esc_html_e('RSS Feed URLs (one per line)', 'gpt3-ai-content-generator'); ?></label>
    <textarea id="aipkit_cw_rss_feeds" name="rss_feeds" class="aipkit_form-input aipkit_autosave_trigger" rows="6" placeholder="<?php esc_attr_e('Enter one RSS feed URL per line...', 'gpt3-ai-content-generator'); ?>" <?php disabled(!$is_pro); ?>></textarea>
</div>
<div class="aipkit_form-row">
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_cw_rss_include_keywords"><?php esc_html_e('Include Keywords (optional)', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipkit_cw_rss_include_keywords" name="rss_include_keywords" class="aipkit_form-input aipkit_autosave_trigger" placeholder="<?php esc_attr_e('e.g., wordpress, ai', 'gpt3-ai-content-generator'); ?>" <?php disabled(!$is_pro); ?>>
        <p class="aipkit_form-help"><?php esc_html_e('Fetch items if their title contains any of these keywords.', 'gpt3-ai-content-generator'); ?></p>
    </div>
    <div class="aipkit_form-group aipkit_form-col">
        <label class="aipkit_form-label" for="aipkit_cw_rss_exclude_keywords"><?php esc_html_e('Exclude Keywords (optional)', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipkit_cw_rss_exclude_keywords" name="rss_exclude_keywords" class="aipkit_form-input aipkit_autosave_trigger" placeholder="<?php esc_attr_e('e.g., review, update', 'gpt3-ai-content-generator'); ?>" <?php disabled(!$is_pro); ?>>
        <p class="aipkit_form-help"><?php esc_html_e('Skip items if their title contains any of these keywords.', 'gpt3-ai-content-generator'); ?></p>
    </div>
</div>
<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/views/shared/content-writing/input-mode-url.php
// Status: MODIFIED

/**
 * Partial: Content Writer Form - Website URL Mode (Shared)
 * @since 2.2.0
 */
if (!defined('ABSPATH')) {
    exit;
}
// $is_pro is available from the parent scope
?>
<div class="aipkit_form-group">
    <label class="aipkit_form-label" for="aipkit_cw_url_list"><?php esc_html_e('Website URLs (one per line)', 'gpt3-ai-content-generator'); ?></label>
    <textarea id="aipkit_cw_url_list" name="url_list" class="aipkit_form-input aipkit_autosave_trigger" rows="6" placeholder="<?php esc_attr_e('Enter one website URL per line...', 'gpt3-ai-content-generator'); ?>" <?php disabled(!$is_pro); ?>></textarea>
    <p class="aipkit_form-help">
        <?php
        $placeholders_link = '<a href="https://aipower.org/docs/placeholders/" target="_blank" rel="noopener">' . esc_html__('prompt', 'gpt3-ai-content-generator') . '</a>';
    // translators: %1$s will be replaced with a <code> tag, %2$s with an <a> tag.
    $text_template = __('The content from each URL will be fetched and used as context for the %1$s placeholder in your %2$s.', 'gpt3-ai-content-generator');
    $placeholder_html = sprintf(
        '<code class="aipkit-placeholder" title="%s">{url_content}</code>',
        esc_attr__('Click to copy', 'gpt3-ai-content-generator')
    );
    $final_html = sprintf(
        $text_template,
        $placeholder_html,
        $placeholders_link
    );
    $allowed_html = [
        'a' => ['href' => true, 'target' => true, 'rel' => true],
        'code' => ['class' => true, 'title' => true],
    ];
    echo wp_kses($final_html, $allowed_html);
    ?>
    </p>
</div>
<div class="aipkit_form-group">
    <button type="button" id="aipkit_cw_test_scrape_btn" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small" <?php disabled(!$is_pro); ?>>
        <span class="aipkit_btn-text"><?php esc_html_e('Test First URL', 'gpt3-ai-content-generator'); ?></span>
        <span class="aipkit_spinner" style="display:none;"></span>
    </button>
</div>
<div id="aipkit_cw_scrape_results_wrapper" style="display: none; margin-top: 15px;">
    <label class="aipkit_form-label"><?php esc_html_e('Fetched Content (Preview)', 'gpt3-ai-content-generator'); ?></label>
    <pre id="aipkit_cw_scrape_results" style="white-space: pre-wrap; word-wrap: break-word; background-color: #f0f0f0; border: 1px solid #ddd; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto; font-size: 11px;"></pre>
</div>
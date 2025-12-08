<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/partials/form-inputs/mode-url.php
// Status: MODIFIED
/**
 * Partial: Content Writer Form - Website URL Mode
 * @since NEXT_VERSION
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables passed from parent (loader-vars.php):
// $is_pro
?>
<div class="aipkit_form-group">
    <textarea id="aipkit_cw_url_list" name="url_list" class="aipkit_form-input" rows="5" placeholder="<?php esc_attr_e('Enter one website URL per line...', 'gpt3-ai-content-generator'); ?>"></textarea>
    <p class="aipkit_form-help">
        <?php
        printf(esc_html__('The content from each URL will be fetched and used as context for the {url_content} placeholder in your prompt.', 'gpt3-ai-content-generator'));
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
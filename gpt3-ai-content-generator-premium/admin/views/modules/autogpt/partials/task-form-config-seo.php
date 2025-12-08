<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/autogpt/partials/task-form-config-seo.php
// Status: MODIFIED
// I have added a "Generate Tags" checkbox and its corresponding prompt textarea to the SEO settings UI.

/**
 * Partial: Automated Task Form - SEO Configuration
 * Contains fields for SEO settings for Content Writing tasks.
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\ContentWriter\AIPKit_Content_Writer_Prompts;

// --- Define Default SEO Prompt Templates ---
$default_custom_meta_prompt = AIPKit_Content_Writer_Prompts::get_default_meta_prompt();
$default_custom_keyword_prompt = AIPKit_Content_Writer_Prompts::get_default_keyword_prompt();
$default_custom_excerpt_prompt = AIPKit_Content_Writer_Prompts::get_default_excerpt_prompt();
$default_custom_tags_prompt = AIPKit_Content_Writer_Prompts::get_default_tags_prompt(); // ADDED
// --- End Definitions ---

?>
<div id="aipkit_task_config_seo" class="aipkit_task_config_section">
    <div class="aipkit_form-group">
        <label class="aipkit_form-label"><?php esc_html_e('SEO Options', 'gpt3-ai-content-generator'); ?></label>
        <div class="aipkit_checkbox-group">
            <label class="aipkit_checkbox-label" for="aipkit_task_cw_generate_meta_desc">
                <input type="checkbox" id="aipkit_task_cw_generate_meta_desc" name="generate_meta_description" value="1" checked>
                <?php esc_html_e('Meta Description', 'gpt3-ai-content-generator'); ?>
            </label>
            <label class="aipkit_checkbox-label" for="aipkit_task_cw_generate_focus_keyword">
                <input type="checkbox" id="aipkit_task_cw_generate_focus_keyword" name="generate_focus_keyword" value="1" checked>
                <?php esc_html_e('Focus Keyword', 'gpt3-ai-content-generator'); ?>
            </label>
            <label class="aipkit_checkbox-label" for="aipkit_task_cw_generate_excerpt">
                <input type="checkbox" id="aipkit_task_cw_generate_excerpt" name="generate_excerpt" value="1" checked>
                <?php esc_html_e('Excerpt', 'gpt3-ai-content-generator'); ?>
            </label>
            <label class="aipkit_checkbox-label" for="aipkit_task_cw_generate_tags">
                <input type="checkbox" id="aipkit_task_cw_generate_tags" name="generate_tags" value="1" checked>
                <?php esc_html_e('Tags', 'gpt3-ai-content-generator'); ?>
            </label>
            <label class="aipkit_checkbox-label" for="aipkit_task_cw_generate_toc">
                <input type="checkbox" id="aipkit_task_cw_generate_toc" name="generate_toc" value="1">
                <?php esc_html_e('Table of Contents', 'gpt3-ai-content-generator'); ?>
            </label>
            <!-- NEW: Checkbox for SEO Slug generation -->
            <label class="aipkit_checkbox-label" for="aipkit_task_cw_generate_seo_slug">
                <input type="checkbox" id="aipkit_task_cw_generate_seo_slug" name="generate_seo_slug" value="1">
                <?php esc_html_e('Optimize URL', 'gpt3-ai-content-generator'); ?>
            </label>
            <!-- END NEW -->
        </div>
    </div>
    <hr class="aipkit_hr">
    <div class="aipkit_form-group aipkit_task_cw_custom_meta_prompt">
        <div class="aipkit_form_label_with_toggle">
            <label class="aipkit_form-label" for="aipkit_task_cw_custom_meta_prompt"><?php esc_html_e('Meta Description Prompt', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_task_cw_custom_meta_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-plus-alt2"></span>
            </button>
        </div>
        <div id="aipkit_task_cw_custom_meta_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
            <textarea id="aipkit_task_cw_custom_meta_prompt" name="custom_meta_prompt" class="aipkit_form-input aipkit_autosave_trigger" rows="6" placeholder="<?php echo esc_attr($default_custom_meta_prompt); ?>"><?php echo esc_textarea($default_custom_meta_prompt); ?></textarea>
            <p class="aipkit_form-help"><?php
                $text = __('Use placeholders: {topic}, {content_summary}, {keywords}.', 'gpt3-ai-content-generator');
$html = preg_replace_callback(
    '/(\{[a-zA-Z0-9_]+\})/',
    function ($matches) {
        return sprintf(
            '<code class="aipkit-placeholder" title="%s">%s</code>',
            esc_attr__('Click to copy', 'gpt3-ai-content-generator'),
            esc_html($matches[0])
        );
    },
    $text
);
echo wp_kses($html, ['code' => ['class' => true, 'title' => true]]);
?>
            </p>
        </div>
    </div>
    <div class="aipkit_form-group aipkit_task_cw_custom_keyword_prompt">
        <div class="aipkit_form_label_with_toggle">
           <label class="aipkit_form-label" for="aipkit_task_cw_custom_keyword_prompt"><?php esc_html_e('Focus Keyword Prompt', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_task_cw_custom_keyword_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-plus-alt2"></span>
            </button>
        </div>
        <div id="aipkit_task_cw_custom_keyword_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
           <textarea id="aipkit_task_cw_custom_keyword_prompt" name="custom_keyword_prompt" class="aipkit_form-input aipkit_autosave_trigger" rows="6" placeholder="<?php echo esc_attr($default_custom_keyword_prompt); ?>"><?php echo esc_textarea($default_custom_keyword_prompt); ?></textarea>
           <p class="aipkit_form-help"><?php
            $text = __('Use placeholders: {topic}, {content_summary}.', 'gpt3-ai-content-generator');
$html = preg_replace_callback(
    '/(\{[a-zA-Z0-9_]+\})/',
    function ($matches) {
        return sprintf(
            '<code class="aipkit-placeholder" title="%s">%s</code>',
            esc_attr__('Click to copy', 'gpt3-ai-content-generator'),
            esc_html($matches[0])
        );
    },
    $text
);
echo wp_kses($html, ['code' => ['class' => true, 'title' => true]]);
?></p>
        </div>
    </div>
    <div class="aipkit_form-group aipkit_task_cw_custom_excerpt_prompt">
        <div class="aipkit_form_label_with_toggle">
           <label class="aipkit_form-label" for="aipkit_task_cw_custom_excerpt_prompt"><?php esc_html_e('Excerpt Prompt', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_task_cw_custom_excerpt_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-plus-alt2"></span>
            </button>
        </div>
        <div id="aipkit_task_cw_custom_excerpt_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
           <textarea id="aipkit_task_cw_custom_excerpt_prompt" name="custom_excerpt_prompt" class="aipkit_form-input aipkit_autosave_trigger" rows="6"><?php echo esc_textarea($default_custom_excerpt_prompt); ?></textarea>
           <p class="aipkit_form-help"><?php
                $text = __('Use placeholders: {topic}, {content_summary}.', 'gpt3-ai-content-generator');
                $html = preg_replace_callback(
                    '/(\{[a-zA-Z0-9_]+\})/',
                    function ($matches) {
                        return sprintf(
                            '<code class="aipkit-placeholder" title="%s">%s</code>',
                            esc_attr__('Click to copy', 'gpt3-ai-content-generator'),
                            esc_html($matches[0])
                        );
                    },
                    $text
                );
                echo wp_kses($html, ['code' => ['class' => true, 'title' => true]]);
?></p>
        </div>
    </div>
    <div class="aipkit_form-group aipkit_task_cw_custom_tags_prompt">
        <div class="aipkit_form_label_with_toggle">
            <label class="aipkit_form-label" for="aipkit_task_cw_custom_tags_prompt"><?php esc_html_e('Tags Prompt', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipkit_textarea_toggle" data-target="aipkit_task_cw_custom_tags_prompt_wrapper" title="<?php esc_attr_e('Expand', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-plus-alt2"></span>
            </button>
        </div>
        <div id="aipkit_task_cw_custom_tags_prompt_wrapper" class="aipkit_collapsible_wrapper aipkit_collapsed">
            <textarea id="aipkit_task_cw_custom_tags_prompt" name="custom_tags_prompt" class="aipkit_form-input aipkit_autosave_trigger" rows="6"><?php echo esc_textarea($default_custom_tags_prompt); ?></textarea>
            <p class="aipkit_form-help"><?php
                $text = __('Use placeholders: {topic}, {content_summary}.', 'gpt3-ai-content-generator');
                $html = preg_replace_callback(
                    '/(\{[a-zA-Z0-9_]+\})/',
                    function ($matches) {
                        return sprintf(
                            '<code class="aipkit-placeholder" title="%s">%s</code>',
                            esc_attr__('Click to copy', 'gpt3-ai-content-generator'),
                            esc_html($matches[0])
                        );
                    },
                    $text
                );
                echo wp_kses($html, ['code' => ['class' => true, 'title' => true]]);
                ?></p>
        </div>
    </div>
</div>
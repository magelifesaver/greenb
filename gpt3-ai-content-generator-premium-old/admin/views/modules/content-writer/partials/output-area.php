<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/partials/output-area.php
// Status: MODIFIED
// I have added a new textarea for displaying the generated post tags.

/**
 * Partial: Content Writer Output Area
 * Contains the title display, main action buttons, and the content output display.
 * The primary Generate/Create button has been moved to the new Action Bar in index.php.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="aipkit_cw_single_output_wrapper" style="display: none;">
    <div id="aipkit_content_writer_output_display" class="aipkit_form-input">
        <!-- Title Display Area - MOVED INSIDE -->
        <h3 id="aipkit_cw_generated_title_display" style="display: none; margin-top:0;"></h3>

        <!-- Content Area where the article body will be streamed -->
        <div id="aipkit_cw_generated_content_area">
            <div class="aipkit_cw_output_placeholder">
                <span class="dashicons dashicons-edit-large"></span>
                <span><?php esc_html_e('Your article will appear here...', 'gpt3-ai-content-generator'); ?></span>
            </div>
        </div>
    </div>

    <!-- Excerpt Display Area -->
    <div id="aipkit_cw_excerpt_output_wrapper" style="display: none; margin-top: 15px;">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_cw_generated_excerpt"><?php esc_html_e('Excerpt', 'gpt3-ai-content-generator'); ?></label>
            <textarea id="aipkit_cw_generated_excerpt" name="generated_excerpt" class="aipkit_form-input aipkit_autosave_trigger aipkit_cw_generated_output_field" rows="3" placeholder="<?php esc_attr_e('Generated excerpt will appear here...', 'gpt3-ai-content-generator'); ?>"></textarea>
        </div>
    </div>

    <!-- Tags Display Area -->
    <div id="aipkit_cw_tags_output_wrapper" style="display: none; margin-top: 15px;">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_cw_generated_tags"><?php esc_html_e('Tags', 'gpt3-ai-content-generator'); ?></label>
            <textarea id="aipkit_cw_generated_tags" name="generated_tags" class="aipkit_form-input aipkit_autosave_trigger aipkit_cw_generated_output_field" rows="2" placeholder="<?php esc_attr_e('Generated tags will appear here...', 'gpt3-ai-content-generator'); ?>"></textarea>
        </div>
    </div>

    <!-- Meta Description Display Area -->
    <div id="aipkit_cw_meta_desc_output_wrapper" style="display: none; margin-top: 15px;">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_cw_generated_meta_desc"><?php esc_html_e('Meta Description', 'gpt3-ai-content-generator'); ?></label>
            <textarea id="aipkit_cw_generated_meta_desc" name="meta_description" class="aipkit_form-input aipkit_autosave_trigger aipkit_cw_generated_output_field" rows="3" placeholder="<?php esc_attr_e('Generated SEO meta description will appear here...', 'gpt3-ai-content-generator'); ?>"></textarea>
        </div>
    </div>
    
    <!-- Focus Keyword Display Area -->
    <div id="aipkit_cw_focus_keyword_output_wrapper" style="display: none; margin-top: 15px;">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label" for="aipkit_cw_generated_focus_keyword"><?php esc_html_e('Focus Keyword', 'gpt3-ai-content-generator'); ?></label>
            <textarea id="aipkit_cw_generated_focus_keyword" name="focus_keyword" class="aipkit_form-input aipkit_autosave_trigger aipkit_cw_generated_output_field" rows="1" placeholder="<?php esc_attr_e('Generated SEO focus keyword will appear here...', 'gpt3-ai-content-generator'); ?>"></textarea>
        </div>
    </div>

    <!-- Action buttons for the output -->
    <div class="aipkit_content_writer_output_actions" style="margin-top: 10px; display: none;">
         <button type="button" id="aipkit_cw_save_as_post_btn" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small" disabled>
            <span class="dashicons dashicons-edit-page"></span>
            <span class="aipkit_btn-text"><?php esc_html_e('Save as Post', 'gpt3-ai-content-generator'); ?></span>
            <span class="aipkit_spinner" style="display:none;"></span>
        </button>
        <button type="button" id="aipkit_content_writer_copy_btn" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small" disabled>
            <span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('Copy', 'gpt3-ai-content-generator'); ?>
        </button>
        <button type="button" id="aipkit_content_writer_clear_btn" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small" disabled>
            <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Clear', 'gpt3-ai-content-generator'); ?>
        </button>
    </div>

     <div id="aipkit_cw_save_post_status" class="aipkit_form-help" style="margin-top: 5px; min-height: 1.5em;"></div>
</div>
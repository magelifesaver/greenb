<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-forms/partials/_form-editor-actions.php
// Status: MODIFIED

/**
 * Partial: AI Form Editor - Actions
 * Contains the main action buttons for the form editor (Save, Preview, Cancel),
 * wrapped in a modern container at the bottom of the editor view.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="aipkit_form_editor_actions">
    <button type="button" id="aipkit_save_ai_form_btn" class="aipkit_btn aipkit_btn-primary">
        <span class="aipkit_btn-text"><?php esc_html_e('Save Form', 'gpt3-ai-content-generator'); ?></span>
        <span class="aipkit_spinner" style="display:none;"></span>
    </button>
    <button type="button" id="aipkit_preview_ai_form_btn" class="aipkit_btn aipkit_btn-secondary" disabled>
        <span class="aipkit_btn-text"><?php esc_html_e('Preview', 'gpt3-ai-content-generator'); ?></span>
    </button>
    <button type="button" id="aipkit_cancel_edit_ai_form_btn" class="aipkit_btn aipkit_btn-secondary">
        <?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?>
    </button>
</div>
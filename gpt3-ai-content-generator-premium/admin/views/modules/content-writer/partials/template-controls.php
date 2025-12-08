<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/content-writer/partials/template-controls.php
// Status: MODIFIED
/**
 * Partial: Content Writer Template Controls
 * Contains the dropdown to select a template and buttons to save/manage templates.
 * Wrapped in an accordion for a consistent UI.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header aipkit_active">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Templates', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content aipkit_active">
        <div class="aipkit_cw_template_controls">
            <div class="aipkit_form-group aipkit_cw_template_select_group">
                <label class="aipkit_form-label" for="aipkit_cw_template_select"><?php esc_html_e('Load Template', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipkit_cw_template_inline_controls">
                    <select id="aipkit_cw_template_select" name="cw_template_id" class="aipkit_form-input">
                        <option value=""><?php esc_html_e('-- Select Template --', 'gpt3-ai-content-generator'); ?></option>
                        <?php // Options will be populated by JS?>
                    </select>
                    <div class="aipkit_cw_template_action_buttons">
                        <button type="button" id="aipkit_cw_update_template_btn" class="aipkit_btn aipkit_btn-primary aipkit_icon_btn" title="<?php esc_attr_e('Update Current Template', 'gpt3-ai-content-generator'); ?>" disabled>
                            <span class="dashicons dashicons-saved"></span>
                        </button>
                        <button type="button" id="aipkit_cw_save_as_template_btn" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn" title="<?php esc_attr_e('Create New Template', 'gpt3-ai-content-generator'); ?>">
                            <span class="dashicons dashicons-plus"></span>
                        </button>
                        <button type="button" id="aipkit_cw_rename_template_btn" class="aipkit_btn aipkit_btn-secondary aipkit_icon_btn" title="<?php esc_attr_e('Rename Selected Template', 'gpt3-ai-content-generator'); ?>" disabled>
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" id="aipkit_cw_delete_template_btn" class="aipkit_btn aipkit_btn-danger aipkit_icon_btn" title="<?php esc_attr_e('Delete Selected Template', 'gpt3-ai-content-generator'); ?>" disabled>
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Inline form for new/rename -->
            <div id="aipkit_cw_template_inline_form" class="aipkit_cw_template_inline_form" style="display: none;">
                <input type="text" id="aipkit_cw_template_name_input" class="aipkit_form-input" placeholder="<?php esc_attr_e('Enter template name...', 'gpt3-ai-content-generator'); ?>">
                <div class="aipkit_cw_template_inline_form_actions">
                    <button type="button" id="aipkit_cw_template_save_inline_btn" class="aipkit_btn aipkit_btn-primary aipkit_btn-small"><?php esc_html_e('Save', 'gpt3-ai-content-generator'); ?></button>
                    <button type="button" id="aipkit_cw_template_cancel_inline_btn" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small"><?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?></button>
                </div>
            </div>
            <!-- NEW: Inline confirmation for delete -->
            <div id="aipkit_cw_template_inline_delete_confirm" class="aipkit_cw_template_inline_delete_confirm" style="display: none;">
                <span id="aipkit_cw_template_delete_confirm_text"></span>
                <div class="aipkit_cw_template_inline_form_actions">
                    <button type="button" id="aipkit_cw_template_confirm_delete_btn" class="aipkit_btn aipkit_btn-danger aipkit_btn-small"><?php esc_html_e('Confirm', 'gpt3-ai-content-generator'); ?></button>
                    <button type="button" id="aipkit_cw_template_cancel_delete_btn" class="aipkit_btn aipkit_btn-secondary aipkit_btn-small"><?php esc_html_e('Cancel', 'gpt3-ai-content-generator'); ?></button>
                </div>
            </div>
            <div id="aipkit_cw_template_status" class="aipkit_form-help"></div>
        </div>
    </div>
</div>
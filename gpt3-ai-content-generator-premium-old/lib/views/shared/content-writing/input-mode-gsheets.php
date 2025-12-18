<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/views/shared/content-writing/input-mode-gsheets.php
// Status: MODIFIED

/**
 * Partial: Content Writer Form - Google Sheets Input Mode (Shared)
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}
// $is_pro is available from the parent scope
?>
<div class="aipkit_gsheets_section_container">
    <div class="aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_cw_gsheets_sheet_id"><?php esc_html_e('Google Sheet ID', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipkit_cw_gsheets_sheet_id" name="gsheets_sheet_id" class="aipkit_form-input aipkit_autosave_trigger aipkit_gsheets_sheet_id_input" <?php disabled(!$is_pro); ?>>
    </div>
    <div class="aipkit_form-group">
        <label class="aipkit_form-label" for="aipkit_cw_gsheets_credentials_file">
            <?php esc_html_e('Credentials (JSON File)', 'gpt3-ai-content-generator'); ?>
            <span class="aipkit_gsheets_file_display" style="display: none;"></span>
        </label>
        <input type="file" id="aipkit_cw_gsheets_credentials_file" name="gsheets_credentials_file" class="aipkit_form-input aipkit_gsheets_credentials_file_input" accept=".json,application/json" <?php disabled(!$is_pro); ?>>
        <textarea id="aipkit_cw_gsheets_credentials" name="gsheets_credentials" class="aipkit_autosave_trigger aipkit_gsheets_credentials_hidden_input" style="display:none;"></textarea>
        <div class="aipkit_gsheets_indicator"></div>
        <div class="aipkit_gsheets_shortcut_link_wrapper" style="display: none; margin-top: 5px;">
            <a href="#" target="_blank" rel="noopener noreferrer" class="aipkit_gsheets_shortcut_link">
                <span class="dashicons dashicons-external"></span>
                <?php esc_html_e('Open Spreadsheet', 'gpt3-ai-content-generator'); ?>
            </a>
        </div>
        <p class="aipkit_form-help">
            <?php esc_html_e('Format your sheet with columns:', 'gpt3-ai-content-generator'); ?>
        </p>
        <p class="aipkit_form-help">
            <?php esc_html_e('A = Topic, B = Keywords, C = Category ID, D = Author Login, E = Post Type Slug. One topic per row.', 'gpt3-ai-content-generator'); ?>
        </p>
        <p class="aipkit_form-help">
            <a href="https://docs.google.com/spreadsheets/d/18QIWggMmbTVTb-nztTo7SFdGJTUC6kwRxgc841xq4x0/edit?gid=0#gid=0" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Click here to view a sample Google Sheet.', 'gpt3-ai-content-generator'); ?>
            </a>
        </p>
    </div>
</div>
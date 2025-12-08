<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/settings/partials/integrations/enhancer-actions.php
// Status: NEW FILE

/**
 * Partial: Content Enhancer Actions integration settings.
 * Included within the "Integrations" settings tab.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables from parent: $enhancer_editor_integration_enabled
?>
<!-- Content Enhancer Actions Accordion -->
<div class="aipkit_accordion">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Content Assistant', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <div class="aipkit_form-group">
            <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_enhancer_editor_integration">
                <input
                    type="checkbox"
                    id="aipkit_enhancer_editor_integration"
                    name="enhancer_editor_integration"
                    class="aipkit_autosave_trigger"
                    value="1"
                    <?php checked($enhancer_editor_integration_enabled, '1'); ?>
                >
                <?php esc_html_e('Enable Content Assistant in Post Editors', 'gpt3-ai-content-generator'); ?>
            </label>
            <p class="aipkit_form-help" style="margin-left: 23px; margin-top: -5px;">
                <?php esc_html_e('Show the "Content Assistant" menu in the Classic and Block editors.', 'gpt3-ai-content-generator'); ?>
            </p>
        </div>

        <div class="aipkit_form-group" style="margin-top: 12px;">
            <label class="aipkit_form-label" for="aipkit_enhancer_insert_position_default">
                <?php esc_html_e('Default Insert Position', 'gpt3-ai-content-generator'); ?>
            </label>
            <select
                id="aipkit_enhancer_insert_position_default"
                name="enhancer_insert_position_default"
                class="aipkit_form-input aipkit_autosave_trigger"
            >
                <?php
                $pos_current = isset($enhancer_default_insert_position) ? sanitize_key($enhancer_default_insert_position) : 'replace';
                $positions = [
                    'replace' => __('Replace selection', 'gpt3-ai-content-generator'),
                    'after'   => __('Insert after', 'gpt3-ai-content-generator'),
                    'before'  => __('Insert before', 'gpt3-ai-content-generator'),
                ];
                foreach ($positions as $key => $label) {
                    echo '<option value="' . esc_attr($key) . '"' . selected($pos_current, $key, false) . '>' . esc_html($label) . '</option>';
                }
                ?>
            </select>
            <p class="aipkit_form-help">
                <?php esc_html_e('Controls where generated content is placed relative to your current selection.', 'gpt3-ai-content-generator'); ?>
            </p>
        </div>
        <hr class="aipkit_hr">

        <div id="aipkit_enhancer_actions_container">
            <table class="wp-list-table widefat striped aipkit_enhancer_actions_table">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?php esc_html_e('Label', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php esc_html_e('Prompt', 'gpt3-ai-content-generator'); ?></th>
                        <th style="width: 160px;">&nbsp;<?php esc_html_e('Position', 'gpt3-ai-content-generator'); ?></th>
                        <th style="width: 120px; text-align: right;">&nbsp;<?php esc_html_e('Actions', 'gpt3-ai-content-generator'); ?></th>
                    </tr>
                </thead>
                <tbody id="aipkit_enhancer_actions_list">
                    <!-- JS will render rows here -->
                </tbody>
            </table>
            <div id="aipkit_enhancer_actions_status" class="aipkit-modal-status" style="min-height: 20px; text-align: left; margin-top: 10px;"></div>
            <div style="margin-top: 15px; display:flex; gap:8px;">
                <button type="button" class="button aipkit-enhancer-add-new-btn"><?php esc_html_e('Add New', 'gpt3-ai-content-generator'); ?></button>
                <button type="button" class="button button-secondary aipkit-enhancer-reset-actions-btn"><?php esc_html_e('Reset to Defaults', 'gpt3-ai-content-generator'); ?></button>
            </div>
        </div>
    </div>
</div>

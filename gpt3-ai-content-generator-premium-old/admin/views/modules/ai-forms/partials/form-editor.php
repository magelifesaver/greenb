<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/ai-forms/partials/form-editor.php
// Status: MODIFIED

/**
 * Partial: AI Form Editor (Main Orchestrator)
 * UI for creating or editing an AI Form. Implements a modern 3-column layout for an improved user experience.
 */

if (!defined('ABSPATH')) {
    exit;
}

use WPAICG\aipkit_dashboard;
use WPAICG\AIPKIT_AI_Settings;

// --- Get available providers (always show, lock via disabled when not eligible) ---
$providers = ['OpenAI', 'OpenRouter', 'Google', 'Azure', 'DeepSeek', 'Ollama'];
$is_pro = class_exists('\\WPAICG\\aipkit_dashboard') && aipkit_dashboard::is_pro_plan();
$deepseek_addon_active = class_exists('\\WPAICG\\aipkit_dashboard') && aipkit_dashboard::is_addon_active('deepseek');
$ollama_addon_active = class_exists('\\WPAICG\\aipkit_dashboard') && aipkit_dashboard::is_addon_active('ollama');
// --- Get global AI param defaults ---
$global_ai_params = [];
if (class_exists('\\WPAICG\\AIPKIT_AI_Settings')) {
    $global_ai_params = AIPKIT_AI_Settings::get_ai_parameters();
}
$default_temp = $global_ai_params['temperature'] ?? 1.0;
$default_max_tokens = $global_ai_params['max_completion_tokens'] ?? 4000;
$default_top_p = $global_ai_params['top_p'] ?? 1.0;
$default_frequency_penalty = $global_ai_params['frequency_penalty'] ?? 0.0;
$default_presence_penalty = $global_ai_params['presence_penalty'] ?? 0.0;
?>
<div class="aipkit_form_editor">
    <form id="aipkit_ai_form_editor_form" onsubmit="return false;">
        <input type="hidden" id="aipkit_ai_form_id" name="form_id" value="">

        <!-- New 3-Column Layout Wrapper -->
        <div class="aipkit_form_editor_layout_wrapper">

            <!-- Column 1: Elements Palette / Settings Panel -->
            <div class="aipkit_form_editor_col_left">
                <!-- This wrapper allows the palette and settings panel to switch visibility -->
                <div class="aipkit_form_designer_left_controls_wrapper">
                    <!-- Form Elements & Layouts Palette in a Sub-Container -->
                    <div class="aipkit_sub_container" id="aipkit_ai_form_elements_palette">
                        <div class="aipkit_accordion-group">
                            <!-- Elements Accordion -->
                            <div class="aipkit_accordion">
                                <div class="aipkit_accordion-header aipkit_active">
                                    <span class="dashicons dashicons-list-view"></span>
                                    <?php esc_html_e('Form Elements', 'gpt3-ai-content-generator'); ?>
                                </div>
                                <div class="aipkit_accordion-content aipkit_active">
                                    <div class="aipkit_form_element_item" data-element-type="text-input" draggable="true">
                                        <span class="dashicons dashicons-edit-large"></span>
                                        <?php esc_html_e('Text Input', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                    <div class="aipkit_form_element_item" data-element-type="textarea" draggable="true">
                                        <span class="dashicons dashicons-text"></span>
                                        <?php esc_html_e('Text Area', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                    <div class="aipkit_form_element_item" data-element-type="select" draggable="true">
                                        <span class="dashicons dashicons-menu-alt"></span>
                                        <?php esc_html_e('Dropdown', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                    <div class="aipkit_form_element_item" data-element-type="checkbox" draggable="true">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php esc_html_e('Checkbox', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                    <div class="aipkit_form_element_item" data-element-type="radio-button" draggable="true">
                                        <span class="dashicons dashicons-marker"></span>
                                        <?php esc_html_e('Radio Buttons', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                    <?php if (\WPAICG\aipkit_dashboard::is_pro_plan()): ?>
                                        <div class="aipkit_form_element_item" data-element-type="file-upload" draggable="true">
                                            <span class="dashicons dashicons-media-default"></span>
                                            <?php esc_html_e('File Upload', 'gpt3-ai-content-generator'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="aipkit_form_element_item aipkit-pro-feature-locked" title="<?php esc_attr_e('This is a Pro feature. Please upgrade.', 'gpt3-ai-content-generator'); ?>">
                                            <span class="dashicons dashicons-media-default"></span>
                                            <?php esc_html_e('File Upload', 'gpt3-ai-content-generator'); ?>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" target="_blank" class="aipkit_pro_tag">Pro</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Layouts Accordion -->
                            <div class="aipkit_accordion">
                                <div class="aipkit_accordion-header">
                                    <span class="dashicons dashicons-layout"></span>
                                    <?php esc_html_e('Layouts', 'gpt3-ai-content-generator'); ?>
                                </div>
                                <div class="aipkit_accordion-content">
                                    <div class="aipkit_form_element_item" data-layout-type="1-col" draggable="true">
                                        <span class="dashicons dashicons-align-wide"></span>
                                        <?php esc_html_e('Single Column', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                    <div class="aipkit_form_element_item" data-layout-type="2-col-50-50" draggable="true">
                                        <span class="dashicons dashicons-editor-table"></span>
                                        <?php esc_html_e('2 Columns (50/50)', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                    <div class="aipkit_form_element_item" data-layout-type="2-col-30-70" draggable="true">
                                        <span class="dashicons dashicons-align-left"></span>
                                        <?php esc_html_e('2 Columns (30/70)', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                    <div class="aipkit_form_element_item" data-layout-type="2-col-70-30" draggable="true">
                                        <span class="dashicons dashicons-align-right"></span>
                                        <?php esc_html_e('2 Columns (70/30)', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                    <div class="aipkit_form_element_item" data-layout-type="3-col-33-33-33" draggable="true">
                                        <span class="dashicons dashicons-editor-table"></span>
                                        <?php esc_html_e('3 Columns', 'gpt3-ai-content-generator'); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Labels Accordion -->
                            <div class="aipkit_accordion">
                                <div class="aipkit_accordion-header">
                                    <span class="dashicons dashicons-text-page"></span>
                                    <?php esc_html_e('Labels', 'gpt3-ai-content-generator'); ?>
                                </div>
                                <div class="aipkit_accordion-content">
                                    <?php include __DIR__ . '/labels-config.php'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Panel (Hidden by default, shown when an element is clicked) -->
                    <div class="aipkit_sub_container" id="aipkit_ai_form_element_settings_panel" style="display:none;">
                        <div class="aipkit_sub_container_header">
                            <h5 class="aipkit_sub_container_title">
                                <?php esc_html_e('Element Settings', 'gpt3-ai-content-generator'); ?>
                                <span id="aipkit_settings_panel_element_type" style="font-weight:normal; font-style:italic; font-size:0.9em;"></span>
                            </h5>
                        </div>
                        <div class="aipkit_sub_container_body">
                            <div id="aipkit_settings_panel_fields">
                                <!-- Settings fields will be injected here by JS -->
                            </div>
                            <button type="button" id="aipkit_settings_panel_close_btn" class="aipkit_btn aipkit_btn-secondary" style="margin-top:15px; width: 100%;"><?php esc_html_e('Done', 'gpt3-ai-content-generator'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 2: Form Designer / Drop Zone -->
            <div class="aipkit_form_editor_col_center">
                <div class="aipkit_ai_form_designer_area" id="aipkit_ai_form_designer_area">
                    <div class="aipkit_form_designer_placeholder" id="aipkit_form_designer_placeholder">
                        <span class="dashicons dashicons-layout"></span>
                        <?php esc_html_e('Drag a Layout Here to Start', 'gpt3-ai-content-generator'); ?>
                    </div>
                    <!-- Dropped elements will be appended here by JS -->
                </div>
            </div>

            <!-- Column 3: Form Title, Prompt, AI Config -->
            <div class="aipkit_form_editor_col_right">
                <?php include __DIR__ . '/_form-editor-main-settings.php'; ?>
            </div>

        </div>
        <!-- End 3-Column Layout Wrapper -->

        <?php include __DIR__ . '/_form-editor-actions.php'; ?>

    </form>

    <!-- Container for AI Form Preview -->
    <div id="aipkit_ai_form_preview_container" style="display: none; margin-top: 20px; padding: 15px; border: 1px solid var(--aipkit_container-border); border-radius: 4px; background-color: #fff;">
        <!-- Preview will be injected here by JS -->
    </div>
</div>